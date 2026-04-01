&lt;?php
/**
 * API: User Messages Management
 * File: api/mensajes_usuario.php
 * Purpose: Handle user messages and notifications
 */

// Disable error display to prevent breaking JSON responses
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://intranet.clautmetropolitano.mx');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$user_id = intval($_SESSION['user_id']);
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    switch ($method) {
        case 'GET':
            // Get user messages with pagination
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            $leido = isset($_GET['leido']) ? intval($_GET['leido']) : null;
            $offset = ($page - 1) * $limit;
            
            // Build query
            $where = "WHERE usuario_id = ?";
            $params = [$user_id];
            
            if ($leido !== null) {
                $where .= " AND leido = ?";
                $params[] = $leido;
            }
            
            // Get total count
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM mensajes_usuario $where");
            $stmt->execute($params);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get messages
            $stmt = $conn->prepare("
                SELECT * FROM mensajes_usuario 
                $where 
                ORDER BY fecha_creacion DESC 
                LIMIT ? OFFSET ?
            ");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'mensajes' => $mensajes,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => intval($total),
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'POST':
            // Create message (system use - could be restricted)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['titulo']) || !isset($data['contenido'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                exit();
            }
            
            $stmt = $conn->prepare("
                INSERT INTO mensajes_usuario 
                (usuario_id, tipo, titulo, contenido, relacionado_tipo, relacionado_id, icono, color) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $user_id,
                $data['tipo'] ?? 'mensaje',
                $data['titulo'],
                $data['contenido'],
                $data['relacionado_tipo'] ?? null,
                $data['relacionado_id'] ?? null,
                $data['icono'] ?? 'fa-envelope',
                $data['color'] ?? 'info'
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Mensaje creado exitosamente',
                    'mensaje_id' => $conn->lastInsertId()
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear el mensaje']);
            }
            break;
            
        case 'PUT':
            // Mark message as read/unread
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de mensaje requerido']);
                exit();
            }
            
            $mensaje_id = intval($data['id']);
            $leido = isset($data['leido']) ? intval($data['leido']) : 1;
            
            // Verify ownership
            $stmt = $conn->prepare("SELECT id FROM mensajes_usuario WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$mensaje_id, $user_id]);
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Mensaje no encontrado']);
                exit();
            }
            
            $stmt = $conn->prepare("UPDATE mensajes_usuario SET leido = ? WHERE id = ?");
            $result = $stmt->execute([$leido, $mensaje_id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Mensaje actualizado exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar el mensaje']);
            }
            break;
            
        case 'DELETE':
            // Delete message
            $mensaje_id = intval($_GET['id'] ?? 0);
            
            if (!$mensaje_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de mensaje requerido']);
                exit();
            }
            
            // Verify ownership
            $stmt = $conn->prepare("SELECT id FROM mensajes_usuario WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$mensaje_id, $user_id]);
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Mensaje no encontrado']);
                exit();
            }
            
            $stmt = $conn->prepare("DELETE FROM mensajes_usuario WHERE id = ?");
            $result = $stmt->execute([$mensaje_id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Mensaje eliminado exitosamente'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar el mensaje']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor',
        'message' => $e->getMessage()
    ]);
}
