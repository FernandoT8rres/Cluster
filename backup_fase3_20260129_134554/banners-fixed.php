<?php
/**
 * API de Banners - Versión corregida para visualización en login
 * Soluciona problemas de URLs y carga de imágenes
 */

// Headers necesarios
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Desactivar errores visibles
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Manejar preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Función de respuesta limpia
function jsonResponse($success, $data = null, $message = '', $debug = null) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_clean();
    }
    
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Solo incluir debug si se solicita
    if ($debug && (isset($_GET['debug']) && $_GET['debug'] === '1')) {
        $response['debug'] = $debug;
    }
    
    http_response_code($success ? 200 : 400);
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

// Función para construir URL absoluta de imagen
function buildImageUrl($imagePath) {
    if (empty($imagePath)) {
        return null;
    }
    
    // Si ya es una URL completa, devolverla tal como está
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return $imagePath;
    }
    
    // Construir URL absoluta
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Obtener la base path del proyecto
    $scriptPath = $_SERVER['SCRIPT_NAME']; // /build/api/banners.php
    $basePath = dirname(dirname($scriptPath)); // /build
    
    // Limpiar y normalizar la ruta de la imagen
    $cleanPath = ltrim($imagePath, '/');
    
    // Si la imagen está en uploads/, agregarla a la base
    if (strpos($cleanPath, 'uploads/') === 0) {
        $fullUrl = $protocol . '://' . $host . $basePath . '/' . $cleanPath;
    } else {
        // Asumir que está en uploads/banners/
        $fullUrl = $protocol . '://' . $host . $basePath . '/uploads/banners/' . $cleanPath;
    }
    
    return $fullUrl;
}

// Función para verificar si una imagen existe
function imageExists($imagePath) {
    if (empty($imagePath)) {
        return false;
    }
    
    // Si es una URL externa, asumir que existe
    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return true;
    }
    
    // Verificar archivo local
    $localPath = '../' . ltrim($imagePath, '/');
    return file_exists($localPath);
}

try {
    // Incluir configuración de base de datos
    $configPaths = [
        '../config/database.php',
        '../assets/conexion/config.php',
        '../../assets/conexion/config.php'
    ];
    
    $dbConfigLoaded = false;
    foreach ($configPaths as $configPath) {
        if (file_exists($configPath)) {
            require_once $configPath;
            $dbConfigLoaded = true;
            break;
        }
    }
    
    if (!$dbConfigLoaded) {
        throw new Exception('Archivo de configuración de BD no encontrado');
    }
    
    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception('No se pudo conectar a la base de datos');
    }
    
} catch (Exception $e) {
    jsonResponse(false, null, 'Error de conexión: ' . $e->getMessage());
}

// Obtener parámetros de la solicitud
$action = $_GET['action'] ?? 'active';
$method = $_SERVER['REQUEST_METHOD'];

try {
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'active':
                case 'list':
                    // Obtener banners activos para el carrusel de login
                    $query = "
                        SELECT 
                            id, 
                            titulo, 
                            descripcion, 
                            COALESCE(imagen_url, imagen) as imagen_original,
                            enlace, 
                            orden, 
                            posicion,
                            activo,
                            fecha_inicio,
                            fecha_fin,
                            created_at
                        FROM banner_carrusel 
                        WHERE activo = 1 
                        AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())
                        AND (fecha_fin IS NULL OR fecha_fin >= NOW())
                        ORDER BY orden ASC, posicion ASC, id ASC
                        LIMIT 10
                    ";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Procesar cada banner
                    $processedBanners = [];
                    
                    foreach ($banners as $banner) {
                        $imageUrl = buildImageUrl($banner['imagen_original']);
                        
                        // Solo incluir banners con imágenes válidas
                        if ($imageUrl && imageExists($banner['imagen_original'])) {
                            $processedBanners[] = [
                                'id' => (int)$banner['id'],
                                'titulo' => $banner['titulo'] ?: 'Banner ' . $banner['id'],
                                'descripcion' => $banner['descripcion'] ?: '',
                                'imagen_url' => $imageUrl,
                                'enlace' => $banner['enlace'] ?: null,
                                'orden' => (int)$banner['orden'],
                                'posicion' => (int)$banner['posicion']
                            ];
                        }
                    }
                    
                    // Log para debug
                    error_log("Banners encontrados: " . count($banners));
                    error_log("Banners procesados: " . count($processedBanners));
                    
                    if (empty($processedBanners)) {
                        jsonResponse(true, [], 'No hay banners activos disponibles');
                    } else {
                        jsonResponse(true, $processedBanners, 'Banners cargados exitosamente');
                    }
                    break;
                    
                case 'all':
                    // Obtener todos los banners para administración
                    $query = "
                        SELECT 
                            id, 
                            titulo, 
                            descripcion, 
                            COALESCE(imagen_url, imagen) as imagen_original,
                            enlace, 
                            orden, 
                            posicion,
                            activo,
                            fecha_inicio,
                            fecha_fin,
                            created_at
                        FROM banner_carrusel 
                        ORDER BY orden ASC, posicion ASC, created_at DESC
                    ";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Procesar todos los banners
                    $allBanners = [];
                    foreach ($banners as $banner) {
                        $imageUrl = buildImageUrl($banner['imagen_original']);
                        
                        $allBanners[] = [
                            'id' => (int)$banner['id'],
                            'titulo' => $banner['titulo'],
                            'descripcion' => $banner['descripcion'],
                            'imagen_url' => $imageUrl,
                            'imagen_exists' => imageExists($banner['imagen_original']),
                            'enlace' => $banner['enlace'],
                            'orden' => (int)$banner['orden'],
                            'posicion' => (int)$banner['posicion'],
                            'activo' => (bool)$banner['activo'],
                            'fecha_inicio' => $banner['fecha_inicio'],
                            'fecha_fin' => $banner['fecha_fin']
                        ];
                    }
                    
                    jsonResponse(true, $allBanners, 'Todos los banners obtenidos');
                    break;
                    
                case 'test':
                    // Test de conectividad y configuración
                    $testResults = [
                        'database_connected' => true,
                        'server_info' => [
                            'host' => $_SERVER['HTTP_HOST'],
                            'script_name' => $_SERVER['SCRIPT_NAME'],
                            'document_root' => $_SERVER['DOCUMENT_ROOT']
                        ],
                        'uploads_info' => [
                            'uploads_dir_exists' => file_exists('../uploads/'),
                            'banners_dir_exists' => file_exists('../uploads/banners/'),
                            'uploads_writable' => is_writable('../uploads/'),
                            'banners_writable' => is_writable('../uploads/banners/')
                        ]
                    ];
                    
                    // Contar banners en BD
                    $countQuery = "SELECT COUNT(*) as total, SUM(activo) as activos FROM banner_carrusel";
                    $countStmt = $conn->prepare($countQuery);
                    $countStmt->execute();
                    $counts = $countStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $testResults['database_info'] = [
                        'total_banners' => (int)$counts['total'],
                        'active_banners' => (int)$counts['activos']
                    ];
                    
                    jsonResponse(true, $testResults, 'Test completado exitosamente');
                    break;
                    
                case 'debug':
                    // Debug específico para troubleshooting
                    $debugQuery = "SELECT * FROM banner_carrusel ORDER BY id DESC LIMIT 5";
                    $debugStmt = $conn->prepare($debugQuery);
                    $debugStmt->execute();
                    $debugBanners = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $debugInfo = [
                        'recent_banners' => $debugBanners,
                        'server_vars' => [
                            'HTTP_HOST' => $_SERVER['HTTP_HOST'],
                            'REQUEST_URI' => $_SERVER['REQUEST_URI'],
                            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
                            'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT']
                        ],
                        'file_checks' => []
                    ];
                    
                    // Verificar archivos de imagen
                    foreach ($debugBanners as $banner) {
                        $imagePath = $banner['imagen_url'] ?? $banner['imagen'] ?? '';
                        if ($imagePath) {
                            $fullUrl = buildImageUrl($imagePath);
                            $localPath = '../' . ltrim($imagePath, '/');
                            
                            $debugInfo['file_checks'][] = [
                                'banner_id' => $banner['id'],
                                'original_path' => $imagePath,
                                'full_url' => $fullUrl,
                                'local_path' => $localPath,
                                'file_exists' => file_exists($localPath),
                                'is_readable' => file_exists($localPath) ? is_readable($localPath) : false
                            ];
                        }
                    }
                    
                    jsonResponse(true, $debugInfo, 'Información de debug generada');
                    break;
                    
                default:
                    jsonResponse(false, null, 'Acción no reconocida: ' . $action);
            }
            break;
            
        default:
            jsonResponse(false, null, 'Método HTTP no soportado: ' . $method);
    }
    
} catch (PDOException $e) {
    error_log("Error de base de datos en banners.php: " . $e->getMessage());
    jsonResponse(false, null, 'Error de base de datos');
    
} catch (Exception $e) {
    error_log("Error general en banners.php: " . $e->getMessage());
    jsonResponse(false, null, 'Error interno del servidor');
}
?>