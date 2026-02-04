<?php
/**
 * Solucionador específico para problemas de banners
 * Diagnostica y corrige problemas comunes con la visualización de banners
 */

header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>🖼️ Solucionador de Banners - Clúster</title>";
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
.banner-preview { max-width: 200px; max-height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 5px; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
th { background-color: #f8f9fa; }
.btn { padding: 8px 16px; background: #C7252B; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 2px; }
.btn:hover { background: #8B1538; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🖼️ Solucionador de Problemas de Banners</h1>";

$fixes = [];
$errors = [];

try {
    // 1. Conectar a la base de datos
    echo "<h2>🔧 Paso 1: Verificación de Base de Datos</h2>";
    
    $configPaths = [
        '../assets/conexion/config.php',
        '../config/database.php',
        '../../assets/conexion/config.php'
    ];
    
    $configLoaded = false;
    foreach ($configPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            echo "<div class='status success'>✅ Configuración cargada desde: $path</div>";
            $configLoaded = true;
            break;
        }
    }
    
    if (!$configLoaded) {
        throw new Exception('No se encontró archivo de configuración de BD');
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<div class='status success'>✅ Conexión a base de datos exitosa</div>";
    
    // 2. Verificar tabla de banners
    echo "<h2>📋 Paso 2: Verificación de Tabla de Banners</h2>";
    
    $tableCheck = $conn->query("SHOW TABLES LIKE 'banner_carrusel'");
    if ($tableCheck->rowCount() === 0) {
        echo "<div class='status error'>❌ Tabla 'banner_carrusel' no existe</div>";
        
        // Crear tabla
        $createTable = "
        CREATE TABLE `banner_carrusel` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `titulo` varchar(255) DEFAULT NULL,
            `descripcion` text DEFAULT NULL,
            `imagen` varchar(500) DEFAULT NULL,
            `imagen_url` varchar(500) DEFAULT NULL,
            `enlace` varchar(500) DEFAULT NULL,
            `orden` int(11) DEFAULT 0,
            `posicion` int(11) DEFAULT 1,
            `activo` tinyint(1) DEFAULT 1,
            `fecha_inicio` datetime DEFAULT NULL,
            `fecha_fin` datetime DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        try {
            $conn->exec($createTable);
            echo "<div class='status success'>✅ Tabla 'banner_carrusel' creada exitosamente</div>";
            $fixes[] = 'Tabla banner_carrusel creada';
        } catch (PDOException $e) {
            echo "<div class='status error'>❌ Error creando tabla: " . $e->getMessage() . "</div>";
            $errors[] = 'Error creando tabla: ' . $e->getMessage();
        }
    } else {
        echo "<div class='status success'>✅ Tabla 'banner_carrusel' existe</div>";
    }
    
    // 3. Verificar banners existentes
    echo "<h2>📊 Paso 3: Análisis de Banners Existentes</h2>";
    
    $bannersQuery = "SELECT * FROM banner_carrusel ORDER BY orden ASC, id ASC";
    $stmt = $conn->prepare($bannersQuery);
    $stmt->execute();
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='status info'>📊 Total de banners encontrados: " . count($banners) . "</div>";
    
    if (count($banners) === 0) {
        echo "<div class='status warning'>⚠️ No hay banners en la base de datos</div>";
        echo "<h3>Crear Banners de Ejemplo</h3>";
        
        // Crear banners de ejemplo
        $bannersSample = [
            [
                'titulo' => 'Bienvenido a Clúster',
                'descripcion' => 'Conectando la industria automotriz mexicana',
                'imagen_url' => 'https://via.placeholder.com/800x400/C7252B/FFFFFF?text=Clúster+Bienvenido',
                'activo' => 1,
                'orden' => 1
            ],
            [
                'titulo' => 'Nuestra Comunidad',
                'descripcion' => 'Más de 1000 empresas confían en nosotros',
                'imagen_url' => 'https://via.placeholder.com/800x400/764ba2/FFFFFF?text=Comunidad+Clúster',
                'activo' => 1,
                'orden' => 2
            ],
            [
                'titulo' => 'Innovación Automotriz',
                'descripcion' => 'Liderando el futuro de la industria',
                'imagen_url' => 'https://via.placeholder.com/800x400/667eea/FFFFFF?text=Innovacion+Clúster',
                'activo' => 1,
                'orden' => 3
            ]
        ];
        
        $insertQuery = "INSERT INTO banner_carrusel (titulo, descripcion, imagen_url, activo, orden) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        
        foreach ($bannersSample as $banner) {
            try {
                $insertStmt->execute([
                    $banner['titulo'],
                    $banner['descripcion'],
                    $banner['imagen_url'],
                    $banner['activo'],
                    $banner['orden']
                ]);
                echo "<div class='status success'>✅ Banner creado: " . $banner['titulo'] . "</div>";
                $fixes[] = 'Banner creado: ' . $banner['titulo'];
            } catch (PDOException $e) {
                echo "<div class='status error'>❌ Error creando banner: " . $e->getMessage() . "</div>";
                $errors[] = 'Error creando banner: ' . $e->getMessage();
            }
        }
        
        // Recargar banners
        $stmt->execute();
        $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        echo "<h3>Banners Existentes</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Título</th><th>Imagen</th><th>Estado</th><th>Orden</th><th>Acciones</th></tr>";
        
        foreach ($banners as $banner) {
            $imageUrl = $banner['imagen_url'] ?: $banner['imagen'];
            $status = $banner['activo'] ? '✅ Activo' : '❌ Inactivo';
            
            echo "<tr>";
            echo "<td>" . $banner['id'] . "</td>";
            echo "<td>" . htmlspecialchars($banner['titulo'] ?: 'Sin título') . "</td>";
            echo "<td>";
            if ($imageUrl) {
                echo "<img src='" . htmlspecialchars($imageUrl) . "' class='banner-preview' onerror=\"this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2VlZSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5FcnJvcjwvdGV4dD48L3N2Zz4='; this.title='Error cargando imagen'\">";
                echo "<br><small>" . htmlspecialchars(substr($imageUrl, 0, 50)) . "...</small>";
            } else {
                echo "❌ Sin imagen";
            }
            echo "</td>";
            echo "<td>" . $status . "</td>";
            echo "<td>" . ($banner['orden'] ?: 0) . "</td>";
            echo "<td>";
            if (!$banner['activo']) {
                echo "<button class='btn' onclick=\"activateBanner(" . $banner['id'] . ")\">Activar</button>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Verificar directorio de uploads
    echo "<h2>📁 Paso 4: Verificación de Directorio de Uploads</h2>";
    
    $uploadsDir = '../uploads/banners/';
    if (!file_exists($uploadsDir)) {
        echo "<div class='status warning'>⚠️ Directorio uploads/banners/ no existe</div>";
        if (mkdir($uploadsDir, 0755, true)) {
            echo "<div class='status success'>✅ Directorio uploads/banners/ creado</div>";
            $fixes[] = 'Directorio uploads creado';
        } else {
            echo "<div class='status error'>❌ No se pudo crear directorio uploads/banners/</div>";
            $errors[] = 'Error creando directorio uploads';
        }
    } else {
        echo "<div class='status success'>✅ Directorio uploads/banners/ existe</div>";
        
        // Listar archivos
        $files = glob($uploadsDir . '*');
        echo "<div class='status info'>📄 Archivos en uploads/banners/: " . count($files) . "</div>";
        
        if (count($files) > 0) {
            echo "<h4>Archivos encontrados:</h4><ul>";
            foreach ($files as $file) {
                $filename = basename($file);
                $size = round(filesize($file) / 1024, 2);
                echo "<li>$filename ($size KB)</li>";
            }
            echo "</ul>";
        }
    }
    
    // 5. Test de la API
    echo "<h2>🧪 Paso 5: Test de la API de Banners</h2>";
    
    $apiUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/banners.php?action=active';
    echo "<div class='status info'>🔗 URL de la API: <a href='$apiUrl' target='_blank'>$apiUrl</a></div>";
    
    // Test directo de la API desde el servidor
    try {
        // Obtener banners directamente de la base de datos
        $activeBannersQuery = "
            SELECT id, titulo, descripcion, 
                   COALESCE(imagen_url, imagen) as imagen_url,
                   enlace, orden, posicion
            FROM banner_carrusel 
            WHERE activo = 1 
            AND (fecha_inicio IS NULL OR fecha_inicio <= NOW())
            AND (fecha_fin IS NULL OR fecha_fin >= NOW())
            ORDER BY orden ASC, posicion ASC, id ASC
        ";
        
        $activeStmt = $conn->prepare($activeBannersQuery);
        $activeStmt->execute();
        $activeBanners = $activeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='status success'>✅ Consulta directa exitosa - " . count($activeBanners) . " banners activos</div>";
        
        if (count($activeBanners) > 0) {
            echo "<h4>Banners Activos (Respuesta esperada de la API):</h4>";
            foreach ($activeBanners as $banner) {
                $imageUrl = $banner['imagen_url'];
                
                // Construir URL absoluta si es necesaria
                if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $protocol = $_SERVER['REQUEST_SCHEME'] ?? 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $basePath = dirname(dirname($_SERVER['REQUEST_URI']));
                    
                    if (strpos($imageUrl, 'uploads/') === 0) {
                        $imageUrl = $protocol . '://' . $host . $basePath . '/' . $imageUrl;
                    } else {
                        $imageUrl = $protocol . '://' . $host . $basePath . '/uploads/banners/' . $imageUrl;
                    }
                    
                    $banner['imagen_url'] = $imageUrl;
                }
                
                echo "<div class='status info'>";
                echo "<strong>" . htmlspecialchars($banner['titulo']) . "</strong><br>";
                echo "ID: " . $banner['id'] . " | Orden: " . $banner['orden'] . "<br>";
                if ($imageUrl) {
                    echo "Imagen: " . htmlspecialchars($imageUrl) . "<br>";
                    echo "<img src='" . htmlspecialchars($imageUrl) . "' style='max-width: 150px; max-height: 75px; object-fit: cover; margin-top: 5px;' 
                            onerror=\"this.style.display='none'; this.nextElementSibling.style.display='inline';\">";
                    echo "<span style='display:none; color: #dc3545;'>[Error cargando imagen]</span>";
                }
                echo "</div>";
            }
            
            // Crear JSON de respuesta esperada
            $expectedResponse = [
                'success' => true,
                'data' => $activeBanners,
                'message' => 'Banners activos obtenidos correctamente'
            ];
            
            echo "<h4>JSON esperado de la API:</h4>";
            echo "<pre>" . json_encode($expectedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</pre>";
            
        } else {
            echo "<div class='status warning'>⚠️ No hay banners activos para mostrar</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='status error'>❌ Error en consulta directa: " . $e->getMessage() . "</div>";
    }
    
    // 6. Verificar archivos de la API
    echo "<h2>🔧 Paso 6: Verificación de Archivos de la API</h2>";
    
    $apiFiles = [
        'banners.php' => '../api/banners.php',
        'banners-fixed.php' => '../api/banners-fixed.php'
    ];
    
    foreach ($apiFiles as $name => $path) {
        if (file_exists($path)) {
            $size = round(filesize($path) / 1024, 2);
            echo "<div class='status success'>✅ $name existe ($size KB)</div>";
        } else {
            echo "<div class='status error'>❌ $name NO existe en $path</div>";
            $errors[] = "$name no encontrado";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='status error'>❌ Error general: " . $e->getMessage() . "</div>";
    $errors[] = 'Error general: ' . $e->getMessage();
}

// Resumen final
echo "<h2>📋 Resumen del Diagnóstico</h2>";

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
echo "<h2>🚀 Próximos Pasos</h2>";
echo "<div class='status info'>";
echo "<h3>Para solucionar completamente el problema de banners:</h3>";
echo "<ol>";
echo "<li><strong>Si no hay banners:</strong> Se crearon banners de ejemplo automáticamente</li>";
echo "<li><strong>Si la API no funciona:</strong> Reemplaza banners.php con banners-fixed.php</li>";
echo "<li><strong>Probar el carrusel:</strong> Ve a <a href='../pages/sign-in.html' target='_blank'>la página de login</a></li>";
echo "<li><strong>Verificar resultado:</strong> Los banners deberían aparecer en el lado derecho</li>";
echo "<li><strong>Si persiste el problema:</strong> Usa la <a href='#' onclick=\"window.open('../api/banners.php?action=debug', '_blank')\">API de debug</a></li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<button class='btn' onclick=\"window.location.reload()\">🔄 Ejecutar Diagnóstico Nuevamente</button>";
echo "<button class='btn' onclick=\"window.open('../pages/sign-in.html', '_blank')\">🔐 Ir a Login</button>";
echo "<button class='btn' onclick=\"window.open('../api/banners.php?action=active', '_blank')\">🧪 Probar API</button>";
echo "</div>";

echo "</div>";

echo "<script>
function activateBanner(id) {
    if (confirm('¿Activar banner ID ' + id + '?')) {
        // Aquí iría la lógica para activar el banner
        alert('Funcionalidad de activación pendiente de implementación');
    }
}
</script>";

echo "</body></html>";
?>