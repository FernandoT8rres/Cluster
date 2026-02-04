<?php
/**
 * API de estadísticas simplificada para debugging
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Estadísticas de ejemplo/fallback
    $estadisticas = [
        'usuarios' => [
            'total' => 125,
            'por_rol' => [
                'admin' => 5,
                'empresa' => 45,
                'empleado' => 75
            ],
            'nuevos_mes' => 12
        ],
        'empresas' => [
            'total' => 32,
            'total_empleados' => 120,
            'promedio_empleados' => 3.8,
            'top_participacion' => []
        ],
        'comites' => [
            'total' => 8,
            'total_miembros' => 45,
            'promedio_miembros' => 5.6
        ],
        'descuentos' => [
            'total' => 15,
            'proximos_vencer' => 3,
            'descuento_promedio' => 12.5
        ],
        'eventos' => [
            'total' => 6,
            'este_mes' => 2,
            'proximos' => []
        ],
        'boletines' => [
            'total' => 18,
            'este_mes' => 4,
            'borradores' => 2
        ]
    ];

    $response = [
        'success' => true,
        'data' => $estadisticas,
        'timestamp' => date('c'),
        'message' => 'Estadísticas cargadas correctamente'
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno: ' . $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>