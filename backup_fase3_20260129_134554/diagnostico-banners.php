<?php
/**
 * Diagnóstico del Sistema de Banners
 * Archivo para verificar la configuración y detectar problemas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$resultados = [];

// 1. Verificar conexión a base de datos
try {
    require_once '../config/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $resultados['database_connection'] = ['success' => true, 'message' => 'Conexión exitosa'];
} catch (Exception $e) {
    $resultados['database_connection'] = ['success' => false, 'message' => $e->getMessage()];
    $conn = null;
}

// 2. Verificar tabla banner_carrusel
if ($conn) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'banner_carrusel'");
        if ($stmt->rowCount() > 0) {
            $resultados['tabla_banner'] = ['success' => true, 'message' => 'Tabla existe'];
            
            // Verificar estructura de la tabla
            $stmt = $conn->query("DESCRIBE banner_carrusel");
            $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $resultados['columnas_tabla'] = ['success' => true, 'columnas' => $columnas];
        } else {
            // Intentar crear la tabla
            $sql = "CREATE TABLE IF NOT EXISTS banner_carrusel (
                id INT PRIMARY KEY AUTO_INCREMENT,
                titulo VARCHAR(255) NOT NULL,
                descripcion TEXT,
                imagen_url VARCHAR(500) NOT NULL,
                posicion INT DEFAULT 1,
                activo BOOLEAN DEFAULT TRUE,
                fecha_inicio DATETIME DEFAULT NULL,
                fecha_fin DATETIME DEFAULT NULL,
                creado_por INT DEFAULT NULL,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            
            if ($conn->exec($sql) !== false) {
                $resultados['tabla_banner'] = ['success' => true, 'message' => 'Tabla creada exitosamente'];
            } else {
                $resultados['tabla_banner'] = ['success' => false, 'message' => 'No se pudo crear la tabla'];
            }
        }
    } catch (PDOException $e) {
        $resultados['tabla_banner'] = ['success' => false, 'message' => $e->getMessage()];
    }
}

// 3. Verificar directorio de uploads
$directorioUploads = '../uploads/banners/';
$resultados['directorio_uploads'] = [
    'existe' => file_exists($directorioUploads),
    'escribible' => is_writable($directorioUploads) || is_writable('../uploads/'),
    'ruta' => realpath($directorioUploads) ?: 'No existe'
];

// Intentar crear el directorio si no existe
if (!file_exists($directorioUploads)) {
    if (mkdir($directorioUploads, 0755, true)) {
        $resultados['directorio_uploads']['creado'] = true;
        $resultados['directorio_uploads']['existe'] = true;
        $resultados['directorio_uploads']['escribible'] = true;
    } else {
        $resultados['directorio_uploads']['creado'] = false;
    }
}

// 4. Verificar configuración PHP
$resultados['php_config'] = [
    'file_uploads' => ini_get('file_uploads') === '1' ? 'Habilitado' : 'Deshabilitado',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];

// 5. Test de inserción
if ($conn && isset($resultados['tabla_banner']) && $resultados['tabla_banner']['success']) {
    try {
        // Insertar un banner de prueba
        $sql = "INSERT INTO banner_carrusel (titulo, descripcion, imagen_url, posicion, activo) 
                VALUES (:titulo, :descripcion, :imagen_url, :posicion, :activo)";
        
        $stmt = $conn->prepare($sql);
        $testData = [
            ':titulo' => 'Banner de Prueba ' . time(),
            ':descripcion' => 'Este es un banner de prueba para verificar el sistema',
            ':imagen_url' => 'https://via.placeholder.com/800x400',
            ':posicion' => 99,
            ':activo' => 1
        ];
        
        if ($stmt->execute($testData)) {
            $resultados['test_insert'] = ['success' => true, 'id' => $conn->lastInsertId()];
            
            // Eliminar el banner de prueba
            $conn->exec("DELETE FROM banner_carrusel WHERE id = " . $conn->lastInsertId());
        } else {
            $resultados['test_insert'] = ['success' => false, 'error' => $stmt->errorInfo()];
        }
    } catch (PDOException $e) {
        $resultados['test_insert'] = ['success' => false, 'message' => $e->getMessage()];
    }
}

// 6. Verificar banners existentes
if ($conn) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total FROM banner_carrusel");
        $count = $stmt->fetch();
        $resultados['banners_count'] = $count['total'];
    } catch (PDOException $e) {
        $resultados['banners_count'] = 'Error: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Sistema de Banners</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .test-item {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ddd;
        }
        .test-item.success {
            background: #e8f5e9;
            border-left-color: #4CAF50;
        }
        .test-item.error {
            background: #ffebee;
            border-left-color: #f44336;
        }
        .test-item.warning {
            background: #fff3e0;
            border-left-color: #ff9800;
        }
        .test-title {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        .test-details {
            color: #666;
            margin-top: 10px;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
            margin: 10px 0;
            overflow-x: auto;
        }
        .actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #2196F3;
        }
        .btn-secondary:hover {
            background: #1976D2;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Diagnóstico del Sistema de Banners</h1>
        
        <!-- Conexión a Base de Datos -->
        <div class="test-item <?= $resultados['database_connection']['success'] ? 'success' : 'error' ?>">
            <div class="test-title">
                <?= $resultados['database_connection']['success'] ? '✅' : '❌' ?> 
                Conexión a Base de Datos
            </div>
            <div class="test-details">
                <?= htmlspecialchars($resultados['database_connection']['message']) ?>
            </div>
        </div>
        
        <!-- Tabla de Banners -->
        <?php if (isset($resultados['tabla_banner'])): ?>
        <div class="test-item <?= $resultados['tabla_banner']['success'] ? 'success' : 'error' ?>">
            <div class="test-title">
                <?= $resultados['tabla_banner']['success'] ? '✅' : '❌' ?> 
                Tabla banner_carrusel
            </div>
            <div class="test-details">
                <?= htmlspecialchars($resultados['tabla_banner']['message']) ?>
                <?php if (isset($resultados['columnas_tabla'])): ?>
                    <div class="code">
                        Columnas: <?= implode(', ', $resultados['columnas_tabla']['columnas']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Directorio de Uploads -->
        <div class="test-item <?= $resultados['directorio_uploads']['existe'] && $resultados['directorio_uploads']['escribible'] ? 'success' : ($resultados['directorio_uploads']['existe'] ? 'warning' : 'error') ?>">
            <div class="test-title">
                <?= $resultados['directorio_uploads']['existe'] && $resultados['directorio_uploads']['escribible'] ? '✅' : ($resultados['directorio_uploads']['existe'] ? '⚠️' : '❌') ?> 
                Directorio de Uploads
            </div>
            <div class="test-details">
                <p>Existe: <?= $resultados['directorio_uploads']['existe'] ? 'Sí' : 'No' ?></p>
                <p>Escribible: <?= $resultados['directorio_uploads']['escribible'] ? 'Sí' : 'No' ?></p>
                <p>Ruta: <?= htmlspecialchars($resultados['directorio_uploads']['ruta']) ?></p>
                <?php if (isset($resultados['directorio_uploads']['creado'])): ?>
                    <p>Creación automática: <?= $resultados['directorio_uploads']['creado'] ? 'Exitosa' : 'Fallida' ?></p>
                <?php endif; ?>
                
                <?php if (!$resultados['directorio_uploads']['escribible']): ?>
                    <div class="code">
                        # Ejecutar estos comandos para dar permisos:
                        sudo mkdir -p <?= realpath('../uploads') ?>/banners
                        sudo chmod 755 <?= realpath('../uploads') ?>/banners
                        sudo chown www-data:www-data <?= realpath('../uploads') ?>/banners
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Configuración PHP -->
        <div class="test-item success">
            <div class="test-title">
                📋 Configuración PHP
            </div>
            <div class="test-details">
                <pre><?= json_encode($resultados['php_config'], JSON_PRETTY_PRINT) ?></pre>
            </div>
        </div>
        
        <!-- Test de Inserción -->
        <?php if (isset($resultados['test_insert'])): ?>
        <div class="test-item <?= $resultados['test_insert']['success'] ? 'success' : 'error' ?>">
            <div class="test-title">
                <?= $resultados['test_insert']['success'] ? '✅' : '❌' ?> 
                Test de Inserción en Base de Datos
            </div>
            <div class="test-details">
                <?php if ($resultados['test_insert']['success']): ?>
                    <p>✅ Se puede insertar y eliminar banners correctamente</p>
                <?php else: ?>
                    <p>Error: <?= isset($resultados['test_insert']['message']) ? htmlspecialchars($resultados['test_insert']['message']) : 'Error desconocido' ?></p>
                    <?php if (isset($resultados['test_insert']['error'])): ?>
                        <pre><?= print_r($resultados['test_insert']['error'], true) ?></pre>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Conteo de Banners -->
        <?php if (isset($resultados['banners_count'])): ?>
        <div class="test-item success">
            <div class="test-title">
                📊 Banners en el Sistema
            </div>
            <div class="test-details">
                <p>Total de banners: <?= $resultados['banners_count'] ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Resumen y Acciones -->
        <div class="actions">
            <h2>Acciones Recomendadas</h2>
            
            <?php 
            $todoBien = true;
            $problemas = [];
            
            if (!$resultados['database_connection']['success']) {
                $todoBien = false;
                $problemas[] = "Verificar credenciales de base de datos en /config/database.php";
            }
            
            if (isset($resultados['tabla_banner']) && !$resultados['tabla_banner']['success']) {
                $todoBien = false;
                $problemas[] = "Ejecutar el script SQL para crear la tabla banner_carrusel";
            }
            
            if (!$resultados['directorio_uploads']['escribible']) {
                $todoBien = false;
                $problemas[] = "Dar permisos de escritura al directorio uploads/banners/";
            }
            
            if (isset($resultados['test_insert']) && !$resultados['test_insert']['success']) {
                $todoBien = false;
                $problemas[] = "Revisar permisos de INSERT en la tabla banner_carrusel";
            }
            ?>
            
            <?php if ($todoBien): ?>
                <div class="test-item success">
                    <div class="test-title">✅ Todo está funcionando correctamente</div>
                    <div class="test-details">
                        El sistema de banners está listo para usar.
                    </div>
                </div>
            <?php else: ?>
                <div class="test-item warning">
                    <div class="test-title">⚠️ Se encontraron algunos problemas</div>
                    <div class="test-details">
                        <ul>
                            <?php foreach ($problemas as $problema): ?>
                                <li><?= htmlspecialchars($problema) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <a href="banner-admin-mejorado.php" class="btn">Ir al Panel de Administración</a>
                <a href="../api/banners.php?action=test" class="btn btn-secondary" target="_blank">Probar API</a>
                <a href="../pages/sign-in.html" class="btn btn-secondary" target="_blank">Ver Login</a>
            </div>
        </div>
    </div>
</body>
</html>
