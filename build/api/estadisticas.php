<?php
/**
 * API de estadísticas
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
    // Test simple first
    error_log("Estadisticas API: Starting...");

    $db = Database::getInstance();
    error_log("Estadisticas API: Database connection successful");

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        jsonError('Método no permitido', 405);
    }

    // Test simple stat first
    $estadisticas = [];

    try {
        $estadisticas['usuarios'] = getUsuariosStats($db);
        error_log("Estadisticas API: Usuarios stats loaded");
    } catch (Exception $e) {
        error_log("Error usuarios stats: " . $e->getMessage());
        $estadisticas['usuarios'] = ['total' => 0, 'error' => $e->getMessage()];
    }

    try {
        $estadisticas['empresas'] = getEmpresasStats($db);
        error_log("Estadisticas API: Empresas stats loaded");
    } catch (Exception $e) {
        error_log("Error empresas stats: " . $e->getMessage());
        $estadisticas['empresas'] = ['total' => 0, 'error' => $e->getMessage()];
    }

    try {
        $estadisticas['comites'] = getComitesStats($db);
        error_log("Estadisticas API: Comites stats loaded");
    } catch (Exception $e) {
        error_log("Error comites stats: " . $e->getMessage());
        $estadisticas['comites'] = ['total' => 0, 'error' => $e->getMessage()];
    }

    // Simple stats for now
    $estadisticas['descuentos'] = ['total' => 0];
    $estadisticas['eventos'] = ['total' => 0];
    $estadisticas['boletines'] = ['total' => 0];

    error_log("Estadisticas API: Sending response");
    jsonResponse($estadisticas);

} catch (Exception $e) {
    error_log("Error en estadísticas API: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    jsonError('Error interno del servidor: ' . $e->getMessage(), 500);
}

function getComitesStats($db) {
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM comites");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $result ? $result['count'] : 0;

        return [
            'total' => (int)$total,
            'total_miembros' => 0,
            'promedio_miembros' => 0
        ];
    } catch (Exception $e) {
        error_log("Error in getComitesStats: " . $e->getMessage());
        return [
            'total' => 0,
            'total_miembros' => 0,
            'promedio_miembros' => 0
        ];
    }
}

function getEmpresasStats($db) {
    try {
        $conn = $db->getConnection();

        // Count active companies
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM empresas_convenio WHERE activo = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $result ? $result['count'] : 0;
        error_log("EmpresasStats: Total empresas activas = " . $total);

        // Count users with companies
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios_perfil WHERE empresa_id IS NOT NULL AND empresa_id != 0");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalUsuarios = $result ? $result['count'] : 0;
        error_log("EmpresasStats: Total usuarios con empresa = " . $totalUsuarios);

        // Get top companies by participation
        $stmt = $conn->prepare("
            SELECT ec.nombre, COUNT(up.id) as empleados
            FROM empresas_convenio ec
            LEFT JOIN usuarios_perfil up ON ec.id = up.empresa_id
            WHERE ec.activo = 1
            GROUP BY ec.id, ec.nombre
            HAVING empleados > 0
            ORDER BY empleados DESC
            LIMIT 5
        ");
        $stmt->execute();
        $topEmpresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("EmpresasStats: Top empresas encontradas = " . count($topEmpresas));

        $promedio = $total > 0 ? round($totalUsuarios / $total, 1) : 0;

        return [
            'total' => (int)$total,
            'total_empleados' => (int)$totalUsuarios,
            'promedio_empleados' => $promedio,
            'top_participacion' => $topEmpresas
        ];
    } catch (Exception $e) {
        error_log("Error in getEmpresasStats: " . $e->getMessage());
        return [
            'total' => 0,
            'total_empleados' => 0,
            'promedio_empleados' => 0,
            'top_participacion' => []
        ];
    }
}

function getDescuentosStats($db) {
    $total = $db->selectOne("SELECT COUNT(*) as count FROM descuentos WHERE estado = 'activo'")['count'];
    $proximosVencer = $db->selectOne("
        SELECT COUNT(*) as count 
        FROM descuentos 
        WHERE estado = 'activo' 
        AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ")['count'];
    
    return [
        'total' => (int)$total,
        'proximos_vencer' => (int)$proximosVencer,
        'descuento_promedio' => $db->selectOne("SELECT AVG(porcentaje_descuento) as promedio FROM descuentos WHERE estado = 'activo'")['promedio'] ?? 0
    ];
}

function getEventosStats($db) {
    $total = $db->selectOne("SELECT COUNT(*) as count FROM eventos WHERE estado = 'programado'")['count'];
    $esteMes = $db->selectOne("
        SELECT COUNT(*) as count 
        FROM eventos 
        WHERE estado = 'programado' 
        AND MONTH(fecha_inicio) = MONTH(CURDATE()) 
        AND YEAR(fecha_inicio) = YEAR(CURDATE())
    ")['count'];
    
    $proximosEventos = $db->select("
        SELECT titulo, fecha_inicio, ubicacion
        FROM eventos
        WHERE estado = 'programado' AND fecha_inicio >= NOW()
        ORDER BY fecha_inicio ASC
        LIMIT 3
    ");
    
    return [
        'total' => (int)$total,
        'este_mes' => (int)$esteMes,
        'proximos' => $proximosEventos
    ];
}

function getUsuariosStats($db) {
    try {
        $conn = $db->getConnection();

        // Count total users
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios_perfil");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $result ? $result['count'] : 0;
        error_log("UsuariosStats: Total usuarios = " . $total);

        // Count by role
        $stmt = $conn->prepare("SELECT rol, COUNT(*) as count FROM usuarios_perfil GROUP BY rol");
        $stmt->execute();
        $porRol = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rolStats = [];
        foreach ($porRol as $rol) {
            $rolStats[$rol['rol']] = (int)$rol['count'];
            error_log("UsuariosStats: Rol " . $rol['rol'] . " = " . $rol['count']);
        }

        // Count new users this month
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios_perfil WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nuevosMes = $result ? $result['count'] : 0;
        error_log("UsuariosStats: Nuevos este mes = " . $nuevosMes);

        return [
            'total' => (int)$total,
            'por_rol' => $rolStats,
            'nuevos_mes' => (int)$nuevosMes
        ];
    } catch (Exception $e) {
        error_log("Error in getUsuariosStats: " . $e->getMessage());
        return [
            'total' => 0,
            'por_rol' => [],
            'nuevos_mes' => 0
        ];
    }
}

function getBoletinesStats($db) {
    $total = $db->selectOne("SELECT COUNT(*) as count FROM boletines WHERE estado = 'publicado'")['count'];
    $esteMes = $db->selectOne("
        SELECT COUNT(*) as count 
        FROM boletines 
        WHERE estado = 'publicado' 
        AND MONTH(fecha_publicacion) = MONTH(CURDATE()) 
        AND YEAR(fecha_publicacion) = YEAR(CURDATE())
    ")['count'];
    
    return [
        'total' => (int)$total,
        'este_mes' => (int)$esteMes,
        'borradores' => (int)$db->selectOne("SELECT COUNT(*) as count FROM boletines WHERE estado = 'borrador'")['count']
    ];
}
?>