<?php
/**
 * Token Blacklist System
 * 
 * Sistema de blacklist para tokens JWT revocados
 * Compatible con Hostinger - Basado en archivos (no requiere Redis)
 * 
 * @version 1.0.0
 * @date 2026-01-30
 */

class TokenBlacklist {
    
    private static $storageDir = null;
    private static $cleanupInterval = 3600; // 1 hora
    
    /**
     * Inicializar directorio de almacenamiento
     */
    private static function init() {
        if (self::$storageDir === null) {
            self::$storageDir = dirname(__DIR__) . '/storage/token-blacklist/';
            
            // Crear directorio si no existe
            if (!is_dir(self::$storageDir)) {
                mkdir(self::$storageDir, 0755, true);
                
                // Crear .htaccess para proteger el directorio
                $htaccess = self::$storageDir . '.htaccess';
                if (!file_exists($htaccess)) {
                    file_put_contents($htaccess, "Deny from all\n");
                }
            }
        }
    }
    
    /**
     * Agregar token a la blacklist
     * 
     * @param string $token Token a agregar
     * @param int $expiry Timestamp de expiración del token
     * @return bool True si se agregó correctamente
     */
    public static function add($token, $expiry = null) {
        try {
            self::init();
            
            // Si no se proporciona expiry, usar 7 días por defecto
            if ($expiry === null) {
                $expiry = time() + 604800; // 7 días
            }
            
            // Generar hash del token para el nombre del archivo
            $hash = hash('sha256', $token);
            
            // Usar subdirectorios para mejor performance (primeros 2 caracteres del hash)
            $subdir = substr($hash, 0, 2);
            $subdirPath = self::$storageDir . $subdir . '/';
            
            if (!is_dir($subdirPath)) {
                mkdir($subdirPath, 0755, true);
            }
            
            // Guardar token con su expiry
            $filepath = $subdirPath . $hash . '.json';
            $data = [
                'token_hash' => $hash,
                'added_at' => time(),
                'expires_at' => $expiry
            ];
            
            file_put_contents($filepath, json_encode($data));
            
            // Ejecutar limpieza periódica
            self::autoCleanup();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Token Blacklist Error (add): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si un token está en la blacklist
     * 
     * @param string $token Token a verificar
     * @return bool True si está en blacklist
     */
    public static function isBlacklisted($token) {
        try {
            self::init();
            
            $hash = hash('sha256', $token);
            $subdir = substr($hash, 0, 2);
            $filepath = self::$storageDir . $subdir . '/' . $hash . '.json';
            
            if (!file_exists($filepath)) {
                return false;
            }
            
            // Leer datos del token
            $data = json_decode(file_get_contents($filepath), true);
            
            if (!$data) {
                return false;
            }
            
            // Verificar si ya expiró
            if (isset($data['expires_at']) && time() > $data['expires_at']) {
                // Token expirado, eliminarlo de la blacklist
                @unlink($filepath);
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Token Blacklist Error (isBlacklisted): " . $e->getMessage());
            // En caso de error, asumir que NO está en blacklist para no bloquear usuarios
            return false;
        }
    }
    
    /**
     * Eliminar token de la blacklist
     * 
     * @param string $token Token a eliminar
     * @return bool True si se eliminó correctamente
     */
    public static function remove($token) {
        try {
            self::init();
            
            $hash = hash('sha256', $token);
            $subdir = substr($hash, 0, 2);
            $filepath = self::$storageDir . $subdir . '/' . $hash . '.json';
            
            if (file_exists($filepath)) {
                return @unlink($filepath);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Token Blacklist Error (remove): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Limpiar tokens expirados de la blacklist
     * 
     * @return array Estadísticas de limpieza
     */
    public static function cleanup() {
        try {
            self::init();
            
            $stats = [
                'total_checked' => 0,
                'expired_removed' => 0,
                'errors' => 0
            ];
            
            // Recorrer todos los subdirectorios
            $subdirs = glob(self::$storageDir . '*', GLOB_ONLYDIR);
            
            foreach ($subdirs as $subdir) {
                $files = glob($subdir . '/*.json');
                
                foreach ($files as $file) {
                    $stats['total_checked']++;
                    
                    try {
                        $data = json_decode(file_get_contents($file), true);
                        
                        if ($data && isset($data['expires_at']) && time() > $data['expires_at']) {
                            if (@unlink($file)) {
                                $stats['expired_removed']++;
                            } else {
                                $stats['errors']++;
                            }
                        }
                    } catch (Exception $e) {
                        $stats['errors']++;
                        error_log("Error cleaning up token file $file: " . $e->getMessage());
                    }
                }
            }
            
            // Actualizar timestamp de última limpieza
            file_put_contents(self::$storageDir . '.last_cleanup', time());
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Token Blacklist Error (cleanup): " . $e->getMessage());
            return [
                'total_checked' => 0,
                'expired_removed' => 0,
                'errors' => 1
            ];
        }
    }
    
    /**
     * Limpieza automática periódica
     * Se ejecuta solo si ha pasado el intervalo de limpieza
     */
    private static function autoCleanup() {
        try {
            self::init();
            
            $lastCleanupFile = self::$storageDir . '.last_cleanup';
            $lastCleanup = file_exists($lastCleanupFile) ? (int)file_get_contents($lastCleanupFile) : 0;
            
            // Solo limpiar si ha pasado el intervalo
            if (time() - $lastCleanup > self::$cleanupInterval) {
                self::cleanup();
            }
            
        } catch (Exception $e) {
            error_log("Token Blacklist Error (autoCleanup): " . $e->getMessage());
        }
    }
    
    /**
     * Obtener estadísticas de la blacklist
     * 
     * @return array Estadísticas
     */
    public static function getStats() {
        try {
            self::init();
            
            $stats = [
                'total_tokens' => 0,
                'expired_tokens' => 0,
                'active_tokens' => 0,
                'subdirectories' => 0,
                'last_cleanup' => null
            ];
            
            // Contar subdirectorios
            $subdirs = glob(self::$storageDir . '*', GLOB_ONLYDIR);
            $stats['subdirectories'] = count($subdirs);
            
            // Contar tokens
            foreach ($subdirs as $subdir) {
                $files = glob($subdir . '/*.json');
                
                foreach ($files as $file) {
                    $stats['total_tokens']++;
                    
                    try {
                        $data = json_decode(file_get_contents($file), true);
                        
                        if ($data && isset($data['expires_at'])) {
                            if (time() > $data['expires_at']) {
                                $stats['expired_tokens']++;
                            } else {
                                $stats['active_tokens']++;
                            }
                        }
                    } catch (Exception $e) {
                        // Ignorar errores en archivos individuales
                    }
                }
            }
            
            // Última limpieza
            $lastCleanupFile = self::$storageDir . '.last_cleanup';
            if (file_exists($lastCleanupFile)) {
                $stats['last_cleanup'] = date('Y-m-d H:i:s', (int)file_get_contents($lastCleanupFile));
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Token Blacklist Error (getStats): " . $e->getMessage());
            return [
                'total_tokens' => 0,
                'expired_tokens' => 0,
                'active_tokens' => 0,
                'subdirectories' => 0,
                'last_cleanup' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Limpiar toda la blacklist (usar con precaución)
     * 
     * @return bool True si se limpió correctamente
     */
    public static function clear() {
        try {
            self::init();
            
            $subdirs = glob(self::$storageDir . '*', GLOB_ONLYDIR);
            
            foreach ($subdirs as $subdir) {
                $files = glob($subdir . '/*.json');
                
                foreach ($files as $file) {
                    @unlink($file);
                }
                
                @rmdir($subdir);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Token Blacklist Error (clear): " . $e->getMessage());
            return false;
        }
    }
}
