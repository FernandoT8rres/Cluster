<?php
/**
 * API de boletines - Versión simplificada funcional
 * Este archivo es un wrapper de boletines.php para compatibilidad
 */

// Headers CORS
header('Access-Control-Allow-Origin: https://intranet.clautmetropolitano.mx');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ============================================
// RATE LIMITING - Protección contra abuso de API
// ============================================
try {
    require_once dirname(__DIR__) . '/middleware/rate-limiter.php';
    
    $rateLimiter = new RateLimiter();
    $clientIP = getRateLimitIdentifier();
    
    // Verificar límite (100 requests / minuto para APIs públicas)
    $rateLimiter->protect(
        $clientIP,
        RateLimitConfig::API_PUBLIC['max'],
        RateLimitConfig::API_PUBLIC['window'],
        RateLimitConfig::API_PUBLIC['action']
    );
    
} catch (Exception $e) {
    // Si hay error en rate limiter, continuar sin bloquear
    error_log("Error en rate limiter (boletines): " . $e->getMessage());
}
// ============================================

// Usar configuración de base de datos remota únicamente
require_once '../config/database.php';

// Función para respuesta JSON
function sendJsonResponse($data, $success = true) {
    $response = [
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($success) {
        $response = array_merge($response, $data);
    } else {
        $response['message'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Función para sanitizar input
function clean($input) {
    if (is_array($input)) {
        return array_map('clean', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

try {
    // Conectar a la base de datos usando singleton
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
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
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Obtener boletines
            $id = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if ($id) {
                // Obtener un boletín específico
                $stmt = $pdo->prepare("SELECT * FROM boletines WHERE id = ?");
                $stmt->execute([$id]);
                $boletin = $stmt->fetch();
                
                if (!$boletin) {
                    sendJsonResponse('Boletín no encontrado', false);
                }
                
                // Incrementar visualizaciones
                $updateStmt = $pdo->prepare("UPDATE boletines SET visualizaciones = COALESCE(visualizaciones, 0) + 1 WHERE id = ?");
                $updateStmt->execute([$id]);
                $boletin['visualizaciones'] = intval($boletin['visualizaciones']) + 1;
                
                sendJsonResponse(['data' => $boletin]);
                
            } else {
                // Obtener todos los boletines
                $estado = isset($_GET['estado']) ? clean($_GET['estado']) : null;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                $orderBy = isset($_GET['orderBy']) ? clean($_GET['orderBy']) : 'fecha_creacion';
                $order = isset($_GET['order']) ? strtoupper(clean($_GET['order'])) : 'DESC';
                
                // Validar orderBy
                $validOrderBy = ['fecha_creacion', 'fecha_publicacion', 'titulo', 'visualizaciones', 'id'];
                if (!in_array($orderBy, $validOrderBy)) {
                    $orderBy = 'fecha_creacion';
                }
                
                // Validar order
                if (!in_array($order, ['ASC', 'DESC'])) {
                    $order = 'DESC';
                }
                
                $sql = "SELECT * FROM boletines";
                $params = [];
                
                if ($estado && $estado !== 'todos') {
                    $sql .= " WHERE estado = ?";
                    $params[] = $estado;
                }
                
                $sql .= " ORDER BY $orderBy $order";
                
                if ($limit > 0) {
                    $sql .= " LIMIT " . $limit;
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $boletines = $stmt->fetchAll();
                
                // Asegurar que los IDs sean enteros
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
            // Crear o Actualizar boletín (se unifica en POST para soportar FormData + $_FILES)
            $id        = intval($_POST['id'] ?? 0); // > 0 = edición, 0 = creación
            $titulo    = clean($_POST['titulo'] ?? '');
            $contenido = clean($_POST['contenido'] ?? '');
            $estado    = clean($_POST['estado'] ?? 'borrador');
            $fecha_publicacion = $_POST['fecha_publicacion'] ?? null;

            if (empty($titulo) || empty($contenido)) {
                sendJsonResponse('Título y contenido son requeridos', false);
            }

            // Procesar archivo adjunto si viene en el request
            $archivo_adjunto = null;

            // Modo edición: conservar el archivo existente si no se sube uno nuevo
            if ($id > 0) {
                $existStmt = $pdo->prepare("SELECT archivo_adjunto FROM boletines WHERE id = ?");
                $existStmt->execute([$id]);
                $existingRow = $existStmt->fetch();
                $archivo_adjunto = $existingRow['archivo_adjunto'] ?? null;
            }

            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(__DIR__) . '/uploads/boletines/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $originalName = basename($_FILES['archivo']['name']);
                $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExts  = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','jpg','jpeg','png','gif','mp4','mp3','csv'];

                if (!in_array($ext, $allowedExts)) {
                    sendJsonResponse('Tipo de archivo no permitido', false);
                }

                $newFilename  = 'boletin_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destination  = $uploadDir . $newFilename;

                if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destination)) {
                    $archivo_adjunto = $newFilename;
                } else {
                    sendJsonResponse('Error al guardar el archivo adjunto', false);
                }
            }

            if ($estado === 'publicado' && empty($fecha_publicacion)) {
                $fecha_publicacion = date('Y-m-d H:i:s');
            }

            if ($id > 0) {
                // === ACTUALIZAR ===
                if (!$existingRow) {
                    sendJsonResponse('Boletín no encontrado', false);
                }

                $sql  = "UPDATE boletines SET titulo = ?, contenido = ?, estado = ?, fecha_publicacion = ?, archivo_adjunto = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);

                if ($stmt->execute([$titulo, $contenido, $estado, $fecha_publicacion, $archivo_adjunto, $id])) {
                    $getStmt = $pdo->prepare("SELECT * FROM boletines WHERE id = ?");
                    $getStmt->execute([$id]);
                    $boletinActualizado = $getStmt->fetch();

                    sendJsonResponse([
                        'message' => 'Boletín actualizado exitosamente',
                        'id'      => $id,
                        'data'    => $boletinActualizado
                    ]);
                } else {
                    sendJsonResponse('Error al actualizar el boletín', false);
                }
            } else {
                // === CREAR ===
                $sql  = "INSERT INTO boletines (titulo, contenido, estado, fecha_publicacion, archivo_adjunto) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);

                if ($stmt->execute([$titulo, $contenido, $estado, $fecha_publicacion, $archivo_adjunto])) {
                    $newId   = $pdo->lastInsertId();
                    $getStmt = $pdo->prepare("SELECT * FROM boletines WHERE id = ?");
                    $getStmt->execute([$newId]);
                    $nuevoBoletin = $getStmt->fetch();

                    sendJsonResponse([
                        'message' => 'Boletín creado exitosamente',
                        'id'      => $newId,
                        'data'    => $nuevoBoletin
                    ]);
                } else {
                    sendJsonResponse('Error al crear el boletín', false);
                }
            }
            break;

        case 'PUT':
            // PUT ya no se usa desde el frontend; redirigir a POST para compatibilidad
            sendJsonResponse('Use POST con el campo id para actualizar boletines', false);
            break;
            
        case 'DELETE':
            // Eliminar boletín — acepta ID via GET o via JSON body
            $id = 0;
            if (isset($_GET['id'])) {
                $id = intval($_GET['id']);
            } else {
                $bodyRaw = file_get_contents('php://input');
                $bodyData = json_decode($bodyRaw, true);
                if (isset($bodyData['id'])) {
                    $id = intval($bodyData['id']);
                }
            }

            if ($id <= 0) {
                sendJsonResponse('ID inválido', false);
            }

            // Obtener nombre de archivo antes de eliminar
            $fileStmt = $pdo->prepare("SELECT archivo_adjunto FROM boletines WHERE id = ?");
            $fileStmt->execute([$id]);
            $fileRow = $fileStmt->fetch();

            $stmt = $pdo->prepare("DELETE FROM boletines WHERE id = ?");

            if ($stmt->execute([$id])) {
                // Eliminar archivo físico si existe
                if (!empty($fileRow['archivo_adjunto'])) {
                    $filePath = dirname(__DIR__) . '/uploads/boletines/' . $fileRow['archivo_adjunto'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                sendJsonResponse([
                    'message' => 'Boletín eliminado exitosamente',
                    'id'      => $id
                ]);
            } else {
                sendJsonResponse('Error al eliminar el boletín', false);
            }
            break;
            
        default:
            sendJsonResponse('Método no permitido: ' . $method, false);
    }
    
} catch (PDOException $e) {
    error_log("Error en boletines API (PDO): " . $e->getMessage());
    sendJsonResponse('Error de base de datos: ' . $e->getMessage(), false);
    
} catch (Exception $e) {
    error_log("Error general en boletines API: " . $e->getMessage());
    sendJsonResponse('Error del servidor: ' . $e->getMessage(), false);
}
?>
