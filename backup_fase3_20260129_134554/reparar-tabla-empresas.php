<?php
/**
 * Script de reparación urgente para tabla empresas_convenio
 * Ejecutar desde: /api/reparar-tabla-empresas.php
 */

header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../assets/conexion/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Reparación Tabla Empresas</title>";
echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .info{color:blue;}</style>";
echo "</head><body>";
echo "<h1>🔧 Reparación de Tabla empresas_convenio</h1>";

try {
    $db = new DatabaseWrapper();
    $pdo = $db->getConnection();
    
    echo "<div class='info'>✅ Conexión a base de datos establecida</div><br>";
    
    // Verificar estructura actual (MySQL/MariaDB)
    echo "<h2>📋 Diagnóstico Actual:</h2>";
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM empresas_convenio");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $nombresColumnas = array_column($columnas, 'Field');
        
        echo "<div class='info'>Tabla existe con " . count($columnas) . " columnas:</div>";
        echo "<ul>";
        foreach ($nombresColumnas as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
        
        // Lista de todas las columnas necesarias con sus definiciones (MySQL/MariaDB)
        $columnasNecesarias = [
            'nombre' => "VARCHAR(255) DEFAULT 'Empresa Sin Nombre'",
            'activo' => "TINYINT(1) DEFAULT 1",
            'fecha_registro' => "DATETIME DEFAULT CURRENT_TIMESTAMP",
            'descripcion' => "TEXT",
            'logo_url' => "VARCHAR(500)",
            'sitio_web' => "VARCHAR(300)",
            'email' => "VARCHAR(150)",
            'telefono' => "VARCHAR(50)",
            'direccion' => "TEXT",
            'sector' => "VARCHAR(100)",
            'estado' => "VARCHAR(20) DEFAULT 'activa'",
            'fecha_convenio' => "DATE",
            'contacto_nombre' => "VARCHAR(150)",
            'contacto_cargo' => "VARCHAR(100)",
            'contacto_telefono' => "VARCHAR(50)",
            'contacto_email' => "VARCHAR(150)",
            'beneficios' => "TEXT",
            'descuento_porcentaje' => "DECIMAL(5,2)",
            'condiciones' => "TEXT"
        ];
        
        echo "<h2>🔧 Verificando y Reparando Columnas:</h2>";
        $columnasFaltantes = [];
        $columnasAgregadas = 0;
        
        foreach ($columnasNecesarias as $nombreColumna => $definicion) {
            if (!in_array($nombreColumna, $nombresColumnas)) {
                $columnasFaltantes[] = $nombreColumna;
                echo "<div class='error'>❌ FALTA: $nombreColumna</div>";
                
                try {
                    $sql = "ALTER TABLE empresas_convenio ADD COLUMN $nombreColumna $definicion";
                    $pdo->exec($sql);
                    echo "<div class='ok'>✅ Columna '$nombreColumna' agregada exitosamente</div>";
                    $columnasAgregadas++;
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Error agregando '$nombreColumna': " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div class='ok'>✅ $nombreColumna existe</div>";
            }
        }
        
        if ($columnasAgregadas > 0) {
            echo "<div class='info'>🎉 Se agregaron $columnasAgregadas columnas nuevas</div>";
            
            // Si se agregó la columna nombre, actualizar registros existentes
            if (in_array('nombre', $columnasFaltantes)) {
                $stmt = $pdo->query("SELECT id FROM empresas_convenio WHERE nombre = 'Empresa Sin Nombre' OR nombre IS NULL OR nombre = ''");
                $registrosSinNombre = $stmt->fetchAll();
                
                if (count($registrosSinNombre) > 0) {
                    $updateStmt = $pdo->prepare("UPDATE empresas_convenio SET nombre = ? WHERE id = ?");
                    foreach ($registrosSinNombre as $i => $registro) {
                        $nombreDefault = "Empresa " . ($i + 1);
                        $updateStmt->execute([$nombreDefault, $registro['id']]);
                    }
                    echo "<div class='ok'>✅ " . count($registrosSinNombre) . " registros actualizados con nombres por defecto</div>";
                }
            }
        }
        
        // Probar consulta problemática
        echo "<h2>🧪 Probando Consulta:</h2>";
        $stmt = $pdo->query("SELECT id, nombre FROM empresas_convenio ORDER BY nombre ASC LIMIT 3");
        $empresas = $stmt->fetchAll();
        
        echo "<div class='ok'>✅ Consulta ORDER BY nombre funciona correctamente</div>";
        echo "<div class='info'>Muestra de datos:</div>";
        echo "<ul>";
        foreach ($empresas as $empresa) {
            echo "<li>ID: {$empresa['id']} - Nombre: {$empresa['nombre']}</li>";
        }
        echo "</ul>";
        
        // Agregar datos de ejemplo si la tabla está vacía
        $stmt = $pdo->query("SELECT COUNT(*) FROM empresas_convenio");
        $totalEmpresas = $stmt->fetchColumn();
        
        if ($totalEmpresas < 3) {
            echo "<h2>📦 Agregando Datos de Ejemplo:</h2>";
            
            $empresasEjemplo = [
                [
                    'nombre' => 'TechSolutions México',
                    'descripcion' => 'Empresa líder en soluciones tecnológicas empresariales.',
                    'logo_url' => 'https://via.placeholder.com/300x200/2563eb/ffffff?text=TechSolutions',
                    'sitio_web' => 'https://techsolutions.mx',
                    'email' => 'info@techsolutions.mx',
                    'telefono' => '+52 55 1234 5678',
                    'sector' => 'Tecnología',
                    'beneficios' => 'Descuento del 15% en todos los servicios.',
                    'descuento_porcentaje' => 15.00
                ],
                [
                    'nombre' => 'Salud Integral Plus', 
                    'descripcion' => 'Centro médico especializado en medicina preventiva.',
                    'logo_url' => 'https://via.placeholder.com/300x200/059669/ffffff?text=Salud+Plus',
                    'sitio_web' => 'https://saludintegralplus.com',
                    'email' => 'contacto@saludintegralplus.com',
                    'telefono' => '+52 55 9876 5432',
                    'sector' => 'Salud',
                    'beneficios' => '20% de descuento en consultas médicas.',
                    'descuento_porcentaje' => 20.00
                ],
                [
                    'nombre' => 'Fitness & Wellness Center',
                    'descripcion' => 'Gimnasio y centro de bienestar con instalaciones de primer nivel.',
                    'logo_url' => 'https://via.placeholder.com/300x200/dc2626/ffffff?text=Fitness+Center',
                    'sitio_web' => 'https://fitnesswellness.mx', 
                    'email' => 'info@fitnesswellness.mx',
                    'telefono' => '+52 55 5555 1234',
                    'sector' => 'Deportes y Bienestar',
                    'beneficios' => 'Membresía mensual con 25% de descuento.',
                    'descuento_porcentaje' => 25.00
                ]
            ];
            
            // Verificar qué campos existen antes de insertar (MySQL/MariaDB)
            $stmt = $pdo->query("SHOW COLUMNS FROM empresas_convenio");
            $columnasExistentes = array_column($stmt->fetchAll(), 'Field');
            
            // Preparar consulta de inserción dinámicamente
            $camposInsertar = ['nombre', 'activo'];
            $valoresDefecto = ['activo' => 1];
            
            $camposOpcionales = ['descripcion', 'logo_url', 'sitio_web', 'email', 'telefono', 'sector', 'beneficios', 'descuento_porcentaje'];
            foreach ($camposOpcionales as $campo) {
                if (in_array($campo, $columnasExistentes)) {
                    $camposInsertar[] = $campo;
                }
            }
            
            $placeholders = str_repeat('?,', count($camposInsertar));
            $placeholders = rtrim($placeholders, ',');
            
            $insertSQL = "INSERT INTO empresas_convenio (" . implode(', ', $camposInsertar) . ") VALUES ($placeholders)";
            $insertStmt = $pdo->prepare($insertSQL);
            
            $insertadas = 0;
            foreach ($empresasEjemplo as $empresa) {
                $valores = [];
                foreach ($camposInsertar as $campo) {
                    $valores[] = $empresa[$campo] ?? $valoresDefecto[$campo] ?? null;
                }
                
                if ($insertStmt->execute($valores)) {
                    $insertadas++;
                }
            }
            
            echo "<div class='ok'>✅ $insertadas empresas de ejemplo insertadas</div>";
        }
        
        echo "<h2>🎉 Reparación Completada</h2>";
        echo "<div class='ok'>✅ La tabla empresas_convenio está lista para usar</div>";
        echo "<div class='info'>Puedes cerrar esta página y probar las funcionalidades de empresas</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        
        // Si hay un error grave, intentar recrear la tabla
        if (strpos($e->getMessage(), 'no such table') !== false) {
            echo "<h2>🔄 Recreando Tabla Completa:</h2>";
            
            $sqlCrearTabla = "
                CREATE TABLE IF NOT EXISTS empresas_convenio (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(255) NOT NULL DEFAULT 'Nueva Empresa',
                    activo TINYINT(1) DEFAULT 1,
                    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                    descripcion TEXT,
                    logo_url VARCHAR(500),
                    sitio_web VARCHAR(300),
                    email VARCHAR(150),
                    telefono VARCHAR(50),
                    direccion TEXT,
                    sector VARCHAR(100),
                    estado VARCHAR(20) DEFAULT 'activa',
                    fecha_convenio DATE,
                    contacto_nombre VARCHAR(150),
                    contacto_cargo VARCHAR(100),
                    contacto_telefono VARCHAR(50),
                    contacto_email VARCHAR(150),
                    beneficios TEXT,
                    descuento_porcentaje DECIMAL(5,2),
                    condiciones TEXT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            
            $pdo->exec($sqlCrearTabla);
            echo "<div class='ok'>✅ Tabla recreada exitosamente</div>";
            echo "<div class='info'>Por favor, ejecuta este script nuevamente para agregar datos de ejemplo</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error crítico: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Archivo: " . $e->getFile() . "</div>";
    echo "<div class='error'>Línea: " . $e->getLine() . "</div>";
}

echo "<br><hr><p><small>Script de reparación ejecutado en: " . date('Y-m-d H:i:s') . "</small></p>";
echo "</body></html>";
?>