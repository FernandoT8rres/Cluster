<?php
/**
 * Environment Variables Loader
 * Carga variables de entorno desde archivo .env
 */

class EnvLoader {
    private static $loaded = false;
    private static $variables = [];
    
    /**
     * Cargar variables de entorno desde archivo .env
     */
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        if ($path === null) {
            $path = dirname(__DIR__) . '/.env';
        }
        
        // Si no existe .env, intentar cargar desde valores por defecto
        if (!file_exists($path)) {
            error_log("⚠️ Archivo .env no encontrado en: $path");
            error_log("ℹ️ Usando valores por defecto o variables de entorno del sistema");
            self::$loaded = true;
            return;
        }
        
        try {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Ignorar comentarios
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parsear línea KEY=VALUE
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    
                    // Remover comillas si existen
                    if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                        $value = $matches[2];
                    }
                    
                    // Guardar en array interno
                    self::$variables[$name] = $value;
                    
                    // Setear en $_ENV y putenv()
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
            }
            
            self::$loaded = true;
            error_log("✅ Variables de entorno cargadas desde: $path");
            
        } catch (Exception $e) {
            error_log("❌ Error cargando .env: " . $e->getMessage());
            throw new Exception("Error cargando archivo .env: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener variable de entorno con valor por defecto
     */
    public static function get($key, $default = null) {
        // Prioridad: 1. Array interno, 2. $_ENV, 3. getenv(), 4. Default
        if (isset(self::$variables[$key])) {
            return self::$variables[$key];
        }
        
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }
    
    /**
     * Obtener variable requerida (lanza excepción si no existe)
     */
    public static function required($key) {
        $value = self::get($key);
        
        if ($value === null || $value === '') {
            throw new Exception("Variable de entorno requerida no encontrada: $key");
        }
        
        return $value;
    }
    
    /**
     * Verificar si una variable existe
     */
    public static function has($key) {
        return self::get($key) !== null;
    }
    
    /**
     * Obtener todas las variables cargadas
     */
    public static function all() {
        return self::$variables;
    }
    
    /**
     * Verificar si .env fue cargado
     */
    public static function isLoaded() {
        return self::$loaded;
    }
}
?>
