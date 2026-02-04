<?php
require_once '../config/database.php';

// Script para generar notificaciones de ejemplo
try {
    $db = Database::getInstance();
    $connection = $db->getConnection();

    // Limpiar notificaciones antiguas (opcional)
    $connection->exec("DELETE FROM notificaciones WHERE titulo LIKE '%[DEMO]%'");

    // Notificaciones de ejemplo
    $sampleNotifications = [
        [
            'titulo' => '[DEMO] Nuevo Boletín Informativo',
            'contenido' => 'Se ha publicado el boletín mensual con las últimas noticias y actualizaciones de CLAUT Metropolitano. Incluye información sobre nuevos convenios y beneficios para los afiliados.',
            'tipo' => 'boletin',
            'dirigido_a' => 'todos',
            'importante' => false
        ],
        [
            'titulo' => '[DEMO] Evento: Sesión Informativa Virtual',
            'contenido' => 'Te invitamos a participar en nuestra sesión informativa virtual el próximo viernes a las 10:00 AM. Hablaremos sobre los nuevos beneficios y servicios disponibles.',
            'tipo' => 'evento',
            'dirigido_a' => 'todos',
            'importante' => true
        ],
        [
            'titulo' => '[DEMO] Nuevo Documento Disponible',
            'contenido' => 'Se ha agregado el manual de procedimientos actualizado en la sección de documentación. Revisa los cambios más importantes en las políticas internas.',
            'tipo' => 'documento',
            'dirigido_a' => 'todos',
            'importante' => false
        ],
        [
            'titulo' => '[DEMO] Comité de Finanzas - Próxima Reunión',
            'contenido' => 'El Comité de Finanzas convoca a reunión virtual el miércoles 15 de noviembre a las 3:00 PM. Se enviará el enlace de acceso a los miembros registrados.',
            'tipo' => 'comite',
            'destinatario_rol' => 'admin',
            'dirigido_a' => 'rol',
            'importante' => true
        ],
        [
            'titulo' => '[DEMO] Nueva Empresa en Convenio',
            'contenido' => 'Damos la bienvenida a TechSolutions como nueva empresa en convenio. Los afiliados podrán acceder a descuentos exclusivos en servicios de tecnología.',
            'tipo' => 'general',
            'dirigido_a' => 'todos',
            'importante' => false
        ],
        [
            'titulo' => '[DEMO] Banner Promocional Actualizado',
            'contenido' => 'Se ha actualizado el banner principal con información sobre la campaña de descuentos de temporada. No te pierdas las ofertas especiales de nuestros aliados.',
            'tipo' => 'general',
            'dirigido_a' => 'todos',
            'importante' => true
        ]
    ];

    // Insertar notificaciones
    $sql = "INSERT INTO notificaciones (
                titulo, contenido, tipo, dirigido_a, importante,
                destinatario_rol, fecha_creacion
            ) VALUES (
                :titulo, :contenido, :tipo, :dirigido_a, :importante,
                :destinatario_rol, :fecha_creacion
            )";

    $stmt = $connection->prepare($sql);

    foreach ($sampleNotifications as $index => $notification) {
        // Crear fechas escalonadas para simular diferentes momentos
        $horasAtras = $index * 2; // 0, 2, 4, 6, 8, 10 horas atrás
        $fechaCreacion = date('Y-m-d H:i:s', strtotime("-{$horasAtras} hours"));

        $stmt->execute([
            ':titulo' => $notification['titulo'],
            ':contenido' => $notification['contenido'],
            ':tipo' => $notification['tipo'],
            ':dirigido_a' => $notification['dirigido_a'],
            ':importante' => $notification['importante'] ? 1 : 0,
            ':destinatario_rol' => $notification['destinatario_rol'] ?? null,
            ':fecha_creacion' => $fechaCreacion
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Notificaciones de ejemplo generadas correctamente',
        'count' => count($sampleNotifications)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al generar notificaciones: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>