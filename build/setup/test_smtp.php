<?php
/**
 * test_smtp.php — Script de diagnóstico SMTP
 *
 * Ejecutar SOLO localmente para verificar la configuración SMTP.
 * NUNCA subir a producción ni dejar accesible públicamente.
 *
 * USO:
 *   php build/setup/test_smtp.php
 *
 * REQUISITOS:
 *   - MAIL_PASS configurado en build/.env
 *   - Tabla email_tokens creada (para prueba completa)
 */

// Asegurarse de ejecutar desde raíz del proyecto
$buildPath = __DIR__ . '/..';

echo "\n╔══════════════════════════════════════════╗\n";
echo "║  DIAGNÓSTICO SMTP — Claut Intranet       ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

// Cargar dependencias
require_once $buildPath . '/config/env-loader.php';
EnvLoader::load($buildPath . '/.env');

echo "📋 Configuración SMTP detectada:\n";
echo "   Host:       " . EnvLoader::get('MAIL_HOST', '(no configurado)') . "\n";
echo "   Port:       " . EnvLoader::get('MAIL_PORT', '(no configurado)') . "\n";
echo "   Encryption: " . EnvLoader::get('MAIL_ENCRYPTION', '(no configurado)') . "\n";
echo "   User:       " . EnvLoader::get('MAIL_USER', '(no configurado)') . "\n";
echo "   Pass:       " . (EnvLoader::get('MAIL_PASS', '') !== '' && EnvLoader::get('MAIL_PASS') !== 'TU_CLAVE_SMTP_AQUI' ? '✅ Configurada' : '❌ SIN CONFIGURAR (MAIL_PASS vacío o placeholder)') . "\n";
echo "   From:       " . EnvLoader::get('MAIL_FROM', '(no configurado)') . "\n\n";

$mailPass = EnvLoader::get('MAIL_PASS', '');
if (empty($mailPass) || $mailPass === 'TU_CLAVE_SMTP_AQUI' || $mailPass === 'contrasena_smtp_aqui') {
    echo "⛔ ERROR: MAIL_PASS no está configurada en .env\n";
    echo "   → Obtener la contraseña desde:\n";
    echo "     hPanel Hostinger → Hosting → Emails → auxsistemas@clautmetropolitano.mx\n";
    echo "   → Luego actualizar build/.env: MAIL_PASS=tu_contraseña\n\n";
    exit(1);
}

// Cargar EmailService
require_once $buildPath . '/services/EmailService.php';

echo "🔌 Probando conexión SMTP...\n";

$adminEmail = EnvLoader::get('MAIL_ADMIN', EnvLoader::get('MAIL_USER'));
$result = EmailService::sendAdminAlert(
    '✅ Prueba SMTP — Claut Intranet',
    "Este correo confirma que el sistema de envío SMTP está configurado correctamente.\n\n" .
    "Servidor: " . EnvLoader::get('MAIL_HOST') . ":" . EnvLoader::get('MAIL_PORT') . "\n" .
    "Fecha de prueba: " . date('Y-m-d H:i:s') . "\n\n" .
    "Puedes ignorar este mensaje de prueba."
);

if ($result['success']) {
    echo "✅ CORREO ENVIADO CORRECTAMENTE\n";
    echo "   → Revisa la bandeja de: $adminEmail\n";
    echo "   → Si no llega, revisa la carpeta de SPAM\n\n";
    echo "🎉 El sistema de correo SMTP está listo para producción.\n\n";
} else {
    echo "❌ ERROR AL ENVIAR: " . $result['message'] . "\n\n";
    echo "Posibles causas:\n";
    echo "  1. Contraseña incorrecta en MAIL_PASS\n";
    echo "  2. Puerto 465 bloqueado (probar con MAIL_PORT=587 y MAIL_ENCRYPTION=tls)\n";
    echo "  3. La cuenta de correo no existe en Hostinger\n";
    echo "  4. El servidor no tiene acceso a internet / SMTP externo bloqueado\n\n";
    exit(1);
}
?>
