<?php
/**
 * Interceptor de peticiones API para identificar cuál archivo está causando el error
 */

// Registrar la petición
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'file' => __FILE__,
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
    'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
    'query' => $_GET,
    'post' => $_POST,
    'referer' => $_SERVER['HTTP_REFERER'] ?? ''
];

file_put_contents('api_intercepts.log', json_encode($logData) . "\n", FILE_APPEND);

header('Content-Type: application/json; charset=utf-8');

// Simular el error que está ocurriendo
echo json_encode([
    'success' => false,
    'data' => null,
    'message' => 'INTERCEPTED: Error del servidor: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'nombre\' in \'ORDER BY\'',
    'intercepted_by' => 'intercept-api.php',
    'timestamp' => date('Y-m-d H:i:s'),
    'debug_info' => $logData
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>