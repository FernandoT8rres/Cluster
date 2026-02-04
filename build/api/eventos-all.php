<?php
/**
 * API específica para obtener TODOS los eventos sin afectar autenticación
 */

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuración independiente para eventos
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=u695712029_claut_intranet;charset=utf8mb4",
        'u695712029_claut_fer',
        'CLAUT@admin_fernando!7',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30,
        ]
    );

    // Consulta SIN filtros para obtener TODOS los eventos
    $sql = "SELECT * FROM eventos ORDER BY fecha_inicio ASC";
    $stmt = $pdo->query($sql);
    $eventos = $stmt->fetchAll();

    // Procesar eventos
    $eventosProcessed = [];
    foreach ($eventos as $evento) {
        $fechaInicio = new DateTime($evento['fecha_inicio']);
        $now = new DateTime();
        $estadoCalculado = $fechaInicio > $now ? 'proximo' : 'en_curso';

        $eventosProcessed[] = [
            'id' => (int)$evento['id'],
            'titulo' => $evento['titulo'],
            'descripcion' => $evento['descripcion'],
            'fecha_inicio' => $evento['fecha_inicio'],
            'fecha_fin' => $evento['fecha_fin'],
            'fecha_formateada' => $fechaInicio->format('d/m/Y H:i'),
            'fecha_corta' => $fechaInicio->format('d M Y'),
            'ubicacion' => $evento['ubicacion'],
            'capacidad_maxima' => (int)$evento['capacidad_maxima'],
            'capacidad_actual' => (int)$evento['capacidad_actual'],
            'tipo' => $evento['tipo'],
            'modalidad' => $evento['modalidad'],
            'estado' => $evento['estado'],
            'estado_calculado' => $estadoCalculado,
            'precio' => (float)$evento['precio'],
            'precio_formateado' => $evento['precio'] > 0 ? '$' . number_format($evento['precio'], 2) : 'Gratuito',
            'imagen' => $evento['imagen'] ?: './assets/img/evento-default.jpg'
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Todos los eventos obtenidos correctamente',
        'data' => [
            'eventos' => $eventosProcessed,
            'total_eventos' => count($eventosProcessed)
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error en api/eventos-all.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener eventos',
        'error' => $e->getMessage()
    ]);
}
?>