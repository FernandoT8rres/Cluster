<?php
/**
 * Script para diagnosticar y corregir problemas con la tabla empresas_convenio
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../assets/conexion/config.php';

function sendJsonResponse($data, $success = true) {
    echo json_encode([
        'success' => $success,
        'data' => $success ? $data : null,
        'message' => $success ? 'OK' : $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $db = new DatabaseWrapper();
    $pdo = $db->getConnection();
    
    $diagnostico = [];
    $accion = $_GET['accion'] ?? 'diagnosticar';
    
    // 1. Verificar si la tabla existe (MySQL/MariaDB)
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'empresas_convenio'");
        $tablaExiste = $stmt->fetch() !== false;
        $diagnostico['tabla_existe'] = $tablaExiste;
        
        if (!$tablaExiste) {
            $diagnostico['error'] = 'La tabla empresas_convenio no existe';
        }
    } catch (Exception $e) {
        $diagnostico['error_verificacion_tabla'] = $e->getMessage();
    }
    
    // 2. Si la tabla existe, verificar estructura (MySQL/MariaDB)
    if ($tablaExiste) {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM empresas_convenio");
            $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $diagnostico['columnas'] = array_column($columnas, 'Field');
            $diagnostico['estructura_completa'] = $columnas;
            
            // Verificar columnas específicas
            $columnasRequeridas = ['id', 'nombre', 'activo', 'fecha_registro'];
            $columnasFaltantes = [];
            
            foreach ($columnasRequeridas as $col) {
                if (!in_array($col, $diagnostico['columnas'])) {
                    $columnasFaltantes[] = $col;
                }
            }
            
            $diagnostico['columnas_faltantes'] = $columnasFaltantes;
            
        } catch (Exception $e) {
            $diagnostico['error_estructura'] = $e->getMessage();
        }
        
        // 3. Contar registros
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM empresas_convenio");
            $diagnostico['total_registros'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $diagnostico['error_conteo'] = $e->getMessage();
        }
        
        // 4. Probar consulta problemática
        try {
            if (in_array('nombre', $diagnostico['columnas'])) {
                $stmt = $pdo->query("SELECT * FROM empresas_convenio ORDER BY nombre ASC LIMIT 1");
                $resultado = $stmt->fetch();
                $diagnostico['consulta_order_by'] = 'OK';
                $diagnostico['muestra_datos'] = $resultado;
            } else {
                $diagnostico['consulta_order_by'] = 'FALLA - columna nombre no existe';
            }
        } catch (Exception $e) {
            $diagnostico['error_consulta'] = $e->getMessage();
        }
    }
    
    // 5. Si se solicita reparación
    if ($accion === 'reparar' && $tablaExiste) {
        $reparaciones = [];
        
        // Lista completa de columnas necesarias (MySQL/MariaDB)
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
        
        // Verificar y crear columnas faltantes
        foreach ($columnasNecesarias as $nombreColumna => $definicion) {
            if (!in_array($nombreColumna, $diagnostico['columnas'])) {
                try {
                    $sql = "ALTER TABLE empresas_convenio ADD COLUMN $nombreColumna $definicion";
                    $pdo->exec($sql);
                    $reparaciones[] = "✅ Agregada columna: $nombreColumna";
                } catch (Exception $e) {
                    $reparaciones[] = "❌ Error agregando $nombreColumna: " . $e->getMessage();
                }
            }
        }
        
        // Si se agregó la columna nombre, actualizar registros
        if (in_array('nombre', array_keys($columnasNecesarias)) && !in_array('nombre', $diagnostico['columnas'])) {
            try {
                $stmt = $pdo->query("SELECT id FROM empresas_convenio WHERE nombre IS NULL OR nombre = 'Empresa Sin Nombre' OR nombre = ''");
                $registros = $stmt->fetchAll();
                
                $updateStmt = $pdo->prepare("UPDATE empresas_convenio SET nombre = ? WHERE id = ?");
                foreach ($registros as $i => $registro) {
                    $nombreDefault = "Empresa " . ($i + 1);
                    $updateStmt->execute([$nombreDefault, $registro['id']]);
                }
                $reparaciones[] = "✅ " . count($registros) . " registros actualizados con nombres por defecto";
            } catch (Exception $e) {
                $reparaciones[] = "❌ Error actualizando nombres: " . $e->getMessage();
            }
        }
        
        $diagnostico['reparaciones'] = $reparaciones;
        
        // Re-verificar después de reparaciones (MySQL/MariaDB)
        $stmt = $pdo->query("SHOW COLUMNS FROM empresas_convenio");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $diagnostico['columnas_despues_reparacion'] = array_column($columnas, 'Field');
    }
    
    // 6. Si se solicita recrear tabla completa
    if ($accion === 'recrear') {
        try {
            // Respaldar datos existentes si los hay
            $datosExistentes = [];
            if ($tablaExiste) {
                try {
                    $stmt = $pdo->query("SELECT * FROM empresas_convenio");
                    $datosExistentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    // Ignorar errores al respaldar datos corruptos
                }
            }
            
            // Eliminar tabla existente
            $pdo->exec("DROP TABLE IF EXISTS empresas_convenio");
            
            // Crear tabla nueva con estructura correcta (MySQL/MariaDB)
            $sqlCrearTabla = "
                CREATE TABLE empresas_convenio (
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
            
            // Insertar datos de ejemplo
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
                    'descuento_porcentaje' => 15.00,
                    'activo' => 1
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
                    'descuento_porcentaje' => 20.00,
                    'activo' => 1
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
                    'descuento_porcentaje' => 25.00,
                    'activo' => 1
                ]
            ];
            
            $insertSQL = "INSERT INTO empresas_convenio (nombre, descripcion, logo_url, sitio_web, email, telefono, sector, beneficios, descuento_porcentaje, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($insertSQL);
            
            foreach ($empresasEjemplo as $empresa) {
                $stmt->execute([
                    $empresa['nombre'], $empresa['descripcion'], $empresa['logo_url'],
                    $empresa['sitio_web'], $empresa['email'], $empresa['telefono'],
                    $empresa['sector'], $empresa['beneficios'], $empresa['descuento_porcentaje'],
                    $empresa['activo']
                ]);
            }
            
            $diagnostico['tabla_recreada'] = true;
            $diagnostico['empresas_insertadas'] = count($empresasEjemplo);
            $diagnostico['datos_respaldados'] = count($datosExistentes);
            
        } catch (Exception $e) {
            $diagnostico['error_recreacion'] = $e->getMessage();
        }
    }
    
    sendJsonResponse($diagnostico);
    
} catch (Exception $e) {
    error_log("Error en diagnosticar-empresas: " . $e->getMessage());
    sendJsonResponse('Error del servidor: ' . $e->getMessage(), false);
}
?>