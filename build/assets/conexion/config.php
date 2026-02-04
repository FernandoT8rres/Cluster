<?php
// config.php - Incluir la configuración principal de Database
require_once dirname(dirname(__DIR__)) . '/config/database.php';

// Clase wrapper para mantener compatibilidad con el código existente
class DatabaseWrapper {
    private $db;
    private $connection;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Error en consulta: " . $e->getMessage());
        }
    }
    
    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $this->connection->lastInsertId();
    }
    
    public function select($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function selectOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function update($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function delete($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}

// Clase para manejo de usuarios
class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = new DatabaseWrapper();
    }
    
    public function crear($datos) {
        $sql = "INSERT INTO usuarios_perfil (nombre, apellido, email, password, telefono, empresa_id, rol, estado, avatar) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $datos['nombre'],
            $datos['apellido'],
            $datos['email'],
            password_hash($datos['password'], PASSWORD_DEFAULT),
            $datos['telefono'] ?? null,
            $datos['empresa_id'] ?? null,
            $datos['rol'] ?? 'empleado',
            $datos['estado'] ?? 'pendiente',
            $datos['avatar'] ?? null
        ];
        
        return $this->db->insert($sql, $params);
    }
    
    public function login($email, $password) {
        $sql = "SELECT * FROM usuarios_perfil WHERE email = ? AND estado = 'activo'";
        $usuario = $this->db->selectOne($sql, [$email]);
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Actualizar fecha de último acceso
            $this->actualizarUltimoAcceso($usuario['id']);
            return $usuario;
        }
        
        return false;
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM usuarios_perfil WHERE id = ?";
        return $this->db->selectOne($sql, [$id]);
    }
    
    public function obtenerPorEmail($email) {
        $sql = "SELECT * FROM usuarios_perfil WHERE email = ?";
        return $this->db->selectOne($sql, [$email]);
    }
    
    public function obtenerTodos($filtros = []) {
        $sql = "SELECT id, nombre, apellido, email, telefono, empresa_id, rol, estado, fecha_registro, avatar FROM usuarios_perfil";
        $params = [];
        $condiciones = [];
        
        if (!empty($filtros['rol'])) {
            $condiciones[] = "rol = ?";
            $params[] = $filtros['rol'];
        }
        
        if (!empty($filtros['estado'])) {
            $condiciones[] = "estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(" AND ", $condiciones);
        }
        
        $sql .= " ORDER BY fecha_registro DESC";
        
        return $this->db->select($sql, $params);
    }
    
    public function actualizar($id, $datos) {
        $campos = [];
        $params = [];
        
        foreach ($datos as $campo => $valor) {
            if ($campo === 'password') {
                $campos[] = "password = ?";
                $params[] = password_hash($valor, PASSWORD_DEFAULT);
            } else {
                $campos[] = "$campo = ?";
                $params[] = $valor;
            }
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
        $sql = "UPDATE usuarios_perfil SET ultima_actividad = CURRENT_TIMESTAMP WHERE id = ?";
        $this->db->update($sql, [$id]);
    }
    
    public function cambiarEstado($id, $estado) {
        $sql = "UPDATE usuarios_perfil SET estado = ? WHERE id = ?";
        return $this->db->update($sql, [$estado, $id]);
    }
}

// Funciones de utilidad
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function verificarSesion() {
    iniciarSesion();
    return isset($_SESSION['usuario_id']);
}

function obtenerUsuarioActual() {
    iniciarSesion();
    if (isset($_SESSION['usuario_id'])) {
        $usuario = new Usuario();
        return $usuario->obtenerPorId($_SESSION['usuario_id']);
    }
    return null;
}

function cerrarSesion() {
    iniciarSesion();
    session_destroy();
}

function redirigir($url) {
    header("Location: $url");
    exit;
}

function respuestaJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>