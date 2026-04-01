<?php
/**
 * EmailService — Servicio Central de Correo Claut Intranet
 *
 * Motor de envío de correos vía SMTP usando PHPMailer standalone.
 * Toda la configuración se lee del .env vía EnvLoader.
 *
 * USO:
 *   require_once __DIR__ . '/../services/EmailService.php';
 *   $result = EmailService::sendPasswordReset($email, $nombre, $token);
 *
 * IMPORTANTE:
 *   - NUNCA hardcodear credenciales aquí — solo leer de EnvLoader
 *   - Todos los métodos retornan ['success' => bool, 'message' => string]
 *   - El envío es best-effort: un fallo NO debe detener el flujo principal
 */

// Carga PHPMailer standalone (sin Composer)
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carga variables de entorno
if (!class_exists('EnvLoader')) {
    require_once __DIR__ . '/../config/env-loader.php';
}
EnvLoader::load();

class EmailService
{
    // ─── Paleta Corporativa ──────────────────────────────────────────────
    private const BRAND_RED    = '#C7252B';
    private const BRAND_DARK   = '#1e293b';
    private const BRAND_LIGHT  = '#f8fafc';
    private const BRAND_NAME   = 'Clúster Automotriz Metropolitano';
    private const APP_URL      = 'https://intranet.clautmetropolitano.mx';

    // ─── Crear instancia PHPMailer configurada ───────────────────────────
    private static function buildMailer(): PHPMailer
    {
        $mailer = new PHPMailer(true); // true = lanzar excepciones

        // SMTP config desde .env
        $mailer->isSMTP();
        $mailer->Host       = EnvLoader::get('MAIL_HOST', 'smtp.hostinger.com');
        $mailer->SMTPAuth   = true;
        $mailer->Username   = EnvLoader::get('MAIL_USER', '');
        $mailer->Password   = EnvLoader::get('MAIL_PASS', '');
        $mailer->Port       = (int) EnvLoader::get('MAIL_PORT', 465);

        // Cifrado por puerto
        $encryption = EnvLoader::get('MAIL_ENCRYPTION', 'ssl');
        $mailer->SMTPSecure = ($encryption === 'tls') ? PHPMailer::ENCRYPTION_STARTTLS
                                                      : PHPMailer::ENCRYPTION_SMTPS;
        // Remitente
        $fromEmail = EnvLoader::get('MAIL_FROM', 'auxsistemas@clautmetropolitano.mx');
        $fromName  = EnvLoader::get('MAIL_FROM_NAME', 'Claut Intranet');
        $mailer->setFrom($fromEmail, $fromName);
        $mailer->CharSet   = 'UTF-8';
        $mailer->isHTML(true);

        // Debug: solo en desarrollo
        $mailer->SMTPDebug = (EnvLoader::get('APP_ENV', 'production') === 'development')
            ? SMTP::DEBUG_SERVER
            : SMTP::DEBUG_OFF;

        return $mailer;
    }

    // ─── Envío genérico interno ──────────────────────────────────────────
    private static function send(string $toEmail, string $toName, string $subject, string $htmlBody): array
    {
        try {
            $mailer = self::buildMailer();
            $mailer->addAddress($toEmail, $toName);
            $mailer->Subject = $subject;
            $mailer->Body    = $htmlBody;
            $mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $htmlBody));

            $mailer->send();

            error_log("✅ [EmailService] Correo enviado a: $toEmail | Asunto: $subject");
            return ['success' => true, 'message' => 'Correo enviado correctamente'];

        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            error_log("❌ [EmailService] Error enviando a $toEmail: $errorMsg");
            return ['success' => false, 'message' => 'Error al enviar correo: ' . $errorMsg];
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // MÉTODOS PÚBLICOS
    // ════════════════════════════════════════════════════════════════════

    /**
     * Correo de recuperación de contraseña
     */
    public static function sendPasswordReset(string $email, string $nombre, string $token): array
    {
        $resetUrl = self::APP_URL . '/pages/sign-in.html?reset_token=' . urlencode($token);
        $subject  = '🔐 Recupera tu contraseña — Clúster Intranet';

        $body = self::wrapTemplate(
            'Recuperación de Contraseña',
            "
            <p style=\"color: #475569; font-size: 16px; line-height: 1.7;\">
                Hola <strong style=\"color: " . self::BRAND_DARK . ";\">$nombre</strong>,
            </p>
            <p style=\"color: #475569; font-size: 15px; line-height: 1.7;\">
                Recibimos una solicitud para restablecer la contraseña de tu cuenta en la Intranet del
                <strong>" . self::BRAND_NAME . "</strong>.
            </p>
            <p style=\"color: #475569; font-size: 15px; line-height: 1.7;\">
                Haz clic en el botón para crear una nueva contraseña. Este enlace es válido durante
                <strong>1 hora</strong>.
            </p>
            " . self::ctaButton($resetUrl, '🔐 Restablecer Contraseña') . "
            <p style=\"margin-top: 24px; color: #94a3b8; font-size: 13px; line-height: 1.6;\">
                Si no solicitaste este cambio, ignora este correo. Tu contraseña actual permanece sin cambios.
            </p>
            <p style=\"color: #94a3b8; font-size: 12px; margin-top: 8px;\">
                ¿Problemas con el botón? Copia y pega este enlace en tu navegador:<br>
                <a href=\"$resetUrl\" style=\"color: " . self::BRAND_RED . "; word-break: break-all;\">$resetUrl</a>
            </p>"
        );

        return self::send($email, $nombre, $subject, $body);
    }

    /**
     * Correo de verificación de cuenta al registrarse
     */
    public static function sendAccountVerification(string $email, string $nombre, string $token): array
    {
        $verifyUrl = self::APP_URL . '/api/auth/verify-account.php?token=' . urlencode($token);
        $subject   = '✅ Verifica tu cuenta — Clúster Intranet';

        $body = self::wrapTemplate(
            'Verifica tu Cuenta',
            "
            <p style=\"color: #475569; font-size: 16px; line-height: 1.7;\">
                ¡Bienvenido/a a la Intranet, <strong style=\"color: " . self::BRAND_DARK . ";\">$nombre</strong>!
            </p>
            <p style=\"color: #475569; font-size: 15px; line-height: 1.7;\">
                Tu registro en el <strong>" . self::BRAND_NAME . "</strong> fue recibido y está
                <strong>pendiente de aprobación</strong> por un administrador.
            </p>
            <p style=\"color: #475569; font-size: 15px; line-height: 1.7;\">
                Para agilizar el proceso, por favor confirma tu dirección de correo electrónico:
            </p>
            " . self::ctaButton($verifyUrl, '✅ Confirmar mi Correo') . "
            <div style=\"background: #f1f5f9; border-radius: 12px; padding: 16px; margin-top: 20px;\">
                <p style=\"color: #64748b; font-size: 13px; margin: 0;\">
                    📌 Una vez que confirmes tu correo y un administrador apruebe tu cuenta,
                    recibirás otro correo para iniciar sesión.
                </p>
            </div>"
        );

        return self::send($email, $nombre, $subject, $body);
    }

    /**
     * Correo cuando el admin aprueba una cuenta
     */
    public static function sendAccountApproved(string $email, string $nombre): array
    {
        $loginUrl = self::APP_URL . '/pages/sign-in.html';
        $subject  = '🎉 ¡Tu cuenta fue aprobada! — Clúster Intranet';

        $body = self::wrapTemplate(
            '¡Cuenta Aprobada!',
            "
            <p style=\"color: #475569; font-size: 16px; line-height: 1.7;\">
                ¡Excelente noticia, <strong style=\"color: " . self::BRAND_DARK . ";\">$nombre</strong>!
            </p>
            <p style=\"color: #475569; font-size: 15px; line-height: 1.7;\">
                Tu cuenta en la Intranet del <strong>" . self::BRAND_NAME . "</strong> ha sido
                <strong style=\"color: #16a34a;\">aprobada y activada</strong>.
            </p>
            <p style=\"color: #475569; font-size: 15px; line-height: 1.7;\">
                Ya puedes acceder al sistema con las credenciales que registraste:
            </p>
            " . self::ctaButton($loginUrl, '🚀 Acceder al Sistema') . "
            <div style=\"background: #f0fdf4; border-left: 4px solid #16a34a; border-radius: 8px; padding: 16px; margin-top: 20px;\">
                <p style=\"color: #15803d; font-size: 13px; margin: 0;\">
                    ✓ Accede con tu correo electrónico y la contraseña que definiste al registrarte.
                </p>
            </div>"
        );

        return self::send($email, $nombre, $subject, $body);
    }

    /**
     * Correo cuando el admin rechaza una cuenta
     */
    public static function sendAccountRejected(string $email, string $nombre, string $razon = ''): array
    {
        $subject = '❌ Solicitud de cuenta — Clúster Intranet';

        $razonTexto = $razon
            ? "<p style=\"color: #475569; font-size: 14px; margin-top: 8px;\"><strong>Motivo:</strong> " . htmlspecialchars($razon) . "</p>"
            : '';

        $body = self::wrapTemplate(
            'Solicitud No Aprobada',
            "
            <p style=\"color: #475569; font-size: 16px; line-height: 1.7;\">
                Hola <strong style=\"color: " . self::BRAND_DARK . ";\">$nombre</strong>,
            </p>
            <p style=\"color: #475569; font-size: 15px; line-height: 1.7;\">
                Lamentamos informarte que tu solicitud de acceso a la Intranet del
                <strong>" . self::BRAND_NAME . "</strong> no fue aprobada en esta ocasión.
            </p>
            $razonTexto
            <div style=\"background: #fef2f2; border-left: 4px solid " . self::BRAND_RED . "; border-radius: 8px; padding: 16px; margin-top: 20px;\">
                <p style=\"color: #991b1b; font-size: 13px; margin: 0;\">
                    Si consideras que esto es un error, por favor contacta a tu coordinador o al
                    área de sistemas del Clúster.
                </p>
            </div>"
        );

        return self::send($email, $nombre, $subject, $body);
    }

    /**
     * Correo de alerta/notificación básica
     */
    public static function sendNotification(string $email, string $nombre, string $titulo, string $contenido): array
    {
        $subject = '🔔 ' . $titulo . ' — Clúster Intranet';

        $body = self::wrapTemplate(
            $titulo,
            "
            <p style=\"color: #475569; font-size: 16px; line-height: 1.7;\">
                Hola <strong style=\"color: " . self::BRAND_DARK . ";\">$nombre</strong>,
            </p>
            <p style=\"color: #475569; font-size: 15px; line-height: 1.7;\">
                " . nl2br(htmlspecialchars($contenido)) . "
            </p>
            " . self::ctaButton(self::APP_URL . '/dashboard.html', '📋 Ver en el Sistema')
        );

        return self::send($email, $nombre, $subject, $body);
    }

    /**
     * Alerta de seguridad para el administrador del sistema
     */
    public static function sendAdminAlert(string $titulo, string $contenido): array
    {
        $adminEmail = EnvLoader::get('MAIL_ADMIN', 'auxsistemas@clautmetropolitano.mx');
        return self::sendNotification($adminEmail, 'Administrador', $titulo, $contenido);
    }

    // ════════════════════════════════════════════════════════════════════
    // HELPERS DE PLANTILLA HTML
    // ════════════════════════════════════════════════════════════════════

    /**
     * Envuelve el contenido en el layout HTML base con branding Claut
     */
    private static function wrapTemplate(string $titulo, string $contenido): string
    {
        $year      = date('Y');
        $brandRed  = self::BRAND_RED;
        $brandDark = self::BRAND_DARK;
        $brandName = self::BRAND_NAME;
        $appUrl    = self::APP_URL;

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$titulo — Clúster Intranet</title>
</head>
<body style="margin:0; padding:0; font-family: 'Segoe UI', Arial, sans-serif; background:#f1f5f9;">

    <!-- Wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9; padding: 40px 20px;">
        <tr>
            <td align="center">

                <!-- Card -->
                <table width="600" cellpadding="0" cellspacing="0"
                       style="background:#ffffff; border-radius:24px; overflow:hidden;
                              box-shadow: 0 10px 40px rgba(0,0,0,0.08); max-width:600px; width:100%;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, $brandRed 0%, #a51d22 100%);
                                   padding: 32px 40px; text-align:center;">
                            <p style="margin:0; color:rgba(255,255,255,0.8); font-size:11px;
                                      font-weight:800; letter-spacing:3px; text-transform:uppercase;">
                                CLÚSTER AUTOMOTRIZ METROPOLITANO
                            </p>
                            <h1 style="margin:8px 0 0; color:#ffffff; font-size:26px;
                                       font-weight:800; letter-spacing:-0.5px;">
                                $titulo
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            $contenido
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <hr style="border:none; border-top:1px solid #e2e8f0; margin:0;">
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 24px 40px; text-align:center;">
                            <p style="margin:0; color:#94a3b8; font-size:12px; line-height:1.6;">
                                Este es un correo automático del sistema Intranet del<br>
                                <strong style="color:$brandDark;">$brandName</strong>.<br>
                                Por favor no respondas directamente a este mensaje.
                            </p>
                            <p style="margin: 12px 0 0; color:#cbd5e1; font-size:11px;">
                                © $year $brandName · <a href="$appUrl" style="color:#94a3b8;">intranet.clautmetropolitano.mx</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Genera un botón CTA con estilo Porsche
     */
    private static function ctaButton(string $url, string $texto): string
    {
        $brandRed = self::BRAND_RED;
        return <<<HTML
<table width="100%" cellpadding="0" cellspacing="0" style="margin: 28px 0;">
    <tr>
        <td align="center">
            <a href="$url"
               style="display:inline-block; background: linear-gradient(135deg, $brandRed 0%, #a51d22 100%);
                      color:#ffffff; text-decoration:none; font-weight:700; font-size:15px;
                      padding: 16px 40px; border-radius:14px;
                      box-shadow: 0 8px 20px rgba(199,37,43,0.35); letter-spacing:0.3px;">
                $texto
            </a>
        </td>
    </tr>
</table>
HTML;
    }
}
?>
