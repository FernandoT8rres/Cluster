<?php
/**
 * CORS Middleware — Claut Intranet
 * 
 * Restricts cross-origin requests to allowed domains only.
 * SECURITY: Never use * in production — exposes APIs to any origin.
 * 
 * Usage: require_once __DIR__ . '/../middleware/cors.php'; setCorsHeaders();
 */

/**
 * Lista de orígenes permitidos
 */
function getAllowedOrigins(): array {
    return [
        'https://intranet.clautmetropolitano.mx',
        'http://localhost',
        'http://localhost:8000',
        'http://localhost:3000',
        'http://127.0.0.1',
        'http://127.0.0.1:8000',
    ];
}

/**
 * Establece los headers CORS correctos según el origen de la solicitud.
 * Si el origen no está en la lista, no se establece Access-Control-Allow-Origin
 * (el navegador bloqueará la solicitud automáticamente).
 */
function setCorsHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = getAllowedOrigins();

    if (in_array($origin, $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Credentials: true');
    } else {
        // En desarrollo sin Origin header (ej. Postman, curl, mismo servidor)
        // permitir sin restricción solo si no hay Origin header
        if (empty($origin)) {
            header('Access-Control-Allow-Origin: *');
        }
        // Si hay Origin pero no está en la lista: no se envía header → browser bloquea
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Vary: Origin');
}

/**
 * Maneja preflight OPTIONS y termina la ejecución.
 * Llamar DESPUÉS de setCorsHeaders().
 */
function handlePreflight(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
