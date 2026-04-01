<?php
/**
 * API: Company Requests Management
 * File: api/solicitudes_empresa.php
 * Purpose: Handle company creation/update/display requests and admin approvals
 */

define('CLAUT_ACCESS', true);

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://intranet.clautmetropolitano.mx');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Start session
if (file_exists(dirname(__DIR__) . '/config/session-config.php')) {
    require_once dirname(__DIR__) . '/config/session-config.php';
    if (!session_id()) SessionConfig::init();
} else {
    if (session_status() == PHP_SESSION_NONE) session_start();
}

// Resolve user_id from session (supports both key naming conventions)
$user_id = null;
$user_email = null;

if (!empty($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
} elseif (!empty($_SESSION['usuario_id'])) {
    $user_id = intval($_SESSION['usuario_id']);
} else {
    $user_email = $_SESSION['user_email'] ?? $_SESSION['usuario_email'] ?? null;
}

$user_rol = $_SESSION['user_rol'] ?? $_SESSION['usuario_rol'] ?? $_SESSION['rol'] ?? 'empleado';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Derive user_id from email if missing
    if (!$user_id && $user_email) {
        $stmt = $conn->prepare("SELECT id, rol FROM usuarios_perfil WHERE email = ? LIMIT 1");
        $stmt->execute([$user_email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $user_id = intval($u['id']);
            if ($user_rol === 'empleado') $user_rol = $u['rol'] ?? 'empleado';
        }
    }

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado']);
        exit();
    }

    switch ($method) {
        case 'GET':
            $estado = $_GET['estado'] ?? null;
            $is_admin = in_array($user_rol, ['admin', 'superadmin']);

            if ($is_admin) {
                $sql = "
                    SELECT 
                        s.*,
                        u.nombre as usuario_nombre,
                        u.apellidos as usuario_apellido,
                        u.email as usuario_email,
                        COALESCE(e.nombre, e.nombre_empresa) as empresa_nombre_existente,
                        a.nombre as admin_nombre
                    FROM solicitudes_empresa s
                    INNER JOIN usuarios_perfil u ON s.usuario_id = u.id
                    LEFT JOIN empresas_convenio e ON s.empresa_id = e.id
                    LEFT JOIN usuarios_perfil a ON s.admin_id = a.id
                ";
                $params = [];
                if ($estado) {
                    $sql .= " WHERE s.estado = ?";
                    $params = [$estado];
                }
            } else {
                $sql = "
                    SELECT 
                        s.*,
                        COALESCE(e.nombre, e.nombre_empresa) as empresa_nombre_existente,
                        a.nombre as admin_nombre
                    FROM solicitudes_empresa s
                    LEFT JOIN empresas_convenio e ON s.empresa_id = e.id
                    LEFT JOIN usuarios_perfil a ON s.admin_id = a.id
                    WHERE s.usuario_id = ?
                ";
                $params = [$user_id];
                if ($estado) {
                    $sql .= " AND s.estado = ?";
                    $params[] = $estado;
                }
            }

            $sql .= " ORDER BY s.fecha_solicitud DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($solicitudes as &$s) {
                if ($s['datos_empresa']) {
                    $s['datos_empresa'] = json_decode($s['datos_empresa'], true);
                }
            }

            echo json_encode(['success' => true, 'solicitudes' => $solicitudes]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['tipo_solicitud'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                exit();
            }

            $tipo = $data['tipo_solicitud'];
            $empresa_id = isset($data['empresa_id']) ? intval($data['empresa_id']) : null;
            $datos_empresa = isset($data['datos_empresa']) ? json_encode($data['datos_empresa']) : null;

            if (!in_array($tipo, ['crear', 'mostrar', 'actualizar'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Tipo de solicitud inválido. Use: crear, mostrar, actualizar']);
                exit();
            }

            if ($tipo === 'mostrar' && !$empresa_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Se requiere empresa_id para solicitudes de tipo "mostrar"']);
                exit();
            }

            if (in_array($tipo, ['crear', 'actualizar']) && !$datos_empresa) {
                http_response_code(400);
                echo json_encode(['error' => 'Se requieren datos_empresa para este tipo de solicitud']);
                exit();
            }

            // For 'actualizar', empresa_id is also required
            if ($tipo === 'actualizar' && !$empresa_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Se requiere empresa_id para solicitudes de tipo "actualizar"']);
                exit();
            }

            // Check for existing pending request of same type for same company
            if ($tipo === 'actualizar' && $empresa_id) {
                $stmt = $conn->prepare("SELECT id FROM solicitudes_empresa WHERE usuario_id = ? AND empresa_id = ? AND tipo_solicitud = 'actualizar' AND estado = 'pendiente' LIMIT 1");
                $stmt->execute([$user_id, $empresa_id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    // Update instead of insert
                    $stmt = $conn->prepare("UPDATE solicitudes_empresa SET datos_empresa = ?, fecha_solicitud = NOW() WHERE id = ?");
                    $stmt->execute([$datos_empresa, $existing['id']]);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Solicitud de cambio actualizada. El administrador revisará tus cambios.',
                        'solicitud_id' => $existing['id']
                    ]);
                    exit();
                }
            }

            $stmt = $conn->prepare("
                INSERT INTO solicitudes_empresa 
                (usuario_id, tipo_solicitud, empresa_id, datos_empresa, estado) 
                VALUES (?, ?, ?, ?, 'pendiente')
            ");
            $result = $stmt->execute([$user_id, $tipo, $empresa_id, $datos_empresa]);

            if ($result) {
                $solicitud_id = $conn->lastInsertId();

                $titulo = match($tipo) {
                    'crear'      => 'Solicitud de creación de empresa enviada',
                    'actualizar' => 'Solicitud de cambio de datos enviada',
                    default      => 'Solicitud para mostrar empresa enviada',
                };
                $contenido = 'Tu solicitud ha sido enviada y está pendiente de revisión por un administrador.';

                try {
                    $stmt = $conn->prepare("
                        INSERT INTO mensajes_usuario 
                        (usuario_id, tipo, titulo, contenido, relacionado_tipo, relacionado_id, icono, color) 
                        VALUES (?, 'notificacion', ?, ?, 'solicitud', ?, 'fa-paper-plane', 'info')
                    ");
                    $stmt->execute([$user_id, $titulo, $contenido, $solicitud_id]);
                } catch (Exception $e) {
                    // Notification failure is non-critical
                }

                echo json_encode([
                    'success'      => true,
                    'message'      => 'Solicitud enviada. El administrador revisará tus cambios.',
                    'solicitud_id' => $solicitud_id
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear la solicitud']);
            }
            break;

        case 'PUT':
            // Admin: approve or reject a request
            $is_admin = in_array($user_rol, ['admin', 'superadmin']);
            if (!$is_admin) {
                http_response_code(403);
                echo json_encode(['error' => 'Se requieren permisos de administrador']);
                exit();
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['id']) || !isset($data['accion'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos. Se requiere id y accion.']);
                exit();
            }

            $solicitud_id = intval($data['id']);
            $accion = $data['accion'];
            $notas = $data['notas'] ?? null;

            if (!in_array($accion, ['aprobar', 'rechazar'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Acción inválida. Use: aprobar, rechazar']);
                exit();
            }

            $stmt = $conn->prepare("SELECT * FROM solicitudes_empresa WHERE id = ?");
            $stmt->execute([$solicitud_id]);
            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$solicitud) {
                http_response_code(404);
                echo json_encode(['error' => 'Solicitud no encontrada']);
                exit();
            }

            if ($solicitud['estado'] !== 'pendiente') {
                http_response_code(400);
                echo json_encode(['error' => 'Esta solicitud ya fue procesada']);
                exit();
            }

            $conn->beginTransaction();
            try {
                $empresa_creada_id = null;

                if ($accion === 'aprobar') {
                    if ($solicitud['tipo_solicitud'] === 'actualizar') {
                        // Apply changes directly to empresas_convenio
                        $datos = json_decode($solicitud['datos_empresa'], true);
                        $empresa_id_upd = intval($solicitud['empresa_id']);

                        // Map frontend field names to actual DB column names
                        // (profile.html uses 'contacto_persona' but the table has 'contacto_nombre')
                        $field_map = [
                            'contacto_persona' => 'contacto_nombre',
                        ];

                        // Columns that actually exist in empresas_convenio
                        $allowed_db = [
                            'nombre_empresa', 'nombre', 'sector', 'descripcion', 'logo_url',
                            'email', 'telefono', 'sitio_web', 'direccion',
                            'contacto_nombre', 'contacto_telefono', 'contacto_email',
                            'descuento_porcentaje', 'beneficios', 'condiciones'
                        ];

                        $fields = [];
                        $vals   = [];

                        foreach ($datos as $key => $value) {
                            // Remap if needed
                            $col = $field_map[$key] ?? $key;
                            // Only update actual DB columns
                            if (in_array($col, $allowed_db)) {
                                $fields[] = "`$col` = ?";
                                $vals[]   = $value;
                            }
                        }

                        if (!empty($fields)) {
                            $vals[] = $empresa_id_upd;
                            $sql = "UPDATE empresas_convenio SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            if (!$stmt->execute($vals)) {
                                throw new Exception('Error ejecutando UPDATE: ' . implode(' ', $stmt->errorInfo()));
                            }
                        }

                        $empresa_creada_id = $empresa_id_upd;


                    } elseif ($solicitud['tipo_solicitud'] === 'crear') {
                        $datos = json_decode($solicitud['datos_empresa'], true);

                        $stmt = $conn->prepare("
                            INSERT INTO empresas_convenio 
                            (nombre_empresa, nombre, sector, descripcion, logo_url, email, telefono, 
                             sitio_web, direccion, contacto_nombre, contacto_telefono, contacto_email,
                             descuento_porcentaje, beneficios, condiciones, fecha_convenio, activo, admin_usuario_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
                        ");
                        $stmt->execute([
                            $datos['nombre'] ?? '', $datos['nombre'] ?? '',
                            $datos['sector'] ?? 'General', $datos['descripcion'] ?? '',
                            $datos['logo_url'] ?? '', $datos['email'] ?? '',
                            $datos['telefono'] ?? '', $datos['sitio_web'] ?? '',
                            $datos['direccion'] ?? '', $datos['contacto_persona'] ?? '',
                            $datos['contacto_telefono'] ?? '', $datos['contacto_email'] ?? '',
                            $datos['descuento_porcentaje'] ?? 0,
                            $datos['beneficios'] ?? '', $datos['condiciones'] ?? '',
                            $datos['fecha_convenio'] ?? date('Y-m-d'),
                            $solicitud['usuario_id']
                        ]);

                        $empresa_creada_id = $conn->lastInsertId();

                    } elseif ($solicitud['tipo_solicitud'] === 'mostrar') {
                        $empresa_creada_id = intval($solicitud['empresa_id']);
                        $stmt = $conn->prepare("UPDATE empresas_convenio SET admin_usuario_id = ? WHERE id = ?");
                        $stmt->execute([$solicitud['usuario_id'], $empresa_creada_id]);
                    }
                }

                $nuevo_estado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';
                $stmt = $conn->prepare("
                    UPDATE solicitudes_empresa 
                    SET estado = ?, fecha_respuesta = NOW(), admin_id = ?, notas_admin = ?, empresa_creada_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nuevo_estado, $user_id, $notas, $empresa_creada_id, $solicitud_id]);

                // Notify user
                $titulo    = $accion === 'aprobar' ? 'Solicitud aprobada' : 'Solicitud rechazada';
                $contenido = $accion === 'aprobar'
                    ? 'Tu solicitud ha sido aprobada. Los cambios ya están aplicados.'
                    : 'Tu solicitud ha sido rechazada.' . ($notas ? ' Motivo: ' . $notas : '');
                $color = $accion === 'aprobar' ? 'success' : 'danger';
                $icono = $accion === 'aprobar' ? 'fa-check-circle' : 'fa-times-circle';

                try {
                    $stmt = $conn->prepare("
                        INSERT INTO mensajes_usuario 
                        (usuario_id, tipo, titulo, contenido, relacionado_tipo, relacionado_id, icono, color) 
                        VALUES (?, 'notificacion', ?, ?, 'solicitud', ?, ?, ?)
                    ");
                    $stmt->execute([$solicitud['usuario_id'], $titulo, $contenido, $solicitud_id, $icono, $color]);
                } catch (Exception $e) { /* non-critical */ }

                $conn->commit();

                echo json_encode([
                    'success'    => true,
                    'message'    => 'Solicitud ' . $nuevo_estado . ' exitosamente',
                    'empresa_id' => $empresa_creada_id
                ]);

            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        case 'DELETE':
            $solicitud_id = intval($_GET['id'] ?? 0);
            if (!$solicitud_id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de solicitud requerido']);
                exit();
            }

            $stmt = $conn->prepare("SELECT * FROM solicitudes_empresa WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$solicitud_id, $user_id]);
            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$solicitud) {
                http_response_code(404);
                echo json_encode(['error' => 'Solicitud no encontrada']);
                exit();
            }

            if ($solicitud['estado'] !== 'pendiente') {
                http_response_code(400);
                echo json_encode(['error' => 'Solo puedes cancelar solicitudes pendientes']);
                exit();
            }

            $stmt = $conn->prepare("DELETE FROM solicitudes_empresa WHERE id = ?");
            $result = $stmt->execute([$solicitud_id]);

            echo json_encode($result
                ? ['success' => true, 'message' => 'Solicitud cancelada']
                : ['error' => 'Error al cancelar']
            );
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor', 'message' => $e->getMessage()]);
}
