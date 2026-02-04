<?php
/**
 * Diagnóstico de rutas para el sistema de administración
 * Verifica que todos los archivos necesarios estén en su lugar
 */

echo "<h1>🔍 Diagnóstico de Rutas - Sistema Admin</h1>";
echo "<style>body{font-family:Arial;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Obtener la ruta base
$currentDir = __DIR__;
$basePath = dirname($currentDir); // Subir un nivel desde /admin/

echo "<p><strong>📂 Directorio actual:</strong> $currentDir</p>";
echo "<p><strong>📁 Ruta base detectada:</strong> $basePath</p>";

// Archivos críticos a verificar
$archivos = [
    'Config Principal' => $basePath . '/assets/conexion/config.php',
    'JWT Helper' => $basePath . '/api/auth/jwt_helper.php',
    'Login API' => $basePath . '/api/auth/login.php',
    'Logout API' => $basePath . '/api/auth/logout.php',
    'Página de Login' => $basePath . '/pages/sign-in.html',
    'Dashboard Admin' => $currentDir . '/admin-dashboard.php',
    'Middleware Auth' => $currentDir . '/middleware/auth-admin.php',
    'Gestionar Usuarios' => $basePath . '/gestionar_usuarios.php'
];

echo "<h2>📋 Verificación de Archivos</h2>";

$errores = 0;
foreach ($archivos as $nombre => $ruta) {
    if (file_exists($ruta)) {
        echo "<span class='success'>✅ $nombre: OK</span><br>";
        echo "<span class='info'>   → $ruta</span><br><br>";
    } else {
        echo "<span class='error'>❌ $nombre: NO ENCONTRADO</span><br>";
        echo "<span class='error'>   → $ruta</span><br><br>";
        $errores++;
    }
}

// Verificar directorios
echo "<h2>📁 Verificación de Directorios</h2>";

$directorios = [
    'Admin' => $currentDir,
    'Middleware' => $currentDir . '/middleware',
    'Assets' => $basePath . '/assets',
    'API Auth' => $basePath . '/api/auth',
    'Pages' => $basePath . '/pages',
    'Uploads' => $basePath . '/uploads'
];

foreach ($directorios as $nombre => $ruta) {
    if (is_dir($ruta)) {
        $permisos = substr(sprintf('%o', fileperms($ruta)), -4);
        echo "<span class='success'>✅ Directorio $nombre: Existe (permisos: $permisos)</span><br>";
        echo "<span class='info'>   → $ruta</span><br><br>";
    } else {
        echo "<span class='error'>❌ Directorio $nombre: NO EXISTE</span><br>";
        echo "<span class='error'>   → $ruta</span><br><br>";
        $errores++;
    }
}

// Probar inclusión de archivos críticos
echo "<h2>🔧 Prueba de Inclusión de Archivos</h2>";

// Probar config.php
$configPath = $basePath . '/assets/conexion/config.php';
if (file_exists($configPath)) {
    try {
        require_once $configPath;
        echo "<span class='success'>✅ config.php: Se incluye correctamente</span><br>";
        
        // Probar conexión a BD
        try {
            $db = Database::getInstance();
            echo "<span class='success'>✅ Conexión a BD: OK</span><br>";
        } catch (Exception $e) {
            echo "<span class='error'>❌ Conexión a BD: Error - " . $e->getMessage() . "</span><br>";
            $errores++;
        }
    } catch (Exception $e) {
        echo "<span class='error'>❌ config.php: Error al incluir - " . $e->getMessage() . "</span><br>";
        $errores++;
    }
} else {
    echo "<span class='error'>❌ config.php: No se puede probar (archivo no existe)</span><br>";
    $errores++;
}

// Probar JWT helper
$jwtPath = $basePath . '/api/auth/jwt_helper.php';
if (file_exists($jwtPath)) {
    try {
        require_once $jwtPath;
        echo "<span class='success'>✅ jwt_helper.php: Se incluye correctamente</span><br>";
        
        // Probar funciones JWT
        $testToken = generateJWT(['test' => true]);
        if ($testToken && verifyJWT($testToken)) {
            echo "<span class='success'>✅ Funciones JWT: Funcionando</span><br>";
        } else {
            echo "<span class='error'>❌ Funciones JWT: No funcionan correctamente</span><br>";
            $errores++;
        }
    } catch (Exception $e) {
        echo "<span class='error'>❌ jwt_helper.php: Error al incluir - " . $e->getMessage() . "</span><br>";
        $errores++;
    }
} else {
    echo "<span class='error'>❌ jwt_helper.php: No se puede probar (archivo no existe)</span><br>";
    $errores++;
}

// Verificar configuración PHP
echo "<h2>⚙️ Configuración PHP</h2>";
echo "<span class='info'>📋 Versión PHP: " . phpversion() . "</span><br>";
echo "<span class='info'>📋 Include Path: " . get_include_path() . "</span><br>";
echo "<span class='info'>📋 Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</span><br>";
echo "<span class='info'>📋 Script Name: " . $_SERVER['SCRIPT_NAME'] . "</span><br>";

// Resumen
echo "<h2>📊 Resumen</h2>";
if ($errores === 0) {
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; color: green;'>";
    echo "<strong>🎉 ¡TODAS LAS RUTAS SON CORRECTAS!</strong><br>";
    echo "El sistema debería funcionar sin problemas.";
    echo "</div>";
} else {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; color: red;'>";
    echo "<strong>❌ SE ENCONTRARON $errores ERRORES</strong><br>";
    echo "Revisa los archivos faltantes antes de continuar.";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Diagnóstico ejecutado el " . date('d/m/Y H:i:s') . "</small></p>";
echo "<p><a href='admin-dashboard.php'>🏠 Ir al Panel de Administración</a></p>";
?>