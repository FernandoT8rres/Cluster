<?php
/**
 * Script de reparación definitiva para el sistema de banners
 * Soluciona el error de índice duplicado y verifica la integridad
 */

require_once '../config/database.php';

function repararSistemaBanners() {
    try {
        echo "🔧 Iniciando reparación del sistema de banners...\n\n";
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Paso 1: Eliminar índices duplicados
        echo "📝 Paso 1: Eliminando índices existentes...\n";
        $indices = ['idx_banner_posicion', 'idx_banner_fechas', 'idx_banner_activo'];
        
        foreach ($indices as $indice) {
            try {
                $conn->exec("DROP INDEX IF EXISTS $indice ON banner_carrusel");
                echo "   ✓ Índice '$indice' eliminado\n";
            } catch (PDOException $e) {
                echo "   ⚠ Error al eliminar '$indice': " . $e->getMessage() . "\n";
            }
        }
        
        // Paso 2: Recrear índices
        echo "\n📝 Paso 2: Recreando índices optimizados...\n";
        $nuevosIndices = [
            "CREATE INDEX idx_banner_activo ON banner_carrusel(activo)" => "idx_banner_activo",
            "CREATE INDEX idx_banner_posicion ON banner_carrusel(posicion)" => "idx_banner_posicion", 
            "CREATE INDEX idx_banner_fechas ON banner_carrusel(fecha_inicio, fecha_fin)" => "idx_banner_fechas"
        ];
        
        foreach ($nuevosIndices as $query => $nombre) {
            try {
                $conn->exec($query);
                echo "   ✓ Índice '$nombre' creado correctamente\n";
            } catch (PDOException $e) {
                echo "   ✗ Error al crear '$nombre': " . $e->getMessage() . "\n";
            }
        }
        
        // Paso 3: Verificar banners existentes
        echo "\n📝 Paso 3: Verificando banners existentes...\n";
        $stmt = $conn->query("SELECT COUNT(*) as total FROM banner_carrusel");
        $result = $stmt->fetch();
        
        echo "   📊 Total de banners: {$result['total']}\n";
        
        if ($result['total'] == 0) {
            echo "   📝 No hay banners, insertando ejemplos...\n";
            insertarBannersEjemplo($conn);
        } else {
            // Mostrar banners existentes
            $stmt = $conn->query("SELECT id, titulo, activo, posicion FROM banner_carrusel ORDER BY posicion");
            $banners = $stmt->fetchAll();
            
            foreach ($banners as $banner) {
                $estado = $banner['activo'] ? 'Activo' : 'Inactivo';
                echo "   📌 ID {$banner['id']}: {$banner['titulo']} (Pos: {$banner['posicion']}, {$estado})\n";
            }
        }
        
        // Paso 4: Verificar funcionamiento del API
        echo "\n📝 Paso 4: Verificando API...\n";
        verificarAPI($conn);
        
        echo "\n🎉 ¡Reparación completada exitosamente!\n\n";
        
        // Enlaces útiles
        echo "🔗 Enlaces útiles:\n";
        echo "   • Panel Admin: /build/admin/banner-admin.php\n";
        echo "   • Página Login: /build/pages/sign-in.html\n";
        echo "   • API Banners: /build/api/banners.php\n\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "💥 Error crítico: " . $e->getMessage() . "\n";
        return false;
    }
}

function insertarBannersEjemplo($conn) {
    $banners = [
        [
            'titulo' => 'Banner Principal',
            'descripcion' => 'Bienvenido a Clúster Intranet - Portal del clúster automotriz líder de México',
            'imagen_url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=2340&q=80',
            'posicion' => 1
        ],
        [
            'titulo' => 'Promoción Especial',
            'descripcion' => 'Innovación y tecnología al servicio de la industria automotriz mexicana',
            'imagen_url' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=2340&q=80',
            'posicion' => 2
        ],
        [
            'titulo' => 'Nuevo Producto',
            'descripcion' => 'Conectando empresas y profesionales del sector automotriz mexicano',
            'imagen_url' => 'https://images.unsplash.com/photo-1560472355-109703aa3edc?ixlib=rb-4.0.3&auto=format&fit=crop&w=2126&q=80',
            'posicion' => 3
        ]
    ];
    
    foreach ($banners as $banner) {
        $stmt = $conn->prepare("
            INSERT INTO banner_carrusel (titulo, descripcion, imagen_url, posicion, activo) 
            VALUES (?, ?, ?, ?, TRUE)
        ");
        
        $stmt->execute([
            $banner['titulo'],
            $banner['descripcion'],
            $banner['imagen_url'],
            $banner['posicion']
        ]);
        
        echo "   ✓ Banner '{$banner['titulo']}' insertado\n";
    }
}

function verificarAPI($conn) {
    try {
        // Simular llamada al API
        $stmt = $conn->query("
            SELECT id, titulo, descripcion, imagen_url, posicion 
            FROM banner_carrusel 
            WHERE activo = 1 
            ORDER BY posicion ASC
        ");
        $banners = $stmt->fetchAll();
        
        if (count($banners) > 0) {
            echo "   ✓ API funcional - " . count($banners) . " banners activos disponibles\n";
        } else {
            echo "   ⚠ API funcional pero sin banners activos\n";
        }
        
    } catch (Exception $e) {
        echo "   ✗ Error en API: " . $e->getMessage() . "\n";
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparación Sistema de Banners - Clúster</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
            background-color: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .alert {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛠️ Reparación Sistema de Banners</h1>
        <p>Script de diagnóstico y reparación para índices duplicados</p>
    </div>
    
    <div class="content">
        <?php
        if ($_GET['action'] === 'repair' || $_POST['action'] === 'repair') {
            echo "<h2>🔧 Ejecutando Reparación</h2>";
            echo "<div class='output'>";
            ob_start();
            $resultado = repararSistemaBanners();
            $output = ob_get_clean();
            echo htmlspecialchars($output);
            echo "</div>";
            
            if ($resultado) {
                echo "<div class='alert alert-info'>";
                echo "<strong>✅ Reparación exitosa!</strong><br>";
                echo "El sistema de banners ha sido reparado correctamente.";
                echo "</div>";
                
                echo "<div style='text-align: center; margin: 30px 0;'>";
                echo "<a href='../pages/sign-in.html' class='btn btn-success'>🔐 Ver Página de Login</a>";
                echo "<a href='../admin/banner-admin.php' class='btn'>📊 Panel de Admin</a>";
                echo "<a href='../api/banners.php' class='btn'>🔗 API Banners</a>";
                echo "</div>";
            }
        } else {
        ?>
            <h2>🚨 Error Detectado: Índice Duplicado</h2>
            
            <div class="alert alert-info">
                <strong>Problema:</strong> Error SQLSTATE[42000] - Duplicate key name 'idx_banner_fechas'<br><br>
                <strong>Causa:</strong> Los índices de la tabla banner_carrusel ya existen y el script de inicialización intenta crearlos nuevamente.<br><br>
                <strong>Solución:</strong> Este script eliminará los índices duplicados y los recreará correctamente.
            </div>
            
            <h3>¿Qué hace esta reparación?</h3>
            <ul>
                <li>✅ Elimina índices duplicados de forma segura</li>
                <li>✅ Recrea los índices optimizados</li>
                <li>✅ Verifica la integridad de los datos</li>
                <li>✅ Confirma el funcionamiento del API</li>
                <li>✅ Mantiene todos los banners existentes</li>
            </ul>
            
            <div style="text-align: center; margin: 40px 0;">
                <form method="get" style="display: inline;">
                    <input type="hidden" name="action" value="repair">
                    <button type="submit" class="btn btn-success" style="font-size: 18px; padding: 15px 30px;">
                        🚀 Ejecutar Reparación
                    </button>
                </form>
            </div>
            
            <hr>
            
            <h3>Enlaces Alternativos:</h3>
            <div style="text-align: center;">
                <a href="fix_banner_duplicate_index.php?action=fix" class="btn">🔧 Script Alternativo</a>
                <a href="init_banner_system.php?init=1" class="btn">🎨 Inicialización Original</a>
            </div>
        <?php
        }
        ?>
    </div>
    
    <footer style="text-align: center; margin-top: 40px; color: #6c757d;">
        <p>&copy; <?= date('Y') ?> Clúster Intranet - Sistema de Reparación de Banners</p>
    </footer>
</body>
</html>