<?php
// Definir constante de acceso
define('CLAUT_ACCESS', true);

// Configuración segura de sesiones
require_once dirname(dirname(__DIR__)) . '/config/session-config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar y validar sesión automáticamente
$sessionValid = SessionConfig::init() && SessionConfig::isValid();

try {
    // Verificar si existe sesión activa
    $isValid = $sessionValid && isset($_SESSION['user_email']) && !empty($_SESSION['user_email']);
    $userData = null;

    // Verificar variables de sesión (usar las mismas que el sistema actual)
    if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])) {
        $isValid = true;
        $userData = [
            'email' => $_SESSION['user_email'],
            'nombre' => $_SESSION['user_nombre'] ?? null,
            'empresa' => $_SESSION['user_empresa'] ?? null,
            'rol' => $_SESSION['user_rol'] ?? null
        ];
    }

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'valid' => $isValid,
        'data' => $userData,
        'session_id' => session_id(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Error del servidor
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'valid' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>