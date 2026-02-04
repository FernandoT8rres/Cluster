<?php
/**
 * ARCHIVO TEMPORAL DE DEBUG - ELIMINAR DESPUÉS DE PRUEBAS
 * Muestra el estado actual de la sesión
 */

// Definir constante de acceso
define('CLAUT_ACCESS', true);

// Cargar configuración de sesiones seguras
require_once dirname(__DIR__) . '/config/session-config.php';

// Iniciar sesión
SessionConfig::init();

// Headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

// Obtener información de sesión
$sessionInfo = [
    'timestamp' => date('Y-m-d H:i:s'),
    'session_status' => session_status(),
    'session_status_text' => [
        PHP_SESSION_DISABLED => 'DISABLED',
        PHP_SESSION_NONE => 'NONE',
        PHP_SESSION_ACTIVE => 'ACTIVE'
    ][session_status()],
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_data' => $_SESSION,
    'session_data_count' => count($_SESSION),
    'has_user_data' => isset($_SESSION['user_email']),
    'user_email' => $_SESSION['user_email'] ?? null,
    'user_rol' => $_SESSION['user_rol'] ?? null,
    'user_nombre' => $_SESSION['user_nombre'] ?? null,
    'is_admin' => (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'),
    'cookies' => $_COOKIE,
    'session_cookie_params' => session_get_cookie_params(),
    'session_config_info' => SessionConfig::getInfo()
];

echo json_encode($sessionInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
