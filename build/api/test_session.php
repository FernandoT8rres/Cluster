<?php
// Script de diagnóstico de sesiones
// Acceder vía navegador: /api/test_session.php

// 1. Configuración básica de cookies (imitando SessionConfig pero simple)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
session_name('CLAUT_TEST_SESSION');

// 2. Iniciar sesión
session_start();

$phase = $_GET['phase'] ?? '1';
$messages = [];

if ($phase === '1') {
    // FASE 1: Escribir datos
    $_SESSION['test_var'] = 'Probando persistencia ' . time();
    $_SESSION['test_array'] = ['email' => 'test@example.com', 'role' => 'admin'];
    
    $messages[] = "FASE 1: Datos escritos en sesión.";
    $messages[] = "ID de sesión: " . session_id();
    $messages[] = "Valor guardado: " . $_SESSION['test_var'];
    
    // Forzar escritura
    session_write_close();
    
    $messages[] = "Sesión cerrada y escrita.";
    $messages[] = "<a href='?phase=2'>Haga clic aquí para ir a la FASE 2 (Leer datos)</a>";

} elseif ($phase === '2') {
    // FASE 2: Leer datos
    $messages[] = "FASE 2: Leyendo datos de sesión...";
    $messages[] = "ID de sesión: " . session_id();
    
    if (isset($_SESSION['test_var'])) {
        $messages[] = "✅ ÉXITO: Variable 'test_var' encontrada: " . $_SESSION['test_var'];
    } else {
        $messages[] = "❌ ERROR: Variable 'test_var' NO encontrada.";
    }
    
    if (isset($_SESSION['test_array'])) {
        $messages[] = "✅ ÉXITO: Array recuperado correctamente.";
        $messages[] = "Datos: " . print_r($_SESSION['test_array'], true);
    } else {
        $messages[] = "❌ ERROR: Array 'test_array' NO encontrado.";
    }
    
    $messages[] = "Contenido completo de \$_SESSION: " . print_r($_SESSION, true);
    $messages[] = "<a href='?phase=1'>Volver a empezar</a>";
}

// Salida HTML simple
echo "<html><body style='font-family: sans-serif; padding: 20px;'>";
echo "<h1>Diagnóstico de Sesión PHP</h1>";
foreach ($messages as $msg) {
    echo "<p>{$msg}</p>";
}
echo "</body></html>";
