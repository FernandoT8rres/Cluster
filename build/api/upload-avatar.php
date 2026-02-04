<?php
/**
 * API para subir avatares de usuarios y guardarlos en la base de datos
 * Endpoint: /api/upload-avatar.php
 */

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

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
 * Validar que el usuario esté autenticado
 */
function verificarAutenticacion() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_data'])) {
        responderJSON(false, null, 'Usuario no autenticado');
    }
    return $_SESSION['user_id'];
}

/**
 * Validar archivo de imagen
 */
function validarImagen($file) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Verificar que se subió correctamente
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo: ' . $file['error']);
    }
    
    // Verificar tamaño
    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB permitidos.');
    }
    
    // Verificar tipo MIME
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, WEBP.');
    }
    
    // Verificar que es realmente una imagen
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        throw new Exception('El archivo no es una imagen válida.');
    }
    
    return [
        'mime_type' => $file['type'],
        'size' => $file['size'],
        'width' => $imageInfo[0],
        'height' => $imageInfo[1],
        'filename' => $file['name']
    ];
}

/**
 * Redimensionar imagen si es necesario
 */
function redimensionarImagen($tmpPath, $maxWidth = 300, $maxHeight = 300) {
    $imageInfo = getimagesize($tmpPath);
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Si la imagen ya es pequeña, no redimensionar
    if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
        return file_get_contents($tmpPath);
    }
    
    // Calcular nuevas dimensiones manteniendo aspecto
    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    $newWidth = intval($originalWidth * $ratio);
    $newHeight = intval($originalHeight * $ratio);
    
    // Crear imagen fuente
    switch ($mimeType) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($tmpPath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($tmpPath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($tmpPath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($tmpPath);
            break;
        default:
            throw new Exception('Tipo de imagen no soportado para redimensionar');
    }
    
    // Crear imagen destino
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preservar transparencia para PNG y GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Redimensionar
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // Obtener datos binarios
    ob_start();
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($destination, null, 85);
            break;
        case 'image/png':
            imagepng($destination, null, 6);
            break;
        case 'image/gif':
            imagegif($destination);
            break;
        case 'image/webp':
            imagewebp($destination, null, 85);
            break;
    }
    $imageData = ob_get_contents();
    ob_end_clean();
    
    // Limpiar memoria
    imagedestroy($source);
    imagedestroy($destination);
    
    return $imageData;
}

/**
 * Guardar avatar en la base de datos
 */
function guardarAvatar($userId, $imageData, $mimeType, $filename) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $query = "UPDATE usuarios_perfil 
                  SET avatar = :avatar, 
                      avatar_mime_type = :mime_type, 
                      avatar_filename = :filename
                  WHERE id = :user_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':avatar', $imageData, PDO::PARAM_LOB);
        $stmt->bindParam(':mime_type', $mimeType);
        $stmt->bindParam(':filename', $filename);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error guardando avatar: " . $e->getMessage());
        throw new Exception("Error al guardar avatar en la base de datos");
    }
}

// Manejar la subida
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar autenticación
        $userId = verificarAutenticacion();
        
        // Verificar que se envió un archivo
        if (!isset($_FILES['avatar']) || empty($_FILES['avatar']['tmp_name'])) {
            responderJSON(false, null, 'No se encontró archivo de imagen');
        }
        
        $file = $_FILES['avatar'];
        
        // Validar imagen
        $imageInfo = validarImagen($file);
        
        // Redimensionar imagen
        $imageData = redimensionarImagen($file['tmp_name']);
        
        // Guardar en base de datos
        $success = guardarAvatar($userId, $imageData, $imageInfo['mime_type'], $imageInfo['filename']);
        
        if ($success) {
            // Actualizar datos de sesión
            $_SESSION['user_data']['avatar'] = 'data:' . $imageInfo['mime_type'] . ';base64,' . base64_encode($imageData);
            
            responderJSON(true, [
                'avatar_url' => 'data:' . $imageInfo['mime_type'] . ';base64,' . base64_encode($imageData),
                'size' => strlen($imageData),
                'original_size' => $imageInfo['size'],
                'filename' => $imageInfo['filename']
            ], 'Avatar actualizado correctamente');
        } else {
            responderJSON(false, null, 'Error al guardar el avatar');
        }
        
    } catch (Exception $e) {
        error_log("Error en upload-avatar: " . $e->getMessage());
        responderJSON(false, null, $e->getMessage());
    }
} else {
    http_response_code(405);
    responderJSON(false, null, 'Método no permitido');
}
?>