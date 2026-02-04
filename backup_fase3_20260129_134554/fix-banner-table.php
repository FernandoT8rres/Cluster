<?php
/**
 * Script para corregir la estructura de la tabla banner_carrusel
 * Ejecutar este archivo para solucionar problemas de columnas faltantes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

$mensajes = [];
$errores = [];

try {
    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $mensajes[] = "✅ Conexión a base de datos exitosa";
    
    // Verificar si la tabla existe
    $stmt = $conn->query("SHOW TABLES LIKE 'banner_carrusel'");
    
    if ($stmt->rowCount() > 0) {
        $mensajes[] = "✅ Tabla banner_carrusel encontrada";
        
        // Obtener columnas existentes
        $stmt = $conn->query("DESCRIBE banner_carrusel");
        $columnas_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $mensajes[] = "📋 Columnas actuales: " . implode(', ', $columnas_existentes);
        
        // Definir estructura completa esperada
        $columnas_requeridas = [
            'id' => "INT PRIMARY KEY AUTO_INCREMENT",
            'titulo' => "VARCHAR(255) NOT NULL",
            'descripcion' => "TEXT",
            'imagen_url' => "VARCHAR(500) NOT NULL",
            'posicion' => "INT DEFAULT 1",
            'activo' => "BOOLEAN DEFAULT TRUE",
            'fecha_inicio' => "DATETIME DEFAULT NULL",
            'fecha_fin' => "DATETIME DEFAULT NULL",
            'creado_por' => "INT DEFAULT NULL",
            'fecha_creacion' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            'fecha_actualizacion' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        // Verificar y agregar columnas faltantes
        foreach ($columnas_requeridas as $columna => $definicion) {
            if (!in_array($columna, $columnas_existentes)) {
                try {
                    // Agregar columna faltante
                    $sql = "ALTER TABLE banner_carrusel ADD COLUMN $columna $definicion";
                    $conn->exec($sql);
                    $mensajes[] = "✅ Columna '$columna' agregada exitosamente";
                } catch (PDOException $e) {
                    // Si es la columna id y ya existe, ignorar
                    if ($columna !== 'id') {
                        $errores[] = "❌ Error al agregar columna '$columna': " . $e->getMessage();
                    }
                }
            } else {
                $mensajes[] = "✓ Columna '$columna' ya existe";
            }
        }
        
        // Verificar de nuevo la estructura
        $stmt = $conn->query("DESCRIBE banner_carrusel");
        $columnas_finales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mensajes[] = "";
        $mensajes[] = "📊 Estructura final de la tabla:";
        foreach ($columnas_finales as $col) {
            $mensajes[] = "   - " . $col['Field'] . " (" . $col['Type'] . ")";
        }
        
    } else {
        // La tabla no existe, crearla desde cero
        $mensajes[] = "⚠️ Tabla banner_carrusel no existe. Creándola...";
        
        $sql = "CREATE TABLE banner_carrusel (
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
        
        $conn->exec($sql);
        $mensajes[] = "✅ Tabla banner_carrusel creada exitosamente";
        
        // Insertar datos de ejemplo
        $sql = "INSERT INTO banner_carrusel (titulo, descripcion, imagen_url, posicion, activo) VALUES 
                ('Bienvenido a Clúster', 'Conecta con la industria automotriz', 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=800', 1, 1),
                ('Innovación Automotriz', 'Transformación digital del sector', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800', 2, 1),
                ('Colaboración Empresarial', 'Fortaleciendo alianzas estratégicas', 'https://images.unsplash.com/photo-1560472355-109703aa3edc?w=800', 3, 1)";
        
        $conn->exec($sql);
        $mensajes[] = "✅ Datos de ejemplo insertados";
    }
    
    // Verificar que ahora funciona correctamente
    $stmt = $conn->query("SELECT COUNT(*) as total FROM banner_carrusel");
    $count = $stmt->fetch();
    $mensajes[] = "";
    $mensajes[] = "📈 Total de banners en el sistema: " . $count['total'];
    
    // Test de inserción
    $test_sql = "INSERT INTO banner_carrusel (titulo, descripcion, imagen_url, posicion, activo) 
                 VALUES ('Test', 'Test', 'test.jpg', 999, 0)";
    $conn->exec($test_sql);
    $test_id = $conn->lastInsertId();
    
    $conn->exec("DELETE FROM banner_carrusel WHERE id = $test_id");
    $mensajes[] = "✅ Test de inserción/eliminación exitoso";
    
} catch (Exception $e) {
    $errores[] = "❌ Error general: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparar Tabla de Banners</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .mensaje {
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            font-size: 14px;
        }
        .error {
            background: #fff5f5;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .btn-container {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
        }
        .btn-info {
            background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
        }
        .success-box {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
        .warning-box {
            background: linear-gradient(135deg, #F2994A 0%, #F2C94C 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Reparación de Tabla de Banners</h1>
        
        <?php if (empty($errores)): ?>
            <div class="success-box">
                ✅ ¡Proceso completado exitosamente!
            </div>
        <?php else: ?>
            <div class="warning-box">
                ⚠️ Se encontraron algunos problemas
            </div>
        <?php endif; ?>
        
        <h2>📋 Resultados del proceso:</h2>
        
        <?php foreach ($mensajes as $mensaje): ?>
            <div class="mensaje">
                <?= $mensaje ?>
            </div>
        <?php endforeach; ?>
        
        <?php foreach ($errores as $error): ?>
            <div class="mensaje error">
                <?= $error ?>
            </div>
        <?php endforeach; ?>
        
        <div class="btn-container">
            <a href="banner-admin-mejorado.php" class="btn">
                🎨 Ir al Panel de Banners
            </a>
            <a href="diagnostico-banners.php" class="btn btn-secondary">
                🔍 Diagnóstico Completo
            </a>
            <a href="../pages/sign-in.html" class="btn btn-info" target="_blank">
                👁️ Ver Login
            </a>
        </div>
        
        <?php if (empty($errores)): ?>
            <div style="margin-top: 30px; padding: 20px; background: #e8f5e9; border-radius: 10px;">
                <h3>✨ La tabla está lista para usar</h3>
                <p>Todas las columnas necesarias están presentes y funcionando correctamente.</p>
                <p>Ahora puedes crear, editar y eliminar banners sin problemas.</p>
            </div>
        <?php else: ?>
            <div style="margin-top: 30px; padding: 20px; background: #ffebee; border-radius: 10px;">
                <h3>⚠️ Acción requerida</h3>
                <p>Revisa los errores mostrados arriba y contacta al administrador si persisten.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
