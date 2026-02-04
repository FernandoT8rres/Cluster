<?php
/**
 * Configuración principal de la aplicación Clúster Intranet
 * Este archivo centraliza toda la configuración de la aplicación
 */

// Prevenir acceso directo
if (!defined('CLAUT_ACCESS')) {
    die('Acceso directo no permitido');
}

// Configuración de errores (cambiar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS

// Configuración de la base de datos
class DatabaseConfig {
    // Configuración local (desarrollo)
    const LOCAL_CONFIG = [
        'host' => '127.0.0.1',
        'username' => 'u695712029_claut_fer',
        'password' => 'CLAUT@admin_fernando!7',
        'database' => 'u695712029_claut_intranet',
        'charset' => 'utf8mb4'
    ];
    
    // Configuración de producción
    const PRODUCTION_CONFIG = [
        'host' => '127.0.0.1',
        'username' => 'u695712029_claut_fer',
        'password' => 'CLAUT@admin_fernando!7',
        'database' => 'u695712029_claut_intranet',
        'charset' => 'utf8mb4'
    ];
    
    public static function getConfig() {
        // Detectar ambiente (cambiar según necesidad)
        $isProduction = isset($_SERVER['HTTP_HOST']) && 
                       (strpos($_SERVER['HTTP_HOST'], 'localhost') === false && 
                        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') === false);
        
        return $isProduction ? self::PRODUCTION_CONFIG : self::LOCAL_CONFIG;
    }
}

// Clase principal de base de datos
class Database {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $this->config = DatabaseConfig::getConfig();
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 30,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']}"
            ];
            
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
            
        } catch (PDOException $e) {
            error_log("Error de conexión a BD: " . $e->getMessage());
            die("Error de conexión a la base de datos. Por favor, contacte al administrador.");
        }
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
            error_log("Error en consulta SQL: " . $e->getMessage() . " - SQL: " . $sql);
            throw new Exception("Error en la base de datos: " . $e->getMessage());
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
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
}

// Funciones de utilidad para respuestas JSON
function jsonResponse($data, $statusCode = 200, $message = null) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'data' => $data,
        'timestamp' => date('c')
    ];
    
    if ($message !== null) {
        $response['message'] = $message;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($message, $statusCode = 400, $details = null) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => false,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if ($details !== null) {
        $response['details'] = $details;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para validar y limpiar datos de entrada
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Función para validar email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Función para generar token JWT simple (para casos básicos)
function generateToken($userId, $email) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'email' => $email,
        'exp' => time() + (24 * 60 * 60) // 24 horas
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, 'claut_secret_key_2025', true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

// Función para verificar token JWT
function verifyToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($header, $payload, $signature) = $parts;
    
    $validSignature = hash_hmac('sha256', $header . "." . $payload, 'claut_secret_key_2025', true);
    $validSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
    
    if ($signature !== $validSignature) {
        return false;
    }
    
    $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    
    if ($payloadData['exp'] < time()) {
        return false; // Token expirado
    }
    
    return $payloadData;
}

// Función para requerir autenticación en endpoints
function requireAuth() {
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        jsonError('Token de autenticación requerido', 401);
    }
    
    $payload = verifyToken($token);
    if (!$payload) {
        jsonError('Token inválido o expirado', 401);
    }
    
    return $payload;
}

// Configuración de CORS
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }
}

// Constantes de la aplicación
define('CLAUT_VERSION', '1.0.0');
define('CLAUT_NAME', 'Clúster Intranet');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Inicialización automática
setCorsHeaders();

// Crear directorio de uploads si no existe
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
?>