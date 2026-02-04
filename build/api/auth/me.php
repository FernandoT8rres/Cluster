<?php
// API para verificar el token del usuario actual
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Desactivar output de errores para evitar contaminar el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Función para respuesta JSON limpia
function jsonResponse($data, $httpCode = 200) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['status' => 'ok']);
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse([
        'success' => false, 
        'message' => 'Método no permitido. Use GET.'
    ], 405);
}

try {
    // Incluir archivos necesarios
    $basePath = dirname(dirname(__DIR__));
    $configPath = $basePath . '/assets/conexion/config.php';
    $jwtPath = dirname(__FILE__) . '/jwt_helper.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("Archivo de configuración no encontrado");
    }
    
    if (!file_exists($jwtPath)) {
        throw new Exception("JWT helper no encontrado");
    }
    
    require_once $configPath;
    require_once $jwtPath;
    
    // Obtener el token del header Authorization
    $headers = getallheaders();
    $authHeader = null;
    
    // Buscar el header Authorization (case-insensitive)
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
    
    if (!$authHeader) {
        jsonResponse([
            'success' => false,
            'message' => 'Token de autorización requerido'
        ], 401);
    }
    
    // Extraer el token (formato: "Bearer TOKEN")
    $token = null;
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
    
    if (!$token) {
        jsonResponse([
            'success' => false,
            'message' => 'Formato de token inválido'
        ], 401);
    }
    
    // Verificar el token JWT
    $decoded = verifyJWT($token);
    
    if (!$decoded) {
        jsonResponse([
            'success' => false,
            'message' => 'Token inválido o expirado'
        ], 401);
    }
    
    // Obtener información actual del usuario
    $usuario = new Usuario();
    $userData = $usuario->obtenerPorId($decoded['user_id']);
    
    if (!$userData || $userData['estado'] !== 'activo') {
        jsonResponse([
            'success' => false,
            'message' => 'Usuario no válido o inactivo'
        ], 401);
    }
    
    // Preparar datos del usuario (sin contraseña)
    unset($userData['password']);
    
    // Respuesta exitosa
    jsonResponse([
        'success' => true,
        'message' => 'Token válido',
        'user' => [
            'id' => $userData['id'],
            'nombre' => $userData['nombre'],
            'apellido' => $userData['apellido'],
            'email' => $userData['email'],
            'rol' => $userData['rol'],
            'estado' => $userData['estado'],
            'telefono' => $userData['telefono'] ?? null,
            'avatar' => $userData['avatar'] ?? null
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en me.php: " . $e->getMessage());
    
    jsonResponse([
        'success' => false,
        'message' => 'Error interno del servidor'
    ], 500);
}
?>