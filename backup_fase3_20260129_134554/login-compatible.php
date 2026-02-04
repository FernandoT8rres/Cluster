<?php
/**
 * API de login compatible con cualquier estructura de tabla usuarios_perfil
 * Se adapta automáticamente a las columnas disponibles
 */

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_lifetime', 3600); // 1 hora
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');

session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
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
        'session_id' => session_id()
    ];
    
    foreach ($extra as $key => $value) {
        $response[$key] = $value;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Verificar usuario por credenciales - Compatible con cualquier estructura
 */
function verificarCredenciales($email, $password) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Query incluyendo estado_usuario para verificar aprobación
        $selectQuery = "SELECT email, password, nombre, nombre_empresa, rol, estado_usuario FROM usuarios_perfil WHERE email = :email";
        error_log("Attempting login for email: " . $email);
        
        $stmt = $conn->prepare($selectQuery);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("User found: " . ($user ? "YES" : "NO"));
        if ($user) {
            error_log("User data: " . json_encode(array_keys($user)));
            error_log("Password in DB exists: " . (!empty($user['password']) ? "YES" : "NO"));
        }
        
        if ($user && !empty($user['password'])) {
            if (password_verify($password, $user['password'])) {
                // Verificar estado de aprobación
                $estado_usuario = $user['estado_usuario'] ?? 'pendiente';

                if ($estado_usuario !== 'activo') {
                    $mensajes_estado = [
                        'pendiente' => 'Tu cuenta está pendiente de aprobación por un administrador. Te notificaremos cuando sea aprobada.',
                        'rechazado' => 'Tu cuenta ha sido rechazada. Contacta al administrador para más información.',
                        'lista_espera' => 'Tu cuenta está en lista de espera. Te notificaremos cuando sea aprobada.'
                    ];

                    return [
                        'error' => 'account_not_approved',
                        'message' => $mensajes_estado[$estado_usuario] ?? 'Tu cuenta no está activa. Contacta al administrador.'
                    ];
                }

                // Usuario aprobado - permitir acceso
                return [
                    'email' => $user['email'],
                    'nombre' => $user['nombre'],
                    'nombre_empresa' => $user['nombre_empresa'],
                    'rol' => $user['rol'],
                    'estado_usuario' => $estado_usuario
                ];
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error verificando credenciales: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener usuario actual de la sesión - Simplificado
 */
function obtenerUsuarioSesion() {
    if (isset($_SESSION['user_email'])) {
        return [
            'email' => $_SESSION['user_email'],
            'nombre' => $_SESSION['user_nombre'] ?? '',
            'nombre_empresa' => $_SESSION['user_nombre_empresa'] ?? '',
            'rol' => $_SESSION['user_rol'] ?? ''
        ];
    }

    return false;
}

/**
 * Iniciar sesión de usuario
 */
function iniciarSesion($user) {
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
    session_destroy();
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
                    // Verificar si hay error de aprobación
                    if (isset($user['error']) && $user['error'] === 'account_not_approved') {
                        responderJSON(false, null, $user['message'], ['error_type' => 'account_not_approved']);
                    } else {
                        // Usuario aprobado - iniciar sesión
                        iniciarSesion($user);
                        responderJSON(true, $user, 'Login exitoso');
                    }
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