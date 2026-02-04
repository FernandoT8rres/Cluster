<?php
/**
 * API de Estadísticas (Admin)
 */

// Definir constante de acceso
define('CLAUT_ACCESS', true);

// Configuración segura de sesiones
require_once dirname(dirname(__DIR__)) . '/config/session-config.php';
SessionConfig::init();

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
        'timestamp' => date('c'),
        'debug' => [
            'session_id' => session_id(),
            'session_vars' => array_keys($_SESSION)
        ]
    ];
    
    foreach ($extra as $key => $value) {
        $response[$key] = $value;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Verificar que el usuario es administrador - ORDEN CORREGIDO
 */
function verificarAdmin() {
    // PRIORIDAD 1: Verificar sesión local - Formato PRIMARIO (session.php y login-compatible.php)
    if (isset($_SESSION['user_email']) && isset($_SESSION['user_rol'])) {
        if ($_SESSION['user_rol'] === 'admin') {
            error_log("✅ Admin verificado con formato session.php - Email: " . $_SESSION['user_email']);
            return true;
        }
    }

    // PRIORIDAD 2: Verificar sesión local - Formato secundario (user_data)
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_data'])) {
        $userData = $_SESSION['user_data'];
        if (isset($userData['rol']) && $userData['rol'] === 'admin') {
            error_log("✅ Admin verificado con formato user_data");
            return true;
        }
    }
    
    // PRIORIDAD 3: Si no hay sesión local, verificar con APIs compatibles
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
                    // Actualizar sesión local con formato primario
                    $_SESSION['user_email'] = $data['data']['email'];
                    $_SESSION['user_nombre'] = $data['data']['nombre'];
                    $_SESSION['user_rol'] = $data['data']['rol'];
                    
                    error_log("✅ Admin verificado con API externa: $endpoint");
                    return true;
                }
            }
        } catch (Exception $e) {
            error_log("❌ Error verificando con $endpoint: " . $e->getMessage());
            continue;
        }
    }
    
    error_log("❌ Verificación admin FALLÓ - Session vars: " . print_r($_SESSION, true));
    responderJSON(false, null, 'Acceso denegado. Solo administradores pueden acceder', [
        'session_vars' => array_keys($_SESSION)
    ]);
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
    
    // Total de usuarios (usar la misma lógica que gestionar_usuarios.php)
    $userQuery = "SELECT COUNT(*) as total FROM usuarios_perfil";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute();
    $userCount = $userStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stats['users'] = $userCount;

    // Log para debugging
    error_log("Total usuarios encontrados en stats: " . $userCount);
    
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
    $roleQuery = "SELECT rol, COUNT(*) as total FROM usuarios_perfil GROUP BY rol";
    $roleStmt = $conn->prepare($roleQuery);
    $roleStmt->execute();
    while ($row = $roleStmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['users_by_role'][$row['rol']] = $row['total'];
    }
    
    // Usuarios registrados en los últimos 30 días (usar campo que existe)
    try {
        $recentQuery = "SELECT COUNT(*) as total FROM usuarios_perfil
                        WHERE COALESCE(fecha_ingreso, created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $recentStmt = $conn->prepare($recentQuery);
        $recentStmt->execute();
        $stats['recent_users'] = $recentStmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        error_log("Error obteniendo usuarios recientes: " . $e->getMessage());
        $stats['recent_users'] = 0;
    }
    
    error_log("✅ Estadísticas obtenidas correctamente: " . json_encode($stats));
    responderJSON(true, $stats, 'Estadísticas obtenidas correctamente');
    
} catch (Exception $e) {
    error_log("❌ Error obteniendo estadísticas: " . $e->getMessage());
    responderJSON(false, null, 'Error interno del servidor: ' . $e->getMessage());
}
?>
