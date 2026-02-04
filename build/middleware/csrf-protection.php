<?php
/**
 * CSRF Protection Middleware
 * Protege contra ataques Cross-Site Request Forgery
 * 
 * Uso:
 * require_once 'middleware/csrf-protection.php';
 * CSRFProtection::validateToken();
 */

class CSRFProtection {
    private static $tokenName = 'csrf_token';
    private static $tokenLength = 32;
    
    /**
     * Genera un token CSRF y lo almacena en la sesión
     * @return string El token generado
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$tokenName])) {
            $_SESSION[self::$tokenName] = bin2hex(random_bytes(self::$tokenLength));
        }
        
        return $_SESSION[self::$tokenName];
    }
    
    /**
     * Obtiene el token CSRF actual de la sesión
     * @return string|null El token o null si no existe
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION[self::$tokenName] ?? null;
    }
    
    /**
     * Valida el token CSRF recibido
     * @param string|null $token Token a validar (si es null, se busca en POST o headers)
     * @return bool True si el token es válido
     */
    public static function validateToken($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Si no se proporciona token, buscarlo en POST o headers
        if ($token === null) {
            $token = $_POST[self::$tokenName] ?? 
                     $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
                     $_SERVER['HTTP_X_XSRF_TOKEN'] ?? 
                     null;
        }
        
        // Verificar que existe token en sesión
        if (!isset($_SESSION[self::$tokenName])) {
            return false;
        }
        
        // Verificar que se recibió un token
        if ($token === null || $token === '') {
            return false;
        }
        
        // Comparación segura contra timing attacks
        return hash_equals($_SESSION[self::$tokenName], $token);
    }
    
    /**
     * Valida el token y lanza excepción si es inválido
     * @throws Exception Si el token es inválido
     */
    public static function requireValidToken() {
        if (!self::validateToken()) {
            http_response_code(403);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'CSRF token inválido o faltante',
                'code' => 'CSRF_VALIDATION_FAILED'
            ], JSON_UNESCAPED_UNICODE);
            
            exit;
        }
    }
    
    /**
     * Regenera el token CSRF (útil después de login/logout)
     */
    public static function regenerateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION[self::$tokenName] = bin2hex(random_bytes(self::$tokenLength));
        return $_SESSION[self::$tokenName];
    }
    
    /**
     * Middleware para proteger automáticamente requests POST/PUT/DELETE
     * Llamar al inicio de archivos API que modifican datos
     */
    public static function protect() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Solo validar en métodos que modifican datos
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            self::requireValidToken();
        }
    }
    
    /**
     * Genera un campo hidden HTML con el token CSRF
     * @return string HTML del campo hidden
     */
    public static function getHiddenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Genera un meta tag HTML con el token CSRF (para AJAX)
     * @return string HTML del meta tag
     */
    public static function getMetaTag() {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
?>
