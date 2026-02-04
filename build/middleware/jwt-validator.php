<?php
/**
 * JWT Validator Middleware
 * 
 * Validación mejorada de tokens JWT con soporte para blacklist
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

class JwtValidator {
    
    /**
     * Validar token JWT completo
     * 
     * @param string $token Token JWT a validar
     * @param string $secret Secreto para validar firma
     * @return array ['valid' => bool, 'payload' => array|null, 'error' => string|null]
     */
    public static function validate($token, $secret) {
        try {
            // Verificar formato básico
            if (empty($token)) {
                return [
                    'valid' => false,
                    'payload' => null,
                    'error' => 'Token vacío'
                ];
            }
            
            // Verificar que el token tenga 3 partes
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return [
                    'valid' => false,
                    'payload' => null,
                    'error' => 'Formato de token inválido'
                ];
            }
            
            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
            
            // Decodificar payload
            $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
            
            if (!$payload) {
                return [
                    'valid' => false,
                    'payload' => null,
                    'error' => 'Payload inválido'
                ];
            }
            
            // Verificar expiración
            if (isset($payload['exp']) && time() > $payload['exp']) {
                return [
                    'valid' => false,
                    'payload' => $payload,
                    'error' => 'Token expirado'
                ];
            }
            
            // Verificar firma
            $signature = self::base64UrlDecode($signatureEncoded);
            $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
            
            if (!hash_equals($signature, $expectedSignature)) {
                return [
                    'valid' => false,
                    'payload' => null,
                    'error' => 'Firma inválida'
                ];
            }
            
            // Verificar contra blacklist
            if (self::isBlacklisted($token)) {
                return [
                    'valid' => false,
                    'payload' => $payload,
                    'error' => 'Token revocado'
                ];
            }
            
            return [
                'valid' => true,
                'payload' => $payload,
                'error' => null
            ];
            
        } catch (Exception $e) {
            error_log("JWT Validator Error: " . $e->getMessage());
            return [
                'valid' => false,
                'payload' => null,
                'error' => 'Error al validar token'
            ];
        }
    }
    
    /**
     * Verificar si un token está en la blacklist
     * 
     * @param string $token Token a verificar
     * @return bool True si está en blacklist
     */
    public static function isBlacklisted($token) {
        // Cargar TokenBlacklist si está disponible
        $blacklistFile = __DIR__ . '/../utils/token-blacklist.php';
        
        if (file_exists($blacklistFile)) {
            require_once $blacklistFile;
            return TokenBlacklist::isBlacklisted($token);
        }
        
        // Si no hay blacklist, asumir que no está bloqueado
        return false;
    }
    
    /**
     * Extraer payload del token sin validar
     * ADVERTENCIA: Solo usar para debugging, no para autenticación
     * 
     * @param string $token Token JWT
     * @return array|null Payload decodificado o null
     */
    public static function getPayload($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            
            $payload = json_decode(self::base64UrlDecode($parts[1]), true);
            return $payload;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Verificar si el usuario tiene un permiso específico
     * 
     * @param string $token Token JWT
     * @param string $permission Permiso requerido
     * @return bool True si tiene el permiso
     */
    public static function hasPermission($token, $permission) {
        $payload = self::getPayload($token);
        
        if (!$payload) {
            return false;
        }
        
        // Verificar rol de admin (tiene todos los permisos)
        if (isset($payload['rol']) && $payload['rol'] === 'admin') {
            return true;
        }
        
        // Verificar permisos específicos
        if (isset($payload['permissions']) && is_array($payload['permissions'])) {
            return in_array($permission, $payload['permissions']);
        }
        
        return false;
    }
    
    /**
     * Generar nuevo token JWT
     * 
     * @param array $payload Datos del payload
     * @param string $secret Secreto para firmar
     * @param int $expiresIn Tiempo de expiración en segundos (default: 900 = 15 min)
     * @return string Token JWT generado
     */
    public static function generate($payload, $secret, $expiresIn = 900) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiresIn;
        
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Generar refresh token (larga duración)
     * 
     * @param array $payload Datos del payload
     * @param string $secret Secreto para firmar
     * @param int $expiresIn Tiempo de expiración en segundos (default: 604800 = 7 días)
     * @return string Refresh token generado
     */
    public static function generateRefreshToken($payload, $secret, $expiresIn = 604800) {
        $payload['type'] = 'refresh';
        return self::generate($payload, $secret, $expiresIn);
    }
    
    /**
     * Validar que un token sea de tipo refresh
     * 
     * @param string $token Token a validar
     * @return bool True si es refresh token
     */
    public static function isRefreshToken($token) {
        $payload = self::getPayload($token);
        return isset($payload['type']) && $payload['type'] === 'refresh';
    }
    
    /**
     * Base64 URL Encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL Decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Respuesta de error estandarizada para JWT
     * 
     * @param string $error Mensaje de error
     * @param int $httpCode Código HTTP (default: 401)
     * @return void (envía respuesta JSON y termina ejecución)
     */
    public static function errorResponse($error, $httpCode = 401) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'error' => $error,
            'requires_login' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

/**
 * Configuración de tiempos de expiración
 */
class JwtConfig {
    // Access token: 15 minutos
    const ACCESS_TOKEN_EXPIRY = 900;
    
    // Refresh token: 7 días
    const REFRESH_TOKEN_EXPIRY = 604800;
    
    // Tiempo de gracia para renovación (5 minutos antes de expirar)
    const RENEWAL_GRACE_PERIOD = 300;
}
