<?php
// API para verificar el token del usuario actual
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://intranet.clautmetropolitano.mx');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Desactivar output de errores para evitar contaminar el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Incluir utilerías y middleware
require_once dirname(dirname(__DIR__)) . '/utils/api-response.php';
require_once dirname(dirname(__DIR__)) . '/middleware/jwt-validator.php';

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ApiResponse::success(null, 'ok');
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiResponse::error('Método no permitido. Use GET.', 405);
}

try {
    require_once $configPath;
    // El middleware JWT ya está cargado arriba
    
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
        ApiResponse::error('Token de autorización requerido', 401, ['requires_login' => true]);
    }
    
    // Extraer el token (formato: "Bearer TOKEN")
    $token = null;
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
    
    if (!$token) {
        ApiResponse::error('Formato de token inválido', 401, ['requires_login' => true]);
    }
    
    // Verificar el token JWT usando el validador central
    $jwtSecret = getenv('JWT_SECRET') ?: 'CLAUT_SECRET_KEY_2024_SECURE';
    $result = JwtValidator::validate($token, $jwtSecret);
    
    if (!$result['valid']) {
        ApiResponse::error($result['error'] ?? 'Token inválido o expirado', 401, ['requires_login' => true]);
    }
    
    $decoded = $result['payload'];
    
    // Obtener información actual del usuario
    $usuario = new Usuario();
    $userData = $usuario->obtenerPorId($decoded['user_id']);
    
    if (!$userData || ($userData['estado'] ?? '') !== 'activo') {
        ApiResponse::error('Usuario no válido o inactivo', 401, ['requires_login' => true]);
    }
    
    // Preparar datos del usuario (sin contraseña)
    unset($userData['password']);
    
    // Respuesta exitosa
    ApiResponse::success([
        'user' => [
            'id' => $userData['id'],
            'nombre' => $userData['nombre'],
            'apellido' => $userData['apellido'],
            'email' => $userData['email'],
            'rol' => $userData['rol'],
            'estado' => $userData['estado'] ?? 'activo',
            'telefono' => $userData['telefono'] ?? null,
            'avatar' => $userData['avatar'] ?? null
        ]
    ], 'Token válido');
    
} catch (Exception $e) {
    error_log("Error en me.php: " . $e->getMessage());
    
    ApiResponse::error('Error interno del servidor', 500);
}
?>