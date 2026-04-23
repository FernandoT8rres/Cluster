<?php
define('CLAUT_ACCESS', true);
require_once __DIR__ . '/config/session-config.php';
SessionConfig::init();

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos'. $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        try {
            $stmt = $pdo->query("
                SELECT n.id, n.usuario_id, n.campo_modificado, n.valor_anterior, n.valor_nuevo, n.fecha_solicitud, n.estado,
                       u.nombre, u.apellidos, u.email
                FROM notificaciones_cambios_perfil n
                JOIN usuarios_perfil u ON n.usuario_id = u.id
                WHERE n.estado = 'pendiente'
                ORDER BY n.fecha_solicitud DESC
            ");
            
            $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'notificaciones' => $notificaciones]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener notificaciones: ' . $e->getMessage()]);
        }
        break;

    case 'aprobar':
        $notificacion_id = $_POST['notificacion_id'] ?? null;
        
        if (!$notificacion_id) {
            echo json_encode(['success' => false, 'message' => 'ID de notificación no proporcionado']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Obtener detalles de la notificación
            $stmt = $pdo->prepare("SELECT usuario_id, campo_modificado, valor_nuevo FROM notificaciones_cambios_perfil WHERE id = ? AND estado = 'pendiente'");
            $stmt->execute([$notificacion_id]);
            $notif = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$notif) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Notificación no encontrada o ya procesada.']);
                exit;
            }

            // 2. Mappear el campo que viene del Frontend a la columna real de la BD
            $allowedFields = [
                'phone' => 'telefono',
                'department' => 'departamento',
                'position' => 'rol',
                'bio' => 'biografia',
                'address' => 'direccion',
                'city' => 'ciudad',
                'state' => 'estado',
                'zipCode' => 'codigo_postal',
                'country' => 'pais',
                'emergencyPhone' => 'telefono_emergencia',
                'emergencyContact' => 'contacto_emergencia',
                'birthDate' => 'fecha_nacimiento'
            ];

            $dbColumn = $allowedFields[$notif['campo_modificado']] ?? null;

            if ($dbColumn) {
                // Actualizar la tabla del usuario
                $updateUser = $pdo->prepare("UPDATE usuarios_perfil SET {$dbColumn} = ? WHERE id = ?");
                $updateUser->execute([$notif['valor_nuevo'], $notif['usuario_id']]);
            }

            // 3. Marcar la notificación como aprobada
            $updateNotif = $pdo->prepare("UPDATE notificaciones_cambios_perfil SET estado = 'aprobado' WHERE id = ?");
            $updateNotif->execute([$notificacion_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Cambio aprobado y perfil actualizado correctamente.']);
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]);
        }
        break;

    case 'rechazar':
        $notificacion_id = $_POST['notificacion_id'] ?? null;
        
        if (!$notificacion_id) {
            echo json_encode(['success' => false, 'message' => 'ID de notificación no proporcionado']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE notificaciones_cambios_perfil SET estado = 'rechazado' WHERE id = ?");
            $stmt->execute([$notificacion_id]);
            
            echo json_encode(['success' => true, 'message' => 'La solicitud de cambio ha sido rechazada.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error de BD: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
