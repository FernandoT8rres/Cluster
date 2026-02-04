<?php
/**
 * API de Actividad Reciente (Admin)
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
        'timestamp' => date('c')
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
    // PRIORIDAD 1: Verificar formato session.php
    if (isset($_SESSION['user_email']) && isset($_SESSION['user_rol'])) {
        if ($_SESSION['user_rol'] === 'admin') {
            return true;
        }
    }
    
    // PRIORIDAD 2: Verificar formato antiguo
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_data'])) {
        $userData = $_SESSION['user_data'];
        if (isset($userData['rol']) && $userData['rol'] === 'admin') {
            return true;
        }
    }
    
    responderJSON(false, null, 'Acceso denegado. Solo administradores');
}

/**
 * Obtener actividad reciente
 */
function obtenerActividadReciente($limite = 10) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Obtener registros de usuarios recientes
        $query = "SELECT 
                    CONCAT('Nuevo usuario registrado: ', nombre, ' ', apellido) as action,
                    fecha_registro as created_at
                  FROM usuarios_perfil
                  WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                  ORDER BY fecha_registro DESC
                  LIMIT :limite";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        $actividad = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear fechas
        foreach ($actividad as &$item) {
            $item['created_at'] = date('d/m/Y H:i', strtotime($item['created_at']));
        }
        
        return $actividad;
        
    } catch (Exception $e) {
        error_log("Error obteniendo actividad reciente: " . $e->getMessage());
        return [];
    }
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    responderJSON(false, null, 'Método no permitido');
}

// Verificar permisos de administrador
verificarAdmin();

// Obtener límite de resultados
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;

// Obtener y retornar actividad
$actividad = obtenerActividadReciente($limite);
responderJSON(true, $actividad, 'Actividad reciente obtenida');
?>
