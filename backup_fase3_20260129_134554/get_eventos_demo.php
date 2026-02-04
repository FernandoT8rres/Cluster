<?php
/**
 * API temporal con datos de demostración para eventos
 * Usar mientras se resuelve el problema de persistencia en base de datos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Datos de eventos de demostración
    $eventosDemo = [
        [
            'id' => 1,
            'titulo' => 'Conferencia Automotriz CLAUT 2024',
            'descripcion' => 'Evento anual más importante del sector automotriz mexicano. Presentación de nuevas tecnologías, tendencias del mercado y networking con los principales actores de la industria.',
            'descripcion_corta' => 'Evento anual más importante del sector automotriz mexicano.',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+15 days 09:00')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+15 days 18:00')),
            'fecha_corta' => date('d/m/Y', strtotime('+15 days')),
            'ubicacion' => 'Centro de Convenciones World Trade Center',
            'capacidad_maxima' => 500,
            'capacidad_actual' => 156,
            'tipo' => 'Conferencia',
            'modalidad' => 'Presencial',
            'estado' => 'programado',
            'imagen' => './assets/img/evento-conferencia.jpg',
            'precio' => 0.00,
            'precio_formateado' => 'Gratuito'
        ],
        [
            'id' => 2,
            'titulo' => 'Taller de Mantenimiento Vehicular Avanzado',
            'descripcion' => 'Taller práctico intensivo sobre técnicas modernas de mantenimiento preventivo y correctivo. Incluye uso de tecnología diagnóstica avanzada.',
            'descripcion_corta' => 'Taller práctico intensivo sobre técnicas modernas de mantenimiento.',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+20 days 08:00')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+20 days 17:00')),
            'fecha_corta' => date('d/m/Y', strtotime('+20 days')),
            'ubicacion' => 'Instituto Técnico Automotriz CLAUT',
            'capacidad_maxima' => 50,
            'capacidad_actual' => 23,
            'tipo' => 'Taller',
            'modalidad' => 'Presencial',
            'estado' => 'programado',
            'imagen' => './assets/img/evento-taller.jpg',
            'precio' => 150.00,
            'precio_formateado' => '$150.00'
        ],
        [
            'id' => 3,
            'titulo' => 'Expo Vehículos Eléctricos y Sostenibilidad',
            'descripcion' => 'Exposición de los últimos modelos de vehículos eléctricos, híbridos y tecnologías sostenibles. Conferencias magistrales sobre el futuro de la movilidad.',
            'descripcion_corta' => 'Exposición de vehículos eléctricos y tecnologías sostenibles.',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+30 days 10:00')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+32 days 19:00')),
            'fecha_corta' => date('d/m/Y', strtotime('+30 days')),
            'ubicacion' => 'Centro Banamex',
            'capacidad_maxima' => 1000,
            'capacidad_actual' => 45,
            'tipo' => 'Exposición',
            'modalidad' => 'Presencial',
            'estado' => 'programado',
            'imagen' => './assets/img/evento-expo.jpg',
            'precio' => 0.00,
            'precio_formateado' => 'Gratuito'
        ],
        [
            'id' => 4,
            'titulo' => 'Seminario Web: Normativas de Seguridad Vial 2024',
            'descripcion' => 'Seminario virtual sobre las nuevas normativas de seguridad vial, cambios regulatorios y su impacto en el sector automotriz.',
            'descripcion_corta' => 'Seminario virtual sobre normativas de seguridad vial.',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+7 days 15:00')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+7 days 17:00')),
            'fecha_corta' => date('d/m/Y', strtotime('+7 days')),
            'ubicacion' => 'Plataforma Virtual CLAUT',
            'capacidad_maxima' => 200,
            'capacidad_actual' => 78,
            'tipo' => 'Webinar',
            'modalidad' => 'Virtual',
            'estado' => 'programado',
            'imagen' => './assets/img/evento-webinar.jpg',
            'precio' => 75.00,
            'precio_formateado' => '$75.00'
        ]
    ];

    // Calcular estadísticas
    $total = count($eventosDemo);
    $proximos = count(array_filter($eventosDemo, function($e) {
        return $e['estado'] === 'programado';
    }));

    $response = [
        'success' => true,
        'message' => 'Eventos obtenidos correctamente (datos demo)',
        'data' => [
            'eventos' => $eventosDemo,
            'estadisticas' => [
                'total' => $total,
                'proximos' => $proximos,
                'en_curso' => 0,
                'finalizados' => 0
            ],
            'total_eventos' => $total
        ],
        'environment' => [
            'database_type' => 'demo_data',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>