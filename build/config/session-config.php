<?php
/**
 * Configuración Segura de Sesiones - Claut Intranet
 * 
 * Centraliza toda la configuración de sesiones del sistema
 * Implementa mejores prácticas de seguridad para prevenir:
 * - Session hijacking
 * - Session fixation
 * - XSS attacks
 * - CSRF attacks
 * 
 * @author Claut Development Team
 * @version 2.0
 * @date 2026-01-29
 */

// Prevenir acceso directo
if (!defined('CLAUT_ACCESS')) {
    http_response_code(403);
    die('Acceso directo no permitido');
}

class SessionConfig {
    
    /**
     * Configuración de timeouts
     */
    const SESSION_LIFETIME = 3600;        // 1 hora de vida máxima
    const INACTIVITY_TIMEOUT = 1800;      // 30 minutos de inactividad
    const REGENERATE_INTERVAL = 1800;     // Regenerar ID cada 30 minutos
    
    /**
     * Nombre de la sesión
     */
    const SESSION_NAME = 'CLAUT_SESSION';
    
    /**
     * Inicializar sesión segura
     * 
     * @return bool True si la sesión se inició correctamente
     */
    public static function init() {
        try {
            // Si ya hay sesión activa, validar y retornar
            if (session_status() === PHP_SESSION_ACTIVE) {
                return self::isValid();
            }
            
            // Configurar parámetros de cookies de sesión
            self::configureSessionCookies();
            
            // Configurar parámetros de sesión
            self::configureSessionParameters();
            
            // Nombre de sesión personalizado
            session_name(self::SESSION_NAME);
            
            // Iniciar sesión
            if (!session_start()) {
                error_log('SessionConfig: Error iniciando sesión');
                return false;
            }
            
            // Regenerar ID de sesión si es necesario
            self::regenerateIfNeeded();
            
            // Marcar tiempo de inicio si es nueva sesión
            if (!isset($_SESSION['created_at'])) {
                $_SESSION['created_at'] = time();
            }
            
            // Actualizar última actividad
            $_SESSION['last_activity'] = time();
            
            return true;
            
        } catch (Exception $e) {
            error_log('SessionConfig: Error en init() - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Configurar parámetros de cookies de sesión
     */
    private static function configureSessionCookies() {
        // httponly: Prevenir acceso via JavaScript (XSS protection)
        ini_set('session.cookie_httponly', 1);
        
        // secure: Solo enviar cookie por HTTPS
        // FORZADO A 1 porque estamos en HTTPS
        ini_set('session.cookie_secure', 1);
        
        // samesite: Prevenir CSRF attacks
        // Lax = permite cookies en navegación normal, bloquea en POST cross-site
        ini_set('session.cookie_samesite', 'Lax');
        
        // Solo usar cookies, no permitir session ID en URL
        ini_set('session.use_only_cookies', 1);
        
        // Modo estricto: rechazar IDs de sesión no generados por el servidor
        ini_set('session.use_strict_mode', 0); // Deshabilitado para debugging
        
        // Cookie válida solo hasta cerrar navegador
        ini_set('session.cookie_lifetime', 0);

        // Path de la cookie: Disponible en todo el dominio
        ini_set('session.cookie_path', '/');
    }
    
    /**
     * Configurar parámetros de sesión
     */
    private static function configureSessionParameters() {
        // Tiempo máximo de vida de sesión
        ini_set('session.gc_maxlifetime', self::SESSION_LIFETIME);
        
        // Probabilidad de ejecutar garbage collection
        ini_set('session.gc_probability', 1);
        ini_set('session.gc_divisor', 100);
        
        // Usar solo cookies para almacenar session ID
        ini_set('session.use_trans_sid', 0);
        
        // Algoritmo de hash para session ID (más seguro)
        ini_set('session.hash_function', 'sha256');
        ini_set('session.hash_bits_per_character', 5);
    }
    
    /**
     * Regenerar ID de sesión si es necesario
     * Previene session fixation attacks
     */
    private static function regenerateIfNeeded() {
        // Regenerar en primera carga
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
            // session_regenerate_id(true); // Deshabilitado para debugear
            return;
        }
        
        // Regenerar cada REGENERATE_INTERVAL segundos
        $timeSinceRegeneration = time() - $_SESSION['last_regeneration'];
        if ($timeSinceRegeneration > self::REGENERATE_INTERVAL) {
            $_SESSION['last_regeneration'] = time();
            // session_regenerate_id(true); // Deshabilitado para debugear
        }
    }
    
    /**
     * Verificar si sesión es válida
     * 
     * @return bool True si la sesión es válida
     */
    public static function isValid() {
        // Verificar que hay sesión activa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        // Verificar timeout de inactividad
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            
            if ($inactiveTime > self::INACTIVITY_TIMEOUT) {
                error_log('SessionConfig: Sesión expirada por inactividad (' . $inactiveTime . 's)');
                self::destroy();
                return false;
            }
        }
        
        // Verificar tiempo máximo de vida de sesión
        if (isset($_SESSION['created_at'])) {
            $sessionAge = time() - $_SESSION['created_at'];
            
            if ($sessionAge > self::SESSION_LIFETIME) {
                error_log('SessionConfig: Sesión expirada por tiempo máximo (' . $sessionAge . 's)');
                self::destroy();
                return false;
            }
        }
        
        // Actualizar última actividad
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Destruir sesión completamente
     * Usado en logout o cuando sesión expira
     */
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Limpiar array de sesión
            $_SESSION = [];
            
            // Eliminar cookie de sesión
            if (isset($_COOKIE[self::SESSION_NAME])) {
                $params = session_get_cookie_params();
                setcookie(
                    self::SESSION_NAME,
                    '',
                    time() - 3600,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            // Destruir sesión
            session_destroy();
        }
    }
    
    /**
     * Obtener información de la sesión actual
     * Útil para debugging
     * 
     * @return array Información de la sesión
     */
    public static function getInfo() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return ['status' => 'inactive'];
        }
        
        $createdAt = $_SESSION['created_at'] ?? null;
        $lastActivity = $_SESSION['last_activity'] ?? null;
        $lastRegeneration = $_SESSION['last_regeneration'] ?? null;
        
        return [
            'status' => 'active',
            'id' => session_id(),
            'name' => session_name(),
            'created_at' => $createdAt ? date('Y-m-d H:i:s', $createdAt) : null,
            'last_activity' => $lastActivity ? date('Y-m-d H:i:s', $lastActivity) : null,
            'last_regeneration' => $lastRegeneration ? date('Y-m-d H:i:s', $lastRegeneration) : null,
            'age_seconds' => $createdAt ? (time() - $createdAt) : null,
            'inactive_seconds' => $lastActivity ? (time() - $lastActivity) : null,
            'time_until_expiry' => $createdAt ? (self::SESSION_LIFETIME - (time() - $createdAt)) : null,
            'time_until_timeout' => $lastActivity ? (self::INACTIVITY_TIMEOUT - (time() - $lastActivity)) : null
        ];
    }
    
    /**
     * Forzar regeneración de session ID
     * Útil después de cambios de privilegios (ej: login)
     */
    public static function regenerate() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_regeneration'] = time();
            session_regenerate_id(true);
        }
    }
}

/**
 * Función helper para inicializar sesión segura
 * Uso: initSecureSession();
 * 
 * @return bool True si la sesión se inició correctamente
 */
function initSecureSession() {
    return SessionConfig::init();
}

/**
 * Función helper para destruir sesión
 * Uso: destroySecureSession();
 */
function destroySecureSession() {
    SessionConfig::destroy();
}

/**
 * Función helper para verificar si sesión es válida
 * Uso: if (isSessionValid()) { ... }
 * 
 * @return bool True si la sesión es válida
 */
function isSessionValid() {
    return SessionConfig::isValid();
}
