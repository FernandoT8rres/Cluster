<?php
/**
 * notify-approval.php — Correo de aprobación/rechazo de cuenta
 *
 * Llamado por el panel admin cuando aprueba o rechaza un usuario.
 * Requiere sesión activa con rol admin.
 *
 * POST {
 *   email: string,          — Email del usuario afectado
 *   accion: 'aprobar'|'rechazar',
 *   razon?: string          — Motivo de rechazo (opcional)
 * }
 */

define('CLAUT_ACCESS', true);
require_once dirname(dirname(__DIR__)) . '/config/session-config.php';
SessionConfig::init();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); exit(json_encode(['success' => false, 'message' => 'Método no permitido'])); }

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/env-loader.php';
require_once __DIR__ . '/../../utils/security-logger.php';
require_once __DIR__ . '/../../services/EmailService.php';

EnvLoader::load();

function jsonResponse(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

// ── Verificar sesión admin ───────────────────────────────────────────────
$rol = $_SESSION['user_rol'] ?? '';
if (!in_array($rol, ['admin', 'Administrador'], true)) {
    http_response_code(403);
    jsonResponse(false, 'Acceso no autorizado.');
}

try {
    $input  = json_decode(file_get_contents('php://input'), true);
    $email  = strtolower(trim($input['email'] ?? ''));
    $accion = trim($input['accion'] ?? '');
    $razon  = trim($input['razon'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Email inválido.');
    }
    if (!in_array($accion, ['aprobar', 'rechazar'], true)) {
        jsonResponse(false, 'Acción inválida. Use: aprobar o rechazar.');
    }

    $db = Database::getInstance()->getConnection();

    // Obtener datos del usuario
    $stmt = $db->prepare(
        "SELECT id, nombre, apellidos, estado_usuario
         FROM usuarios_perfil
         WHERE email = :email AND activo = 1
         LIMIT 1"
    );
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        jsonResponse(false, 'Usuario no encontrado.');
    }

    $nombre = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')) ?: 'Usuario';

    // Enviar correo según acción
    if ($accion === 'aprobar') {
        $result = EmailService::sendAccountApproved($email, $nombre);
        $logEvento = 'account_approval_email_sent';
    } else {
        $result = EmailService::sendAccountRejected($email, $nombre, $razon);
        $logEvento = 'account_rejection_email_sent';
    }

    SecurityLogger::log($logEvento, 'INFO', [
        'email'      => $email,
        'admin'      => $_SESSION['user_email'] ?? 'unknown',
        'email_sent' => $result['success']
    ]);

    if ($result['success']) {
        jsonResponse(true, 'Correo de notificación enviado correctamente.');
    } else {
        jsonResponse(false, 'No se pudo enviar el correo: ' . $result['message']);
    }

} catch (Exception $e) {
    error_log('[notify-approval] Error: ' . $e->getMessage());
    jsonResponse(false, 'Error al procesar la solicitud.');
}
?>
