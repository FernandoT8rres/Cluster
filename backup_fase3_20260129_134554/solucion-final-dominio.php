<?php
/**
 * Script final para solucionar la redirección del dominio
 * Identifica qué archivo está interfiriendo y lo soluciona
 */

header('Content-Type: text/html; charset=UTF-8');
echo "<!DOCTYPE html><html><head><title>🔧 Solución Final - Dominio Login</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; background: #f5f5f5; }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
h1 { color: #C7252B; border-bottom: 3px solid #C7252B; padding-bottom: 10px; }
h2 { color: #333; margin-top: 30px; }
.status { padding: 10px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.btn { padding: 8px 16px; background: #C7252B; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
.btn:hover { background: #8B1538; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
.file-tree { background: #f8f9fa; padding: 15px; border-left: 4px solid #C7252B; margin: 15px 0; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Solución Final - Dominio Mostrando Login</h1>";
echo "<p><strong>Problema:</strong> El dominio principal muestra el dashboard completo en lugar del login</p>";

$fixes = [];
$issues = [];

// Paso 1: Diagnosticar la situación actual
echo "<h2>🔍 Paso 1: Diagnóstico del Problema</h2>";

$currentDir = '/Users/fernandotorres/Desktop/Clúster_BD';
$buildDir = $currentDir . '/build';

echo "<div class='info status'>📂 Estructura de archivos detectada:</div>";
echo "<div class='file-tree'>";
echo "<strong>Directorio raíz del proyecto:</strong> /Users/fernandotorres/Desktop/Clúster_BD/<br>";
echo "<strong>Directorio build:</strong> /Users/fernandotorres/Desktop/Clúster_BD/build/<br>";
echo "</div>";

// Verificar archivos en la raíz
$rootFiles = [
    'index.html' => 'Página principal de redirección',
    'index.php' => 'Script de redirección inteligente',
    '.htaccess' => 'Configuración del servidor'
];

echo "<h3>Archivos en la raíz del proyecto:</h3>";
foreach ($rootFiles as $file => $desc) {
    $fullPath = $currentDir . '/' . $file;
    $exists = file_exists($fullPath);
    $status = $exists ? 'success' : 'warning';
    $icon = $exists ? '✅' : '⚠️';
    
    echo "<div class='status $status'>$icon <code>$file</code> - $desc " . ($exists ? "(Existe)" : "(No existe)") . "</div>";
    
    if (!$exists && $file !== 'index.php') {
        $issues[] = "Archivo $file faltante en la raíz";
    }
}

// Verificar archivos en build que podrían interferir
echo "<h3>Archivos en /build/ que podrían interferir:</h3>";
$buildFiles = [
    'index.html' => 'Dashboard principal (debe estar como dashboard.html)',
    'dashboard.html' => 'Dashboard renombrado correctamente'
];

foreach ($buildFiles as $file => $desc) {
    $fullPath = $buildDir . '/' . $file;
    $exists = file_exists($fullPath);
    
    if ($file === 'index.html' && $exists) {
        echo "<div class='status error'>❌ <code>build/$file</code> - $desc (PROBLEMA: Interfiere con la redirección)</div>";
        $issues[] = "build/index.html interfiere con la redirección";
    } elseif ($file === 'dashboard.html' && $exists) {
        echo "<div class='status success'>✅ <code>build/$file</code> - $desc</div>";
    } elseif ($file === 'dashboard.html' && !$exists) {
        echo "<div class='status warning'>⚠️ <code>build/$file</code> - $desc (Faltante)</div>";
        $issues[] = "build/dashboard.html faltante";
    } else {
        echo "<div class='status info'>ℹ️ <code>build/$file</code> - $desc (" . ($exists ? "Existe" : "No existe") . ")</div>";
    }
}

// Paso 2: Solucionar los problemas identificados
echo "<h2>🔧 Paso 2: Aplicando Soluciones</h2>";

// Solución 1: Mover build/index.html si existe
if (file_exists($buildDir . '/index.html')) {
    echo "<div class='warning status'>🔄 Moviendo build/index.html que interfiere...</div>";
    
    try {
        // Si no existe dashboard.html, renombrar index.html
        if (!file_exists($buildDir . '/dashboard.html')) {
            if (rename($buildDir . '/index.html', $buildDir . '/dashboard.html')) {
                echo "<div class='success status'>✅ build/index.html renombrado a dashboard.html</div>";
                $fixes[] = 'build/index.html convertido en dashboard.html';
            } else {
                echo "<div class='error status'>❌ Error renombrando build/index.html</div>";
            }
        } else {
            // Si dashboard.html ya existe, hacer backup de index.html
            if (rename($buildDir . '/index.html', $buildDir . '/index-backup-' . date('Y-m-d-H-i-s') . '.html')) {
                echo "<div class='success status'>✅ build/index.html movido a backup</div>";
                $fixes[] = 'build/index.html movido a backup';
            } else {
                echo "<div class='error status'>❌ Error moviendo build/index.html</div>";
            }
        }
    } catch (Exception $e) {
        echo "<div class='error status'>❌ Error procesando build/index.html: " . $e->getMessage() . "</div>";
    }
}

// Solución 2: Verificar que el index.html raíz esté correcto
if (!file_exists($currentDir . '/index.html')) {
    echo "<div class='warning status'>🔄 Creando index.html de redirección en la raíz...</div>";
    
    // Este archivo ya fue creado anteriormente, verificar que existe
    if (file_exists($currentDir . '/index.html')) {
        echo "<div class='success status'>✅ index.html de redirección ya existe</div>";
    } else {
        echo "<div class='error status'>❌ Error: No se pudo crear index.html de redirección</div>";
    }
}

// Solución 3: Crear .htaccess específico para la raíz del servidor web
echo "<div class='info status'>🔄 Verificando configuración del servidor web...</div>";

// El .htaccess debe estar en la raíz del dominio web, no en nuestro directorio de trabajo
$webRootHtaccess = $currentDir . '/.htaccess';
if (file_exists($webRootHtaccess)) {
    echo "<div class='success status'>✅ .htaccess existe en la raíz del proyecto</div>";
} else {
    echo "<div class='warning status'>⚠️ .htaccess no encontrado en la raíz</div>";
}

// Paso 3: Crear configuración específica para el servidor web
echo "<h2>🌐 Paso 3: Configuración del Servidor Web</h2>";

echo "<div class='info status'>";
echo "<strong>📋 Configuración requerida en el servidor web:</strong><br><br>";
echo "El servidor web (Apache/Nginx) debe estar configurado para que el dominio <code>intranet.clústermetropolitano.mx</code> apunte al directorio:<br>";
echo "<code>/Users/fernandotorres/Desktop/Clúster_BD/</code><br><br>";
echo "Y NO al subdirectorio <code>build/</code>";
echo "</div>";

// Verificar la configuración actual
echo "<h3>Estado actual de la configuración:</h3>";

if (file_exists($currentDir . '/index.html')) {
    echo "<div class='success status'>✅ Archivo de redirección principal: <strong>Listo</strong></div>";
} else {
    echo "<div class='error status'>❌ Archivo de redirección principal: <strong>Faltante</strong></div>";
}

if (!file_exists($buildDir . '/index.html') || file_exists($buildDir . '/dashboard.html')) {
    echo "<div class='success status'>✅ Conflicto build/index.html: <strong>Resuelto</strong></div>";
} else {
    echo "<div class='error status'>❌ Conflicto build/index.html: <strong>Persiste</strong></div>";
}

// Paso 4: Verificación y pruebas
echo "<h2>🧪 Paso 4: Verificación y Pruebas</h2>";

echo "<div class='info status'>";
echo "<h4>🔗 Enlaces para probar:</h4>";
echo "<ul>";
echo "<li><strong>Dominio principal:</strong> <a href='https://intranet.clústermetropolitano.mx' target='_blank'>https://intranet.clústermetropolitano.mx</a></li>";
echo "<li><strong>Index de redirección:</strong> <a href='../index.html' target='_blank'>Ver index.html</a></li>";
echo "<li><strong>Login directo:</strong> <a href='../build/pages/sign-in.html' target='_blank'>Login</a></li>";
echo "<li><strong>Dashboard:</strong> <a href='../build/dashboard.html' target='_blank'>Dashboard</a></li>";
echo "</ul>";
echo "</div>";

echo "<h3>🎯 Resultado esperado:</h3>";
echo "<div class='success status'>";
echo "<ol>";
echo "<li><strong>Al acceder a https://intranet.clústermetropolitano.mx</strong><br>";
echo "   → Debe mostrar la pantalla de redirección con el logo de Clúster</li>";
echo "<li><strong>Después de 2 segundos</strong><br>";
echo "   → Redirige automáticamente al login (build/pages/sign-in.html)</li>";
echo "<li><strong>Después del login exitoso</strong><br>";
echo "   → Redirige al dashboard (build/dashboard.html)</li>";
echo "</ol>";
echo "</div>";

// Resumen final
echo "<h2>📋 Resumen de Cambios</h2>";

if (count($fixes) > 0) {
    echo "<div class='success status'>";
    echo "<h3>✅ Correcciones Aplicadas:</h3>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (count($issues) > 0) {
    echo "<div class='error status'>";
    echo "<h3>⚠️ Problemas Identificados:</h3>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<h3>🚀 Pasos finales recomendados:</h3>";
echo "<div class='info status'>";
echo "<ol>";
echo "<li><strong>Verificar configuración del servidor:</strong> Asegúrate que el dominio apunte a la raíz del proyecto, no a /build/</li>";
echo "<li><strong>Limpiar cache del navegador:</strong> Ctrl+F5 o Cmd+Shift+R</li>";
echo "<li><strong>Probar en incógnito:</strong> Para evitar cache del navegador</li>";
echo "<li><strong>Verificar .htaccess:</strong> Que esté en la ubicación correcta del servidor web</li>";
echo "</ol>";
echo "</div>";

// Botones de acción
echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='https://intranet.clústermetropolitano.mx' target='_blank' class='btn'>🌐 Probar Dominio</a>";
echo "<a href='../index.html' target='_blank' class='btn'>👀 Ver Index</a>";
echo "<a href='../build/pages/sign-in.html' target='_blank' class='btn'>🔐 Login Directo</a>";
echo "<button onclick='window.location.reload()' class='btn'>🔄 Ejecutar Nuevamente</button>";
echo "</div>";

echo "</div></body></html>";
?>