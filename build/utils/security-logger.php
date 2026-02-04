<?php
/**
 * Security Logger
 * Registro de eventos de seguridad
 * 
 * Uso:
 * require_once 'utils/security-logger.php';
 * SecurityLogger::log('failed_login', 'WARNING', ['email' => $email]);
 */

class SecurityLogger {
    private static $logDir = __DIR__ . '/../logs/security/';
    private static $logFile = 'security.log';
    private static $alertEmail = 'admin@claut.com';
    
    /**
     * Registra un evento de seguridad
     * @param string $event Nombre del evento
     * @param string $severity Severidad: INFO, WARNING, CRITICAL
     * @param array $details Detalles adicionales
     */
    public static function log($event, $severity = 'INFO', array $details = []) {
        // Crear directorio de logs si no existe
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0750, true);
        }
        
        // Preparar entrada de log
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'severity' => $severity,
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_email' => $_SESSION['user_data']['email'] ?? null,
            'details' => $details
        ];
        
        // Escribir en archivo de log
        $logLine = json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        $logPath = self::$logDir . self::$logFile;
        
        file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
        
        // Rotar logs si es muy grande (> 10MB)
        if (file_exists($logPath) && filesize($logPath) > 10485760) {
            self::rotateLogs();
        }
        
        // Enviar alerta si es crítico
        if ($severity === 'CRITICAL') {
            self::sendAlert($entry);
        }
    }
    
    /**
     * Obtiene la IP real del cliente
     * @return string IP del cliente
     */
    private static function getClientIP() {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Rota los archivos de log
     */
    private static function rotateLogs() {
        $logPath = self::$logDir . self::$logFile;
        $timestamp = date('Y-m-d_H-i-s');
        $archivePath = self::$logDir . "security_{$timestamp}.log";
        
        rename($logPath, $archivePath);
        
        // Comprimir archivo antiguo
        if (function_exists('gzopen')) {
            $gzPath = $archivePath . '.gz';
            $gz = gzopen($gzPath, 'wb9');
            gzwrite($gz, file_get_contents($archivePath));
            gzclose($gz);
            unlink($archivePath);
        }
        
        // Eliminar logs antiguos (más de 30 días)
        $files = glob(self::$logDir . 'security_*.log*');
        $now = time();
        foreach ($files as $file) {
            if ($now - filemtime($file) > 30 * 24 * 3600) {
                unlink($file);
            }
        }
    }
    
    /**
     * Envía alerta por email
     * @param array $entry Entrada de log
     */
    private static function sendAlert($entry) {
        $subject = '[CLAUT SECURITY ALERT] ' . $entry['event'];
        $message = "Se ha detectado un evento de seguridad crítico:\n\n";
        $message .= "Evento: {$entry['event']}\n";
        $message .= "Fecha: {$entry['timestamp']}\n";
        $message .= "IP: {$entry['ip']}\n";
        $message .= "Usuario: " . ($entry['user_email'] ?? 'No autenticado') . "\n";
        $message .= "URI: {$entry['request_uri']}\n";
        $message .= "Detalles: " . json_encode($entry['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        $headers = "From: security@claut.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        @mail(self::$alertEmail, $subject, $message, $headers);
    }
    
    /**
     * Métodos de conveniencia para eventos comunes
     */
    
    public static function logFailedLogin($email, $reason = '') {
        self::log('failed_login', 'WARNING', [
            'email' => $email,
            'reason' => $reason
        ]);
    }
    
    public static function logSuccessfulLogin($userId, $email) {
        self::log('successful_login', 'INFO', [
            'user_id' => $userId,
            'email' => $email
        ]);
    }
    
    public static function logUnauthorizedAccess($resource) {
        self::log('unauthorized_access', 'CRITICAL', [
            'resource' => $resource
        ]);
    }
    
    public static function logCSRFViolation() {
        self::log('csrf_violation', 'CRITICAL', [
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
        ]);
    }
    
    public static function logSuspiciousFileUpload($filename, $reason) {
        self::log('suspicious_file_upload', 'CRITICAL', [
            'filename' => $filename,
            'reason' => $reason
        ]);
    }
    
    public static function logRateLimitExceeded($identifier) {
        self::log('rate_limit_exceeded', 'WARNING', [
            'identifier' => $identifier
        ]);
    }
}
?>
