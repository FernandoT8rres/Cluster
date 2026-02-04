<?php
/**
 * API para obtener eventos - Versión optimizada para servidor remoto
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

try {
    // Configuración de base de datos remota
    $host = 'clautmetropolitano.mx';
    $username = 'u695712029_claut_fer';
    $password = 'CLAUT@admin_fernando!7';
    $database = 'u695712029_claut_intranet';

    // Establecer conexión PDO
    $pdo = new PDO(
        "mysql:host={$host};port=3306;dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 30,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]
    );

    // Log conexión exitosa
    error_log("✅ Conexión MySQL exitosa a servidor remoto");

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
        WHERE e.estado IN ('programado', 'en_curso', 'activo')
        ORDER BY e.fecha_inicio ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar los eventos para agregar información adicional
    $eventosProcessed = [];

    foreach ($eventos as $evento) {
        $fechaInicio = new DateTime($evento['fecha_inicio']);
        $fechaFin = $evento['fecha_fin'] ? new DateTime($evento['fecha_fin']) : null;
        $now = new DateTime();

        // Calcular estado dinámicamente
        $estadoCalculado = 'proximo';
        if ($fechaInicio <= $now) {
            if ($fechaFin && $fechaFin < $now) {
                $estadoCalculado = 'finalizado';
            } else {
                $estadoCalculado = 'en_curso';
            }
        }

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
        'message' => 'Eventos obtenidos correctamente desde servidor remoto [' . date('Y-m-d H:i:s') . ']',
        'data' => [
            'eventos' => $eventosProcessed,
            'estadisticas' => $stats,
            'total_eventos' => count($eventosProcessed)
        ],
        'environment' => [
            'database_type' => 'mysql_remote',
            'host' => $host,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("get_eventos_remote.php - Error PDO: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos remota',
        'error' => 'Error de conexión: ' . $e->getMessage(),
        'error_code' => 'DB_CONNECTION_ERROR'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("get_eventos_remote.php - Error general: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => 'Error inesperado: ' . $e->getMessage(),
        'error_code' => 'INTERNAL_ERROR'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>