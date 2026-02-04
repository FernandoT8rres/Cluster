<?php
/**
 * API Validator Middleware
 * 
 * Middleware centralizado para validación de APIs
 * Compatible con Hostinger - No requiere dependencias externas
 * 
 * IMPORTANTE: Este middleware es OPCIONAL y NO INVASIVO
 * - Si no se usa, las APIs funcionan normalmente
 * - Solo agrega validación cuando se invoca explícitamente
 * - No altera la lógica existente
 * 
 * @version 1.0.0
 * @date 2026-01-30
 */

class ApiValidator {
    
    /**
     * Validar parámetros requeridos
     * 
     * @param array $data Datos a validar
     * @param array $required Lista de campos requeridos
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    public static function validateRequired($data, $required) {
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[$field] = "El campo '$field' es requerido";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }
    
    /**
     * Validar tipos de datos según reglas
     * 
     * Reglas soportadas:
     * - 'required' => Campo obligatorio
     * - 'email' => Formato de email válido
     * - 'int' => Número entero
     * - 'string' => Cadena de texto
     * - 'min:X' => Longitud/valor mínimo
     * - 'max:X' => Longitud/valor máximo
     * - 'in:a,b,c' => Valor debe estar en lista
     * - 'regex:pattern' => Expresión regular
     * 
     * @param array $data Datos a validar
     * @param array $rules Reglas de validación ['campo' => 'rule1|rule2']
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    public static function validateTypes($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = isset($data[$field]) ? $data[$field] : null;
            
            foreach ($fieldRules as $rule) {
                // Required
                if ($rule === 'required' && (is_null($value) || trim($value) === '')) {
                    $errors[$field] = "El campo '$field' es requerido";
                    continue;
                }
                
                // Si el campo no es requerido y está vacío, skip otras validaciones
                if (is_null($value) || trim($value) === '') {
                    continue;
                }
                
                // Email
                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "El campo '$field' debe ser un email válido";
                }
                
                // Integer
                if ($rule === 'int' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $errors[$field] = "El campo '$field' debe ser un número entero";
                }
                
                // String
                if ($rule === 'string' && !is_string($value)) {
                    $errors[$field] = "El campo '$field' debe ser una cadena de texto";
                }
                
                // Min length/value
                if (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    if (is_numeric($value) && $value < $min) {
                        $errors[$field] = "El campo '$field' debe ser mayor o igual a $min";
                    } elseif (is_string($value) && strlen($value) < $min) {
                        $errors[$field] = "El campo '$field' debe tener al menos $min caracteres";
                    }
                }
                
                // Max length/value
                if (strpos($rule, 'max:') === 0) {
                    $max = (int)substr($rule, 4);
                    if (is_numeric($value) && $value > $max) {
                        $errors[$field] = "El campo '$field' debe ser menor o igual a $max";
                    } elseif (is_string($value) && strlen($value) > $max) {
                        $errors[$field] = "El campo '$field' debe tener máximo $max caracteres";
                    }
                }
                
                // In list
                if (strpos($rule, 'in:') === 0) {
                    $list = explode(',', substr($rule, 3));
                    if (!in_array($value, $list)) {
                        $errors[$field] = "El campo '$field' debe ser uno de: " . implode(', ', $list);
                    }
                }
                
                // Regex
                if (strpos($rule, 'regex:') === 0) {
                    $pattern = substr($rule, 6);
                    if (!preg_match($pattern, $value)) {
                        $errors[$field] = "El campo '$field' tiene un formato inválido";
                    }
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }
    
    /**
     * Validar y sanitizar datos
     * 
     * @param array $data Datos a validar
     * @param array $rules Reglas de validación
     * @param bool $sanitize Si debe sanitizar los datos (default: true)
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    public static function validateAndSanitize($data, $rules, $sanitize = true) {
        // Primero validar
        $result = self::validateTypes($data, $rules);
        
        // Si es válido y se requiere sanitización
        if ($result['valid'] && $sanitize) {
            $sanitizedData = [];
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    // Sanitizar strings
                    $sanitizedData[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                } else {
                    $sanitizedData[$key] = $value;
                }
            }
            $result['data'] = $sanitizedData;
        }
        
        return $result;
    }
    
    /**
     * Respuesta de error estandarizada
     * 
     * @param string|array $errors Errores de validación
     * @param int $httpCode Código HTTP (default: 400)
     * @return void (envía respuesta JSON y termina ejecución)
     */
    public static function errorResponse($errors, $httpCode = 400) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'error' => is_array($errors) ? 'Errores de validación' : $errors,
            'validation_errors' => is_array($errors) ? $errors : null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Validación rápida de un solo campo
     * 
     * @param mixed $value Valor a validar
     * @param string $rules Reglas separadas por |
     * @param string $fieldName Nombre del campo (para mensajes de error)
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public static function validateField($value, $rules, $fieldName = 'campo') {
        $result = self::validateTypes([$fieldName => $value], [$fieldName => $rules]);
        
        return [
            'valid' => $result['valid'],
            'error' => isset($result['errors'][$fieldName]) ? $result['errors'][$fieldName] : null
        ];
    }
    
    /**
     * Sanitizar datos sin validación
     * 
     * @param array $data Datos a sanitizar
     * @return array Datos sanitizados
     */
    public static function sanitize($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validar archivo subido
     * 
     * @param array $file Archivo de $_FILES
     * @param array $options Opciones de validación
     *   - 'required' => bool
     *   - 'maxSize' => int (bytes)
     *   - 'allowedTypes' => array (extensiones permitidas)
     *   - 'allowedMimes' => array (tipos MIME permitidos)
     * @return array ['valid' => bool, 'error' => string|null, 'file' => array]
     */
    public static function validateFile($file, $options = []) {
        $defaults = [
            'required' => false,
            'maxSize' => 5 * 1024 * 1024, // 5MB
            'allowedTypes' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
            'allowedMimes' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']
        ];
        
        $options = array_merge($defaults, $options);
        
        // Si no es requerido y no se subió archivo
        if (!$options['required'] && (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE)) {
            return ['valid' => true, 'error' => null, 'file' => null];
        }
        
        // Si es requerido y no se subió archivo
        if ($options['required'] && (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE)) {
            return ['valid' => false, 'error' => 'El archivo es requerido', 'file' => null];
        }
        
        // Verificar errores de subida
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo'
            ];
            
            $error = isset($errorMessages[$file['error']]) ? $errorMessages[$file['error']] : 'Error desconocido al subir el archivo';
            return ['valid' => false, 'error' => $error, 'file' => null];
        }
        
        // Verificar tamaño
        if ($file['size'] > $options['maxSize']) {
            $maxSizeMB = round($options['maxSize'] / 1024 / 1024, 2);
            return ['valid' => false, 'error' => "El archivo no puede superar {$maxSizeMB}MB", 'file' => null];
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $options['allowedTypes'])) {
            return ['valid' => false, 'error' => 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $options['allowedTypes']), 'file' => null];
        }
        
        // Verificar MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $options['allowedMimes'])) {
            return ['valid' => false, 'error' => 'Tipo MIME no permitido', 'file' => null];
        }
        
        return ['valid' => true, 'error' => null, 'file' => $file];
    }
}

/**
 * Configuración de reglas comunes
 */
class ValidationRules {
    // Autenticación
    const EMAIL = 'required|email|max:255';
    const PASSWORD = 'required|string|min:6|max:255';
    const PASSWORD_OPTIONAL = 'string|min:6|max:255';
    
    // Usuarios
    const NOMBRE = 'required|string|min:2|max:100';
    const APELLIDOS = 'required|string|min:2|max:100';
    const TELEFONO = 'string|min:10|max:15|regex:/^[0-9+\-\s()]+$/';
    const ROL = 'required|in:admin,empleado,empresa';
    
    // Empresas
    const NOMBRE_EMPRESA = 'required|string|min:2|max:200';
    const RFC = 'string|min:12|max:13|regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/';
    
    // Eventos
    const TITULO = 'required|string|min:3|max:200';
    const DESCRIPCION = 'required|string|min:10|max:5000';
    const FECHA = 'required|regex:/^\d{4}-\d{2}-\d{2}$/';
    const HORA = 'regex:/^\d{2}:\d{2}(:\d{2})?$/';
    
    // IDs
    const ID = 'required|int|min:1';
    const ID_OPTIONAL = 'int|min:1';
    
    // Paginación
    const PAGE = 'int|min:1';
    const LIMIT = 'int|min:1|max:100';
}
