<?php
/**
 * API de subida de imágenes MEJORADA con validación de seguridad robusta
 * ACTUALIZADO: 2026-01-29
 */

// Headers de seguridad
require_once __DIR__ . '/../middleware/security-headers.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Cargar utilidades de seguridad
require_once __DIR__ . '/../utils/file-upload-validator.php';
require_once __DIR__ . '/../utils/security-logger.php';

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    // Validar método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Método no permitido');
    }

    // Validar que se recibió un archivo
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        sendResponse(false, 'No se recibió imagen o hubo error en la subida');
    }

    $file = $_FILES['image'];
    
    // Validar archivo usando el nuevo validador
    $validator = new FileUploadValidator();
    
    try {
        $fileInfo = $validator->validateImage($file, 5242880); // 5MB máximo
    } catch (Exception $e) {
        // Registrar intento sospechoso
        SecurityLogger::logSuspiciousFileUpload($file['name'], $e->getMessage());
        sendResponse(false, $e->getMessage());
    }

    // Crear directorio de uploads seguro
    $uploadDir = __DIR__ . '/../uploads/empresas/';
    FileUploadValidator::createSecureUploadDirectory($uploadDir);

    // Generar nombre de archivo seguro
    $fileName = FileUploadValidator::generateSafeFilename($fileInfo['extension'], 'empresa');
    $filePath = $uploadDir . $fileName;

    // Mover archivo de forma segura
    try {
        FileUploadValidator::moveUploadedFileSafely($fileInfo['tmp_name'], $filePath);
    } catch (Exception $e) {
        sendResponse(false, 'Error al guardar el archivo: ' . $e->getMessage());
    }

    // Optimizar imagen si es muy grande
    $imageInfo = getimagesize($filePath);
    
    if ($imageInfo[0] > 800 || $imageInfo[1] > 600) {
        $optimizedPath = $uploadDir . 'opt_' . $fileName;
        
        // Crear imagen desde archivo
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($filePath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($filePath);
                break;
            default:
                sendResponse(false, 'Tipo de imagen no soportado para optimización');
        }

        if ($image) {
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);
            
            // Calcular nuevas dimensiones manteniendo proporción
            $maxWidth = 800;
            $maxHeight = 600;
            
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparencia para PNG y GIF
            if ($imageInfo['mime'] === 'image/png' || $imageInfo['mime'] === 'image/gif') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            
            // Guardar imagen optimizada
            switch ($imageInfo['mime']) {
                case 'image/jpeg':
                    imagejpeg($resized, $optimizedPath, 85);
                    break;
                case 'image/png':
                    imagepng($resized, $optimizedPath, 8);
                    break;
                case 'image/gif':
                    imagegif($resized, $optimizedPath);
                    break;
                case 'image/webp':
                    imagewebp($resized, $optimizedPath, 85);
                    break;
            }
            
            imagedestroy($image);
            imagedestroy($resized);
            
            // Reemplazar archivo original con optimizado
            unlink($filePath);
            rename($optimizedPath, $filePath);
            
            // Actualizar permisos
            chmod($filePath, 0640);
        }
    }

    // URL relativa para la base de datos
    $imageUrl = './uploads/empresas/' . $fileName;

    // Registrar subida exitosa
    SecurityLogger::log('file_upload_success', 'INFO', [
        'filename' => $fileName,
        'original_name' => $fileInfo['original_name'],
        'size' => filesize($filePath),
        'type' => 'image'
    ]);

    sendResponse(true, 'Imagen subida exitosamente', [
        'url' => $imageUrl,
        'filename' => $fileName,
        'size' => filesize($filePath),
        'dimensions' => getimagesize($filePath)
    ]);

} catch (Exception $e) {
    error_log("Error en upload-image.php: " . $e->getMessage());
    SecurityLogger::log('file_upload_error', 'WARNING', [
        'error' => $e->getMessage()
    ]);
    sendResponse(false, 'Error del servidor: ' . $e->getMessage());
}
?>