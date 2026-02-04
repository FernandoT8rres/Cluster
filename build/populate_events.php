<?php
/**
 * Script para poblar la base de datos con eventos de ejemplo
 * Ejecutar una vez para insertar datos de prueba
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Incluir configuración de base de datos
require_once __DIR__ . '/config/database.php';

try {
    // Obtener instancia de base de datos
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    echo "📊 Poblando base de datos con eventos de ejemplo...\n\n";

    // Verificar si ya hay eventos
    $count = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
    if ($count > 0) {
        echo "⚠️ La base de datos ya tiene {$count} eventos.\n";
        echo "¿Quieres continuar? Se agregarán más eventos.\n";
    }

    // Eventos de ejemplo para poblar la BD
    $eventosEjemplo = [
        [
            'titulo' => 'Conferencia Automotriz CLAUT 2024',
            'descripcion' => 'Evento anual más importante del sector automotriz mexicano. Presentación de nuevas tecnologías, tendencias del mercado y networking con los principales actores de la industria.',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+15 days 09:00')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+15 days 18:00')),
            'ubicacion' => 'Centro de Convenciones World Trade Center',
            'capacidad_maxima' => 500,
            'capacidad_actual' => 156,
            'tipo' => 'Conferencia',
            'modalidad' => 'Presencial',
            'estado' => 'activo',
            'imagen' => './assets/img/evento-conferencia.jpg',
            'precio' => 0.00
        ],
        [
            'titulo' => 'Taller de Mantenimiento Vehicular Avanzado',
            'descripcion' => 'Taller práctico intensivo sobre técnicas modernas de mantenimiento preventivo y correctivo. Incluye uso de tecnología diagnóstica avanzada.',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+20 days 08:00')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+20 days 17:00')),
            'ubicacion' => 'Instituto Técnico Automotriz CLAUT',
            'capacidad_maxima' => 50,
            'capacidad_actual' => 23,
            'tipo' => 'Taller',
            'modalidad' => 'Presencial',
            'estado' => 'activo',
            'imagen' => './assets/img/evento-taller.jpg',
            'precio' => 150.00
        ],
        [
            'titulo' => 'Expo Vehículos Eléctricos y Sostenibilidad',
            'descripcion' => 'Exposición de los últimos modelos de vehículos eléctricos, híbridos y tecnologías sostenibles.',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+30 days 10:00')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+32 days 19:00')),
            'ubicacion' => 'Centro Banamex',
            'capacidad_maxima' => 1000,
            'capacidad_actual' => 45,
            'tipo' => 'Exposición',
            'modalidad' => 'Presencial',
            'estado' => 'activo',
            'imagen' => './assets/img/evento-expo.jpg',
            'precio' => 0.00
        ],
        [
            'titulo' => 'Seminario Web: Normativas de Seguridad Vial 2024',
            'descripcion' => 'Seminario virtual sobre las nuevas normativas de seguridad vial y su impacto en el sector automotriz.',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+7 days 15:00')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+7 days 17:00')),
            'ubicacion' => 'Plataforma Virtual CLAUT',
            'capacidad_maxima' => 200,
            'capacidad_actual' => 78,
            'tipo' => 'Webinar',
            'modalidad' => 'Virtual',
            'estado' => 'activo',
            'imagen' => './assets/img/evento-webinar.jpg',
            'precio' => 75.00
        ],
        [
            'titulo' => 'Networking: Encuentro de Proveedores Automotrices',
            'descripcion' => 'Evento de networking exclusivo para proveedores del sector automotriz.',
            'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+10 days 18:00')),
            'fecha_fin' => date('Y-m-d H:i:s', strtotime('+10 days 21:00')),
            'ubicacion' => 'Hotel Marquis Reforma',
            'capacidad_maxima' => 150,
            'capacidad_actual' => 67,
            'tipo' => 'Networking',
            'modalidad' => 'Presencial',
            'estado' => 'activo',
            'imagen' => './assets/img/evento-networking.jpg',
            'precio' => 250.00
        ]
    ];

    // Insertar eventos
    $sql = "INSERT INTO eventos (titulo, descripcion, fecha_inicio, fecha_fin, ubicacion, capacidad_maxima, capacidad_actual, tipo, modalidad, estado, imagen, precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $insertados = 0;

    foreach ($eventosEjemplo as $evento) {
        try {
            $resultado = $stmt->execute([
                $evento['titulo'],
                $evento['descripcion'],
                $evento['fecha_inicio'],
                $evento['fecha_fin'],
                $evento['ubicacion'],
                $evento['capacidad_maxima'],
                $evento['capacidad_actual'],
                $evento['tipo'],
                $evento['modalidad'],
                $evento['estado'],
                $evento['imagen'],
                $evento['precio']
            ]);

            if ($resultado) {
                $insertados++;
                echo "✅ Insertado: {$evento['titulo']}\n";
            }
        } catch (Exception $e) {
            echo "❌ Error insertando {$evento['titulo']}: {$e->getMessage()}\n";
        }
    }

    // Verificar total después de inserción
    $totalEventos = $pdo->query("SELECT COUNT(*) FROM eventos WHERE estado = 'activo'")->fetchColumn();

    $response = [
        'success' => true,
        'message' => 'Base de datos poblada correctamente',
        'data' => [
            'eventos_insertados' => $insertados,
            'total_eventos' => (int)$totalEventos
        ]
    ];

    echo "\n🎉 ¡Completado!\n";
    echo "📊 Eventos insertados: {$insertados}\n";
    echo "📈 Total eventos activos: {$totalEventos}\n";
    echo "\n📝 Ahora puedes probar:\n";
    echo "🌐 get_eventos.php\n";
    echo "🎯 eventos.html\n";

    // Si se accede vía web, devolver JSON
    if (isset($_SERVER['HTTP_HOST'])) {
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    $error = [
        'success' => false,
        'message' => 'Error poblando base de datos: ' . $e->getMessage()
    ];

    echo "\n❌ ERROR: {$e->getMessage()}\n";

    if (isset($_SERVER['HTTP_HOST'])) {
        http_response_code(500);
        echo json_encode($error, JSON_UNESCAPED_UNICODE);
    }
}
?>