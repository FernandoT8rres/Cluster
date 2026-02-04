<?php
/**
 * API para estadísticas del panel de administración
 */

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
        'timestamp' => date('c')
    ];
    
    foreach ($extra as $key => $value) {
        $response[$key] = $value;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Verificar que el usuario es administrador - Compatible con múltiples APIs
 */
function verificarAdmin() {
    // Verificar sesión local - Formato 1 (user_data)
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_data'])) {
        $userData = $_SESSION['user_data'];
        if (isset($userData['rol']) && $userData['rol'] === 'admin') {
            return true;
        }
    }

    // Verificar sesión local - Formato 2 (login-compatible)
    if (isset($_SESSION['user_email']) && isset($_SESSION['user_rol'])) {
        if ($_SESSION['user_rol'] === 'admin') {
            return true;
        }
    }
    
    // Si no hay sesión local, verificar con APIs compatibles
    $endpoints = [
        '../auth/login-compatible.php?action=check',
        '../auth/session.php?action=check'
    ];
    
    foreach ($endpoints as $endpoint) {
        try {
            $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            $cookies = isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : '';

            $url = $scheme . '://' . $host . dirname(dirname($_SERVER['REQUEST_URI'])) . '/' . $endpoint;

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'Cookie: ' . $cookies
                ]
            ]);
            
            $result = file_get_contents($url, false, $context);
            if ($result !== false) {
                $data = json_decode($result, true);
                if ($data && $data['success'] && isset($data['data']['rol']) && $data['data']['rol'] === 'admin') {
                    // Actualizar sesión local
                    $_SESSION['user_id'] = $data['data']['id'];
                    $_SESSION['user_data'] = $data['data'];
                    return true;
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    responderJSON(false, null, 'Acceso denegado. Solo administradores pueden acceder');
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    responderJSON(false, null, 'Método no permitido');
}

try {
    // Verificar permisos de administrador
    verificarAdmin();
    
    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Obtener estadísticas
    $stats = [];
    
    // Total de usuarios
    $userQuery = "SELECT COUNT(*) as total FROM usuarios_perfil WHERE activo = 1";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute();
    $stats['users'] = $userStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de empresas activas
    $companyQuery = "SELECT COUNT(*) as total FROM empresas_convenio WHERE activo = 1";
    $companyStmt = $conn->prepare($companyQuery);
    $companyStmt->execute();
    $stats['companies'] = $companyStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de eventos (si existe la tabla)
    try {
        $eventQuery = "SELECT COUNT(*) as total FROM eventos WHERE activo = 1";
        $eventStmt = $conn->prepare($eventQuery);
        $eventStmt->execute();
        $stats['events'] = $eventStmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $stats['events'] = 0;
    }
    
    // Total de boletines (si existe la tabla)
    try {
        $bulletinQuery = "SELECT COUNT(*) as total FROM boletines WHERE activo = 1";
        $bulletinStmt = $conn->prepare($bulletinQuery);
        $bulletinStmt->execute();
        $stats['bulletins'] = $bulletinStmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $stats['bulletins'] = 0;
    }
    
    // Estadísticas adicionales
    $stats['users_by_role'] = [];
    $roleQuery = "SELECT rol, COUNT(*) as total FROM usuarios_perfil WHERE activo = 1 GROUP BY rol";
    $roleStmt = $conn->prepare($roleQuery);
    $roleStmt->execute();
    while ($row = $roleStmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['users_by_role'][$row['rol']] = $row['total'];
    }
    
    // Usuarios registrados en los últimos 30 días
    $recentQuery = "SELECT COUNT(*) as total FROM usuarios_perfil 
                    WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $recentStmt = $conn->prepare($recentQuery);
    $recentStmt->execute();
    $stats['recent_users'] = $recentStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    responderJSON(true, $stats, 'Estadísticas obtenidas correctamente');
    
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
    responderJSON(false, null, 'Error interno del servidor');
}
?>