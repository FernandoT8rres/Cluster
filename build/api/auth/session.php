<?php
/**
 * Sistema de autenticación basado en sesiones PHP
 * Elimina la dependencia de localStorage y maneja todo desde el servidor
 */

// Definir constante de acceso
define('CLAUT_ACCESS', true);

// Configuración segura de sesiones
require_once dirname(dirname(__DIR__)) . '/config/session-config.php';
SessionConfig::init();

error_log("SESSION DEBUG: Session ID: " . session_id());
error_log("SESSION DEBUG: Session content: " . print_r($_SESSION, true));

header('Content-Type: application/json; charset=UTF-8');

// CORS Dinámico para permitir credenciales
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

/**
 * Función para responder en JSON
 */
function responderJSON($success, $data = null, $message = '', $extra = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c'),
        'session_id' => session_id(),
        'debug_session' => $_SESSION // DUMP COMPLETO DE SESION
    ];
    
    foreach ($extra as $key => $value) {
        $response[$key] = $value;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Verificar usuario por credenciales
 */
function verificarCredenciales($email, $password) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $query = "SELECT email, password, nombre, nombre_empresa, rol FROM usuarios_perfil WHERE email = :email";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
            // Devolver email, nombre, nombre_empresa y rol si el login es exitoso
            return [
                'email' => $user['email'],
                'nombre' => $user['nombre'],
                'nombre_empresa' => $user['nombre_empresa'],
                'rol' => $user['rol']
            ];
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error verificando credenciales: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener usuario actual de la sesión
 */
function obtenerUsuarioSesion() {
    // Verificación estricta: Debe tener email Y rol para ser válida
    if (isset($_SESSION['user_email']) && !empty($_SESSION['user_rol'])) {
        return [
            'email' => $_SESSION['user_email'],
            'nombre' => $_SESSION['user_nombre'] ?? '',
            'nombre_empresa' => $_SESSION['user_nombre_empresa'] ?? '',
            'rol' => $_SESSION['user_rol']
        ];
    }

    return false;
}

/**
 * Iniciar sesión de usuario
 */
function iniciarSesion($user) {
    // Limpiar basura anterior
    session_unset();

    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_nombre'] = $user['nombre'];
    $_SESSION['user_nombre_empresa'] = $user['nombre_empresa'];
    $_SESSION['user_rol'] = $user['rol'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    SessionConfig::destroy();
    return true;
}

// Manejar las diferentes acciones
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        switch ($action) {
            case 'check':
                // Verificar si hay una sesión activa
                $user = obtenerUsuarioSesion();
                if ($user) {
                    responderJSON(true, $user, 'Usuario autenticado');
                } else {
                    responderJSON(false, null, 'No hay sesión activa');
                }
                break;
                
            case 'user':
                // Obtener datos del usuario actual
                $user = obtenerUsuarioSesion();
                if ($user) {
                    responderJSON(true, $user, 'Datos de usuario obtenidos');
                } else {
                    responderJSON(false, null, 'Usuario no autenticado');
                }
                break;
                
            default:
                responderJSON(false, null, 'Acción no válida');
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'login':
                if (!isset($input['email']) || !isset($input['password'])) {
                    responderJSON(false, null, 'Email y contraseña requeridos');
                }
                
                $user = verificarCredenciales($input['email'], $input['password']);
                
                if ($user) {
                    iniciarSesion($user);
                    responderJSON(true, $user, 'Login exitoso');
                } else {
                    responderJSON(false, null, 'Credenciales incorrectas');
                }
                break;
                
            case 'logout':
                cerrarSesion();
                responderJSON(true, null, 'Logout exitoso');
                break;
                
            default:
                responderJSON(false, null, 'Acción no válida');
        }
        break;
        
    default:
        http_response_code(405);
        responderJSON(false, null, 'Método no permitido');
}
?>