<?php
/**
 * API para cerrar sesión
 * Endpoint: /api/auth/logout.php
 * 
 * Implementa blacklist de tokens para logout real
 * 
 * @version 2.0.0
 * @date 2026-01-30
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Desactivar errores en pantalla
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Función para respuesta JSON limpia
function jsonResponse($data, $httpCode = 200) {
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Obtener token del header Authorization
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    $token = str_replace('Bearer ', '', $authHeader);
    
    // Si no hay token en el header, intentar desde POST
    if (empty($token)) {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $input = json_decode($rawInput, true);
            $token = $input['token'] ?? '';
        }
    }
    
    // Agregar token a blacklist si está disponible el sistema
    $tokenBlacklisted = false;
    
    if (!empty($token)) {
        $blacklistPath = dirname(dirname(__DIR__)) . '/utils/token-blacklist.php';
        $jwtValidatorPath = dirname(dirname(__DIR__)) . '/middleware/jwt-validator.php';
        
        if (file_exists($blacklistPath) && file_exists($jwtValidatorPath)) {
            require_once $jwtValidatorPath;
            require_once $blacklistPath;
            
            // Obtener expiry del token
            $payload = JwtValidator::getPayload($token);
            $expiry = $payload['exp'] ?? null;
            
            // Agregar a blacklist
            if (TokenBlacklist::add($token, $expiry)) {
                $tokenBlacklisted = true;
                error_log("Token agregado a blacklist para usuario: " . ($payload['email'] ?? 'unknown'));
            }
        }
    }
    
    // Limpiar cookies
    if (isset($_COOKIE['clúster_token'])) {
        setcookie('clúster_token', '', time() - 3600, '/');
    }
    
    // Limpiar sesión
    require_once dirname(dirname(__DIR__)) . '/config/session-config.php';
    SessionConfig::init();
    
    // Guardar email antes de destruir sesión para logging
    $userEmail = $_SESSION['user_email'] ?? 'unknown';
    
    session_destroy();
    
    // Log del logout
    error_log("Logout exitoso para usuario: $userEmail" . ($tokenBlacklisted ? " (token revocado)" : ""));
    
    // Respuesta exitosa
    jsonResponse([
        'success' => true,
        'message' => 'Sesión cerrada correctamente',
        'token_revoked' => $tokenBlacklisted
    ]);
    
} catch (Exception $e) {
    error_log("Error en logout API: " . $e->getMessage());
    
    // Aún así intentar limpiar la sesión
    try {
        if (isset($_COOKIE['clúster_token'])) {
            setcookie('clúster_token', '', time() - 3600, '/');
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    } catch (Exception $cleanupError) {
        error_log("Error limpiando sesión: " . $cleanupError->getMessage());
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Sesión cerrada (con advertencias)',
        'warning' => $e->getMessage()
    ]);
}
?>