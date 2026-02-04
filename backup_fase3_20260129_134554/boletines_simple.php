<?php
/**
 * API de boletines simplificada con debugging mejorado
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuración de base de datos
$config = [
    'host' => '127.0.0.1',
    'username' => 'u695712029_claut_fer', 
    'password' => 'CLAUT@admin_fernando!7',
    'database' => 'u695712029_claut_intranet',
    'charset' => 'utf8mb4'
];

// Función para respuesta JSON
function sendJsonResponse($data, $success = true) {
    $response = [
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($success) {
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
            $response['message'] = $data;
        }
    } else {
        $response['message'] = $data;
        $response['success'] = false;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Función para sanitizar input
function clean($input) {
    if (is_array($input)) {
        return array_map('clean', $input);
    }
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

try {
    // Conectar a la base de datos
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Crear tabla si no existe
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `boletines` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `titulo` varchar(255) NOT NULL,
        `contenido` text NOT NULL,
        `estado` enum('borrador','publicado','archivado') DEFAULT 'borrador',
        `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
        `fecha_publicacion` datetime DEFAULT NULL,
        `archivo_adjunto` varchar(255) DEFAULT NULL,
        `visualizaciones` int(11) DEFAULT 0,
        `autor_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_estado` (`estado`),
        KEY `idx_fecha_publicacion` (`fecha_publicacion`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createTableSQL);
    
    // Crear directorio de uploads si no existe
    $uploadDir = '../uploads/';
    if (!file_exists($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM boletines WHERE id = ?");
                $stmt->execute([$id]);
                $boletin = $stmt->fetch();
                
                if (!$boletin) {
                    sendJsonResponse('Boletín no encontrado', false);
                }
                
                $updateStmt = $pdo->prepare("UPDATE boletines SET visualizaciones = COALESCE(visualizaciones, 0) + 1 WHERE id = ?");
                $updateStmt->execute([$id]);
                $boletin['visualizaciones'] = intval($boletin['visualizaciones']) + 1;
                
                sendJsonResponse(['data' => $boletin]);
                
            } else {
                $estado = isset($_GET['estado']) ? clean($_GET['estado']) : null;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
                
                $sql = "SELECT * FROM boletines";
                $params = [];
                
                if ($estado && $estado !== 'todos') {
                    $sql .= " WHERE estado = ?";
                    $params[] = $estado;
                }
                
                $sql .= " ORDER BY fecha_creacion DESC";
                
                if ($limit && $limit > 0) {
                    $sql .= " LIMIT " . $limit;
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $boletines = $stmt->fetchAll();
                
                foreach ($boletines as &$boletin) {
                    $boletin['id'] = intval($boletin['id']);
                    $boletin['visualizaciones'] = intval($boletin['visualizaciones'] ?? 0);
                }
                
                sendJsonResponse([
                    'data' => $boletines,
                    'total' => count($boletines)
                ]);
            }
            break;
            
        case 'POST':
            // Debug información recibida
            error_log("=== DEBUG POST ===");
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));
            
            $titulo = isset($_POST['titulo']) ? clean($_POST['titulo']) : '';
            $contenido = isset($_POST['contenido']) ? clean($_POST['contenido']) : '';
            $estado = isset($_POST['estado']) ? clean($_POST['estado']) : 'borrador';
            
            // Validación más específica
            if (empty($titulo)) {
                sendJsonResponse('El título es requerido', false);
            }
            
            if (empty($contenido)) {
                sendJsonResponse('El contenido es requerido', false);
            }
            
            // Validar estado
            if (!in_array($estado, ['borrador', 'publicado', 'archivado'])) {
                $estado = 'borrador';
            }
            
            // Manejar archivo adjunto
            $archivo_adjunto = null;
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['archivo'];
                
                // Validar tamaño
                if ($file['size'] > 5 * 1024 * 1024) {
                    sendJsonResponse('El archivo es demasiado grande. Máximo 5MB', false);
                }
                
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                                     'txt', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3'];
                
                if (!in_array($extension, $allowedExtensions)) {
                    sendJsonResponse('Formato de archivo no permitido', false);
                }
                
                $archivo_adjunto = 'boletin_' . time() . '_' . uniqid() . '.' . $extension;
                $filePath = $uploadDir . $archivo_adjunto;
                
                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    error_log("Error moviendo archivo a: " . $filePath);
                    sendJsonResponse('Error al subir el archivo', false);
                }
            }
            
            // Insertar boletín
            $fecha_creacion = date('Y-m-d H:i:s');
            $fecha_publicacion = ($estado === 'publicado') ? $fecha_creacion : null;
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO boletines (titulo, contenido, estado, fecha_creacion, fecha_publicacion, archivo_adjunto, visualizaciones)
                    VALUES (?, ?, ?, ?, ?, ?, 0)
                ");
                
                $result = $stmt->execute([$titulo, $contenido, $estado, $fecha_creacion, $fecha_publicacion, $archivo_adjunto]);
                
                if (!$result) {
                    error_log("Error en INSERT: " . print_r($stmt->errorInfo(), true));
                    sendJsonResponse('Error al insertar en la base de datos', false);
                }
                
                $id = $pdo->lastInsertId();
                
                // Obtener el boletín creado
                $stmt = $pdo->prepare("SELECT * FROM boletines WHERE id = ?");
                $stmt->execute([$id]);
                $boletin = $stmt->fetch();
                
                sendJsonResponse([
                    'success' => true,
                    'data' => $boletin,
                    'id' => intval($id),
                    'message' => 'Boletín creado exitosamente'
                ]);
                
            } catch (PDOException $e) {
                error_log("Error PDO en INSERT: " . $e->getMessage());
                sendJsonResponse('Error al crear boletín: ' . $e->getMessage(), false);
            }
            break;
            
        case 'PUT':
            $inputRaw = file_get_contents('php://input');
            $input = json_decode($inputRaw, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendJsonResponse('Error al decodificar JSON', false);
            }
            
            if (!isset($input['id'])) {
                sendJsonResponse('ID del boletín es requerido', false);
            }
            
            $id = intval($input['id']);
            $titulo = isset($input['titulo']) ? clean($input['titulo']) : null;
            $contenido = isset($input['contenido']) ? clean($input['contenido']) : null;
            $estado = isset($input['estado']) ? clean($input['estado']) : null;
            
            $checkStmt = $pdo->prepare("SELECT * FROM boletines WHERE id = ?");
            $checkStmt->execute([$id]);
            $existing = $checkStmt->fetch();
            
            if (!$existing) {
                sendJsonResponse('Boletín no encontrado', false);
            }
            
            $updates = [];
            $params = [];
            
            if ($titulo !== null && !empty($titulo)) {
                $updates[] = "titulo = ?";
                $params[] = $titulo;
            }
            
            if ($contenido !== null && !empty($contenido)) {
                $updates[] = "contenido = ?";
                $params[] = $contenido;
            }
            
            if ($estado !== null && in_array($estado, ['borrador', 'publicado', 'archivado'])) {
                $updates[] = "estado = ?";
                $params[] = $estado;
                
                if ($estado === 'publicado' && !$existing['fecha_publicacion']) {
                    $updates[] = "fecha_publicacion = ?";
                    $params[] = date('Y-m-d H:i:s');
                }
            }
            
            if (empty($updates)) {
                sendJsonResponse('No hay campos para actualizar', false);
            }
            
            $params[] = $id;
            $sql = "UPDATE boletines SET " . implode(', ', $updates) . " WHERE id = ?";
            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute($params);
            
            $stmt = $pdo->prepare("SELECT * FROM boletines WHERE id = ?");
            $stmt->execute([$id]);
            $boletin = $stmt->fetch();
            
            sendJsonResponse([
                'success' => true,
                'data' => $boletin,
                'message' => 'Boletín actualizado exitosamente'
            ]);
            break;
            
        case 'DELETE':
            $inputRaw = file_get_contents('php://input');
            $input = json_decode($inputRaw, true);
            
            if (!isset($input['id'])) {
                sendJsonResponse('ID del boletín es requerido', false);
            }
            
            $id = intval($input['id']);
            
            $stmt = $pdo->prepare("SELECT archivo_adjunto FROM boletines WHERE id = ?");
            $stmt->execute([$id]);
            $boletin = $stmt->fetch();
            
            if (!$boletin) {
                sendJsonResponse('Boletín no encontrado', false);
            }
            
            if ($boletin['archivo_adjunto']) {
                $filePath = $uploadDir . $boletin['archivo_adjunto'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            
            $deleteStmt = $pdo->prepare("DELETE FROM boletines WHERE id = ?");
            $deleteStmt->execute([$id]);
            
            sendJsonResponse([
                'success' => true,
                'message' => 'Boletín eliminado exitosamente',
                'deletedId' => $id
            ]);
            break;
            
        default:
            sendJsonResponse('Método no permitido', false);
    }
    
} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    sendJsonResponse('Error de base de datos: ' . $e->getMessage(), false);
    
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    sendJsonResponse('Error del servidor: ' . $e->getMessage(), false);
}
?>
