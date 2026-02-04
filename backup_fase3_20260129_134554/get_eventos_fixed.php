<?php
/**
 * API para obtener eventos - Versión corregida sin filtros
 * Configurada para obtener TODOS los eventos de la tabla 'eventos'
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

// Configuración de base de datos
class EventosDatabase {
    private $connection;

    public function __construct() {
        $host = '127.0.0.1';
        $username = 'u695712029_claut_fer';
        $password = 'CLAUT@admin_fernando!7';
        $database = 'u695712029_claut_intranet';

        try {
            $this->connection = new PDO(
                "mysql:host={$host};dbname={$database};charset=utf8mb4",
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
            error_log("✅ Conexión exitosa a BD remota: {$database}");
        } catch (PDOException $e) {
            error_log("❌ Error conexión BD: " . $e->getMessage());
            throw new Exception("Error de conexión a base de datos: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->connection;
    }
}

try {
    // Obtener instancia de base de datos
    $database = new EventosDatabase();
    $pdo = $database->getConnection();

    // Log información del entorno para debug
    error_log("get_eventos_fixed.php: Consultando TODOS los eventos desde BD remota MySQL");

    // CONSULTA PRINCIPAL SIN FILTROS - Obtener TODOS los eventos
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

    error_log("📊 Total de eventos encontrados: " . count($eventos));

    // Procesar los eventos para agregar información adicional
    $eventosProcessed = [];

    foreach ($eventos as $evento) {
        $fechaInicio = new DateTime($evento['fecha_inicio']);
        $fechaFin = $evento['fecha_fin'] ? new DateTime($evento['fecha_fin']) : null;
        $now = new DateTime();

        // Calcular estado dinámicamente basado en fechas
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
        $capacidadMaxima = (int)($evento['capacidad_maxima'] ?? 0);
        $capacidadActual = (int)($evento['capacidad_actual'] ?? 0);
        $capacidadDisponible = max(0, $capacidadMaxima - $capacidadActual);
        $porcentajeOcupacion = $capacidadMaxima > 0 ?
                              round(($capacidadActual / $capacidadMaxima) * 100, 1) : 0;

        // Truncar descripción para vista de tarjeta
        $descripcion = $evento['descripcion'] ?? '';
        $descripcionCorta = strlen($descripcion) > 150 ?
                           substr($descripcion, 0, 147) . '...' :
                           $descripcion;

        // Imagen por defecto si no tiene
        $imagenUrl = $evento['imagen'] ?: './assets/img/evento-default.jpg';

        // Precio formateado
        $precio = (float)($evento['precio'] ?? 0);
        $precioFormateado = $precio > 0 ? '$' . number_format($precio, 2) : 'Gratuito';

        $eventosProcessed[] = [
            'id' => (int)$evento['id'],
            'titulo' => $evento['titulo'] ?? 'Sin título',
            'descripcion' => $descripcion,
            'descripcion_corta' => $descripcionCorta,
            'fecha_inicio' => $evento['fecha_inicio'],
            'fecha_fin' => $evento['fecha_fin'],
            'fecha_formateada' => $fechaFormateada,
            'fecha_corta' => $fechaCorta,
            'ubicacion' => $evento['ubicacion'] ?? 'Sin ubicación',
            'capacidad_maxima' => $capacidadMaxima,
            'capacidad_actual' => $capacidadActual,
            'capacidad_disponible' => $capacidadDisponible,
            'porcentaje_ocupacion' => $porcentajeOcupacion,
            'tipo' => $evento['tipo'] ?? 'Evento',
            'modalidad' => $evento['modalidad'] ?? 'Presencial',
            'estado' => $evento['estado'] ?? 'activo',
            'estado_calculado' => $estadoCalculado,
            'precio' => $precio,
            'precio_formateado' => $precioFormateado,
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

    // Log estadísticas
    error_log("📈 Estadísticas eventos: " . json_encode($stats));

    // Respuesta exitosa
    $response = [
        'success' => true,
        'message' => 'TODOS los eventos obtenidos correctamente desde BD remota [' . date('Y-m-d H:i:s') . ']',
        'data' => [
            'eventos' => $eventosProcessed,
            'estadisticas' => $stats,
            'total_eventos' => count($eventosProcessed)
        ],
        'environment' => [
            'database_type' => 'mysql_remote',
            'query_type' => 'sin_filtros',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("get_eventos_fixed.php - Error PDO: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos',
        'error' => 'Error de conexión a la base de datos remota',
        'error_details' => $e->getMessage(),
        'error_code' => 'DB_ERROR'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("get_eventos_fixed.php - Error general: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => 'Error inesperado en el servidor',
        'error_details' => $e->getMessage(),
        'error_code' => 'INTERNAL_ERROR'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// Función helper para debug (solo en desarrollo)
function logDebugInfo($message, $data = null) {
    $logMessage = "[get_eventos_fixed.php] $message";
    if ($data !== null) {
        $logMessage .= ": " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($logMessage);
}
?>