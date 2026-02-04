<?php
/**
 * API para gestión de solicitudes de comité
 */

define('CLAUT_ACCESS', true);
require_once __DIR__ . '../includes/config.php';
require_once __DIR__ . '../includes/models.php';

// Establecer el header de respuesta
header('Content-Type: application/json; charset=utf-8');

// Manejar CORS si es necesario
setCorsHeaders();

// Clase específica para solicitudes de comité
class SolicitudComite extends BaseModel {
    protected $table = 'solicitudes_comite';
    
    public function crear($datos) {
        $rules = [
            'nombre' => ['required' => true, 'max_length' => 255],
            'email' => ['required' => true, 'email' => true],
            'cargo' => ['required' => true, 'max_length' => 255],
            'organizacion' => ['required' => true, 'max_length' => 255],
            'comite_id' => ['required' => true],
            'motivo' => ['required' => true]
        ];
        
        $errors = $this->validate($datos, $rules);
        if (!empty($errors)) {
            throw new Exception('Datos inválidos: ' . implode(', ', $errors));
        }
        
        $sql = "INSERT INTO solicitudes_comite 
                (comite_id, comite_nombre, nombre, email, cargo, organizacion, telefono, motivo, 
                 fecha_solicitud, estado, ip_solicitud) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pendiente', ?)";
        
        $params = [
            sanitizeInput($datos['comite_id']),
            sanitizeInput($datos['comite_nombre']),
            sanitizeInput($datos['nombre']),
            sanitizeInput($datos['email']),
            sanitizeInput($datos['cargo']),
            sanitizeInput($datos['organizacion']),
            sanitizeInput($datos['telefono'] ?? ''),
            sanitizeInput($datos['motivo']),
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    public function obtenerTodas($filtros = []) {
        $sql = "SELECT * FROM solicitudes_comite";
        $params = [];
        $condiciones = [];
        
        if (!empty($filtros['estado'])) {
            $condiciones[] = "estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['comite_id'])) {
            $condiciones[] = "comite_id = ?";
            $params[] = $filtros['comite_id'];
        }
        
        if (!empty($filtros['buscar'])) {
            $condiciones[] = "(nombre LIKE ? OR email LIKE ? OR organizacion LIKE ?)";
            $buscar = '%' . $filtros['buscar'] . '%';
            $params[] = $buscar;
            $params[] = $buscar;
            $params[] = $buscar;
        }
        
        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }
        
        $sql .= " ORDER BY fecha_solicitud DESC";
        
        return $this->db->select($sql, $params);
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM solicitudes_comite WHERE id = ?";
        return $this->db->selectOne($sql, [$id]);
    }
    
    public function actualizarEstado($id, $estado, $comentarios = null) {
        $estadosValidos = ['pendiente', 'aprobada', 'rechazada'];
        if (!in_array($estado, $estadosValidos)) {
            throw new Exception('Estado inválido');
        }
        
        $sql = "UPDATE solicitudes_comite 
                SET estado = ?, fecha_respuesta = NOW(), comentarios_admin = ?
                WHERE id = ?";
        
        return $this->db->update($sql, [$estado, $comentarios, $id]);
    }
    
    public function obtenerEstadisticas() {
        $sql = "SELECT 
                    estado,
                    COUNT(*) as total
                FROM solicitudes_comite 
                GROUP BY estado";
        
        $resultados = $this->db->select($sql);
        
        $estadisticas = [
            'total' => 0,
            'pendientes' => 0,
            'aprobadas' => 0,
            'rechazadas' => 0
        ];
        
        foreach ($resultados as $fila) {
            $estadisticas['total'] += $fila['total'];
            $estadisticas[$fila['estado'] . 's'] = $fila['total'];
        }
        
        return $estadisticas;
    }
}

// Router principal
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetSolicitudes();
        break;
    case 'POST':
        handleCreateSolicitud();
        break;
    case 'PUT':
        handleUpdateSolicitud();
        break;
    default:
        jsonError('Método no permitido', 405);
}

function handleGetSolicitudes() {
    try {
        $solicitudModel = new SolicitudComite();
        
        if (isset($_GET['estadisticas']) && $_GET['estadisticas'] === '1') {
            $estadisticas = $solicitudModel->obtenerEstadisticas();
            jsonResponse($estadisticas, 200, 'Estadísticas obtenidas exitosamente');
        } else {
            $filtros = [];
            
            if (isset($_GET['estado'])) {
                $filtros['estado'] = $_GET['estado'];
            }
            
            if (isset($_GET['comite_id'])) {
                $filtros['comite_id'] = $_GET['comite_id'];
            }
            
            if (isset($_GET['buscar'])) {
                $filtros['buscar'] = $_GET['buscar'];
            }
            
            $solicitudes = $solicitudModel->obtenerTodas($filtros);
            jsonResponse($solicitudes, 200, 'Solicitudes obtenidas exitosamente');
        }
        
    } catch (Exception $e) {
        jsonError('Error al obtener solicitudes: ' . $e->getMessage(), 500);
    }
}

function handleCreateSolicitud() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            jsonError('Datos de solicitud inválidos', 400);
        }
        
        $solicitudModel = new SolicitudComite();
        $id = $solicitudModel->crear($input);
        
        jsonResponse(['id' => $id], 201, 'Solicitud creada exitosamente');
        
    } catch (Exception $e) {
        jsonError('Error al crear solicitud: ' . $e->getMessage(), 400);
    }
}

function handleUpdateSolicitud() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['id']) || !isset($input['estado'])) {
            jsonError('Datos de actualización inválidos', 400);
        }
        
        $solicitudModel = new SolicitudComite();
        $resultado = $solicitudModel->actualizarEstado(
            $input['id'], 
            $input['estado'],
            $input['comentarios'] ?? null
        );
        
        if ($resultado > 0) {
            jsonResponse(['actualizada' => true], 200, 'Solicitud actualizada exitosamente');
        } else {
            jsonError('No se pudo actualizar la solicitud', 404);
        }
        
    } catch (Exception $e) {
        jsonError('Error al actualizar solicitud: ' . $e->getMessage(), 400);
    }
}

// Crear la tabla si no existe
function crearTablaSiNoExiste() {
    try {
        $db = Database::getInstance();
        $sql = "CREATE TABLE IF NOT EXISTS solicitudes_comite (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comite_id VARCHAR(100) NOT NULL,
            comite_nombre VARCHAR(255) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            cargo VARCHAR(255) NOT NULL,
            organizacion VARCHAR(255) NOT NULL,
            telefono VARCHAR(20) NULL,
            motivo TEXT NOT NULL,
            fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_respuesta TIMESTAMP NULL,
            estado ENUM('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
            comentarios_admin TEXT NULL,
            ip_solicitud VARCHAR(45) NULL,
            INDEX idx_estado (estado),
            INDEX idx_comite (comite_id),
            INDEX idx_fecha (fecha_solicitud)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql);
    } catch (Exception $e) {
        error_log("Error al crear tabla solicitudes_comite: " . $e->getMessage());
    }
}

// Ejecutar creación de tabla al cargar el archivo
crearTablaSiNoExiste();
?>