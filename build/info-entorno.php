<?php
/**
 * Información del entorno de base de datos
 */

require_once __DIR__ . '/assets/conexion/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    $info = $db->getEnvironmentInfo();
    
    // Información adicional del servidor
    $serverInfo = [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ];
    
    // Probar conexión
    $connection_test = false;
    $test_error = null;
    
    try {
        $pdo = $db->getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM empresas_convenio");
        $total_empresas = $stmt->fetch()['total'];
        $connection_test = true;
    } catch (Exception $e) {
        $test_error = $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'environment' => $info,
        'server' => $serverInfo,
        'database_test' => [
            'connection_ok' => $connection_test,
            'total_empresas' => $total_empresas ?? 0,
            'error' => $test_error
        ],
        'recommendations' => [
            'local_dev' => $info['using_sqlite'] ? 'Usando SQLite para desarrollo local - perfecto para pruebas' : 'Conectado a base de datos remota desde entorno local',
            'production' => $info['using_remote'] ? 'Usando base de datos MySQL remota - configuración de producción' : 'Usando SQLite - considera cambiar a MySQL en producción'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>