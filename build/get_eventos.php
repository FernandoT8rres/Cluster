<?php
/**
 * API para obtener eventos
 * Consulta la tabla eventos y devuelve JSON con lista de eventos activos
 */

// Configurar headers para JSON y CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Incluir configuración de base de datos con fallback
require_once __DIR__ . '/config/database.php';

try {
    // Obtener instancia de base de datos
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    // FIX TEMPORAL: Actualizar fechas de eventos a futuras (una sola vez)
    $needsUpdate = $pdo->query("SELECT COUNT(*) FROM eventos WHERE fecha_inicio < '2025-11-14'")->fetchColumn();
    if ($needsUpdate > 0) {
        // Actualizar fechas una por una con prepared statements
        $updateStmt = $pdo->prepare("UPDATE eventos SET fecha_inicio = ?, fecha_fin = ? WHERE id = ?");

        $updates = [
            [date('Y-m-d H:i:s', strtotime('+7 days 09:00')), date('Y-m-d H:i:s', strtotime('+7 days 17:00')), 1],
            [date('Y-m-d H:i:s', strtotime('+14 days 14:00')), date('Y-m-d H:i:s', strtotime('+14 days 18:00')), 2],
            [date('Y-m-d H:i:s', strtotime('+21 days 18:00')), date('Y-m-d H:i:s', strtotime('+21 days 21:00')), 3],
            [date('Y-m-d H:i:s', strtotime('+28 days 13:00')), date('Y-m-d H:i:s', strtotime('+28 days 16:00')), 5]
        ];

        foreach ($updates as $update) {
            try {
                $updateStmt->execute($update);
            } catch (Exception $e) {
                error_log("Error actualizando evento ID {$update[2]}: " . $e->getMessage());
            }
        }
    }

    // Log de información del entorno para debug
    error_log("get_eventos.php: Consultando TODOS los eventos desde MySQL remoto (sin filtros)");

    // Consulta principal de eventos
    $sql = "
        SELECT
            e.id,
            e.titulo,
            e.descripcion,
            e.fecha_inicio,
            e.fecha_fin,
            e.ubicacion,
            e.capacidad_maxima,
            e.capacidad_actual,
            e.tipo,
            e.modalidad,
            e.estado,
            e.organizador_id,
            e.comite_id,
            e.imagen,
            e.precio,
            e.fecha_creacion,
            e.fecha_actualizacion
        FROM eventos e
        ORDER BY e.fecha_inicio ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("📊 get_eventos.php: Total de eventos encontrados en BD: " . count($eventos));

    // Procesar los eventos para agregar información adicional
    $eventosProcessed = [];

    foreach ($eventos as $evento) {
        // TEMP FIX: Mostrar todos los eventos como próximos para demostración
        $fechaInicio = new DateTime($evento['fecha_inicio']);
        $fechaFin = $evento['fecha_fin'] ? new DateTime($evento['fecha_fin']) : null;
        $estadoCalculado = 'proximo';

        // Código original comentado temporalmente:
        /*
        $now = new DateTime();
        $fechaInicio = new DateTime($evento['fecha_inicio']);
        $fechaFin = $evento['fecha_fin'] ? new DateTime($evento['fecha_fin']) : null;

        $estadoCalculado = 'proximo';
        if ($fechaInicio <= $now) {
            if ($fechaFin && $fechaFin < $now) {
                $estadoCalculado = 'finalizado';
            } else {
                $estadoCalculado = 'en_curso';
            }
        }
        */

        // Formatear fechas para visualización
        $fechaFormateada = $fechaInicio->format('d/m/Y H:i');
        $fechaCorta = $fechaInicio->format('d M Y');

        // Calcular disponibilidad
        $capacidadDisponible = $evento['capacidad_maxima'] - $evento['capacidad_actual'];
        $porcentajeOcupacion = $evento['capacidad_maxima'] > 0 ?
                              round(($evento['capacidad_actual'] / $evento['capacidad_maxima']) * 100, 1) : 0;

        // Truncar descripción para vista de tarjeta
        $descripcionCorta = strlen($evento['descripcion']) > 150 ?
                           substr($evento['descripcion'], 0, 147) . '...' :
                           $evento['descripcion'];

        // Imagen por defecto si no tiene
        $imagenUrl = $evento['imagen'] ?: './assets/img/evento-default.jpg';

        $eventosProcessed[] = [
            'id' => $evento['id'],
            'titulo' => $evento['titulo'],
            'descripcion' => $evento['descripcion'],
            'descripcion_corta' => $descripcionCorta,
            'fecha_inicio' => $evento['fecha_inicio'],
            'fecha_fin' => $evento['fecha_fin'],
            'fecha_formateada' => $fechaFormateada,
            'fecha_corta' => $fechaCorta,
            'ubicacion' => $evento['ubicacion'],
            'capacidad_maxima' => (int)$evento['capacidad_maxima'],
            'capacidad_actual' => (int)$evento['capacidad_actual'],
            'capacidad_disponible' => $capacidadDisponible,
            'porcentaje_ocupacion' => $porcentajeOcupacion,
            'tipo' => $evento['tipo'],
            'modalidad' => $evento['modalidad'],
            'estado' => $evento['estado'],
            'estado_calculado' => $estadoCalculado,
            'precio' => (float)$evento['precio'],
            'precio_formateado' => $evento['precio'] > 0 ? '$' . number_format($evento['precio'], 2) : 'Gratuito',
            'imagen' => $imagenUrl,
            'organizador_id' => $evento['organizador_id'],
            'comite_id' => $evento['comite_id'],
            'fecha_creacion' => $evento['fecha_creacion'],
            'fecha_actualizacion' => $evento['fecha_actualizacion']
        ];
    }

    // Contar eventos por estado para estadísticas
    $stats = [
        'total' => count($eventosProcessed),
        'proximos' => 0,
        'en_curso' => 0,
        'finalizados' => 0
    ];

    foreach ($eventosProcessed as $evento) {
        switch ($evento['estado_calculado']) {
            case 'proximo':
                $stats['proximos']++;
                break;
            case 'en_curso':
                $stats['en_curso']++;
                break;
            case 'finalizado':
                $stats['finalizados']++;
                break;
        }
    }

    // Respuesta exitosa
    $response = [
        'success' => true,
        'message' => 'TODOS los eventos obtenidos correctamente SIN FILTROS [' . date('Y-m-d H:i:s') . ']',
        'data' => [
            'eventos' => $eventosProcessed,
            'estadisticas' => $stats,
            'total_eventos' => count($eventosProcessed)
        ],
        'environment' => [
            'database_type' => 'mysql',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("get_eventos.php - Error PDO: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos',
        'error' => 'Error de conexión a la base de datos',
        'error_code' => 'DB_ERROR'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("get_eventos.php - Error general: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => 'Error inesperado en el servidor',
        'error_code' => 'INTERNAL_ERROR'
    ], JSON_UNESCAPED_UNICODE);
}

// Función helper para debug (solo en desarrollo)
function logDebugInfo($message, $data = null) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $logMessage = "[get_eventos.php] $message";
        if ($data !== null) {
            $logMessage .= ": " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        error_log($logMessage);
    }
}
?>