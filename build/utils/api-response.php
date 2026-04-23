<?php
/**
 * API Response Helper — Claut Intranet
 * 
 * Estandariza todas las respuestas JSON del sistema.
 * Asegura códigos HTTP correctos y una estructura de datos consistente.
 */

class ApiResponse {
    
    /**
     * Envía una respuesta de éxito (2xx)
     * 
     * @param mixed $data Datos a enviar
     * @param string $message Mensaje opcional
     * @param int $code Código HTTP (default 200)
     */
    public static function success($data = null, $message = 'Operación exitosa', $code = 200, $extra = []) {
        $response = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'timestamp' => date('c')
        ];
        
        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }
        
        self::send($response, $code);
    }
    
    /**
     * Envía una respuesta de error (4xx - 5xx)
     * 
     * @param string $message Mensaje de error
     * @param int $code Código HTTP (default 400)
     * @param array $extra Datos adicionales de error (opcional)
     */
    public static function error($message = 'Ha ocurrido un error', $code = 400, $extra = []) {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }
        
        self::send($response, $code);
    }
    
    /**
     * Método base para envío de respuesta
     */
    private static function send($response, $code) {
        // Limpiar cualquier buffer de salida previo para evitar JSON corrompido
        if (ob_get_level()) {
            ob_clean();
        }
        
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        
        // El header de CORS ya lo maneja .htaccess o el middleware dedicado,
        // pero aquí reforzamos la protección si fuera necesario en el futuro.
        
        // Cerrar escritura de sesión con seguridad antes de emitir respuesta JSON
        // Esto es un fix crítico para Hostinger LiteSpeed y evita redirections continuas al login
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
