<?php
/**
 * API para obtener opciones disponibles para estadísticas dinámicas
 * Analiza la base de datos y proporciona opciones predefinidas
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

require_once '../assets/conexion/config.php';

// Función para respuesta JSON
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
    // Conectar a la base de datos
    $db = new DatabaseWrapper();
    $pdo = $db->getConnection();
    $dbInstance = Database::getInstance();
    
    $action = $_GET['action'] ?? 'all';
    
    // Obtener todas las tablas de la base de datos
    $tablesQuery = "SHOW TABLES";
    $tablesResult = $pdo->query($tablesQuery);
    $tables = $tablesResult->fetchAll(PDO::FETCH_COLUMN);
    
    // Filtrar tablas relevantes para estadísticas
    $relevantTables = [];
    $excludeTables = ['migrations', 'sessions', 'password_resets', 'failed_jobs', 'estadisticas_config'];
    
    foreach ($tables as $table) {
        if (!in_array($table, $excludeTables)) {
            $relevantTables[] = $table;
        }
    }
    
    switch ($action) {
        case 'all':
            // Obtener información completa de todas las opciones
            $opciones = [];
            
            foreach ($relevantTables as $table) {
                $tableInfo = getTableInfo($pdo, $table);
                if ($tableInfo) {
                    $opciones[$table] = $tableInfo;
                }
            }
            
            sendJsonResponse([
                'tablas_disponibles' => count($opciones),
                'opciones' => $opciones
            ]);
            break;
            
        case 'templates':
            // Obtener plantillas predefinidas de estadísticas
            $templates = getStatisticTemplates($pdo, $relevantTables);
            sendJsonResponse([
                'templates' => $templates,
                'total' => count($templates)
            ]);
            break;
            
        case 'table':
            // Obtener información específica de una tabla
            $tableName = $_GET['table'] ?? '';
            if (empty($tableName)) {
                sendJsonResponse('Nombre de tabla requerido', false);
            }
            
            $tableInfo = getDetailedTableInfo($pdo, $tableName);
            sendJsonResponse([
                'tabla' => $tableName,
                'info' => $tableInfo
            ]);
            break;
            
        default:
            sendJsonResponse('Acción no válida', false);
    }
    
} catch (PDOException $e) {
    error_log("Error en estadísticas-opciones (PDO): " . $e->getMessage());
    sendJsonResponse('Error de base de datos: ' . $e->getMessage(), false);
} catch (Exception $e) {
    error_log("Error general en estadísticas-opciones: " . $e->getMessage());
    sendJsonResponse('Error del servidor: ' . $e->getMessage(), false);
}

// Obtener información básica de una tabla
function getTableInfo($pdo, $tableName) {
    try {
        // Obtener estructura de la tabla
        $columnsQuery = "DESCRIBE `$tableName`";
        $columns = $pdo->query($columnsQuery)->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener conteo total
        $countQuery = "SELECT COUNT(*) as total FROM `$tableName`";
        $total = $pdo->query($countQuery)->fetchColumn();
        
        // Identificar columnas relevantes
        $relevantColumns = [];
        $statusColumns = [];
        $dateColumns = [];
        
        foreach ($columns as $column) {
            $name = $column['Field'];
            $type = strtolower($column['Type']);
            
            // Columnas de estado
            if (in_array($name, ['activo', 'estado', 'status', 'publicado', 'visible'])) {
                $statusColumns[] = $name;
            }
            
            // Columnas de fecha
            if (strpos($type, 'date') !== false || strpos($type, 'timestamp') !== false) {
                $dateColumns[] = $name;
            }
            
            $relevantColumns[] = [
                'nombre' => $name,
                'tipo' => $type,
                'es_estado' => in_array($name, ['activo', 'estado', 'status', 'publicado', 'visible']),
                'es_fecha' => strpos($type, 'date') !== false || strpos($type, 'timestamp') !== false
            ];
        }
        
        return [
            'total_registros' => $total,
            'columnas' => $relevantColumns,
            'columnas_estado' => $statusColumns,
            'columnas_fecha' => $dateColumns,
            'tiene_datos' => $total > 0
        ];
        
    } catch (Exception $e) {
        return null;
    }
}

// Obtener información detallada de una tabla
function getDetailedTableInfo($pdo, $tableName) {
    try {
        $info = getTableInfo($pdo, $tableName);
        if (!$info) return null;
        
        // Obtener valores únicos de columnas de estado
        $statusValues = [];
        foreach ($info['columnas_estado'] as $statusCol) {
            try {
                $query = "SELECT DISTINCT `$statusCol` as valor, COUNT(*) as cantidad FROM `$tableName` WHERE `$statusCol` IS NOT NULL GROUP BY `$statusCol` ORDER BY cantidad DESC";
                $values = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
                $statusValues[$statusCol] = $values;
            } catch (Exception $e) {
                $statusValues[$statusCol] = [];
            }
        }
        
        // Obtener rango de fechas
        $dateRanges = [];
        foreach ($info['columnas_fecha'] as $dateCol) {
            try {
                $query = "SELECT MIN(`$dateCol`) as fecha_min, MAX(`$dateCol`) as fecha_max, COUNT(*) as total FROM `$tableName` WHERE `$dateCol` IS NOT NULL";
                $range = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
                $dateRanges[$dateCol] = $range;
            } catch (Exception $e) {
                $dateRanges[$dateCol] = null;
            }
        }
        
        $info['valores_estado'] = $statusValues;
        $info['rangos_fecha'] = $dateRanges;
        
        return $info;
        
    } catch (Exception $e) {
        return null;
    }
}

// Generar plantillas predefinidas de estadísticas
function getStatisticTemplates($pdo, $tables) {
    $templates = [];
    
    foreach ($tables as $table) {
        $info = getTableInfo($pdo, $table);
        if (!$info || !$info['tiene_datos']) continue;
        
        $tableName = ucfirst(str_replace('_', ' ', $table));
        
        // Template básico: Total de registros
        $templates[] = [
            'id' => 'total_' . $table,
            'titulo' => "Total de $tableName",
            'descripcion' => "Cantidad total de registros en $tableName",
            'icono' => getTableIcon($table),
            'color' => getTableColor($table),
            'tabla' => $table,
            'tipo' => 'total',
            'query_generada' => "SELECT COUNT(*) as valor FROM `$table`",
            'formato' => 'number'
        ];
        
        // Templates con filtros de estado
        foreach ($info['columnas_estado'] as $statusCol) {
            // Activos
            $templates[] = [
                'id' => $table . '_' . $statusCol . '_activos',
                'titulo' => "$tableName Activos",
                'descripcion' => "Cantidad de $tableName con estado activo",
                'icono' => getTableIcon($table),
                'color' => 'green',
                'tabla' => $table,
                'tipo' => 'estado_activo',
                'query_generada' => "SELECT COUNT(*) as valor FROM `$table` WHERE `$statusCol` = 1 OR `$statusCol` = 'activo' OR `$statusCol` = 'publicado'",
                'formato' => 'number'
            ];
            
            // Inactivos
            $templates[] = [
                'id' => $table . '_' . $statusCol . '_inactivos',
                'titulo' => "$tableName Inactivos",
                'descripcion' => "Cantidad de $tableName con estado inactivo",
                'icono' => getTableIcon($table),
                'color' => 'red',
                'tabla' => $table,
                'tipo' => 'estado_inactivo',
                'query_generada' => "SELECT COUNT(*) as valor FROM `$table` WHERE `$statusCol` = 0 OR `$statusCol` = 'inactivo' OR `$statusCol` = 'borrador'",
                'formato' => 'number'
            ];
        }
        
        // Templates con filtros de fecha
        foreach ($info['columnas_fecha'] as $dateCol) {
            // Últimos 7 días
            $templates[] = [
                'id' => $table . '_' . $dateCol . '_7dias',
                'titulo' => "$tableName (Últimos 7 días)",
                'descripcion' => "Registros en $tableName de los últimos 7 días",
                'icono' => getTableIcon($table),
                'color' => 'blue',
                'tabla' => $table,
                'tipo' => 'fecha_reciente',
                'query_generada' => "SELECT COUNT(*) as valor FROM `$table` WHERE `$dateCol` >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                'formato' => 'number'
            ];
            
            // Este mes
            $templates[] = [
                'id' => $table . '_' . $dateCol . '_mes',
                'titulo' => "$tableName (Este mes)",
                'descripcion' => "Registros en $tableName del mes actual",
                'icono' => getTableIcon($table),
                'color' => 'purple',
                'tabla' => $table,
                'tipo' => 'fecha_mes',
                'query_generada' => "SELECT COUNT(*) as valor FROM `$table` WHERE MONTH(`$dateCol`) = MONTH(NOW()) AND YEAR(`$dateCol`) = YEAR(NOW())",
                'formato' => 'number'
            ];
        }
    }
    
    return $templates;
}

// Obtener icono apropiado para una tabla
function getTableIcon($table) {
    $icons = [
        'usuarios' => 'fas fa-users',
        'empresas' => 'fas fa-building',
        'empresas_convenio' => 'fas fa-handshake',
        'banners' => 'fas fa-images',
        'boletines' => 'fas fa-newspaper',
        'eventos' => 'fas fa-calendar-alt',
        'comites' => 'fas fa-users-cog',
        'descuentos' => 'fas fa-tags',
        'productos' => 'fas fa-box',
        'servicios' => 'fas fa-cogs',
        'contactos' => 'fas fa-address-book',
        'mensajes' => 'fas fa-envelope',
        'comentarios' => 'fas fa-comments',
        'categorias' => 'fas fa-folder',
        'archivos' => 'fas fa-file',
        'configuracion' => 'fas fa-cog'
    ];
    
    return $icons[$table] ?? 'fas fa-database';
}

// Obtener color apropiado para una tabla
function getTableColor($table) {
    $colors = [
        'usuarios' => 'blue',
        'empresas' => 'green',
        'empresas_convenio' => 'green',
        'banners' => 'purple',
        'boletines' => 'orange',
        'eventos' => 'red',
        'comites' => 'blue',
        'descuentos' => 'orange',
        'productos' => 'green',
        'servicios' => 'blue',
        'contactos' => 'purple',
        'mensajes' => 'blue',
        'comentarios' => 'green',
        'categorias' => 'orange',
        'archivos' => 'purple',
        'configuracion' => 'red'
    ];
    
    return $colors[$table] ?? 'blue';
}
?>