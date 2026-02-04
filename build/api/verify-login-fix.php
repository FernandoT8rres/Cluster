<?php
/**
 * VERIFICAR SI LOGIN.PHP TIENE LOS CAMBIOS
 * Este archivo verifica si el código de login.php contiene el fix de sesión
 */

header('Content-Type: application/json; charset=UTF-8');

$loginFile = dirname(__DIR__) . '/api/auth/login.php';

if (!file_exists($loginFile)) {
    echo json_encode([
        'error' => 'login.php no encontrado',
        'path' => $loginFile
    ], JSON_PRETTY_PRINT);
    exit;
}

$content = file_get_contents($loginFile);

// Buscar las líneas que deberían estar en el fix
$hasSessionFix = strpos($content, '$_SESSION[\'user_rol\']') !== false;
$hasSessionEmail = strpos($content, '$_SESSION[\'user_email\']') !== false;
$hasSessionId = strpos($content, '$_SESSION[\'user_id\']') !== false;
$hasSessionRegenerate = strpos($content, 'SessionConfig::regenerate()') !== false;
$hasSessionComment = strpos($content, 'IMPORTANTE: Guardar datos en sesión') !== false;

echo json_encode([
    'file_exists' => true,
    'file_path' => $loginFile,
    'file_size' => filesize($loginFile),
    'last_modified' => date('Y-m-d H:i:s', filemtime($loginFile)),
    'has_session_fix' => $hasSessionFix && $hasSessionEmail && $hasSessionId,
    'checks' => [
        'has_user_rol' => $hasSessionFix,
        'has_user_email' => $hasSessionEmail,
        'has_user_id' => $hasSessionId,
        'has_regenerate' => $hasSessionRegenerate,
        'has_comment' => $hasSessionComment
    ],
    'verdict' => ($hasSessionFix && $hasSessionEmail && $hasSessionId) 
        ? '✅ LOGIN.PHP TIENE EL FIX' 
        : '❌ LOGIN.PHP NO TIENE EL FIX - NECESITA SUBIRSE'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
