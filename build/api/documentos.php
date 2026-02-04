<?php
/**
 * API de documentos - Gestión completa de documentos con carga de archivos
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

// Función para obtener el tamaño del archivo en formato legible
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Función para procesar carga de archivos
function processFileUpload($file, $uploadDir = '../uploads/documentos/') {
    // Crear directorio si no existe
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validar archivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error en la carga del archivo: ' . $file['error']);
    }

    // Validar tamaño (máximo 50MB)
    $maxSize = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande. Máximo permitido: 50MB');
    }

    // Tipos de archivo permitidos
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'image/jpeg',
        'image/png',
        'image/gif'
    ];

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido: ' . $file['type']);
    }

    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;

    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error al guardar el archivo');
    }

    return [
        'archivo_nombre' => $file['name'],
        'archivo_ruta' => $filePath,
        'tamaño_archivo' => $file['size'],
        'tipo_archivo' => strtoupper($extension)
    ];
}

try {
    // Conectar a la base de datos usando singleton
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Crear tabla si no existe
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS `documentos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `titulo` varchar(255) NOT NULL,
        `descripcion` text,
        `archivo_nombre` varchar(255) NOT NULL,
        `archivo_ruta` varchar(500) NOT NULL,
        `tipo_archivo` varchar(10) NOT NULL,
        `tamaño_archivo` bigint(20) DEFAULT 0,
        `categoria` varchar(100) DEFAULT 'general',
        `subido_por` int(11) DEFAULT NULL,
        `fecha_subida` datetime DEFAULT CURRENT_TIMESTAMP,
        `visibilidad` enum('publico','privado','restringido') DEFAULT 'publico',
        `descargas` int(11) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_categoria` (`categoria`),
        KEY `idx_visibilidad` (`visibilidad`),
        KEY `idx_fecha_subida` (`fecha_subida`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createTableSQL);

    // Manejar servir archivos directamente
    if (isset($_GET['action']) && ($_GET['action'] === 'file' || $_GET['action'] === 'download')) {
        $filepath = isset($_GET['path']) ? $_GET['path'] : (isset($_GET['file']) ? $_GET['file'] : '');
        if (empty($filepath)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Path no especificado']);
            exit;
        }

        // Buscar el documento en la base de datos
        $stmt = $pdo->prepare("SELECT * FROM documentos WHERE archivo_nombre = ? OR archivo_ruta LIKE ?");
        $stmt->execute([$filepath, "%$filepath%"]);
        $documento = $stmt->fetch();

        if (!$documento) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Documento no encontrado en BD']);
            exit;
        }

        // Construir ruta del archivo
        $fullPath = $documento['archivo_ruta'];
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Archivo físico no encontrado']);
            exit;
        }

        // Determinar tipo MIME
        $mimeType = mime_content_type($fullPath);
        if ($mimeType === false) {
            $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];
            $mimeType = isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
        }

        // Incrementar contador de descargas
        $updateStmt = $pdo->prepare("UPDATE documentos SET descargas = COALESCE(descargas, 0) + 1 WHERE id = ?");
        $updateStmt->execute([$documento['id']]);

        // Servir el archivo
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($fullPath));

        // Para action=download forzar descarga, para action=file mostrar inline
        if ($_GET['action'] === 'download') {
            header('Content-Disposition: attachment; filename="' . $documento['archivo_nombre'] . '"');
        } else {
            header('Content-Disposition: inline; filename="' . $documento['archivo_nombre'] . '"');
        }

        header('Cache-Control: public, max-age=3600');
        header('X-Content-Type-Options: nosniff');

        // Limpiar buffer de salida antes de enviar archivo
        if (ob_get_level()) {
            ob_end_clean();
        }

        readfile($fullPath);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Obtener documentos
            $id = isset($_GET['id']) ? intval($_GET['id']) : null;

            if ($id) {
                // Obtener un documento específico
                $stmt = $pdo->prepare("SELECT * FROM documentos WHERE id = ?");
                $stmt->execute([$id]);
                $documento = $stmt->fetch();

                if (!$documento) {
                    sendJsonResponse('Documento no encontrado', false);
                }

                // Incrementar descargas si se solicita descarga
                if (isset($_GET['download']) && $_GET['download'] === '1') {
                    $updateStmt = $pdo->prepare("UPDATE documentos SET descargas = COALESCE(descargas, 0) + 1 WHERE id = ?");
                    $updateStmt->execute([$id]);
                    $documento['descargas'] = intval($documento['descargas']) + 1;
                }

                // Formatear tamaño de archivo
                $documento['tamaño_formateado'] = formatFileSize($documento['tamaño_archivo']);

                sendJsonResponse(['data' => $documento]);

            } else {
                // Obtener todos los documentos
                $categoria = isset($_GET['categoria']) ? clean($_GET['categoria']) : null;
                $visibilidad = isset($_GET['visibilidad']) ? clean($_GET['visibilidad']) : null;
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                $orderBy = isset($_GET['orderBy']) ? clean($_GET['orderBy']) : 'fecha_subida';
                $order = isset($_GET['order']) ? strtoupper(clean($_GET['order'])) : 'DESC';
                $search = isset($_GET['search']) ? clean($_GET['search']) : null;

                // Validar orderBy
                $validOrderBy = ['fecha_subida', 'titulo', 'categoria', 'descargas', 'tamaño_archivo', 'id'];
                if (!in_array($orderBy, $validOrderBy)) {
                    $orderBy = 'fecha_subida';
                }

                // Validar order
                if (!in_array($order, ['ASC', 'DESC'])) {
                    $order = 'DESC';
                }

                $sql = "SELECT * FROM documentos WHERE 1=1";
                $params = [];

                // Filtro por categoría
                if ($categoria && $categoria !== 'all') {
                    $sql .= " AND categoria = ?";
                    $params[] = $categoria;
                }

                // Filtro por visibilidad
                if ($visibilidad && $visibilidad !== 'all') {
                    $sql .= " AND visibilidad = ?";
                    $params[] = $visibilidad;
                } elseif (!$visibilidad || $visibilidad !== 'all') {
                    // Por defecto, solo mostrar documentos públicos (solo si no se especifica 'all')
                    $sql .= " AND visibilidad = 'publico'";
                }
                // Si visibilidad='all', no agregar filtro de visibilidad (mostrar todos)

                // Búsqueda
                if ($search) {
                    $sql .= " AND (titulo LIKE ? OR descripcion LIKE ?)";
                    $searchTerm = '%' . $search . '%';
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }

                $sql .= " ORDER BY $orderBy $order";

                if ($limit > 0) {
                    $sql .= " LIMIT " . $limit;
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $documentos = $stmt->fetchAll();

                // Procesar datos
                foreach ($documentos as &$documento) {
                    $documento['id'] = intval($documento['id']);
                    $documento['descargas'] = intval($documento['descargas'] ?? 0);
                    $documento['tamaño_archivo'] = intval($documento['tamaño_archivo'] ?? 0);
                    $documento['tamaño_formateado'] = formatFileSize($documento['tamaño_archivo']);

                    // Limpiar ruta para frontend (solo nombre de archivo relativo)
                    $documento['archivo_url'] = 'uploads/documentos/' . basename($documento['archivo_ruta']);
                }

                // Obtener estadísticas
                $statsStmt = $pdo->query("
                    SELECT
                        COUNT(*) as total,
                        SUM(descargas) as total_descargas,
                        COUNT(DISTINCT categoria) as categorias
                    FROM documentos
                    WHERE visibilidad = 'publico'
                ");
                $stats = $statsStmt->fetch();

                sendJsonResponse([
                    'data' => $documentos,
                    'total' => count($documentos),
                    'stats' => $stats
                ]);
            }
            break;

        case 'POST':
            // Verificar si es una actualización (PUT disfrazado)
            if (isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
                // Es una actualización, redirigir al código PUT
                goto handle_put;
            }

            // ============================================
            // VALIDACIÓN CON API VALIDATOR
            // ============================================
            require_once dirname(__DIR__) . '/middleware/api-validator.php';
            
            $validation = ApiValidator::validateAndSanitize($_POST, [
                'titulo' => 'required|string|min:3|max:255',
                'descripcion' => 'string|max:1000',
                'categoria' => 'string|max:100',
                'visibilidad' => 'string|in:publico,privado,restringido',
                'subido_por' => 'int|min:1'
            ]);
            
            if (!$validation['valid']) {
                ApiValidator::errorResponse($validation['errors']);
            }
            
            $titulo = $validation['data']['titulo'];
            $descripcion = $validation['data']['descripcion'] ?? '';
            $categoria = $validation['data']['categoria'] ?? 'general';
            $visibilidad = $validation['data']['visibilidad'] ?? 'publico';
            $subido_por = $validation['data']['subido_por'] ?? null;
            // ============================================

            // Procesar archivo subido
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                sendJsonResponse('Archivo es requerido', false);
            }

            try {
                $fileData = processFileUpload($_FILES['archivo']);

                $sql = "INSERT INTO documentos (titulo, descripcion, archivo_nombre, archivo_ruta, tipo_archivo, tamaño_archivo, categoria, visibilidad, subido_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);

                if ($stmt->execute([
                    $titulo,
                    $descripcion,
                    $fileData['archivo_nombre'],
                    $fileData['archivo_ruta'],
                    $fileData['tipo_archivo'],
                    $fileData['tamaño_archivo'],
                    $categoria,
                    $visibilidad,
                    $subido_por
                ])) {
                    $id = $pdo->lastInsertId();

                    // Obtener el documento recién creado
                    $getStmt = $pdo->prepare("SELECT * FROM documentos WHERE id = ?");
                    $getStmt->execute([$id]);
                    $nuevoDocumento = $getStmt->fetch();
                    $nuevoDocumento['tamaño_formateado'] = formatFileSize($nuevoDocumento['tamaño_archivo']);

                    sendJsonResponse([
                        'message' => 'Documento subido exitosamente',
                        'data' => $nuevoDocumento
                    ]);
                } else {
                    sendJsonResponse('Error al guardar el documento en la base de datos', false);
                }

            } catch (Exception $e) {
                sendJsonResponse('Error al procesar el archivo: ' . $e->getMessage(), false);
            }
            break;

        case 'PUT':
        handle_put: // Etiqueta para el goto desde POST
            // Actualizar documento existente - usar $_POST (ya que cambié a POST en frontend)
            $id = intval($_POST['id'] ?? 0);
            $titulo = clean($_POST['titulo'] ?? '');
            $descripcion = clean($_POST['descripcion'] ?? '');
            $categoria = clean($_POST['categoria'] ?? 'general');
            $visibilidad = clean($_POST['visibilidad'] ?? 'publico');

            if ($id <= 0 || empty($titulo)) {
                sendJsonResponse('ID y título son requeridos', false);
            }

            // Verificar que el documento existe
            $checkStmt = $pdo->prepare("SELECT archivo_ruta FROM documentos WHERE id = ?");
            $checkStmt->execute([$id]);
            $documentoExistente = $checkStmt->fetch();
            if (!$documentoExistente) {
                sendJsonResponse('Documento no encontrado', false);
            }

            // Variables para manejar archivo
            $archivo_nombre = null;
            $archivo_ruta = null;
            $tipo_archivo = null;
            $tamaño_archivo = 0;

            // Si se envía un nuevo archivo, procesarlo
            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                try {
                    $fileData = processFileUpload($_FILES['archivo']);
                    $archivo_nombre = $fileData['archivo_nombre'];
                    $archivo_ruta = $fileData['archivo_ruta'];
                    $tipo_archivo = $fileData['tipo_archivo'];
                    $tamaño_archivo = $fileData['tamaño_archivo'];

                    // Eliminar archivo anterior si existe
                    if ($documentoExistente['archivo_ruta'] && file_exists($documentoExistente['archivo_ruta'])) {
                        unlink($documentoExistente['archivo_ruta']);
                    }
                } catch (Exception $e) {
                    sendJsonResponse('Error al procesar el archivo: ' . $e->getMessage(), false);
                }
            }

            // Construir SQL dinámicamente dependiendo de si hay nuevo archivo
            if ($archivo_nombre) {
                $sql = "UPDATE documentos SET titulo = ?, descripcion = ?, categoria = ?, visibilidad = ?, archivo_nombre = ?, archivo_ruta = ?, tipo_archivo = ?, tamaño_archivo = ? WHERE id = ?";
                $params = [$titulo, $descripcion, $categoria, $visibilidad, $archivo_nombre, $archivo_ruta, $tipo_archivo, $tamaño_archivo, $id];
            } else {
                $sql = "UPDATE documentos SET titulo = ?, descripcion = ?, categoria = ?, visibilidad = ? WHERE id = ?";
                $params = [$titulo, $descripcion, $categoria, $visibilidad, $id];
            }

            $stmt = $pdo->prepare($sql);

            if ($stmt->execute($params)) {
                // Obtener documento actualizado
                $getStmt = $pdo->prepare("SELECT * FROM documentos WHERE id = ?");
                $getStmt->execute([$id]);
                $documentoActualizado = $getStmt->fetch();
                $documentoActualizado['tamaño_formateado'] = formatFileSize($documentoActualizado['tamaño_archivo']);

                sendJsonResponse([
                    'message' => 'Documento actualizado exitosamente',
                    'data' => $documentoActualizado
                ]);
            } else {
                sendJsonResponse('Error al actualizar el documento', false);
            }
            break;

        case 'DELETE':
            // Eliminar documento
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            if ($id <= 0) {
                sendJsonResponse('ID es requerido', false);
            }

            // Obtener datos del documento antes de eliminar
            $getStmt = $pdo->prepare("SELECT archivo_ruta FROM documentos WHERE id = ?");
            $getStmt->execute([$id]);
            $documento = $getStmt->fetch();

            if (!$documento) {
                sendJsonResponse('Documento no encontrado', false);
            }

            // Eliminar de la base de datos
            $deleteStmt = $pdo->prepare("DELETE FROM documentos WHERE id = ?");

            if ($deleteStmt->execute([$id])) {
                // Intentar eliminar archivo físico
                if (file_exists($documento['archivo_ruta'])) {
                    unlink($documento['archivo_ruta']);
                }

                sendJsonResponse(['message' => 'Documento eliminado exitosamente']);
            } else {
                sendJsonResponse('Error al eliminar el documento', false);
            }
            break;

        default:
            sendJsonResponse('Método no permitido: ' . $method, false);
    }

} catch (PDOException $e) {
    error_log("Error en documentos API (PDO): " . $e->getMessage());
    sendJsonResponse('Error de base de datos: ' . $e->getMessage(), false);

} catch (Exception $e) {
    error_log("Error general en documentos API: " . $e->getMessage());
    sendJsonResponse('Error del servidor: ' . $e->getMessage(), false);
}
?>