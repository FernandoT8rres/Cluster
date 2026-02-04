<?php
/**
 * API de Banners Mejorada con Soporte para Subida de Archivos Locales
 * Incluye manejo de imágenes locales y mejor gestión de errores
 */

// Desactivar el reporte de errores en pantalla para evitar que se mezclen con el JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers CORS y JSON
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

function responderJSON($success, $data = null, $message = '', $debug = null) {
    http_response_code($success ? 200 : 400);
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if ($debug && isset($_GET['debug']) && $_GET['debug'] === '1') {
        $response['debug'] = $debug;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

function crearDirectorioSiNoExiste($directorio) {
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
}

function subirImagenLocal($archivo) {
    $directorioSubida = '../uploads/banners/';
    crearDirectorioSiNoExiste($directorioSubida);
    
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $extensionesPermitidas)) {
        return ['success' => false, 'message' => 'Formato de imagen no permitido'];
    }
    
    $nombreArchivo = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $rutaCompleta = $directorioSubida . $nombreArchivo;
    
    if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        // Devolver la URL relativa
        $urlImagen = str_replace('../', '', $rutaCompleta);
        return ['success' => true, 'url' => $urlImagen];
    }
    
    return ['success' => false, 'message' => 'Error al subir la imagen'];
}

function obtenerBannersActivos($conn) {
    try {
        $query = "
            SELECT id, titulo, descripcion, 
                   imagen_url, 
                   posicion, 
                   fecha_inicio, fecha_fin, fecha_creacion as created_at, activo
            FROM banner_carrusel 
            WHERE activo = 1 
            /* AND (fecha_inicio IS NULL OR fecha_inicio <= NOW()) */
            /* AND (fecha_fin IS NULL OR fecha_fin >= NOW()) */
            ORDER BY posicion ASC, fecha_creacion DESC
        ";
        
        $stmt = $conn->query($query);
        $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar las URLs de imagen
        foreach ($banners as &$banner) {
            // Si la imagen es local, crear URL absoluta con /build/
            if (!filter_var($banner['imagen_url'], FILTER_VALIDATE_URL)) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                // Usar ruta correcta con /build/
                $banner['imagen_url'] = $protocol . '://' . $host . '/build/' . ltrim($banner['imagen_url'], '/');
            }
            
            // Formatear fechas
            if ($banner['fecha_inicio']) {
                $banner['fecha_inicio'] = date('c', strtotime($banner['fecha_inicio']));
            }
            if ($banner['fecha_fin']) {
                $banner['fecha_fin'] = date('c', strtotime($banner['fecha_fin']));
            }
            if (isset($banner['created_at']) && $banner['created_at']) {
                $banner['created_at'] = date('c', strtotime($banner['created_at']));
            }
        }
        
        return $banners;
        
    } catch (PDOException $e) {
        error_log("Error al obtener banners: " . $e->getMessage());
        return [];
    }
}

function obtenerTodosBanners($conn) {
    try {
        $query = "
            SELECT id, titulo, descripcion, 
                   imagen_url,
                   posicion, 
                   fecha_inicio, fecha_fin, fecha_creacion as created_at, activo
            FROM banner_carrusel 
            ORDER BY posicion ASC, fecha_creacion DESC
        ";
        
        $stmt = $conn->query($query);
        $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar URLs como en obtenerBannersActivos
        foreach ($banners as &$banner) {
            if (!filter_var($banner['imagen_url'], FILTER_VALIDATE_URL)) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                // Usar ruta correcta con /build/
                $banner['imagen_url'] = $protocol . '://' . $host . '/build/' . ltrim($banner['imagen_url'], '/');
            }
        }
        
        return $banners;
        
    } catch (PDOException $e) {
        error_log("Error al obtener todos los banners: " . $e->getMessage());
        return [];
    }
}

function crearBanner($conn, $datos) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO banner_carrusel (titulo, descripcion, imagen_url, posicion, activo, fecha_inicio, fecha_fin)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $resultado = $stmt->execute([
            $datos['titulo'] ?? '',
            $datos['descripcion'] ?? '',
            $datos['imagen_url'] ?? '',
            $datos['posicion'] ?? 1,
            isset($datos['activo']) ? ($datos['activo'] ? 1 : 0) : 1,
            $datos['fecha_inicio'] ?? null,
            $datos['fecha_fin'] ?? null
        ]);
        
        if ($resultado) {
            return [
                'id' => $conn->lastInsertId(),
                'titulo' => $datos['titulo'],
                'descripcion' => $datos['descripcion'],
                'imagen_url' => $datos['imagen_url'],
                'posicion' => $datos['posicion'],
                'activo' => isset($datos['activo']) ? ($datos['activo'] ? 1 : 0) : 1,
                'fecha_inicio' => $datos['fecha_inicio'],
                'fecha_fin' => $datos['fecha_fin']
            ];
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log("Error al crear banner: " . $e->getMessage());
        return false;
    }
}

function actualizarBanner($conn, $id, $datos) {
    try {
        $stmt = $conn->prepare("
            UPDATE banner_carrusel 
            SET titulo = ?, descripcion = ?, imagen_url = ?, posicion = ?, 
                activo = ?, fecha_inicio = ?, fecha_fin = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([
            $datos['titulo'] ?? '',
            $datos['descripcion'] ?? '',
            $datos['imagen_url'] ?? '',
            $datos['posicion'] ?? 1,
            isset($datos['activo']) ? ($datos['activo'] ? 1 : 0) : 1,
            $datos['fecha_inicio'] ?? null,
            $datos['fecha_fin'] ?? null,
            $id
        ]);
        
    } catch (PDOException $e) {
        error_log("Error al actualizar banner: " . $e->getMessage());
        return false;
    }
}

function eliminarBanner($conn, $id) {
    try {
        // Obtener la imagen antes de eliminar para borrar el archivo
        $stmt = $conn->prepare("SELECT imagen_url FROM banner_carrusel WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch();
        
        if ($banner && !filter_var($banner['imagen_url'], FILTER_VALIDATE_URL)) {
            // Es una imagen local, intentar eliminarla
            $rutaImagen = '../' . $banner['imagen_url'];
            if (file_exists($rutaImagen)) {
                unlink($rutaImagen);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM banner_carrusel WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error al eliminar banner: " . $e->getMessage());
        return false;
    }
}

// Inicializar conexión a la base de datos
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    responderJSON(false, null, 'Error de conexión a la base de datos: ' . $e->getMessage());
}

// Obtener método y contenido
$metodo = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Debug información
$debug_info = [
    'method' => $metodo,
    'get_params' => $_GET,
    'post_params' => $_POST,
    'files' => $_FILES,
    'input' => $input
];

switch ($metodo) {
    case 'GET':
        $accion = $_GET['action'] ?? 'list';
        
        switch ($accion) {
            case 'list':
            case 'active':
                // Obtener banners activos para el carrusel
                $banners = obtenerBannersActivos($conn);
                
                // IMPORTANTE: NO devolver banners de prueba, solo los de la BD
                if (empty($banners)) {
                    // Si no hay banners en la BD, devolver array vacío
                    responderJSON(true, [], 'No hay banners activos en la base de datos', $debug_info);
                }
                
                responderJSON(true, $banners, 'Banners activos obtenidos correctamente', $debug_info);
                break;
                
            case 'all':
                // Obtener todos los banners para administración
                $banners = obtenerTodosBanners($conn);
                responderJSON(true, $banners, 'Todos los banners obtenidos correctamente', $debug_info);
                break;
                
            case 'test':
                // Endpoint de prueba
                responderJSON(true, [
                    'server_time' => date('c'),
                    'database_connected' => true,
                    'uploads_dir_exists' => file_exists('../uploads/banners/'),
                    'uploads_dir_writable' => is_writable('../uploads/banners/') || is_writable('../uploads/')
                ], 'API funcionando correctamente', $debug_info);
                break;
                
            default:
                responderJSON(false, null, 'Acción no válida', $debug_info);
        }
        break;
        
    case 'POST':
        // Manejar subida de archivos
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $resultadoSubida = subirImagenLocal($_FILES['imagen']);
            
            if ($resultadoSubida['success']) {
                // Si se subió la imagen, usar la URL local
                $_POST['imagen_url'] = $resultadoSubida['url'];
            } else {
                responderJSON(false, null, $resultadoSubida['message'], $debug_info);
            }
        }
        
        // Crear nuevo banner
        $datos = $_POST ?: $input;
        
        if (!$datos) {
            responderJSON(false, null, 'Datos no válidos', $debug_info);
        }
        
        $nuevoBanner = crearBanner($conn, $datos);
        
        if ($nuevoBanner) {
            responderJSON(true, $nuevoBanner, 'Banner creado correctamente', $debug_info);
        } else {
            responderJSON(false, null, 'Error al crear el banner', $debug_info);
        }
        break;
        
    case 'PUT':
        // Actualizar banner existente
        if (!$input || !isset($input['id'])) {
            responderJSON(false, null, 'ID de banner requerido', $debug_info);
        }
        
        $id = $input['id'];
        unset($input['id']);
        
        if (actualizarBanner($conn, $id, $input)) {
            responderJSON(true, null, 'Banner actualizado correctamente', $debug_info);
        } else {
            responderJSON(false, null, 'Error al actualizar el banner', $debug_info);
        }
        break;
        
    case 'DELETE':
        // Eliminar banner
        $id = $_GET['id'] ?? ($input['id'] ?? null);
        
        if (!$id) {
            responderJSON(false, null, 'ID de banner requerido', $debug_info);
        }
        
        if (eliminarBanner($conn, $id)) {
            responderJSON(true, null, 'Banner eliminado correctamente', $debug_info);
        } else {
            responderJSON(false, null, 'Error al eliminar el banner', $debug_info);
        }
        break;
        
    default:
        http_response_code(405);
        responderJSON(false, null, 'Método no permitido', $debug_info);
}
?>