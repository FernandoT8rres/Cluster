<?php
/**
 * Script de Inicialización del Sistema de Banners
 * Ejecuta la creación de tablas e inserción de datos de ejemplo
 */

require_once '../config/database.php';

function inicializarSistemaBanners() {
    try {
        echo "<h2>Inicializando Sistema de Banners...</h2>\n";
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Leer y ejecutar el archivo SQL
        $sqlFile = '../sql/banner_system.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception("Archivo SQL no encontrado: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Separar las consultas por punto y coma
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        $executedQueries = 0;
        
        foreach ($queries as $query) {
            if (!empty($query) && !preg_match('/^(--|\#)/', $query)) {
                try {
                    $conn->exec($query);
                    $executedQueries++;
                    echo "<p style='color: green;'>✓ Consulta ejecutada exitosamente</p>\n";
                } catch (PDOException $e) {
                    // Ignorar errores de tablas que ya existen
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate entry') === false) {
                        echo "<p style='color: red;'>✗ Error en consulta: " . htmlspecialchars($e->getMessage()) . "</p>\n";
                    } else {
                        echo "<p style='color: orange;'>⚠ Elemento ya existe (se omite)</p>\n";
                    }
                }
            }
        }
        
        echo "<h3 style='color: green;'>✓ Sistema de Banners inicializado correctamente</h3>\n";
        echo "<p>Se ejecutaron $executedQueries consultas.</p>\n";
        
        // Verificar que los banners se insertaron correctamente
        verificarBanners($conn);
        
        echo "<hr>\n";
        echo "<h3>Enlaces útiles:</h3>\n";
        echo "<ul>\n";
        echo "<li><a href='../admin/banner-admin.php'>Panel de Administración de Banners</a></li>\n";
        echo "<li><a href='../pages/sign-in.html'>Ver Página de Login con Carrusel</a></li>\n";
        echo "<li><a href='../api/banners.php'>API de Banners (JSON)</a></li>\n";
        echo "</ul>\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "<h3 style='color: red;'>✗ Error al inicializar el sistema:</h3>\n";
        echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
        return false;
    }
}

function verificarBanners($conn) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM banner_carrusel");
        $result = $stmt->fetch();
        
        echo "<p><strong>Banners en la base de datos:</strong> {$result['total']}</p>\n";
        
        if ($result['total'] > 0) {
            $stmt = $conn->query("SELECT id, titulo, activo FROM banner_carrusel ORDER BY posicion");
            $banners = $stmt->fetchAll();
            
            echo "<ul>\n";
            foreach ($banners as $banner) {
                $estado = $banner['activo'] ? 'Activo' : 'Inactivo';
                echo "<li>ID {$banner['id']}: {$banner['titulo']} ({$estado})</li>\n";
            }
            echo "</ul>\n";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error al verificar banners: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicialización Sistema de Banners - Clúster</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .content {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎨 Clúster Intranet</h1>
        <p>Inicialización del Sistema de Banners</p>
    </div>
    
    <div class="content">
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['init'])) {
            inicializarSistemaBanners();
        } else {
        ?>
            <h2>Sistema de Banner Carrusel</h2>
            <p>Este script inicializará el sistema de banners para la página de login, incluyendo:</p>
            
            <ul>
                <li>✅ Creación de la tabla <code>banner_carrusel</code></li>
                <li>✅ Inserción de banners de ejemplo</li>
                <li>✅ Configuración de índices para optimización</li>
                <li>✅ Verificación de la estructura de datos</li>
            </ul>
            
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;">
                <strong>⚠️ Importante:</strong>
                <ul>
                    <li>Asegúrate de que la base de datos <code>claut_intranet</code> existe</li>
                    <li>Verifica que las credenciales en <code>config/database.php</code> sean correctas</li>
                    <li>El directorio <code>uploads/banners/</code> debe tener permisos de escritura</li>
                </ul>
            </div>
            
            <form method="post" style="text-align: center; margin: 30px 0;">
                <button type="submit" class="btn" style="font-size: 18px; padding: 15px 30px;">
                    🚀 Inicializar Sistema de Banners
                </button>
            </form>
            
            <hr>
            <h3>Enlaces de Acceso Directo:</h3>
            <div style="text-align: center;">
                <a href="?init=1" class="btn">Inicializar sin POST</a>
                <a href="../admin/banner-admin.php" class="btn">Panel de Admin</a>
                <a href="../pages/sign-in.html" class="btn">Ver Login</a>
            </div>
        <?php
        }
        ?>
    </div>
    
    <footer style="text-align: center; margin-top: 40px; color: #6c757d;">
        <p>&copy; <?= date('Y') ?> Clúster Intranet - Sistema de Gestión de Banners</p>
    </footer>
</body>
</html>