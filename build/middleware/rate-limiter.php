<?php
/**
 * Rate Limiter Middleware
 * 
 * Sistema de rate limiting basado en archivos para prevenir:
 * - Ataques de fuerza bruta
 * - Abuso de APIs
 * - Spam de formularios
 * 
 * Compatible con Hostinger (no requiere Redis)
 * 
 * @package ClautIntranet
 * @subpackage Middleware
 * @version 1.0.0
 */

class RateLimiter {
    
    /**
     * Directorio para almacenar datos de rate limiting
     */
    private $storageDir;
    
    /**
     * Tiempo de expiración de registros antiguos (segundos)
     */
    private $cleanupAge = 86400; // 24 horas
    
    /**
     * Última vez que se ejecutó limpieza
     */
    private $lastCleanupFile;
    
    /**
     * Constructor
     * 
     * @param string $storageDir Directorio para almacenar datos
     */
    public function __construct($storageDir = null) {
        if ($storageDir === null) {
            $storageDir = __DIR__ . '/../storage/rate-limit';
        }
        
        $this->storageDir = $storageDir;
        $this->lastCleanupFile = $this->storageDir . '/.last_cleanup';
        
        // Crear directorio si no existe
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
            
            // Crear .htaccess para proteger el directorio
            $htaccess = $this->storageDir . '/.htaccess';
            if (!file_exists($htaccess)) {
                file_put_contents($htaccess, "Deny from all\n");
            }
        }
        
        // Ejecutar limpieza periódica (cada hora)
        $this->periodicCleanup();
    }
    
    /**
     * Verificar si se ha excedido el límite de intentos
     * 
     * @param string $identifier Identificador único (IP, email, user_id, etc.)
     * @param int $maxAttempts Número máximo de intentos permitidos
     * @param int $windowSeconds Ventana de tiempo en segundos
     * @param string $action Acción específica (login, register, api_call, etc.)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
     */
    public function checkLimit($identifier, $maxAttempts = 5, $windowSeconds = 300, $action = 'default') {
        $key = $this->generateKey($identifier, $action);
        $file = $this->getFilePath($key);
        $now = time();
        
        // Leer intentos existentes
        $attempts = $this->readAttempts($file);
        
        // Filtrar intentos dentro de la ventana de tiempo
        $validAttempts = array_filter($attempts, function($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        });
        
        $attemptCount = count($validAttempts);
        $allowed = $attemptCount < $maxAttempts;
        $remaining = max(0, $maxAttempts - $attemptCount);
        
        // Calcular cuándo se resetea el límite
        $resetAt = $now + $windowSeconds;
        if (!empty($validAttempts)) {
            $oldestAttempt = min($validAttempts);
            $resetAt = $oldestAttempt + $windowSeconds;
        }
        
        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
            'retry_after' => $allowed ? 0 : ($resetAt - $now)
        ];
    }
    
    /**
     * Registrar un intento
     * 
     * @param string $identifier Identificador único
     * @param string $action Acción específica
     * @return bool
     */
    public function recordAttempt($identifier, $action = 'default') {
        $key = $this->generateKey($identifier, $action);
        $file = $this->getFilePath($key);
        $now = time();
        
        // Leer intentos existentes
        $attempts = $this->readAttempts($file);
        
        // Agregar nuevo intento
        $attempts[] = $now;
        
        // Guardar intentos
        return $this->writeAttempts($file, $attempts);
    }
    
    /**
     * Resetear intentos para un identificador
     * 
     * @param string $identifier Identificador único
     * @param string $action Acción específica
     * @return bool
     */
    public function reset($identifier, $action = 'default') {
        $key = $this->generateKey($identifier, $action);
        $file = $this->getFilePath($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    /**
     * Middleware para proteger endpoints
     * 
     * @param string $identifier Identificador único
     * @param int $maxAttempts Número máximo de intentos
     * @param int $windowSeconds Ventana de tiempo
     * @param string $action Acción específica
     * @return void (envía respuesta 429 si se excede el límite)
     */
    public function protect($identifier, $maxAttempts = 5, $windowSeconds = 300, $action = 'default') {
        $result = $this->checkLimit($identifier, $maxAttempts, $windowSeconds, $action);
        
        if (!$result['allowed']) {
            $this->sendRateLimitResponse($result);
        }
        
        // Registrar intento
        $this->recordAttempt($identifier, $action);
    }
    
    /**
     * Obtener información de rate limit sin registrar intento
     * 
     * @param string $identifier Identificador único
     * @param int $maxAttempts Número máximo de intentos
     * @param int $windowSeconds Ventana de tiempo
     * @param string $action Acción específica
     * @return array
     */
    public function getStatus($identifier, $maxAttempts = 5, $windowSeconds = 300, $action = 'default') {
        return $this->checkLimit($identifier, $maxAttempts, $windowSeconds, $action);
    }
    
    /**
     * Generar clave única para el almacenamiento
     * 
     * @param string $identifier Identificador
     * @param string $action Acción
     * @return string
     */
    private function generateKey($identifier, $action) {
        return hash('sha256', $identifier . ':' . $action);
    }
    
    /**
     * Obtener ruta del archivo para una clave
     * 
     * @param string $key Clave
     * @return string
     */
    private function getFilePath($key) {
        // Usar subdirectorios para evitar demasiados archivos en un directorio
        $subdir = substr($key, 0, 2);
        $dir = $this->storageDir . '/' . $subdir;
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/' . $key . '.json';
    }
    
    /**
     * Leer intentos desde archivo
     * 
     * @param string $file Ruta del archivo
     * @return array
     */
    private function readAttempts($file) {
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }
        
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return [];
        }
        
        return $data;
    }
    
    /**
     * Escribir intentos a archivo
     * 
     * @param string $file Ruta del archivo
     * @param array $attempts Intentos
     * @return bool
     */
    private function writeAttempts($file, $attempts) {
        $content = json_encode($attempts, JSON_PRETTY_PRINT);
        return file_put_contents($file, $content, LOCK_EX) !== false;
    }
    
    /**
     * Enviar respuesta de rate limit excedido
     * 
     * @param array $result Resultado del check
     * @return void
     */
    private function sendRateLimitResponse($result) {
        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: ' . $result['retry_after']);
        header('X-RateLimit-Limit: ' . $result['remaining']);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . $result['reset_at']);
        
        echo json_encode([
            'success' => false,
            'error' => 'Too many requests',
            'message' => 'Has excedido el límite de intentos. Por favor, intenta de nuevo en ' . $result['retry_after'] . ' segundos.',
            'retry_after' => $result['retry_after'],
            'reset_at' => $result['reset_at']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        exit;
    }
    
    /**
     * Limpieza periódica de archivos antiguos
     * 
     * @return void
     */
    private function periodicCleanup() {
        // Verificar si es necesario limpiar (cada hora)
        if (file_exists($this->lastCleanupFile)) {
            $lastCleanup = (int)file_get_contents($this->lastCleanupFile);
            if ((time() - $lastCleanup) < 3600) {
                return; // No es necesario limpiar aún
            }
        }
        
        // Ejecutar limpieza
        $this->cleanup();
        
        // Actualizar timestamp de última limpieza
        file_put_contents($this->lastCleanupFile, time());
    }
    
    /**
     * Limpiar archivos antiguos
     * 
     * @return int Número de archivos eliminados
     */
    public function cleanup() {
        $deleted = 0;
        $now = time();
        
        // Buscar todos los archivos JSON en el directorio
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->storageDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                // Verificar si el archivo es antiguo
                if (($now - $file->getMTime()) > $this->cleanupAge) {
                    if (unlink($file->getPathname())) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Obtener estadísticas de rate limiting
     * 
     * @return array
     */
    public function getStats() {
        $totalFiles = 0;
        $totalSize = 0;
        
        if (!is_dir($this->storageDir)) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'storage_dir' => $this->storageDir
            ];
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->storageDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'json') {
                $totalFiles++;
                $totalSize += $file->getSize();
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'storage_dir' => $this->storageDir
        ];
    }
}

/**
 * Helper function para obtener identificador del cliente
 * 
 * @return string IP del cliente
 */
function getRateLimitIdentifier() {
    // Obtener IP real del cliente (considerando proxies)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Verificar headers de proxy
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    
    return $ip;
}

/**
 * Configuraciones predefinidas de rate limiting
 */
class RateLimitConfig {
    const LOGIN = ['max' => 5, 'window' => 300, 'action' => 'login']; // 5 intentos / 5 min
    const REGISTER = ['max' => 3, 'window' => 3600, 'action' => 'register']; // 3 registros / hora
    const PASSWORD_RESET = ['max' => 3, 'window' => 3600, 'action' => 'password_reset']; // 3 intentos / hora
    const API_PUBLIC = ['max' => 100, 'window' => 60, 'action' => 'api_public']; // 100 req / min
    const API_PRIVATE = ['max' => 300, 'window' => 60, 'action' => 'api_private']; // 300 req / min
    const FILE_UPLOAD = ['max' => 10, 'window' => 3600, 'action' => 'file_upload']; // 10 uploads / hora
    const CONTACT_FORM = ['max' => 5, 'window' => 3600, 'action' => 'contact']; // 5 envíos / hora
}
?>
