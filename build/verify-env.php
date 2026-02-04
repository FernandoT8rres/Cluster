<?php
/**
 * VERIFICACIÓN DE VARIABLES DE ENTORNO
 * ⚠️ ELIMINAR DESPUÉS DE VERIFICAR
 */

require_once 'config/env-loader.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    EnvLoader::load();
    
    $result = [
        'status' => 'success',
        'env_loaded' => EnvLoader::isLoaded(),
        'timestamp' => date('Y-m-d H:i:s'),
        'checks' => [
            'DB_HOST' => EnvLoader::has('DB_HOST'),
            'DB_USER' => EnvLoader::has('DB_USER'),
            'DB_PASS' => EnvLoader::has('DB_PASS'),
            'DB_NAME' => EnvLoader::has('DB_NAME'),
            'JWT_SECRET' => EnvLoader::has('JWT_SECRET'),
        ],
        'values' => [
            'DB_HOST' => EnvLoader::get('DB_HOST', 'NOT SET'),
            'DB_USER' => EnvLoader::get('DB_USER', 'NOT SET'),
            'DB_NAME' => EnvLoader::get('DB_NAME', 'NOT SET'),
            'DB_PASS' => EnvLoader::has('DB_PASS') ? '****** (SET)' : 'NOT SET',
            'JWT_SECRET' => EnvLoader::has('JWT_SECRET') ? '****** (SET)' : 'NOT SET',
        ],
        'all_ok' => EnvLoader::has('DB_HOST') && 
                    EnvLoader::has('DB_USER') && 
                    EnvLoader::has('DB_PASS') && 
                    EnvLoader::has('DB_NAME') && 
                    EnvLoader::has('JWT_SECRET')
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
