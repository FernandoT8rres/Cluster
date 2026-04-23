<?php
// Definir constante de acceso
define('CLAUT_ACCESS', true);

// Configuración segura de sesiones
require_once dirname(dirname(__DIR__)) . '/config/session-config.php';

header('Content-Type: application/json; charset=utf-8');

// CORS dinámico igual que login-compatible.php para que el browser envíe la cookie
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'https://intranet.clautmetropolitano.mx';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true'); // CRÍTICO: sin esto el browser no manda la cookie de sesión

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Iniciar y validar sesión automáticamente
$sessionValid = SessionConfig::init() && SessionConfig::isValid();

try {
    // Verificar si existe sesión activa
    $isValid = false;
    $userData = null;

    // Verificar variables de sesión (usar las mismas que login-compatible.php establece)
    if ($sessionValid && isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])) {
        $isValid = true;
        $userData = [
            'user_id' => $_SESSION['user_id'] ?? null,
            'email'   => $_SESSION['user_email'],
            'nombre'  => $_SESSION['user_nombre'] ?? null,
            'empresa' => $_SESSION['user_nombre_empresa'] ?? null,
            'rol'     => $_SESSION['user_rol'] ?? null
        ];
    }

    // Cerrar escritura de sesión antes de responder (fix crítico Hostinger LiteSpeed)
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success'   => true,
        'valid'     => $isValid,
        'data'      => $userData,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'valid'   => false,
        'error'   => 'Error interno del servidor'
    ], JSON_UNESCAPED_UNICODE);
}
?>