<?php
/**
 * NotificationMailer — Helper para disparar correo + notificación BD juntos
 *
 * Centraliza el patrón:
 *   1. Verificar si el tipo está activo en email_notification_config
 *   2. Obtener destinatarios según 'dirigido_a'
 *   3. Disparar EmailService (best-effort: nunca bloquea el flujo principal)
 *
 * USO:
 *   require_once __DIR__ . '/../utils/NotificationMailer.php';
 *   NotificationMailer::dispatch('nuevo_evento', 'Nuevo Evento', 'Descripción...', $db);
 */

if (!class_exists('EmailService')) {
    $emailPath = __DIR__ . '/../services/EmailService.php';
    if (file_exists($emailPath)) require_once $emailPath;
}
if (!class_exists('EnvLoader')) {
    require_once __DIR__ . '/../config/env-loader.php';
    EnvLoader::load();
}

class NotificationMailer
{
    /**
     * Despacha correo de notificación si el tipo está activo en la config.
     *
     * @param string $eventoTipo  Clave del tipo (ej. 'nuevo_evento')
     * @param string $titulo      Asunto / título del correo
     * @param string $contenido   Cuerpo del mensaje
     * @param PDO    $db          Conexión a la BD (ya instanciada)
     * @param string|null $emailEspecifico  Para 'destinatario' — email concreto
     */
    public static function dispatch(
        string $eventoTipo,
        string $titulo,
        string $contenido,
        PDO    $db,
        ?string $emailEspecifico = null
    ): void {
        try {
            // Leer configuración del tipo
            $stmt = $db->prepare(
                "SELECT activo, dirigido_a FROM email_notification_config WHERE evento_tipo = ? LIMIT 1"
            );
            $stmt->execute([$eventoTipo]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si no existe la tabla/config aún, salir silenciosamente
            if (!$config || !(int)$config['activo']) {
                return;
            }

            $dirigidoA = $config['dirigido_a'];

            // ── Caso: sólo al admin ───────────────────────────────────────
            if ($dirigidoA === 'admin') {
                $adminEmail = EnvLoader::get('MAIL_ADMIN', EnvLoader::get('MAIL_USER', ''));
                if ($adminEmail) {
                    EmailService::sendNotification($adminEmail, 'Administrador', $titulo, $contenido);
                    error_log("✅ [NotificationMailer] admin ← $eventoTipo");
                }
                return;
            }

            // ── Caso: destinatario específico (buzón) ─────────────────────
            if ($dirigidoA === 'destinatario' && $emailEspecifico) {
                // Obtener nombre del usuario
                $stmtUser = $db->prepare(
                    "SELECT nombre, apellidos FROM usuarios_perfil WHERE email = ? AND activo = 1 LIMIT 1"
                );
                $stmtUser->execute([$emailEspecifico]);
                $usuario = $stmtUser->fetch(PDO::FETCH_ASSOC);
                $nombre  = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')) ?: 'Usuario';

                EmailService::sendNotification($emailEspecifico, $nombre, $titulo, $contenido);
                error_log("✅ [NotificationMailer] $emailEspecifico ← $eventoTipo");
                return;
            }

            // ── Caso: todos los usuarios activos ──────────────────────────
            if ($dirigidoA === 'todos') {
                $stmtUsers = $db->query(
                    "SELECT email, nombre, apellidos
                     FROM usuarios_perfil
                     WHERE activo = 1 AND estado_usuario = 'activo'
                     LIMIT 500"  // Límite de seguridad anti-spam masivo
                );
                $usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

                $enviados = 0;
                foreach ($usuarios as $u) {
                    if (empty($u['email'])) continue;
                    $nombre = trim(($u['nombre'] ?? '') . ' ' . ($u['apellidos'] ?? '')) ?: 'Usuario';
                    EmailService::sendNotification($u['email'], $nombre, $titulo, $contenido);
                    $enviados++;
                    // Pequeña pausa para no saturar el servidor SMTP
                    if ($enviados % 10 === 0) usleep(100000); // 100ms cada 10 correos
                }

                error_log("✅ [NotificationMailer] $enviados usuarios ← $eventoTipo");
            }

        } catch (Exception $e) {
            // NUNCA bloquear el flujo principal por un fallo de correo
            error_log("⚠️ [NotificationMailer] Error en '$eventoTipo': " . $e->getMessage());
        }
    }
}
?>
