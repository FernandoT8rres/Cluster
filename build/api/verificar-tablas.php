<?php
/**
 * Script para verificar qué tablas existen en la base de datos
 * y validar las consultas de estadísticas
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

function sendJsonResponse($data, $success = true) {
    $response = [
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($success) {
        $response = array_merge($response, $data);
    } else {
        $response['message'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Obtener todas las tablas de la base de datos (compatible con MySQL y SQLite)
    if ($db->isUsingSQLite()) {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $stmt = $pdo->query("SHOW TABLES");
        $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    $informacion_tablas = [];
    
    foreach ($tablas as $tabla) {
        try {
            // Contar registros en cada tabla
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `$tabla`");
            $count = $countStmt->fetchColumn();
            
            // Obtener estructura de la tabla
            if ($db->isUsingSQLite()) {
                $structStmt = $pdo->query("PRAGMA table_info(`$tabla`)");
                $estructura = $structStmt->fetchAll();
                $columnas = array_column($estructura, 'name');
            } else {
                $structStmt = $pdo->query("DESCRIBE `$tabla`");
                $estructura = $structStmt->fetchAll();
                $columnas = array_column($estructura, 'Field');
            }
            
            $informacion_tablas[$tabla] = [
                'nombre' => $tabla,
                'registros' => $count,
                'columnas' => $columnas,
                'estructura' => $estructura
            ];
            
        } catch (Exception $e) {
            $informacion_tablas[$tabla] = [
                'nombre' => $tabla,
                'registros' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Probar las consultas predeterminadas actuales
    $consultas_predeterminadas = [
        'comites' => 'SELECT COALESCE(COUNT(*), 0) as valor FROM comites WHERE activo = 1',
        'empresas' => 'SELECT COALESCE(COUNT(*), 0) as valor FROM empresas_convenio WHERE activo = 1', 
        'descuentos' => 'SELECT COALESCE(COUNT(*), 0) as valor FROM descuentos WHERE activo = 1 AND fecha_vencimiento > NOW()',
        'eventos' => 'SELECT COALESCE(COUNT(*), 0) as valor FROM eventos WHERE activo = 1 AND fecha_evento > NOW()'
    ];
    
    $resultados_consultas = [];
    
    foreach ($consultas_predeterminadas as $nombre => $consulta) {
        try {
            $stmt = $pdo->query($consulta);
            $resultado = $stmt->fetchColumn();
            $resultados_consultas[$nombre] = [
                'consulta' => $consulta,
                'resultado' => $resultado,
                'estado' => 'exitosa'
            ];
        } catch (Exception $e) {
            $resultados_consultas[$nombre] = [
                'consulta' => $consulta,
                'resultado' => null,
                'estado' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Sugerir consultas alternativas basadas en las tablas que sí existen
    $sugerencias = [];
    
    // Buscar tablas que podrían contener datos útiles
    foreach ($tablas as $tabla) {
        if ($informacion_tablas[$tabla]['registros'] > 0) {
            $sugerencias[] = [
                'tabla' => $tabla,
                'registros' => $informacion_tablas[$tabla]['registros'],
                'consulta_sugerida' => "SELECT COUNT(*) as valor FROM `$tabla`",
                'titulo_sugerido' => "Total " . ucfirst(str_replace('_', ' ', $tabla))
            ];
        }
    }
    
    sendJsonResponse([
        'tablas_disponibles' => $tablas,
        'informacion_tablas' => $informacion_tablas,
        'resultados_consultas' => $resultados_consultas,
        'sugerencias' => $sugerencias,
        'total_tablas' => count($tablas)
    ]);
    
} catch (Exception $e) {
    error_log("Error verificando tablas: " . $e->getMessage());
    sendJsonResponse('Error verificando base de datos: ' . $e->getMessage(), false);
}
?>