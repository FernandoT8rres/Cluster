<?php
/**
 * Script para solucionar la redirección del dominio principal
 * Configura automáticamente el dominio para mostrar login como página principal
 */

header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>🔧 Solucionador de Redirección - Clúster</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; background: #f5f5f5; }
.container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
h1 { color: #C7252B; border-bottom: 3px solid #C7252B; padding-bottom: 10px; }
h2 { color: #333; margin-top: 30px; }
.status { padding: 10px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.btn { padding: 8px 16px; background: #C7252B; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
.btn:hover { background: #8B1538; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Solucionador de Redirección del Dominio</h1>";
echo "<p><strong>Problema:</strong> El dominio principal redirige al index en lugar del login</p>";

$fixes = [];
$errors = [];

// Paso 1: Analizar configuración actual
echo "<h2>📊 Paso 1: Análisis de Configuración Actual</h2>";

$currentHtaccess = '.htaccess';
$currentIndex = 'index.html';
$currentIndexPHP = 'index.php';

if (file_exists($currentHtaccess)) {
    $htaccessContent = file_get_contents($currentHtaccess);
    echo "<div class='status info'>✅ Archivo .htaccess encontrado</div>";
    
    if (strpos($htaccessContent, 'pages/sign-in.html') !== false) {
        echo "<div class='status success'>✅ Redirección al login ya configurada en .htaccess</div>";
    } else {
        echo "<div class='status warning'>⚠️ Redirección al login NO configurada en .htaccess</div>";
    }
} else {
    echo "<div class='status error'>❌ Archivo .htaccess no encontrado</div>";
}

if (file_exists($currentIndex)) {
    echo "<div class='status info'>✅ Archivo index.html encontrado</div>";
} else {
    echo "<div class='status warning'>⚠️ Archivo index.html no encontrado (normal si ya se movió)</div>";
}

if (file_exists('dashboard.html')) {
    echo "<div class='status success'>✅ Dashboard.html disponible</div>";
} else {
    echo "<div class='status warning'>⚠️ Dashboard.html no encontrado</div>";
}

// Paso 2: Crear/Actualizar .htaccess
echo "<h2>🔧 Paso 2: Configuración de .htaccess</h2>";

$htaccessConfig = 'RewriteEngine On

# Configuración básica de seguridad
Options -Indexes
ErrorDocument 404 /404.html

# ==== REDIRECCIÓN PRINCIPAL DEL DOMINIO ====
# Redirigir la raíz del dominio directamente al login
RewriteCond %{REQUEST_URI} ^/?$
RewriteCond %{REQUEST_METHOD} GET
RewriteRule ^$ build/pages/sign-in.html [R=302,L]

# ==== PROTECCIÓN DE RUTAS PRIVADAS ====
# Proteger el dashboard para que solo se muestre después de login
RewriteCond %{REQUEST_URI} ^/build/dashboard\.html$
RewriteCond %{QUERY_STRING} !authenticated=true
RewriteCond %{HTTP_COOKIE} !claut_authenticated=true
RewriteRule ^build/dashboard\.html$ build/pages/sign-in.html?message=Debes%20iniciar%20sesión%20primero [R=302,L]

# Proteger el index.html original si existe
RewriteCond %{REQUEST_URI} ^/build/index\.html$
RewriteCond %{QUERY_STRING} !authenticated=true
RewriteRule ^build/index\.html$ build/pages/sign-in.html [R=302,L]

# ==== ACCESO DIRECTO A PÁGINAS PÚBLICAS ====
RewriteCond %{REQUEST_URI} ^/build/pages/(sign-in|sign-up|forgot-password)\.html$
RewriteRule .* - [L]

# Permitir acceso a archivos de API y assets
RewriteCond %{REQUEST_URI} ^/build/(api|assets|js|css|uploads)/
RewriteRule .* - [L]

# Permitir acceso al área de administración
RewriteCond %{REQUEST_URI} ^/build/admin/
RewriteRule .* - [L]

# Permitir páginas demo
RewriteCond %{REQUEST_URI} ^/build/demo_
RewriteRule .* - [L]

# ==== CONFIGURACIONES DE RENDIMIENTO ====
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 3 months"
    ExpiresByType image/jpg "access plus 3 months"
    ExpiresByType image/jpeg "access plus 3 months"
    ExpiresByType image/gif "access plus 3 months"
    ExpiresByType image/webp "access plus 3 months"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>

<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
</IfModule>

<Files ~ "\.(env|log|sql|bak|config|ini)$">
    Order allow,deny
    Deny from all
</Files>';

try {
    // Crear backup del .htaccess actual si existe
    if (file_exists($currentHtaccess)) {
        $backupName = '.htaccess.backup.' . date('Y-m-d-H-i-s');
        copy($currentHtaccess, $backupName);
        echo "<div class='status info'>📄 Backup creado: $backupName</div>";
    }
    
    // Escribir nuevo .htaccess en la raíz del dominio (nivel superior)
    $rootHtaccess = '../.htaccess';
    if (file_put_contents($rootHtaccess, $htaccessConfig)) {
        echo "<div class='status success'>✅ .htaccess configurado en la raíz del dominio</div>";
        $fixes[] = 'Archivo .htaccess configurado correctamente';
    } else {
        echo "<div class='status error'>❌ Error escribiendo .htaccess en la raíz</div>";
        $errors[] = 'No se pudo escribir .htaccess en la raíz';
    }
    
} catch (Exception $e) {
    echo "<div class='status error'>❌ Error configurando .htaccess: " . $e->getMessage() . "</div>";
    $errors[] = 'Error configurando .htaccess: ' . $e->getMessage();
}

// Paso 3: Crear index.php de redirección inteligente
echo "<h2>🧠 Paso 3: Creación de Index.php Inteligente</h2>";

$indexPHPContent = '<?php
/**
 * Index principal del dominio - Redirección inteligente
 */
session_start();

// Verificar autenticación
function isAuthenticated() {
    if (isset($_GET["authenticated"]) && $_GET["authenticated"] === "true") return true;
    if (isset($_COOKIE["claut_authenticated"]) && $_COOKIE["claut_authenticated"] === "true") return true;
    if (isset($_SESSION["usuario_id"]) && !empty($_SESSION["usuario_id"])) return true;
    return false;
}

if (isAuthenticated()) {
    header("Location: build/dashboard.html");
} else {
    header("Location: build/pages/sign-in.html");
}
exit;
?>';

try {
    $rootIndex = '../index.php';
    if (file_put_contents($rootIndex, $indexPHPContent)) {
        echo "<div class='status success'>✅ Index.php creado en la raíz del dominio</div>";
        $fixes[] = 'Index.php de redirección inteligente creado';
    } else {
        echo "<div class='status error'>❌ Error creando index.php en la raíz</div>";
        $errors[] = 'No se pudo crear index.php en la raíz';
    }
} catch (Exception $e) {
    echo "<div class='status error'>❌ Error creando index.php: " . $e->getMessage() . "</div>";
    $errors[] = 'Error creando index.php: ' . $e->getMessage();
}

// Paso 4: Mover index.html a dashboard.html si es necesario
echo "<h2>📁 Paso 4: Organización de Archivos</h2>";

if (file_exists($currentIndex) && !file_exists('dashboard.html')) {
    try {
        if (rename($currentIndex, 'dashboard.html')) {
            echo "<div class='status success'>✅ index.html renombrado a dashboard.html</div>";
            $fixes[] = 'index.html convertido en dashboard.html';
        } else {
            echo "<div class='status error'>❌ Error renombrando index.html</div>";
            $errors[] = 'No se pudo renombrar index.html';
        }
    } catch (Exception $e) {
        echo "<div class='status error'>❌ Error moviendo archivos: " . $e->getMessage() . "</div>";
        $errors[] = 'Error moviendo archivos: ' . $e->getMessage();
    }
} else {
    echo "<div class='status info'>ℹ️ Archivos ya organizados correctamente</div>";
}

// Paso 5: Verificar configuración
echo "<h2>🧪 Paso 5: Verificación de la Configuración</h2>";

echo "<div class='status info'>";
echo "<h4>URLs de prueba:</h4>";
echo "<ul>";
echo "<li><strong>Dominio principal:</strong> <a href='https://intranet.clústermetropolitano.mx' target='_blank'>https://intranet.clústermetropolitano.mx</a></li>";
echo "<li><strong>Login directo:</strong> <a href='https://intranet.clústermetropolitano.mx/build/pages/sign-in.html' target='_blank'>Login</a></li>";
echo "<li><strong>Dashboard:</strong> <a href='https://intranet.clústermetropolitano.mx/build/dashboard.html' target='_blank'>Dashboard</a></li>";
echo "</ul>";
echo "</div>";

// Mostrar configuración .htaccess aplicada
echo "<h4>Configuración .htaccess aplicada:</h4>";
echo "<pre>" . htmlspecialchars($htaccessConfig) . "</pre>";

// Resumen
echo "<h2>📋 Resumen de Cambios</h2>";

if (count($fixes) > 0) {
    echo "<div class='status success'>";
    echo "<h3>✅ Correcciones Aplicadas:</h3>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (count($errors) > 0) {
    echo "<div class='status error'>";
    echo "<h3>❌ Problemas Encontrados:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Instrucciones finales
echo "<div class='status info'>";
echo "<h3>🚀 Resultado Esperado:</h3>";
echo "<p>Después de aplicar estos cambios:</p>";
echo "<ol>";
echo "<li><code>https://intranet.clústermetropolitano.mx</code> redirigirá automáticamente al login</li>";
echo "<li>Los usuarios verán la página de inicio de sesión como pantalla principal</li>";
echo "<li>Después del login, serán redirigidos al dashboard</li>";
echo "<li>El acceso directo al dashboard estará protegido hasta después del login</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='https://intranet.clústermetropolitano.mx' target='_blank' class='btn'>🌐 Probar Dominio Principal</a>";
echo "<a href='https://intranet.clústermetropolitano.mx/build/pages/sign-in.html' target='_blank' class='btn'>🔐 Ir al Login</a>";
echo "<button class='btn' onclick='window.location.reload()'>🔄 Ejecutar Nuevamente</button>";
echo "</div>";

echo "</div></body></html>";
?>