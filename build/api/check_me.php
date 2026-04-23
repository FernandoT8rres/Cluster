<?php
define('CLAUT_ACCESS', true);
require_once dirname(__DIR__) . '/config/session-config.php';
SessionConfig::init();

header('Content-Type: application/json');

$keys = ['user_rol', 'usuario_rol', 'rol', 'user_role', 'usuario_tipo', 'user_email', 'user_nombre'];
$debug = [];

foreach ($keys as $key) {
    $debug[$key] = $_SESSION[$key] ?? 'MISSING';
}

$rol = strtolower(
    $_SESSION['user_rol'] ?? 
    $_SESSION['usuario_rol'] ?? 
    $_SESSION['rol'] ?? 
    $_SESSION['user_role'] ?? 
    $_SESSION['usuario_tipo'] ?? 
    'none'
);

$esAdmin = in_array($rol, ['admin', 'administrador'], true);

echo json_encode([
    'success' => true,
    'session_id' => session_id(),
    'session_name' => session_name(),
    'detected_role' => $rol,
    'is_admin' => $esAdmin,
    'raw_session_keys' => $debug,
    'all_session' => $_SESSION
]);
