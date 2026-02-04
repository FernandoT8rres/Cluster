<?php
/**
 * CLAUT GRÁFICOS - INTEGRACIÓN CON INDEX
 * Servicios para mostrar gráficos dinámicos en el dashboard principal
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../assets/conexion/config.php';

// Inicializar conexión a la base de datos
$database = Database::getInstance();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'dashboard_stats':
            getDashboardStats($db);
            break;
        case 'chart_config':
            getChartConfig($db);
            break;
        case 'update_dashboard_chart':
            updateDashboardChart($db);
            break;
        case 'get_widget_data':
            getWidgetData($db);
            break;
        default:
            throw new Exception('Acción no válida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Obtener estadísticas para el dashboard
 */
function getDashboardStats($db) {
    try {
        $stats = [];
        
        // Estadísticas generales
        $generalStats = [
            'empresas_activas' => getCount($db, 'empresas', ['estado' => 'activo']),
            'usuarios_activos' => getCount($db, 'usuarios_perfil', ['estado' => 'activo']),
            'eventos_proximos' => getCount($db, 'eventos', ['fecha_evento' => ['>=', date('Y-m-d')]]),
            'comites_activos' => getCount($db, 'comites', ['activo' => 1])
        ];
        
        // Datos para gráficos del dashboard
        $chartData = [
            'empresas_mes' => getMonthlyData($db, 'empresas', 6),
            'usuarios_mes' => getMonthlyData($db, 'usuarios_perfil', 6),
            'eventos_mes' => getMonthlyData($db, 'eventos', 6, 'fecha_evento')
        ];
        
        // Tendencias
        $trends = [
            'empresas' => calculateTrend($chartData['empresas_mes']),
            'usuarios' => calculateTrend($chartData['usuarios_mes']),
            'eventos' => calculateTrend($chartData['eventos_mes'])
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'general' => $generalStats,
                'charts' => $chartData,
                'trends' => $trends,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error obteniendo estadísticas del dashboard: ' . $e->getMessage());
    }
}

/**
 * Obtener configuración de gráfico predeterminada para el dashboard
 */
function getChartConfig($db) {
    try {
        $stmt = $db->prepare("
            SELECT configuracion 
            FROM configuraciones_graficos 
            WHERE es_predeterminada = 1 
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $defaultConfig = [
            'tipo' => 'line',
            'color_primario' => '#3B82F6',
            'animaciones' => true,
            'mostrar_grilla' => true,
            'mostrar_tooltips' => true,
            'mostrar_leyenda' => false
        ];
        
        if ($result) {
            $config = json_decode($result['configuracion'], true);
            $defaultConfig = array_merge($defaultConfig, $config);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $defaultConfig
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error obteniendo configuración de gráfico: ' . $e->getMessage());
    }
}

/**
 * Actualizar datos de gráfico en el dashboard
 */
function updateDashboardChart($db) {
    try {
        $fuente = $_GET['fuente'] ?? 'empresas';
        $meses = intval($_GET['meses'] ?? 6);
        
        $data = getMonthlyData($db, $fuente, $meses);
        
        // Calcular estadísticas adicionales
        $valores = array_column($data, 'valor');
        $stats = [
            'total' => array_sum($valores),
            'promedio' => count($valores) > 0 ? array_sum($valores) / count($valores) : 0,
            'maximo' => count($valores) > 0 ? max($valores) : 0,
            'minimo' => count($valores) > 0 ? min($valores) : 0,
            'ultimo' => end($valores) ?: 0,
            'tendencia' => calculateTrend($data)
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'chart_data' => $data,
                'statistics' => $stats,
                'metadata' => [
                    'fuente' => $fuente,
                    'periodo_meses' => $meses,
                    'total_puntos' => count($data),
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error actualizando gráfico del dashboard: ' . $e->getMessage());
    }
}

/**
 * Obtener datos para widgets específicos
 */
function getWidgetData($db) {
    try {
        $widget = $_GET['widget'] ?? '';
        
        switch ($widget) {
            case 'empresas_recientes':
                $data = getRecentEmpresas($db);
                break;
            case 'eventos_proximos':
                $data = getUpcomingEvents($db);
                break;
            case 'usuarios_nuevos':
                $data = getRecentUsers($db);
                break;
            case 'comites_resumen':
                $data = getComitesResumen($db);
                break;
            default:
                throw new Exception('Widget no válido');
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'widget' => $widget,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error obteniendo datos del widget: ' . $e->getMessage());
    }
}

/**
 * Funciones auxiliares
 */

function getCount($db, $table, $conditions = []) {
    $sql = "SELECT COUNT(*) as total FROM $table";
    $params = [];
    
    if (!empty($conditions)) {
        $sql .= " WHERE ";
        $whereClauses = [];
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $operator = $value[0];
                $val = $value[1];
                $whereClauses[] = "$field $operator ?";
                $params[] = $val;
            } else {
                $whereClauses[] = "$field = ?";
                $params[] = $value;
            }
        }
        
        $sql .= implode(" AND ", $whereClauses);
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($result['total']);
    } catch (Exception $e) {
        return 0;
    }
}

function getMonthlyData($db, $table, $months = 6, $dateField = 'fecha_registro') {
    $sql = "
        SELECT 
            DATE_FORMAT($dateField, '%Y-%m') as periodo,
            DATE_FORMAT($dateField, '%b %Y') as label,
            COUNT(*) as valor
        FROM $table 
        WHERE $dateField >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT($dateField, '%Y-%m')
        ORDER BY periodo ASC
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$months]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Rellenar meses faltantes
        return fillMissingMonths($data, $months);
        
    } catch (Exception $e) {
        return generateEmptyMonths($months);
    }
}

function fillMissingMonths($data, $months) {
    $result = [];
    $existingData = [];
    
    // Indexar datos existentes por período
    foreach ($data as $item) {
        $existingData[$item['periodo']] = $item;
    }
    
    // Generar todos los meses del período
    for ($i = $months - 1; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $label = date('M Y', strtotime("-$i months"));
        
        if (isset($existingData[$date])) {
            $result[] = $existingData[$date];
        } else {
            $result[] = [
                'periodo' => $date,
                'label' => $label,
                'valor' => 0
            ];
        }
    }
    
    return $result;
}

function generateEmptyMonths($months) {
    $result = [];
    
    for ($i = $months - 1; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $label = date('M Y', strtotime("-$i months"));
        
        $result[] = [
            'periodo' => $date,
            'label' => $label,
            'valor' => 0
        ];
    }
    
    return $result;
}

function calculateTrend($data) {
    if (count($data) < 2) {
        return ['direction' => 'neutral', 'percentage' => 0];
    }
    
    $values = array_column($data, 'valor');
    $firstValue = reset($values);
    $lastValue = end($values);
    
    if ($firstValue == 0) {
        return ['direction' => 'neutral', 'percentage' => 0];
    }
    
    $percentage = (($lastValue - $firstValue) / $firstValue) * 100;
    
    $direction = 'neutral';
    if ($percentage > 0.1) {
        $direction = 'up';
    } elseif ($percentage < -0.1) {
        $direction = 'down';
    }
    
    return [
        'direction' => $direction,
        'percentage' => round(abs($percentage), 1),
        'change' => $lastValue - $firstValue
    ];
}

function getRecentEmpresas($db, $limit = 5) {
    $sql = "
        SELECT id, nombre, sector, fecha_registro 
        FROM empresas 
        WHERE estado = 'activo'
        ORDER BY fecha_registro DESC 
        LIMIT ?
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getUpcomingEvents($db, $limit = 5) {
    $sql = "
        SELECT id, titulo, fecha_evento, tipo 
        FROM eventos 
        WHERE fecha_evento >= CURDATE()
        ORDER BY fecha_evento ASC 
        LIMIT ?
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getRecentUsers($db, $limit = 5) {
    $sql = "
        SELECT id, nombre, apellido, email, fecha_registro 
        FROM usuarios_perfil 
        WHERE estado = 'activo'
        ORDER BY fecha_registro DESC 
        LIMIT ?
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function getComitesResumen($db) {
    $sql = "
        SELECT 
            c.id,
            c.nombre,
            COUNT(cm.usuario_id) as miembros
        FROM comites c
        LEFT JOIN comite_miembros cm ON c.id = cm.comite_id
        WHERE c.activo = 1
        GROUP BY c.id, c.nombre
        ORDER BY miembros DESC
        LIMIT 5
    ";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}
?>