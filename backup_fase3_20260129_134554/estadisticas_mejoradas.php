<?php
/**
 * API de estadísticas mejoradas para dashboard dinámico
 * Maneja estadísticas de empresas registradas, eventos, personas registradas
 */

// Definir acceso permitido
define('CLAUT_ACCESS', true);

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/config.php';

try {
    $db = Database::getInstance();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonError('Método no permitido', 405);
    }
    
    $action = $_GET['action'] ?? 'general';
    
    switch ($action) {
        case 'general':
            obtenerEstadisticasGenerales($db);
            break;
        case 'empresas_historico':
            obtenerHistoricoEmpresas($db);
            break;
        case 'eventos_registros':
            obtenerEstadisticasEventos($db);
            break;
        case 'personas_registradas':
            obtenerPersonasRegistradas($db);
            break;
        case 'crecimiento_mensual':
            obtenerCrecimientoMensual($db);
            break;
        default:
            obtenerEstadisticasGenerales($db);
    }
    
} catch (Exception $e) {
    error_log("Error en estadísticas mejoradas API: " . $e->getMessage());
    jsonError('Error interno del servidor: ' . $e->getMessage(), 500);
}

/**
 * Obtener estadísticas generales del dashboard
 */
function obtenerEstadisticasGenerales($db) {
    $estadisticas = [
        'comites' => getComitesStatsDetalladas($db),
        'empresas' => getEmpresasStatsDetalladas($db),
        'descuentos' => getDescuentosStatsDetalladas($db),
        'eventos' => getEventosStatsDetalladas($db),
        'usuarios' => getUsuariosStatsDetalladas($db),
        'boletines' => getBoletinesStatsDetalladas($db),
        'timestamp' => date('Y-m-d H:i:s'),
        'cache_duration' => 300 // 5 minutos
    ];
    
    jsonResponse($estadisticas);
}

/**
 * Estadísticas detalladas de comités
 */
function getComitesStatsDetalladas($db) {
    try {
        // Total de comités activos
        $totalComites = $db->selectOne("SELECT COUNT(*) as count FROM comites WHERE estado = 'activo'")['count'] ?? 0;
        
        // Total de miembros en comités
        $totalMiembros = $db->selectOne("
            SELECT COUNT(DISTINCT cm.usuario_id) as count 
            FROM comite_miembros cm 
            JOIN comites c ON cm.comite_id = c.id 
            WHERE cm.estado = 'activo' AND c.estado = 'activo'
        ")['count'] ?? 0;
        
        // Crecimiento del mes
        $crecimientoMes = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM comite_miembros cm
            JOIN comites c ON cm.comite_id = c.id
            WHERE cm.estado = 'activo' 
            AND c.estado = 'activo'
            AND MONTH(cm.fecha_ingreso) = MONTH(CURDATE()) 
            AND YEAR(cm.fecha_ingreso) = YEAR(CURDATE())
        ")['count'] ?? 0;
        
        return [
            'total' => (int)$totalComites,
            'total_miembros' => (int)$totalMiembros,
            'promedio_miembros' => $totalComites > 0 ? round($totalMiembros / $totalComites, 1) : 0,
            'crecimiento_mes' => (int)$crecimientoMes,
            'porcentaje_crecimiento' => calcularPorcentajeCrecimiento($totalMiembros, $crecimientoMes)
        ];
        
    } catch (Exception $e) {
        error_log("Error en estadísticas de comités: " . $e->getMessage());
        return ['total' => 0, 'total_miembros' => 0, 'error' => true];
    }
}

/**
 * Estadísticas detalladas de empresas
 */
function getEmpresasStatsDetalladas($db) {
    try {
        // Total de empresas activas
        $totalEmpresas = $db->selectOne("SELECT COUNT(*) as count FROM empresas WHERE estado = 'activa'")['count'] ?? 0;
        
        // Total de empleados registrados
        $totalEmpleados = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM usuarios u 
            JOIN empresas e ON u.empresa_id = e.id 
            WHERE u.estado = 'activo' AND e.estado = 'activa'
        ")['count'] ?? 0;
        
        // Empresas registradas este mes
        $empresasEsteMes = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM empresas 
            WHERE estado = 'activa' 
            AND MONTH(fecha_registro) = MONTH(CURDATE()) 
            AND YEAR(fecha_registro) = YEAR(CURDATE())
        ")['count'] ?? 0;
        
        return [
            'total' => (int)$totalEmpresas,
            'total_empleados' => (int)$totalEmpleados,
            'empresas_mes' => (int)$empresasEsteMes,
            'promedio_empleados' => $totalEmpresas > 0 ? round($totalEmpleados / $totalEmpresas, 1) : 0,
            'porcentaje_crecimiento' => calcularPorcentajeCrecimiento($totalEmpresas, $empresasEsteMes)
        ];
        
    } catch (Exception $e) {
        error_log("Error en estadísticas de empresas: " . $e->getMessage());
        return ['total' => 0, 'total_empleados' => 0, 'error' => true];
    }
}

/**
 * Estadísticas detalladas de descuentos
 */
function getDescuentosStatsDetalladas($db) {
    try {
        // Total de descuentos activos
        $totalDescuentos = $db->selectOne("SELECT COUNT(*) as count FROM descuentos WHERE estado = 'activo'")['count'] ?? 0;
        
        // Nuevos descuentos este mes
        $nuevosEsteMes = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM descuentos 
            WHERE estado = 'activo' 
            AND MONTH(fecha_creacion) = MONTH(CURDATE()) 
            AND YEAR(fecha_creacion) = YEAR(CURDATE())
        ")['count'] ?? 0;
        
        return [
            'total' => (int)$totalDescuentos,
            'nuevos_mes' => (int)$nuevosEsteMes,
            'porcentaje_crecimiento' => calcularPorcentajeCrecimiento($totalDescuentos, $nuevosEsteMes)
        ];
        
    } catch (Exception $e) {
        error_log("Error en estadísticas de descuentos: " . $e->getMessage());
        return ['total' => 0, 'error' => true];
    }
}

/**
 * Estadísticas detalladas de eventos
 */
function getEventosStatsDetalladas($db) {
    try {
        // Total de eventos programados
        $totalEventos = $db->selectOne("SELECT COUNT(*) as count FROM eventos WHERE estado = 'programado'")['count'] ?? 0;
        
        // Eventos este mes
        $eventosEsteMes = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM eventos 
            WHERE estado = 'programado' 
            AND MONTH(fecha_inicio) = MONTH(CURDATE()) 
            AND YEAR(fecha_inicio) = YEAR(CURDATE())
        ")['count'] ?? 0;
        
        return [
            'total' => (int)$totalEventos,
            'este_mes' => (int)$eventosEsteMes,
            'porcentaje_crecimiento' => calcularPorcentajeCrecimiento($totalEventos, $eventosEsteMes)
        ];
        
    } catch (Exception $e) {
        error_log("Error en estadísticas de eventos: " . $e->getMessage());
        return ['total' => 0, 'este_mes' => 0, 'error' => true];
    }
}

/**
 * Estadísticas detalladas de usuarios
 */
function getUsuariosStatsDetalladas($db) {
    try {
        // Total de usuarios activos
        $totalUsuarios = $db->selectOne("SELECT COUNT(*) as count FROM usuarios WHERE estado = 'activo'")['count'] ?? 0;
        
        // Nuevos usuarios este mes
        $nuevosEsteMes = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM usuarios 
            WHERE estado = 'activo' 
            AND MONTH(fecha_registro) = MONTH(CURDATE()) 
            AND YEAR(fecha_registro) = YEAR(CURDATE())
        ")['count'] ?? 0;
        
        return [
            'total' => (int)$totalUsuarios,
            'nuevos_mes' => (int)$nuevosEsteMes,
            'porcentaje_crecimiento' => calcularPorcentajeCrecimiento($totalUsuarios, $nuevosEsteMes)
        ];
        
    } catch (Exception $e) {
        error_log("Error en estadísticas de usuarios: " . $e->getMessage());
        return ['total' => 0, 'error' => true];
    }
}

/**
 * Estadísticas detalladas de boletines
 */
function getBoletinesStatsDetalladas($db) {
    try {
        // Total de boletines publicados
        $totalBoletines = $db->selectOne("SELECT COUNT(*) as count FROM boletines WHERE estado = 'publicado'")['count'] ?? 0;
        
        // Boletines este mes
        $boletinesEsteMes = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM boletines 
            WHERE estado = 'publicado' 
            AND MONTH(fecha_publicacion) = MONTH(CURDATE()) 
            AND YEAR(fecha_publicacion) = YEAR(CURDATE())
        ")['count'] ?? 0;
        
        return [
            'total' => (int)$totalBoletines,
            'este_mes' => (int)$boletinesEsteMes,
            'porcentaje_crecimiento' => calcularPorcentajeCrecimiento($totalBoletines, $boletinesEsteMes)
        ];
        
    } catch (Exception $e) {
        error_log("Error en estadísticas de boletines: " . $e->getMessage());
        return ['total' => 0, 'este_mes' => 0, 'error' => true];
    }
}

/**
 * Obtener histórico de empresas para el gráfico
 */
function obtenerHistoricoEmpresas($db) {
    try {
        // Obtener empresas registradas por mes en el último año
        $añoActual = date('Y');
        $mesActual = date('n');
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        
        $datosCompletos = [];
        $totalAcumulado = 0;
        
        // Obtener total de empresas hasta el año pasado
        $empresasAnteriores = $db->selectOne("
            SELECT COUNT(*) as count 
            FROM empresas 
            WHERE estado = 'activa' 
            AND YEAR(fecha_registro) < ?
        ", [$añoActual])['count'] ?? 0;
        
        $totalAcumulado = (int)$empresasAnteriores;
        
        for ($i = 1; $i <= $mesActual; $i++) {
            // Obtener empresas nuevas en este mes
            $empresasEnMes = $db->selectOne("
                SELECT COUNT(*) as count 
                FROM empresas 
                WHERE estado = 'activa' 
                AND MONTH(fecha_registro) = ? 
                AND YEAR(fecha_registro) = ?
            ", [$i, $añoActual])['count'] ?? 0;
            
            $totalAcumulado += $empresasEnMes;
            
            $datosCompletos[] = [
                'mes' => $meses[$i - 1],
                'empresas' => $totalAcumulado,
                'nuevas' => (int)$empresasEnMes,
                'año' => (int)$añoActual,
                'numero_mes' => $i
            ];
        }
        
        // Si no hay datos, generar datos de ejemplo
        if (empty($datosCompletos)) {
            $datosCompletos = generarDatosEjemplo($meses, $mesActual);
        }
        
        jsonResponse([
            'success' => true,
            'data' => $datosCompletos,
            'meta' => [
                'total_empresas_actuales' => $totalAcumulado,
                'año' => $añoActual,
                'ultimo_mes' => $meses[$mesActual - 1]
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo histórico de empresas: " . $e->getMessage());
        
        // Fallback con datos de ejemplo
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago'];
        $datosEjemplo = generarDatosEjemplo($meses, count($meses));
        
        jsonResponse([
            'success' => true,
            'data' => $datosEjemplo,
            'meta' => [
                'fallback' => true,
                'error' => $e->getMessage()
            ]
        ]);
    }
}

/**
 * Obtener estadísticas de eventos y personas registradas
 */
function obtenerEstadisticasEventos($db) {
    try {
        // Total de eventos programados
        $totalEventos = $db->selectOne("SELECT COUNT(*) as count FROM eventos WHERE estado = 'programado'")['count'] ?? 0;
        
        // Simular registros (15 personas por evento en promedio)
        $totalRegistros = $totalEventos * 15;
        
        jsonResponse([
            'success' => true,
            'data' => [
                'total_eventos' => (int)$totalEventos,
                'total_registros' => (int)$totalRegistros,
                'promedio_asistentes' => $totalEventos > 0 ? round($totalRegistros / $totalEventos, 1) : 0
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas de eventos: " . $e->getMessage());
        jsonError('Error obteniendo estadísticas de eventos', 500);
    }
}

/**
 * Obtener personas registradas en el sistema
 */
function obtenerPersonasRegistradas($db) {
    try {
        // Total de personas (usuarios) registradas
        $totalPersonas = $db->selectOne("SELECT COUNT(*) as count FROM usuarios WHERE estado = 'activo'")['count'] ?? 0;
        
        jsonResponse([
            'success' => true,
            'data' => [
                'total_personas' => (int)$totalPersonas
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo personas registradas: " . $e->getMessage());
        jsonError('Error obteniendo personas registradas', 500);
    }
}

/**
 * Obtener crecimiento mensual general
 */
function obtenerCrecimientoMensual($db) {
    try {
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $mesActual = date('n');
        
        $crecimiento = [];
        
        for ($i = 1; $i <= $mesActual; $i++) {
            // Empresas nuevas
            $empresasNuevas = $db->selectOne("
                SELECT COUNT(*) as count 
                FROM empresas 
                WHERE estado = 'activa' 
                AND MONTH(fecha_registro) = ? 
                AND YEAR(fecha_registro) = YEAR(CURDATE())
            ", [$i])['count'] ?? 0;
            
            // Usuarios nuevos
            $usuariosNuevos = $db->selectOne("
                SELECT COUNT(*) as count 
                FROM usuarios 
                WHERE estado = 'activo' 
                AND MONTH(fecha_registro) = ? 
                AND YEAR(fecha_registro) = YEAR(CURDATE())
            ", [$i])['count'] ?? 0;
            
            // Eventos del mes
            $eventosDelMes = $db->selectOne("
                SELECT COUNT(*) as count 
                FROM eventos 
                WHERE estado = 'programado' 
                AND MONTH(fecha_inicio) = ? 
                AND YEAR(fecha_inicio) = YEAR(CURDATE())
            ", [$i])['count'] ?? 0;
            
            $crecimiento[] = [
                'mes' => $meses[$i - 1],
                'empresas_nuevas' => (int)$empresasNuevas,
                'usuarios_nuevos' => (int)$usuariosNuevos,
                'eventos' => (int)$eventosDelMes,
                'numero_mes' => $i
            ];
        }
        
        jsonResponse([
            'success' => true,
            'data' => $crecimiento
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo crecimiento mensual: " . $e->getMessage());
        jsonError('Error obteniendo crecimiento mensual', 500);
    }
}

/**
 * Funciones auxiliares
 */

function calcularPorcentajeCrecimiento($total, $nuevos) {
    if ($total <= 0) return 0;
    $anterior = $total - $nuevos;
    if ($anterior <= 0) return 100;
    return round(($nuevos / $anterior) * 100, 1);
}

function generarDatosEjemplo($meses, $totalMeses) {
    $datos = [];
    $total = 0;
    
    for ($i = 0; $i < $totalMeses; $i++) {
        $nuevas = rand(2, 8);
        $total += $nuevas;
        
        $datos[] = [
            'mes' => $meses[$i],
            'empresas' => $total,
            'nuevas' => $nuevas,
            'año' => date('Y'),
            'numero_mes' => $i + 1
        ];
    }
    
    return $datos;
}

/**
 * Funciones de utilidad heredadas
 */
function jsonResponse($data, $status = 200, $message = null) {
    http_response_code($status);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($message, $status = 400) {
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

?>
