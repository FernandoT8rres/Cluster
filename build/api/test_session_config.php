<?php
// Script de diagnóstico de SessionConfig
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('CLAUT_ACCESS', true);
// Fix path: __DIR__ is build/api, so dirname(__DIR__) is build
require_once dirname(__DIR__) . '/config/session-config.php';

// Iniciamos usando la clase real
SessionConfig::init();

$phase = $_GET['phase'] ?? '1';

echo "<h1>Diagnóstico de SessionConfig</h1>";
echo "<p>Session ID: " . session_id() . "</p>";

if ($phase === '1') {
    // FASE 1: Guardar datos usando la clase
    $_SESSION['config_test'] = 'Funciona con SessionConfig';
    $_SESSION['user_email'] = 'test@config.com';
    
    echo "<p>Guardando datos...</p>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    // Forzar escritura
    session_write_close();
    
    echo "<p><a href='?phase=2'>Ir a Fase 2 (Leer)</a></p>";
} elseif ($phase === '2') {
    // FASE 2: Leer datos
    echo "<p>Leyendo datos...</p>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    if (isset($_SESSION['config_test']) && $_SESSION['config_test'] === 'Funciona con SessionConfig') {
        echo "<h2 style='color:green'>✅ ÉXITO: SessionConfig funciona correctamente.</h2>";
    } else {
        echo "<h2 style='color:red'>❌ ERROR: Datos no persistidos con SessionConfig.</h2>";
    }
    
    echo "<p><a href='?phase=1'>Reiniciar</a></p>";
}
