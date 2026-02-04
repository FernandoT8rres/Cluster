<?php
/**
 * Modelos de datos para Clúster Intranet
 */

define('CLAUT_ACCESS', true);
require_once __DIR__ . '/config.php';

// Clase base para modelos
abstract class BaseModel {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            if (isset($rule['required']) && $rule['required'] && empty($data[$field])) {
                $errors[$field] = "El campo {$field} es requerido";
                continue;
            }
            
            if (!empty($data[$field])) {
                if (isset($rule['email']) && $rule['email'] && !isValidEmail($data[$field])) {
                    $errors[$field] = "El email no es válido";
                }
                
                if (isset($rule['min_length']) && strlen($data[$field]) < $rule['min_length']) {
                    $errors[$field] = "El campo {$field} debe tener al menos {$rule['min_length']} caracteres";
                }
                
                if (isset($rule['max_length']) && strlen($data[$field]) > $rule['max_length']) {
                    $errors[$field] = "El campo {$field} no puede tener más de {$rule['max_length']} caracteres";
                }
            }
        }
        
        return $errors;
    }
}

// Modelo de Usuario
class Usuario extends BaseModel {
    protected $table = 'usuarios_perfil';
    
    public function crear($datos) {
        // Validar datos
        $rules = [
            'nombre' => ['required' => true, 'max_length' => 100],
            'apellido' => ['required' => true, 'max_length' => 100],
            'email' => ['required' => true, 'email' => true],
            'password' => ['required' => true, 'min_length' => 6]
        ];
        
        $errors = $this->validate($datos, $rules);
        if (!empty($errors)) {
            throw new Exception('Datos inválidos: ' . implode(', ', $errors));
        }
        
        // Verificar si el email ya existe
        if ($this->obtenerPorEmail($datos['email'])) {
            throw new Exception('El email ya está registrado');
        }
        
        $sql = "INSERT INTO usuarios_perfil (nombre, apellido, email, password, telefono, empresa_id, puesto, rol, estado, avatar) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            sanitizeInput($datos['nombre']),
            sanitizeInput($datos['apellido']),
            sanitizeInput($datos['email']),
            password_hash($datos['password'], PASSWORD_DEFAULT),
            sanitizeInput($datos['telefono'] ?? null),
            $datos['empresa_id'] ?? null,
            sanitizeInput($datos['puesto'] ?? null),
            $datos['rol'] ?? 'empleado',
            $datos['estado'] ?? 'pendiente',
            $datos['avatar'] ?? null
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    public function login($email, $password) {
        $sql = "SELECT u.*, e.nombre as empresa_nombre FROM usuarios_perfil u 
                LEFT JOIN empresas e ON u.empresa_id = e.id 
                WHERE u.email = ? AND u.estado = 'activo'";
        $usuario = $this->db->selectOne($sql, [sanitizeInput($email)]);
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Actualizar último acceso
            $this->actualizarUltimoAcceso($usuario['id']);
            
            // Remover password del resultado
            unset($usuario['password']);
            
            return $usuario;
        }
        
        return false;
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT u.*, e.nombre as empresa_nombre FROM usuarios_perfil u 
                LEFT JOIN empresas e ON u.empresa_id = e.id 
                WHERE u.id = ?";
        $usuario = $this->db->selectOne($sql, [$id]);
        
        if ($usuario) {
            unset($usuario['password']);
        }
        
        return $usuario;
    }
    
    public function obtenerPorEmail($email) {
        $sql = "SELECT * FROM usuarios_perfil WHERE email = ?";
        return $this->db->selectOne($sql, [sanitizeInput($email)]);
    }
    
    public function obtenerTodos($filtros = []) {
        $sql = "SELECT u.id, u.nombre, u.apellido, u.email, u.telefono, u.empresa_id, 
                       u.puesto, u.rol, u.estado, u.fecha_registro, u.avatar, u.ultimo_acceso,
                       e.nombre as empresa_nombre
                FROM usuarios_perfil u 
                LEFT JOIN empresas e ON u.empresa_id = e.id";
        $params = [];
        $condiciones = [];
        
        if (!empty($filtros['rol'])) {
            $condiciones[] = "u.rol = ?";
            $params[] = $filtros['rol'];
        }
        
        if (!empty($filtros['estado'])) {
            $condiciones[] = "u.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['empresa_id'])) {
            $condiciones[] = "u.empresa_id = ?";
            $params[] = $filtros['empresa_id'];
        }
        
        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }
        
        $sql .= " ORDER BY u.fecha_registro DESC";
        
        return $this->db->select($sql, $params);
    }
    
    public function actualizar($id, $datos) {
        $campos = [];
        $params = [];
        
        $camposPermitidos = ['nombre', 'apellido', 'telefono', 'empresa_id', 'puesto', 'avatar', 'estado'];
        
        foreach ($datos as $campo => $valor) {
            if (in_array($campo, $camposPermitidos)) {
                if ($campo === 'password') {
                    $campos[] = "password = ?";
                    $params[] = password_hash($valor, PASSWORD_DEFAULT);
                } else {
                    $campos[] = "$campo = ?";
                    $params[] = sanitizeInput($valor);
                }
            }
        }
        
        if (empty($campos)) {
            throw new Exception('No hay campos válidos para actualizar');
        }
        
        $params[] = $id;
        $sql = "UPDATE usuarios_perfil SET " . implode(", ", $campos) . " WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
    
    public function eliminar($id) {
        $sql = "DELETE FROM usuarios_perfil WHERE id = ?";
        return $this->db->delete($sql, [$id]);
    }
    
    private function actualizarUltimoAcceso($id) {
        $sql = "UPDATE usuarios_perfil SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id = ?";
        $this->db->update($sql, [$id]);
    }
    
    public function cambiarEstado($id, $estado) {
        $estadosValidos = ['activo', 'inactivo', 'pendiente', 'suspendido'];
        if (!in_array($estado, $estadosValidos)) {
            throw new Exception('Estado inválido');
        }
        
        $sql = "UPDATE usuarios_perfil SET estado = ? WHERE id = ?";
        return $this->db->update($sql, [$estado, $id]);
    }
}

// Modelo de Empresa
class Empresa extends BaseModel {
    protected $table = 'empresas';
    
    public function crear($datos) {
        $rules = [
            'nombre' => ['required' => true, 'max_length' => 255],
            'email' => ['email' => true]
        ];
        
        $errors = $this->validate($datos, $rules);
        if (!empty($errors)) {
            throw new Exception('Datos inválidos: ' . implode(', ', $errors));
        }
        
        $sql = "INSERT INTO empresas (nombre, razon_social, rfc, direccion, telefono, email, sitio_web, logo, descripcion, sector, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            sanitizeInput($datos['nombre']),
            sanitizeInput($datos['razon_social'] ?? null),
            sanitizeInput($datos['rfc'] ?? null),
            sanitizeInput($datos['direccion'] ?? null),
            sanitizeInput($datos['telefono'] ?? null),
            sanitizeInput($datos['email'] ?? null),
            sanitizeInput($datos['sitio_web'] ?? null),
            $datos['logo'] ?? null,
            sanitizeInput($datos['descripcion'] ?? null),
            sanitizeInput($datos['sector'] ?? null),
            $datos['estado'] ?? 'pendiente'
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    public function obtenerTodas($limite = null) {
        $sql = "SELECT *, 
                (SELECT COUNT(*) FROM usuarios_perfil WHERE empresa_id = empresas_convenio.id AND estado = 'activo') as total_miembros
                FROM empresas_convenio 
                ORDER BY fecha_registro DESC";
        
        if ($limite) {
            $sql .= " LIMIT " . intval($limite);
        }
        
        return $this->db->select($sql);
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT *, 
                (SELECT COUNT(*) FROM usuarios_perfil WHERE empresa_id = empresas_convenio.id AND estado = 'activo') as total_miembros
                FROM empresas_convenio WHERE id = ?";
        return $this->db->selectOne($sql, [$id]);
    }
    
    public function obtenerConMiembros() {
        $sql = "SELECT e.*, 
                COUNT(u.id) as total_miembros
                FROM empresas_convenio e 
                LEFT JOIN usuarios_perfil u ON e.id = u.empresa_id AND u.estado = 'activo'
                WHERE e.activo = 1
                GROUP BY e.id 
                ORDER BY total_miembros DESC, e.nombre ASC";
        
        return $this->db->select($sql);
    }
    
    public function actualizar($id, $datos) {
        $campos = [];
        $params = [];
        
        $camposPermitidos = ['nombre', 'razon_social', 'rfc', 'direccion', 'telefono', 'email', 'sitio_web', 'descripcion', 'sector', 'estado'];
        
        foreach ($datos as $campo => $valor) {
            if (in_array($campo, $camposPermitidos)) {
                $campos[] = "$campo = ?";
                $params[] = sanitizeInput($valor);
            }
        }
        
        if (empty($campos)) {
            throw new Exception('No hay campos válidos para actualizar');
        }
        
        $params[] = $id;
        $sql = "UPDATE empresas SET " . implode(", ", $campos) . " WHERE id = ?";
        
        return $this->db->update($sql, $params);
    }
}

// Modelo de Comité
class Comite extends BaseModel {
    protected $table = 'comites';
    
    public function obtenerTodos() {
        $sql = "SELECT c.*, 
                u.nombre as presidente_nombre, u.apellido as presidente_apellido,
                (SELECT COUNT(*) FROM comite_miembros WHERE comite_id = c.id AND estado = 'activo') as total_miembros
                FROM comites c 
                LEFT JOIN usuarios_perfil u ON c.presidente_id = u.id 
                WHERE c.estado = 'activo'
                ORDER BY c.nombre";
        
        return $this->db->select($sql);
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT c.*, 
                u.nombre as presidente_nombre, u.apellido as presidente_apellido,
                (SELECT COUNT(*) FROM comite_miembros WHERE comite_id = c.id AND estado = 'activo') as total_miembros
                FROM comites c 
                LEFT JOIN usuarios_perfil u ON c.presidente_id = u.id 
                WHERE c.id = ?";
        
        return $this->db->selectOne($sql, [$id]);
    }
    
    public function obtenerMiembros($comiteId) {
        $sql = "SELECT cm.*, u.nombre, u.apellido, u.email, e.nombre as empresa_nombre
                FROM comite_miembros cm
                INNER JOIN usuarios_perfil u ON cm.usuario_id = u.id
                INNER JOIN empresas e ON cm.empresa_id = e.id
                WHERE cm.comite_id = ? AND cm.estado = 'activo'
                ORDER BY cm.cargo, u.nombre";
        
        return $this->db->select($sql, [$comiteId]);
    }
}

// Modelo de Evento
class Evento extends BaseModel {
    protected $table = 'eventos';
    
    public function obtenerProximos($limite = null) {
        $sql = "SELECT e.*, 
                u.nombre as organizador_nombre, u.apellido as organizador_apellido,
                c.nombre as comite_nombre,
                (SELECT COUNT(*) FROM evento_asistentes WHERE evento_id = e.id) as total_asistentes
                FROM eventos e 
                LEFT JOIN usuarios_perfil u ON e.organizador_id = u.id
                LEFT JOIN comites c ON e.comite_id = c.id
                WHERE e.fecha_inicio >= NOW() AND e.estado IN ('programado', 'en_curso')
                ORDER BY e.fecha_inicio ASC";
        
        if ($limite) {
            $sql .= " LIMIT " . intval($limite);
        }
        
        return $this->db->select($sql);
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT e.*, 
                u.nombre as organizador_nombre, u.apellido as organizador_apellido,
                c.nombre as comite_nombre,
                (SELECT COUNT(*) FROM evento_asistentes WHERE evento_id = e.id) as total_asistentes
                FROM eventos e 
                LEFT JOIN usuarios_perfil u ON e.organizador_id = u.id
                LEFT JOIN comites c ON e.comite_id = c.id
                WHERE e.id = ?";
        
        return $this->db->selectOne($sql, [$id]);
    }
}

// Modelo de Boletín
class Boletin extends BaseModel {
    protected $table = 'boletines';
    
    public function obtenerPublicados($limite = null) {
        $sql = "SELECT b.*, u.nombre as autor_nombre, u.apellido as autor_apellido
                FROM boletines b 
                LEFT JOIN usuarios_perfil u ON b.autor_id = u.id
                WHERE b.estado = 'publicado' 
                AND (b.fecha_expiracion IS NULL OR b.fecha_expiracion > NOW())
                ORDER BY b.prioridad DESC, b.fecha_publicacion DESC";
        
        if ($limite) {
            $sql .= " LIMIT " . intval($limite);
        }
        
        return $this->db->select($sql);
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT b.*, u.nombre as autor_nombre, u.apellido as autor_apellido
                FROM boletines b 
                LEFT JOIN usuarios_perfil u ON b.autor_id = u.id
                WHERE b.id = ?";
        
        return $this->db->selectOne($sql, [$id]);
    }
}

// Modelo de Descuento
class Descuento extends BaseModel {
    protected $table = 'descuentos';
    
    public function obtenerActivos($limite = null) {
        $sql = "SELECT d.*, e.nombre as empresa_nombre
                FROM descuentos d 
                INNER JOIN empresas e ON d.empresa_proveedora_id = e.id
                WHERE d.estado = 'activo' 
                AND d.fecha_inicio <= CURDATE() 
                AND d.fecha_fin >= CURDATE()
                ORDER BY d.fecha_registro DESC";
        
        if ($limite) {
            $sql .= " LIMIT " . intval($limite);
        }
        
        return $this->db->select($sql);
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT d.*, e.nombre as empresa_nombre
                FROM descuentos d 
                INNER JOIN empresas e ON d.empresa_proveedora_id = e.id
                WHERE d.id = ?";
        
        return $this->db->selectOne($sql, [$id]);
    }
    
    public function usarDescuento($descuentoId, $usuarioId, $empresaId, $comentarios = '') {
        // Verificar que el descuento existe y está activo
        $descuento = $this->obtenerPorId($descuentoId);
        if (!$descuento || $descuento['estado'] !== 'activo') {
            throw new Exception('Descuento no disponible');
        }
        
        // Verificar límites de uso
        if ($descuento['usos_maximos'] && $descuento['usos_actuales'] >= $descuento['usos_maximos']) {
            throw new Exception('Descuento agotado');
        }
        
        $this->db->beginTransaction();
        
        try {
            // Registrar el uso
            $sql = "INSERT INTO descuento_usos (descuento_id, usuario_id, empresa_id, comentarios) VALUES (?, ?, ?, ?)";
            $this->db->insert($sql, [$descuentoId, $usuarioId, $empresaId, sanitizeInput($comentarios)]);
            
            // Actualizar contador de usos
            $sql = "UPDATE descuentos SET usos_actuales = usos_actuales + 1 WHERE id = ?";
            $this->db->update($sql, [$descuentoId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}

// Modelo de Estadísticas
class Estadistica extends BaseModel {
    protected $table = 'estadisticas';
    
    public function obtenerEstadisticasGenerales() {
        $stats = [];
        
        // Contar empresas activas
        $sql = "SELECT COUNT(*) as total FROM empresas_convenio WHERE activo = 1";
        $result = $this->db->selectOne($sql);
        $stats['empresas'] = $result['total'];
        
        // Contar usuarios activos
        $sql = "SELECT COUNT(*) as total FROM usuarios_perfil WHERE estado = 'activo'";
        $result = $this->db->selectOne($sql);
        $stats['usuarios'] = $result['total'];
        
        // Contar comités activos
        $sql = "SELECT COUNT(*) as total FROM comites WHERE estado = 'activo'";
        $result = $this->db->selectOne($sql);
        $stats['comites'] = $result['total'];
        
        // Contar eventos próximos
        $sql = "SELECT COUNT(*) as total FROM eventos WHERE fecha_inicio >= NOW() AND estado IN ('programado', 'en_curso')";
        $result = $this->db->selectOne($sql);
        $stats['eventos'] = $result['total'];
        
        // Contar descuentos activos
        $sql = "SELECT COUNT(*) as total FROM descuentos WHERE estado = 'activo' AND fecha_inicio <= CURDATE() AND fecha_fin >= CURDATE()";
        $result = $this->db->selectOne($sql);
        $stats['descuentos'] = $result['total'];
        
        // Contar boletines publicados
        $sql = "SELECT COUNT(*) as total FROM boletines WHERE estado = 'publicado'";
        $result = $this->db->selectOne($sql);
        $stats['boletines'] = $result['total'];
        
        return $stats;
    }
    
    public function obtenerEstadisticasPorFecha($fechaInicio, $fechaFin) {
        $sql = "SELECT tipo, valor, fecha FROM estadisticas 
                WHERE fecha BETWEEN ? AND ? 
                ORDER BY fecha DESC, tipo";
        
        return $this->db->select($sql, [$fechaInicio, $fechaFin]);
    }
    
    public function registrarEstadistica($tipo, $valor, $metadata = null) {
        $fecha = date('Y-m-d');
        
        $sql = "INSERT INTO estadisticas (tipo, valor, fecha, metadata) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE valor = ?, metadata = ?";
        
        $metadataJson = $metadata ? json_encode($metadata) : null;
        
        return $this->db->query($sql, [$tipo, $valor, $fecha, $metadataJson, $valor, $metadataJson]);
    }
}
?>