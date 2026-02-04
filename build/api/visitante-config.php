<?php
/**
 * API para configuración de acceso de visitantes
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Función para responder en JSON
 */
function responderJSON($success, $data = null, $message = '', $extra = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ];

    foreach ($extra as $key => $value) {
        $response[$key] = $value;
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

// Configuración por defecto de páginas para visitantes
$configuracionPorDefecto = [
    [
        'id' => 'empresas-convenio',
        'titulo' => 'Empresas Convenio',
        'descripcion' => 'Directorio de empresas con convenios',
        'icono' => 'fas fa-handshake',
        'url' => './empresas-convenio.html',
        'activo' => true
    ],
    [
        'id' => 'boletines',
        'titulo' => 'Boletines',
        'descripcion' => 'Boletines informativos públicos',
        'icono' => 'fas fa-newspaper',
        'url' => './boletines.html',
        'activo' => true
    ],
    [
        'id' => 'eventos',
        'titulo' => 'Eventos',
        'descripcion' => 'Calendario de eventos públicos',
        'icono' => 'fas fa-calendar-alt',
        'url' => './eventos.html',
        'activo' => true
    ],
    [
        'id' => 'documentacion',
        'titulo' => 'Boletines & Documentación',
        'descripcion' => 'Boletines informativos y centro de documentación',
        'icono' => 'fas fa-file-alt',
        'url' => './boletines.html',
        'activo' => true
    ]
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener configuración
        $action = $_GET['action'] ?? 'listar';

        switch ($action) {
            case 'listar':
                // Intentar cargar desde archivo de configuración
                $archivoConfig = __DIR__ . '/../data/visitante-config.json';

                if (file_exists($archivoConfig)) {
                    $contenido = file_get_contents($archivoConfig);
                    $configuracion = json_decode($contenido, true);

                    if ($configuracion) {
                        responderJSON(true, null, 'Configuración cargada', ['paginas' => $configuracion]);
                    }
                }

                // Si no existe archivo, usar configuración por defecto
                responderJSON(true, null, 'Configuración por defecto', ['paginas' => $configuracionPorDefecto]);
                break;

            default:
                responderJSON(false, null, 'Acción no válida');
                break;
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Actualizar configuración (solo para administradores)
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['paginas'])) {
            responderJSON(false, null, 'Datos inválidos');
        }

        // Crear directorio si no existe
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // Guardar configuración
        $archivoConfig = $dataDir . '/visitante-config.json';
        $resultado = file_put_contents($archivoConfig, json_encode($input['paginas'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($resultado !== false) {
            responderJSON(true, null, 'Configuración actualizada correctamente');
        } else {
            responderJSON(false, null, 'Error al guardar configuración');
        }

    } else {
        http_response_code(405);
        responderJSON(false, null, 'Método no permitido');
    }

} catch (Exception $e) {
    error_log("Error en visitante-config.php: " . $e->getMessage());
    responderJSON(false, null, 'Error interno del servidor');
}
?>