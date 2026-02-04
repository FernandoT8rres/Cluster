<?php
/**
 * Security Headers
 * Configuración de headers HTTP de seguridad
 * 
 * Incluir al inicio de cada archivo PHP público:
 * require_once 'middleware/security-headers.php';
 */

// Prevenir clickjacking
header('X-Frame-Options: DENY');

// Prevenir MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Activar protección XSS del navegador
header('X-XSS-Protection: 1; mode=block');

// Política de referrer
header('Referrer-Policy: strict-origin-when-cross-origin');

// Política de permisos
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Content Security Policy
$csp = [
    "default-src 'self'",
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
    "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
    "img-src 'self' data: https: blob:",
    "connect-src 'self'",
    "frame-ancestors 'none'",
    "base-uri 'self'",
    "form-action 'self'"
];
header('Content-Security-Policy: ' . implode('; ', $csp));

// HSTS (solo si se usa HTTPS)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Deshabilitar cache para páginas con datos sensibles
// Descomentar si es necesario:
// header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
// header('Pragma: no-cache');
// header('Expires: 0');
?>
