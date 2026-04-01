<?php
/**
 * forgot-password.php — Solicitud de recuperación de contraseña
 *
 * POST { email }
 *   → valida existencia del usuario
 *   → genera token seguro (64 hex chars)
 *   → guarda en email_tokens con expiración 1h
 *   → envía correo vía EmailService
 *
 * Rate limit: 3 solicitudes / hora por email
 * Respuesta: siempre genérica (no revelar si email existe)
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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');

    // Validación básica
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Por favor ingresa un correo válido.');
    }

    $email = strtolower($email);
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $db   = Database::getInstance()->getConnection();

    // ── Rate Limiting manual: max 3 tokens por email en la última hora ───
    $stmtRate = $db->prepare(
        "SELECT COUNT(*) FROM email_tokens
         WHERE user_email = :email AND tipo = 'password_reset'
           AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    $stmtRate->bindParam(':email', $email);
    $stmtRate->execute();

    if ((int)$stmtRate->fetchColumn() >= 3) {
        SecurityLogger::log('password_reset_limit', 'WARNING', ['email' => $email, 'ip' => $ip]);
        // Respuesta genérica — no revelar si email existe
        jsonResponse(true, 'Si el correo está registrado, recibirás las instrucciones en breve.');
    }

    // ── Verificar si el usuario existe ──────────────────────────────────
    $stmtUser = $db->prepare(
        "SELECT id, nombre, apellidos, estado_usuario
         FROM usuarios_perfil
         WHERE email = :email AND activo = 1
         LIMIT 1"
    );
    $stmtUser->bindParam(':email', $email);
    $stmtUser->execute();
    $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Respuesta genérica siempre — no revelar si existe o no
    if (!$usuario) {
        // Espera artificial para evitar timing attacks
        sleep(1);
        jsonResponse(true, 'Si el correo está registrado, recibirás las instrucciones en breve.');
    }

    // Verificar que la cuenta esté activa
    if ($usuario['estado_usuario'] !== 'activo') {
        jsonResponse(false, 'Tu cuenta aún no está activa. Contacta al administrador.');
    }

    // ── Generar token seguro ─────────────────────────────────────────────
    $token     = bin2hex(random_bytes(32)); // 64 chars hex
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $nombre    = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));

    // Invalidar tokens previos no usados del mismo email
    $db->prepare(
        "UPDATE email_tokens SET usado = 1
         WHERE user_email = :email AND tipo = 'password_reset' AND usado = 0"
    )->execute([':email' => $email]);

    // Insertar nuevo token
    $stmtInsert = $db->prepare(
        "INSERT INTO email_tokens (user_email, token, tipo, expires_at, ip_origen)
         VALUES (:email, :token, 'password_reset', :expires_at, :ip)"
    );
    $stmtInsert->execute([
        ':email'      => $email,
        ':token'      => $token,
        ':expires_at' => $expiresAt,
        ':ip'         => $ip,
    ]);

    // ── Enviar correo ────────────────────────────────────────────────────
    $result = EmailService::sendPasswordReset($email, $nombre ?: 'Usuario', $token);

    if (!$result['success']) {
        // Log el fallo pero respuesta genérica positiva al usuario
        SecurityLogger::log('email_send_failed', 'ERROR', [
            'email'  => $email,
            'motivo' => $result['message'],
            'tipo'   => 'password_reset'
        ]);
    }

    SecurityLogger::log('password_reset_requested', 'INFO', ['email' => $email, 'ip' => $ip]);

    jsonResponse(true, 'Si el correo está registrado, recibirás las instrucciones en breve.');

} catch (Exception $e) {
    error_log('[forgot-password] Error: ' . $e->getMessage());
    jsonResponse(false, 'Ocurrió un error. Intenta nuevamente.');
}
?>
