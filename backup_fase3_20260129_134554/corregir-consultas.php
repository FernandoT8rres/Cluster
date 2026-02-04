<?php
/**
 * Script para corregir consultas SQL problemáticas
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../assets/conexion/config.php';

function sendJsonResponse($data, $success = true) {
    echo json_encode([
        'success' => $success,
        'data' => $success ? $data : null,
        'message' => $success ? 'OK' : $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $db = new DatabaseWrapper();
    $pdo = $db->getConnection();
    
    // Correcciones para las consultas problemáticas
    $correcciones = [
        // ID 1: comites - consulta simple que funcione
        1 => [
            'query_sql' => 'SELECT COUNT(*) as valor FROM comites',
            'titulo' => 'Total Comités',
            'descripcion' => 'Total de comités registrados'
        ],
        
        // ID 2: empresas - consulta simple que funcione  
        2 => [
            'query_sql' => 'SELECT COUNT(*) as valor FROM empresas_convenio',
            'titulo' => 'Total Empresas',
            'descripcion' => 'Total de empresas con convenio'
        ],
        
        // ID 3: descuentos - consulta simple que funcione
        3 => [
            'query_sql' => 'SELECT COUNT(*) as valor FROM descuentos',
            'titulo' => 'Total Descuentos',
            'descripcion' => 'Total de descuentos disponibles'
        ],
        
        // ID 4: eventos - consulta simple que funcione
        4 => [
            'query_sql' => 'SELECT COUNT(*) as valor FROM eventos',
            'titulo' => 'Total Eventos',
            'descripcion' => 'Total de eventos registrados'
        ]
    ];
    
    $corregidas = 0;
    $errores = [];
    
    foreach ($correcciones as $id => $correccion) {
        try {
            // Primero verificar si la consulta nueva funciona
            $testStmt = $pdo->prepare($correccion['query_sql']);
            $testResult = $testStmt->execute();
            
            if (!$testResult) {
                $errores[] = "ID $id: Error en consulta de prueba - " . implode(', ', $testStmt->errorInfo());
                continue;
            }
            
            // Si funciona, actualizar la configuración
            $updateStmt = $pdo->prepare("UPDATE estadisticas_config SET query_sql = ?, titulo = ?, descripcion = ? WHERE id = ?");
            $updateResult = $updateStmt->execute([
                $correccion['query_sql'], 
                $correccion['titulo'], 
                $correccion['descripcion'], 
                $id
            ]);
            
            if ($updateResult) {
                $corregidas++;
            } else {
                $errores[] = "ID $id: Error actualizando configuración";
            }
            
        } catch (Exception $e) {
            $errores[] = "ID $id: " . $e->getMessage();
        }
    }
    
    sendJsonResponse([
        'message' => 'Proceso de corrección completado',
        'consultas_corregidas' => $corregidas,
        'total_correcciones_intentadas' => count($correcciones),
        'errores' => $errores,
        'correcciones_aplicadas' => array_keys($correcciones)
    ]);
    
} catch (Exception $e) {
    error_log("Error en corregir-consultas: " . $e->getMessage());
    sendJsonResponse('Error del servidor: ' . $e->getMessage(), false);
}
?>