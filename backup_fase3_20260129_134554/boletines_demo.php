<?php
/**
 * API de boletines para demo - Sin autenticación requerida
 */

// Definir acceso permitido
define('CLAUT_ACCESS', true);

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/config.php';

// Wrapper para jsonResponse compatible con el formato esperado
function apiResponse($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Crear directorio de uploads si no existe
$uploadDir = '../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'PUT':
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            jsonError('Método no permitido', 405);
    }
    
} catch (Exception $e) {
    error_log("Error en boletines demo API: " . $e->getMessage());
    jsonError('Error interno del servidor: ' . $e->getMessage(), 500);
}

function handleGet($db) {
    $id = $_GET['id'] ?? null;
    $limit = $_GET['limit'] ?? null;
    $estado = $_GET['estado'] ?? null;
    $orderBy = $_GET['orderBy'] ?? 'fecha_creacion';
    $order = $_GET['order'] ?? 'DESC';
    
    try {
        if ($id) {
            // Obtener boletín específico
            $boletin = $db->selectOne(
                "SELECT * FROM boletines WHERE id = ?",
                [$id]
            );
            
            if (!$boletin) {
                jsonError('Boletín no encontrado', 404);
            }
            
            // Incrementar visualizaciones
            $db->update("UPDATE boletines SET visualizaciones = COALESCE(visualizaciones, 0) + 1 WHERE id = ?", [$id]);
            $boletin['visualizaciones'] = ($boletin['visualizaciones'] ?? 0) + 1;
            
            apiResponse(['success' => true, 'data' => $boletin]);
            
        } else {
            // Construir consulta base
            $whereConditions = [];
            $params = [];
            
            // Filtro por estado si se especifica
            if ($estado && $estado !== 'todos') {
                $whereConditions[] = "estado = ?";
                $params[] = $estado;
            }
            
            // Construir WHERE clause
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            // Validar orderBy para prevenir inyección SQL
            $validOrderBy = ['fecha_creacion', 'titulo', 'visualizaciones', 'id', 'fecha_publicacion'];
            if (!in_array($orderBy, $validOrderBy)) {
                $orderBy = 'fecha_creacion';
            }
            
            // Validar order
            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
            
            // Construir consulta completa
            $sql = "SELECT * FROM boletines $whereClause ORDER BY $orderBy $order";
            
            if ($limit && is_numeric($limit) && $limit > 0) {
                $sql .= " LIMIT " . (int)$limit;
            }
            
            $boletines = $db->select($sql, $params);
            
            // Formatear datos para el frontend
            foreach ($boletines as &$boletin) {
                // Asegurar que los campos numéricos sean integers
                $boletin['id'] = (int)$boletin['id'];
                $boletin['visualizaciones'] = (int)($boletin['visualizaciones'] ?? 0);
                $boletin['autor_id'] = $boletin['autor_id'] ? (int)$boletin['autor_id'] : null;
            }
            
            apiResponse([
                'success' => true,
                'data' => $boletines,
                'total' => count($boletines),
                'filters' => [
                    'estado' => $estado,
                    'limit' => $limit,
                    'orderBy' => $orderBy,
                    'order' => $order
                ]
            ]);
        }
    } catch (Exception $e) {
        error_log("Error en GET: " . $e->getMessage());
        jsonError('Error al obtener boletines: ' . $e->getMessage(), 500);
    }
}

function handlePost($db) {
    try {
        // Obtener datos del formulario
        $titulo = $_POST['titulo'] ?? '';
        $contenido = $_POST['contenido'] ?? '';
        $estado = $_POST['estado'] ?? 'borrador';
        
        // Validar campos requeridos
        if (empty($titulo) || empty($contenido)) {
            jsonError('Título y contenido son requeridos', 400);
        }
        
        // Sanitizar datos
        $titulo = sanitizeInput($titulo);
        $contenido = sanitizeInput($contenido);
        $estado = sanitizeInput($estado);
        
        // Manejar archivo adjunto si existe
        $archivo_adjunto = null;
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $archivo_adjunto = handleFileUpload($_FILES['archivo']);
        }
        
        // Preparar datos para insertar
        $fechaCreacion = date('Y-m-d H:i:s');
        $fechaPublicacion = ($estado === 'publicado') ? $fechaCreacion : null;
        
        // Insertar boletín
        $sql = "INSERT INTO boletines (titulo, contenido, estado, fecha_creacion, fecha_publicacion, archivo_adjunto, visualizaciones) 
                VALUES (?, ?, ?, ?, ?, ?, 0)";
        
        $params = [$titulo, $contenido, $estado, $fechaCreacion, $fechaPublicacion, $archivo_adjunto];
        
        $boletinId = $db->insert($sql, $params);
        
        // Obtener el boletín creado
        $boletin = $db->selectOne("SELECT * FROM boletines WHERE id = ?", [$boletinId]);
        
        apiResponse([
            'success' => true,
            'data' => $boletin,
            'id' => $boletinId,
            'message' => 'Boletín creado correctamente'
        ]);
        
    } catch (Exception $e) {
        error_log("Error en POST: " . $e->getMessage());
        jsonError('Error al crear boletín: ' . $e->getMessage(), 500);
    }
}

function handlePut($db) {
    try {
        // Obtener el ID del boletín a actualizar
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            jsonError('ID del boletín es requerido', 400);
        }
        
        $id = (int)$input['id'];
        $titulo = $input['titulo'] ?? null;
        $contenido = $input['contenido'] ?? null;
        $estado = $input['estado'] ?? null;
        
        // Verificar que el boletín existe
        $boletin = $db->selectOne("SELECT * FROM boletines WHERE id = ?", [$id]);
        if (!$boletin) {
            jsonError('Boletín no encontrado', 404);
        }
        
        // Construir la consulta de actualización dinámicamente
        $updateFields = [];
        $params = [];
        
        if ($titulo !== null) {
            $updateFields[] = "titulo = ?";
            $params[] = sanitizeInput($titulo);
        }
        
        if ($contenido !== null) {
            $updateFields[] = "contenido = ?";
            $params[] = sanitizeInput($contenido);
        }
        
        if ($estado !== null) {
            $updateFields[] = "estado = ?";
            $params[] = sanitizeInput($estado);
            
            // Si se cambia a publicado, actualizar fecha de publicación
            if ($estado === 'publicado' && $boletin['estado'] !== 'publicado') {
                $updateFields[] = "fecha_publicacion = ?";
                $params[] = date('Y-m-d H:i:s');
            }
        }
        
        if (empty($updateFields)) {
            jsonError('No hay campos para actualizar', 400);
        }
        
        // Agregar el ID al final de los parámetros
        $params[] = $id;
        
        // Ejecutar actualización
        $sql = "UPDATE boletines SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $db->update($sql, $params);
        
        // Obtener el boletín actualizado
        $boletinActualizado = $db->selectOne("SELECT * FROM boletines WHERE id = ?", [$id]);
        
        apiResponse([
            'success' => true,
            'data' => $boletinActualizado,
            'message' => 'Boletín actualizado correctamente'
        ]);
        
    } catch (Exception $e) {
        error_log("Error en PUT: " . $e->getMessage());
        jsonError('Error al actualizar boletín: ' . $e->getMessage(), 500);
    }
}

function handleDelete($db) {
    try {
        // Obtener el ID del boletín a eliminar
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            jsonError('ID del boletín es requerido', 400);
        }
        
        $id = (int)$input['id'];
        
        // Verificar que el boletín existe
        $boletin = $db->selectOne("SELECT * FROM boletines WHERE id = ?", [$id]);
        if (!$boletin) {
            jsonError('Boletín no encontrado', 404);
        }
        
        // Si hay archivo adjunto, intentar eliminarlo
        if ($boletin['archivo_adjunto']) {
            $filePath = '../uploads/' . $boletin['archivo_adjunto'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Eliminar el boletín de la base de datos
        $db->delete("DELETE FROM boletines WHERE id = ?", [$id]);
        
        apiResponse([
            'success' => true,
            'message' => 'Boletín eliminado correctamente',
            'deletedId' => $id
        ]);
        
    } catch (Exception $e) {
        error_log("Error en DELETE: " . $e->getMessage());
        jsonError('Error al eliminar boletín: ' . $e->getMessage(), 500);
    }
}

function handleFileUpload($file) {
    $uploadDir = '../uploads/';
    
    // Validar tamaño del archivo (5MB máximo)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB permitido.');
    }
    
    // Obtener información del archivo
    $originalName = $file['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Extensiones permitidas
    $allowedExtensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'rtf',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'mp4', 'webm', 'ogg', 'avi',
        'mp3', 'wav', 'ogg'
    ];
    
    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception('Formato de archivo no permitido. Formatos permitidos: ' . implode(', ', $allowedExtensions));
    }
    
    // Generar nombre único para el archivo
    $fileName = 'boletin_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // Mover archivo al directorio de uploads
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error al subir el archivo');
    }
    
    return $fileName;
}

// Función para verificar si la tabla existe y crearla si no
function ensureTableExists($db) {
    try {
        // Intentar hacer una consulta simple para verificar si la tabla existe
        $db->selectOne("SELECT 1 FROM boletines LIMIT 1");
    } catch (Exception $e) {
        // Si falla, crear la tabla
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
        
        $db->query($createTableSQL);
    }
}

// Verificar que la tabla existe al cargar el API
try {
    ensureTableExists($db);
} catch (Exception $e) {
    error_log("Error creando tabla boletines: " . $e->getMessage());
}

?>
