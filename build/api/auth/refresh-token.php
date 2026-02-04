<?php
/**
 * API de Refresh Token
 * 
 * Permite renovar el access token usando un refresh token válido
 * 
 * @version 1.0.0
 * @date 2026-01-30
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Desactivar output de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para respuesta JSON limpia
function jsonResponse($data, $httpCode = 200) {
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ], 405);
}

try {
    // Cargar JWT validator
    $jwtValidatorPath = dirname(dirname(__DIR__)) . '/middleware/jwt-validator.php';
    
    if (!file_exists($jwtValidatorPath)) {
        throw new Exception('Sistema de refresh tokens no disponible');
    }
    
    require_once $jwtValidatorPath;
    
    // Obtener secreto desde .env o usar default
    $jwtSecret = getenv('JWT_SECRET') ?: 'CLAUT_SECRET_KEY_2024_SECURE';
    
    // Obtener datos de entrada
    $rawInput = file_get_contents('php://input');
    
    if (empty($rawInput)) {
        throw new Exception('No se recibieron datos de entrada');
    }
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    if (!isset($input['refresh_token'])) {
        throw new Exception('Refresh token requerido');
    }
    
    $refreshToken = $input['refresh_token'];
    
    // Validar refresh token
    $validation = JwtValidator::validate($refreshToken, $jwtSecret);
    
    if (!$validation['valid']) {
        error_log("Refresh token inválido: " . $validation['error']);
        
        jsonResponse([
            'success' => false,
            'error' => 'invalid_token',
            'message' => 'Refresh token inválido o expirado',
            'requires_login' => true
        ], 401);
    }
    
    // Verificar que sea un refresh token
    if (!JwtValidator::isRefreshToken($refreshToken)) {
        error_log("Token no es de tipo refresh");
        
        jsonResponse([
            'success' => false,
            'error' => 'invalid_token_type',
            'message' => 'El token proporcionado no es un refresh token',
            'requires_login' => true
        ], 400);
    }
    
    // Extraer payload
    $payload = $validation['payload'];
    
    // Generar nuevo access token
    $newPayload = [
        'user_id' => $payload['user_id'],
        'email' => $payload['email'],
        'rol' => $payload['rol']
    ];
    
    $newAccessToken = JwtValidator::generate($newPayload, $jwtSecret, JwtConfig::ACCESS_TOKEN_EXPIRY);
    
    if (!$newAccessToken) {
        throw new Exception('Error generando nuevo access token');
    }
    
    // Log de renovación exitosa
    error_log("Access token renovado para usuario: {$payload['email']} (ID: {$payload['user_id']})");
    
    // Respuesta exitosa
    jsonResponse([
        'success' => true,
        'message' => 'Token renovado exitosamente',
        'access_token' => $newAccessToken,
        'token_type' => 'Bearer',
        'expires_in' => 900 // 15 minutos
    ]);
    
} catch (Exception $e) {
    error_log("Error en refresh token API: " . $e->getMessage());
    
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage(),
        'requires_login' => true
    ], 400);
}
?>
