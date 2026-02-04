<?php
/**
 * File Upload Validator
 * Validación robusta y segura de archivos subidos
 * 
 * Uso:
 * require_once 'utils/file-upload-validator.php';
 * $validator = new FileUploadValidator();
 * $result = $validator->validateImage($_FILES['image']);
 */

class FileUploadValidator {
    
    // Configuración de tipos de archivo permitidos
    private static $allowedMimeTypes = [
        'image' => [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp']
        ],
        'document' => [
            'application/pdf' => ['pdf'],
            'application/msword' => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'application/vnd.ms-excel' => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
            'text/plain' => ['txt']
        ]
    ];
    
    // Patrones maliciosos a detectar
    private static $maliciousPatterns = [
        '<?php',
        '<?=',
        '<script',
        'javascript:',
        'eval(',
        'base64_decode(',
        'exec(',
        'system(',
        'passthru(',
        'shell_exec(',
        'proc_open(',
        'popen('
    ];
    
    /**
     * Valida un archivo de imagen
     * @param array $file Array $_FILES
     * @param int $maxSize Tamaño máximo en bytes (default: 5MB)
     * @return array Información del archivo validado
     * @throws Exception Si la validación falla
     */
    public function validateImage($file, $maxSize = 5242880) {
        return $this->validateFile($file, 'image', $maxSize, true);
    }
    
    /**
     * Valida un archivo de documento
     * @param array $file Array $_FILES
     * @param int $maxSize Tamaño máximo en bytes (default: 10MB)
     * @return array Información del archivo validado
     * @throws Exception Si la validación falla
     */
    public function validateDocument($file, $maxSize = 10485760) {
        return $this->validateFile($file, 'document', $maxSize, false);
    }
    
    /**
     * Valida un archivo genérico
     * @param array $file Array $_FILES
     * @param string $type Tipo de archivo ('image' o 'document')
     * @param int $maxSize Tamaño máximo en bytes
     * @param bool $validateImageContent Validar que sea realmente una imagen
     * @return array Información del archivo validado
     * @throws Exception Si la validación falla
     */
    private function validateFile($file, $type, $maxSize, $validateImageContent) {
        // 1. Verificar que el archivo se subió correctamente
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Error en la estructura del archivo');
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No se seleccionó ningún archivo');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('El archivo excede el tamaño máximo permitido');
            default:
                throw new Exception('Error desconocido al subir el archivo');
        }
        
        // 2. Verificar tamaño del archivo
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            throw new Exception("El archivo es demasiado grande. Máximo permitido: {$maxSizeMB}MB");
        }
        
        if ($file['size'] === 0) {
            throw new Exception('El archivo está vacío');
        }
        
        // 3. Validar extensión del archivo
        $filename = $file['name'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (empty($extension)) {
            throw new Exception('El archivo no tiene extensión');
        }
        
        // 4. Validar MIME type real del archivo (no confiar en el enviado por el cliente)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // 5. Verificar que MIME type y extensión coincidan
        $allowedTypes = self::$allowedMimeTypes[$type] ?? [];
        $validMime = false;
        
        foreach ($allowedTypes as $mime => $exts) {
            if ($mimeType === $mime && in_array($extension, $exts)) {
                $validMime = true;
                break;
            }
        }
        
        if (!$validMime) {
            throw new Exception('Tipo de archivo no permitido');
        }
        
        // 6. Para imágenes, validar que realmente sea una imagen
        if ($validateImageContent) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if (!$imageInfo) {
                throw new Exception('El archivo no es una imagen válida');
            }
        }
        
        // 7. Escanear contenido por patrones maliciosos
        $this->scanForMaliciousContent($file['tmp_name']);
        
        // 8. Validar nombre de archivo
        $this->validateFilename($filename);
        
        // Retornar información del archivo validado
        return [
            'original_name' => $filename,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size' => $file['size'],
            'tmp_name' => $file['tmp_name']
        ];
    }
    
    /**
     * Escanea el contenido del archivo por patrones maliciosos
     * @param string $filePath Ruta temporal del archivo
     * @throws Exception Si se detecta contenido malicioso
     */
    private function scanForMaliciousContent($filePath) {
        // Leer primeros 8KB del archivo para escaneo
        $handle = fopen($filePath, 'rb');
        $content = fread($handle, 8192);
        fclose($handle);
        
        // Buscar patrones maliciosos
        foreach (self::$maliciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                throw new Exception('Contenido malicioso detectado en el archivo');
            }
        }
        
        // Verificar null bytes (técnica de evasión)
        if (strpos($content, "\0") !== false) {
            throw new Exception('Archivo contiene caracteres no válidos');
        }
    }
    
    /**
     * Valida el nombre del archivo
     * @param string $filename Nombre del archivo
     * @throws Exception Si el nombre no es válido
     */
    private function validateFilename($filename) {
        // Verificar longitud
        if (strlen($filename) > 255) {
            throw new Exception('El nombre del archivo es demasiado largo');
        }
        
        // Verificar caracteres peligrosos
        $dangerousChars = ['..', '/', '\\', '<', '>', ':', '"', '|', '?', '*', "\0"];
        foreach ($dangerousChars as $char) {
            if (strpos($filename, $char) !== false) {
                throw new Exception('El nombre del archivo contiene caracteres no permitidos');
            }
        }
    }
    
    /**
     * Genera un nombre de archivo seguro y único
     * @param string $extension Extensión del archivo
     * @param string $prefix Prefijo opcional
     * @return string Nombre de archivo seguro
     */
    public static function generateSafeFilename($extension, $prefix = '') {
        $randomName = bin2hex(random_bytes(16));
        $timestamp = time();
        
        if ($prefix) {
            $prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) . '_';
        }
        
        return $prefix . $randomName . '_' . $timestamp . '.' . $extension;
    }
    
    /**
     * Crea un directorio de uploads seguro
     * @param string $path Ruta del directorio
     * @return bool True si se creó o ya existe
     */
    public static function createSecureUploadDirectory($path) {
        if (!is_dir($path)) {
            // Crear directorio sin permisos de ejecución
            if (!mkdir($path, 0750, true)) {
                throw new Exception('No se pudo crear el directorio de uploads');
            }
        }
        
        // Crear .htaccess para prevenir ejecución de PHP
        $htaccessPath = $path . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = "# Prevenir ejecución de PHP\n";
            $htaccessContent .= "php_flag engine off\n";
            $htaccessContent .= "Options -Indexes\n";
            $htaccessContent .= "Options -ExecCGI\n";
            $htaccessContent .= "AddHandler cgi-script .php .php3 .php4 .phtml .pl .py .jsp .asp .htm .shtml .sh .cgi\n";
            
            file_put_contents($htaccessPath, $htaccessContent);
        }
        
        // Crear index.html vacío para prevenir listado de directorio
        $indexPath = $path . '/index.html';
        if (!file_exists($indexPath)) {
            file_put_contents($indexPath, '<!-- Access Denied -->');
        }
        
        return true;
    }
    
    /**
     * Mueve el archivo subido a su destino final de forma segura
     * @param string $tmpPath Ruta temporal del archivo
     * @param string $destinationPath Ruta de destino
     * @return bool True si se movió correctamente
     * @throws Exception Si falla el movimiento
     */
    public static function moveUploadedFileSafely($tmpPath, $destinationPath) {
        // Verificar que el archivo temporal existe y es un archivo subido
        if (!is_uploaded_file($tmpPath)) {
            throw new Exception('Archivo temporal no válido');
        }
        
        // Verificar que el directorio de destino existe
        $destinationDir = dirname($destinationPath);
        if (!is_dir($destinationDir)) {
            throw new Exception('Directorio de destino no existe');
        }
        
        // Mover archivo
        if (!move_uploaded_file($tmpPath, $destinationPath)) {
            throw new Exception('Error al mover el archivo');
        }
        
        // Establecer permisos seguros (lectura/escritura solo para owner, lectura para grupo)
        chmod($destinationPath, 0640);
        
        return true;
    }
}
?>
