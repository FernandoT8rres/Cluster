<?php
/**
 * Input Validator
 * Validación robusta de entrada de datos
 * 
 * Uso:
 * require_once 'utils/input-validator.php';
 * $id = InputValidator::validateInt($_GET['id'], 1);
 */

class InputValidator {
    
    /**
     * Valida un entero
     * @param mixed $value Valor a validar
     * @param int|null $min Valor mínimo permitido
     * @param int|null $max Valor máximo permitido
     * @return int Valor validado
     * @throws InvalidArgumentException Si la validación falla
     */
    public static function validateInt($value, $min = null, $max = null) {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Valor requerido');
        }
        
        if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== 0 && $value !== '0') {
            throw new InvalidArgumentException('Debe ser un número entero válido');
        }
        
        $int = (int)$value;
        
        if ($min !== null && $int < $min) {
            throw new InvalidArgumentException("El valor debe ser mayor o igual a {$min}");
        }
        
        if ($max !== null && $int > $max) {
            throw new InvalidArgumentException("El valor debe ser menor o igual a {$max}");
        }
        
        return $int;
    }
    
    /**
     * Valida un número flotante
     * @param mixed $value Valor a validar
     * @param float|null $min Valor mínimo permitido
     * @param float|null $max Valor máximo permitido
     * @return float Valor validado
     * @throws InvalidArgumentException Si la validación falla
     */
    public static function validateFloat($value, $min = null, $max = null) {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Valor requerido');
        }
        
        if (!filter_var($value, FILTER_VALIDATE_FLOAT) && $value !== 0 && $value !== '0') {
            throw new InvalidArgumentException('Debe ser un número decimal válido');
        }
        
        $float = (float)$value;
        
        if ($min !== null && $float < $min) {
            throw new InvalidArgumentException("El valor debe ser mayor o igual a {$min}");
        }
        
        if ($max !== null && $float > $max) {
            throw new InvalidArgumentException("El valor debe ser menor o igual a {$max}");
        }
        
        return $float;
    }
    
    /**
     * Valida una cadena de texto
     * @param mixed $value Valor a validar
     * @param int $maxLength Longitud máxima
     * @param string|null $pattern Patrón regex opcional
     * @param bool $allowEmpty Permitir cadena vacía
     * @return string Valor validado y sanitizado
     * @throws InvalidArgumentException Si la validación falla
     */
    public static function validateString($value, $maxLength = 255, $pattern = null, $allowEmpty = false) {
        if ($value === null) {
            $value = '';
        }
        
        $value = trim((string)$value);
        
        if (!$allowEmpty && $value === '') {
            throw new InvalidArgumentException('El campo no puede estar vacío');
        }
        
        if (strlen($value) > $maxLength) {
            throw new InvalidArgumentException("El texto no puede exceder {$maxLength} caracteres");
        }
        
        if ($pattern !== null && !preg_match($pattern, $value)) {
            throw new InvalidArgumentException('El formato del texto no es válido');
        }
        
        // Sanitizar para prevenir XSS
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Valida un email
     * @param mixed $value Email a validar
     * @return string Email validado y normalizado
     * @throws InvalidArgumentException Si el email no es válido
     */
    public static function validateEmail($value) {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('El email es requerido');
        }
        
        $email = filter_var(trim($value), FILTER_VALIDATE_EMAIL);
        
        if ($email === false) {
            throw new InvalidArgumentException('El email no es válido');
        }
        
        return strtolower($email);
    }
    
    /**
     * Valida una URL
     * @param mixed $value URL a validar
     * @param bool $requireHttps Requerir HTTPS
     * @return string URL validada
     * @throws InvalidArgumentException Si la URL no es válida
     */
    public static function validateUrl($value, $requireHttps = false) {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('La URL es requerida');
        }
        
        $url = filter_var(trim($value), FILTER_VALIDATE_URL);
        
        if ($url === false) {
            throw new InvalidArgumentException('La URL no es válida');
        }
        
        if ($requireHttps && strpos($url, 'https://') !== 0) {
            throw new InvalidArgumentException('La URL debe usar HTTPS');
        }
        
        return $url;
    }
    
    /**
     * Valida una fecha
     * @param mixed $value Fecha a validar
     * @param string $format Formato esperado (default: Y-m-d)
     * @return string Fecha validada
     * @throws InvalidArgumentException Si la fecha no es válida
     */
    public static function validateDate($value, $format = 'Y-m-d') {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('La fecha es requerida');
        }
        
        $date = DateTime::createFromFormat($format, $value);
        
        if (!$date || $date->format($format) !== $value) {
            throw new InvalidArgumentException("La fecha debe tener el formato {$format}");
        }
        
        return $value;
    }
    
    /**
     * Valida un enum (valor dentro de una lista permitida)
     * @param mixed $value Valor a validar
     * @param array $allowedValues Valores permitidos
     * @param bool $caseSensitive Sensible a mayúsculas/minúsculas
     * @return string Valor validado
     * @throws InvalidArgumentException Si el valor no está en la lista
     */
    public static function validateEnum($value, array $allowedValues, $caseSensitive = true) {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('El valor es requerido');
        }
        
        $value = trim((string)$value);
        
        if ($caseSensitive) {
            if (!in_array($value, $allowedValues, true)) {
                throw new InvalidArgumentException('Valor no permitido');
            }
        } else {
            $valueLower = strtolower($value);
            $allowedLower = array_map('strtolower', $allowedValues);
            
            if (!in_array($valueLower, $allowedLower, true)) {
                throw new InvalidArgumentException('Valor no permitido');
            }
            
            // Retornar el valor original de la lista
            $index = array_search($valueLower, $allowedLower);
            $value = $allowedValues[$index];
        }
        
        return $value;
    }
    
    /**
     * Valida un booleano
     * @param mixed $value Valor a validar
     * @return bool Valor validado
     */
    public static function validateBoolean($value) {
        if ($value === null) {
            return false;
        }
        
        // Valores que se consideran true
        $trueValues = [true, 1, '1', 'true', 'TRUE', 'yes', 'YES', 'on', 'ON'];
        
        return in_array($value, $trueValues, true);
    }
    
    /**
     * Valida un array de enteros
     * @param mixed $value Valor a validar (puede ser array o string separado por comas)
     * @param int|null $min Valor mínimo para cada elemento
     * @param int|null $max Valor máximo para cada elemento
     * @return array Array de enteros validados
     * @throws InvalidArgumentException Si la validación falla
     */
    public static function validateIntArray($value, $min = null, $max = null) {
        if ($value === null || $value === '') {
            return [];
        }
        
        // Si es string, convertir a array
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        
        if (!is_array($value)) {
            throw new InvalidArgumentException('Debe ser un array');
        }
        
        $result = [];
        foreach ($value as $item) {
            $result[] = self::validateInt($item, $min, $max);
        }
        
        return $result;
    }
    
    /**
     * Sanitiza texto plano (sin HTML)
     * @param mixed $value Valor a sanitizar
     * @return string Texto sanitizado
     */
    public static function sanitizePlainText($value) {
        if ($value === null) {
            return '';
        }
        
        $text = strip_tags((string)$value);
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitiza HTML permitiendo solo tags seguros
     * @param mixed $value Valor a sanitizar
     * @param array $allowedTags Tags HTML permitidos
     * @return string HTML sanitizado
     */
    public static function sanitizeHtml($value, array $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li']) {
        if ($value === null) {
            return '';
        }
        
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';
        return strip_tags((string)$value, $allowedTagsString);
    }
}
?>
