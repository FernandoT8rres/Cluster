<?php
/**
 * TEST DE LOGIN - VERIFICAR QUE SESIÓN SE GUARDA
 * Este script simula un login y verifica que los datos se guarden en sesión
 */

// Definir constante de acceso
define('CLAUT_ACCESS', true);

// Cargar configuración de sesiones
require_once dirname(__DIR__) . '/config/session-config.php';
SessionConfig::init();

header('Content-Type: application/json; charset=UTF-8');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'error' => 'Usa POST con JSON: {"email": "tu@email.com", "password": "tu_password"}',
        'method' => $_SERVER['REQUEST_METHOD']
    ], JSON_PRETTY_PRINT);
    exit;
}

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    echo json_encode([
        'error' => 'Faltan email o password',
        'received' => $input
    ], JSON_PRETTY_PRINT);
    exit;
}

// Cargar clase Usuario
require_once dirname(__DIR__) . '/assets/conexion/config.php';

try {
    $usuario = new Usuario();
    $userData = $usuario->login($input['email'], $input['password']);
    
    if (!$userData) {
        echo json_encode([
            'success' => false,
            'message' => 'Credenciales incorrectas',
            'session_before' => $_SESSION
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // GUARDAR EN SESIÓN (igual que login.php)
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_nombre'] = $userData['nombre'];
    $_SESSION['user_apellido'] = $userData['apellido'] ?? '';
    $_SESSION['user_rol'] = $userData['rol'];
    $_SESSION['user_empresa'] = $userData['nombre_empresa'] ?? '';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Regenerar session ID
    if (class_exists('SessionConfig')) {
        SessionConfig::regenerate();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login exitoso - Sesión guardada',
        'user_data' => [
            'id' => $userData['id'],
            'email' => $userData['email'],
            'nombre' => $userData['nombre'],
            'rol' => $userData['rol']
        ],
        'session_after' => $_SESSION,
        'session_id' => session_id(),
        'next_step' => 'Ahora accede a /build/api/debug-session.php para verificar que la sesión persiste'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
