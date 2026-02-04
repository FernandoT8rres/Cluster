<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Conexión directa
try {
    $host = '127.0.0.1';
    $username = 'u695712029_claut_fer';
    $password = 'CLAUT@admin_fernando!7';
    $database = 'u695712029_claut_intranet';

    $conn = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
        ]
    );

    $action = $_GET['action'] ?? 'listar';

    switch ($action) {
        case 'listar':
            // Obtener notificaciones pendientes
            $stmt = $conn->prepare("
                SELECT n.*, u.nombre, u.apellidos, u.email
                FROM notificaciones_cambios_perfil n
                LEFT JOIN usuarios_perfil u ON n.usuario_id = u.id
                WHERE n.estado = 'pendiente'
                ORDER BY n.fecha_solicitud DESC
            ");
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'notificaciones' => $notificaciones
            ]);
            break;

        case 'aprobar':
            $notificacion_id = $_POST['notificacion_id'] ?? null;
            $revisado_por = $_POST['revisado_por'] ?? 1;

            if (!$notificacion_id) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                break;
            }

            // Obtener detalles de la notificación
            $stmt = $conn->prepare("SELECT * FROM notificaciones_cambios_perfil WHERE id = ?");
            $stmt->execute([$notificacion_id]);
            $notificacion = $stmt->fetch();

            if (!$notificacion) {
                echo json_encode(['success' => false, 'message' => 'Notificación no encontrada']);
                break;
            }

            // Mapeo de campos
            $fieldMapping = [
                'phone' => 'telefono',
                'birthDate' => 'fecha_nacimiento',
                'department' => 'departamento',
                'position' => 'rol',
                'bio' => 'biografia',
                'address' => 'direccion',
                'city' => 'ciudad',
                'state' => 'estado',
                'zipCode' => 'codigo_postal',
                'country' => 'pais',
                'emergencyPhone' => 'telefono_emergencia',
                'emergencyContact' => 'contacto_emergencia'
            ];

            $dbField = $fieldMapping[$notificacion['campo_modificado']] ?? null;
            if (!$dbField) {
                echo json_encode(['success' => false, 'message' => 'Campo no válido']);
                break;
            }

            // Aplicar cambio en usuarios_perfil
            $stmt = $conn->prepare("UPDATE usuarios_perfil SET $dbField = ? WHERE id = ?");
            $result = $stmt->execute([$notificacion['valor_nuevo'], $notificacion['usuario_id']]);

            if ($result) {
                // Marcar notificación como aprobada
                $stmt = $conn->prepare("
                    UPDATE notificaciones_cambios_perfil
                    SET estado = 'aprobado', fecha_revision = NOW(), revisado_por = ?
                    WHERE id = ?
                ");
                $stmt->execute([$revisado_por, $notificacion_id]);

                echo json_encode(['success' => true, 'message' => 'Cambio aprobado y aplicado']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al aplicar cambio']);
            }
            break;

        case 'rechazar':
            $notificacion_id = $_POST['notificacion_id'] ?? null;
            $revisado_por = $_POST['revisado_por'] ?? 1;
            $comentarios = $_POST['comentarios'] ?? '';

            if (!$notificacion_id) {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
                break;
            }

            $stmt = $conn->prepare("
                UPDATE notificaciones_cambios_perfil
                SET estado = 'rechazado', fecha_revision = NOW(), revisado_por = ?, comentarios = ?
                WHERE id = ?
            ");
            $result = $stmt->execute([$revisado_por, $comentarios, $notificacion_id]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Cambio rechazado']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al rechazar']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>