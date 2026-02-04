<?php
/**
 * API para manejar notificaciones de eventos
 * Permite crear, leer, actualizar y eliminar notificaciones de registros de eventos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

class NotificacionesEventosAPI {
    private $pdo;

    public function __construct() {
        try {
            $database = Database::getInstance();
            $this->pdo = $database->getConnection();
        } catch (Exception $e) {
            error_log("Error connecting to database: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];

        try {
            switch ($method) {
                case 'GET':
                    $this->getNotificaciones();
                    break;

                case 'POST':
                    $this->createNotificacion();
                    break;

                case 'PUT':
                    $this->updateRegistro();
                    break;

                case 'DELETE':
                    $this->deleteRegistro();
                    break;

                default:
                    throw new Exception('Método no permitido');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, null, $e->getMessage());
        }
    }

    private function getNotificaciones() {
        $estado = $_GET['estado'] ?? null;
        $debug = $_GET['debug'] ?? false;

        $sql = "
            SELECT
                er.id,
                er.evento_id,
                er.nombre_usuario,
                er.email_contacto,
                er.telefono_contacto,
                er.nombre_empresa,
                er.comentarios,
                er.fecha_registro,
                er.estado_registro,
                e.titulo as evento_titulo,
                e.fecha_inicio as evento_fecha,
                e.ubicacion as evento_ubicacion
            FROM evento_registros er
            JOIN eventos e ON er.evento_id = e.id
            WHERE 1=1
        ";

        $params = [];

        if ($estado) {
            $sql .= " AND er.estado_registro = ?";
            $params[] = $estado;
        }

        $sql .= " ORDER BY er.fecha_registro DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Agrupar por estado para facilitar el manejo
        $grouped = [
            'pendientes' => [],
            'confirmados' => [],
            'rechazados' => []
        ];

        foreach ($notificaciones as $notif) {
            switch ($notif['estado_registro']) {
                case 'pendiente':
                    $grouped['pendientes'][] = $notif;
                    break;
                case 'confirmado':
                    $grouped['confirmados'][] = $notif;
                    break;
                case 'rechazado':
                    $grouped['rechazados'][] = $notif;
                    break;
            }
        }

        $response = [
            'total' => count($notificaciones),
            'por_estado' => $grouped,
            'todas' => $notificaciones
        ];

        if ($debug) {
            $response['debug_info'] = [
                'sql' => $sql,
                'params' => $params,
                'raw_count' => count($notificaciones),
                'estados_encontrados' => array_unique(array_column($notificaciones, 'estado_registro'))
            ];
        }

        $this->sendResponse(true, $response);
    }

    private function createNotificacion() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input)) {
            $input = $_POST;
        }

        $required = ['evento_id', 'nombre_usuario', 'email_contacto'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("El campo '$field' es requerido");
            }
        }

        // Esta función normalmente se llamaría desde register_evento.php
        // pero la incluimos aquí para completitud
        $this->sendResponse(true, null, 'Notificación creada (manejada por register_evento.php)');
    }

    private function updateRegistro() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            error_log("UpdateRegistro - Input received: " . json_encode($input));

            if (empty($input['id'])) {
                throw new Exception('ID del registro es requerido');
            }

            if (empty($input['estado'])) {
                throw new Exception('Estado es requerido');
            }

            $registroId = (int)$input['id'];
            $nuevoEstado = $input['estado'];
            $motivoRechazo = $input['motivo_rechazo'] ?? null;

            // Validar estados permitidos
            $estadosPermitidos = ['pendiente', 'confirmado', 'rechazado'];
            if (!in_array($nuevoEstado, $estadosPermitidos)) {
                throw new Exception('Estado no válido');
            }

            // Verificar si existe la columna motivo_rechazo, si no existe, agregarla
            try {
                // Intentar agregar la columna si no existe (esto funcionará en MySQL y SQLite)
                $database = Database::getInstance();
                if ($database->isUsingSQLite()) {
                    $this->pdo->exec("ALTER TABLE evento_registros ADD COLUMN motivo_rechazo TEXT");
                } else {
                    $this->pdo->exec("ALTER TABLE evento_registros ADD COLUMN motivo_rechazo TEXT");
                }
            } catch (Exception $e) {
                // La columna ya existe, continuar
            }

            // Verificar que el registro existe
            $stmt = $this->pdo->prepare("
                SELECT er.*, e.titulo, e.capacidad_maxima, e.capacidad_actual
                FROM evento_registros er
                JOIN eventos e ON er.evento_id = e.id
                WHERE er.id = ?
            ");
            $stmt->execute([$registroId]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                throw new Exception('Registro no encontrado');
            }

            // Iniciar transacción
            $this->pdo->beginTransaction();

            try {

                // Actualizar estado del registro
                $updateFields = ['estado_registro = ?'];
                $params = [$nuevoEstado];

                if ($motivoRechazo && $nuevoEstado === 'rechazado') {
                    $updateFields[] = 'motivo_rechazo = ?';
                    $params[] = $motivoRechazo;
                }

                $sql = "UPDATE evento_registros SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $params[] = $registroId;

                error_log("UpdateRegistro - SQL: " . $sql);
                error_log("UpdateRegistro - Params: " . json_encode($params));

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);

                // Actualizar capacidad del evento según el cambio de estado
                $estadoAnterior = $registro['estado_registro'];

                if ($estadoAnterior !== $nuevoEstado) {
                    $capacidadChange = 0;

                    // Lógica para actualizar capacidad
                    if ($estadoAnterior === 'pendiente' && $nuevoEstado === 'confirmado') {
                        // Ya se contó cuando se creó el registro, no hacer nada
                    } elseif ($estadoAnterior === 'confirmado' && $nuevoEstado === 'rechazado') {
                        $capacidadChange = -1; // Liberar espacio
                    } elseif ($estadoAnterior === 'pendiente' && $nuevoEstado === 'rechazado') {
                        $capacidadChange = -1; // Liberar espacio
                    } elseif ($estadoAnterior === 'rechazado' && $nuevoEstado === 'confirmado') {
                        $capacidadChange = 1; // Ocupar espacio
                    }

                    if ($capacidadChange !== 0) {
                        $stmt = $this->pdo->prepare("
                            UPDATE eventos
                            SET capacidad_actual = GREATEST(0, capacidad_actual + ?)
                            WHERE id = ?
                        ");
                        $stmt->execute([$capacidadChange, $registro['evento_id']]);
                    }
                }

                $this->pdo->commit();

                $this->sendResponse(true, [
                    'registro_id' => $registroId,
                    'nuevo_estado' => $nuevoEstado,
                    'estado_anterior' => $estadoAnterior,
                    'evento_titulo' => $registro['titulo']
                ], 'Estado actualizado exitosamente');

            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("UpdateRegistro - Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function deleteRegistro() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['id'])) {
            throw new Exception('ID del registro es requerido');
        }

        $registroId = (int)$input['id'];

        // Verificar que el registro existe y está rechazado
        $stmt = $this->pdo->prepare("
            SELECT er.*, e.titulo
            FROM evento_registros er
            JOIN eventos e ON er.evento_id = e.id
            WHERE er.id = ?
        ");
        $stmt->execute([$registroId]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            throw new Exception('Registro no encontrado');
        }

        // Solo permitir eliminar registros rechazados
        if ($registro['estado_registro'] !== 'rechazado') {
            throw new Exception('Solo se pueden eliminar registros rechazados');
        }

        // Iniciar transacción
        $this->pdo->beginTransaction();

        try {
            // Eliminar el registro
            $stmt = $this->pdo->prepare("DELETE FROM evento_registros WHERE id = ?");
            $stmt->execute([$registroId]);

            $this->pdo->commit();

            $this->sendResponse(true, [
                'registro_id' => $registroId,
                'usuario' => $registro['nombre_usuario'],
                'evento_titulo' => $registro['titulo']
            ], 'Registro eliminado exitosamente');

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function sendResponse($success, $data = null, $message = '') {
        http_response_code($success ? 200 : 400);
        echo json_encode([
            'success' => $success,
            'data' => $data,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Instanciar y manejar la petición
$api = new NotificacionesEventosAPI();
$api->handleRequest();
?>