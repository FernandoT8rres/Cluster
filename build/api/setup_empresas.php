<?php
/**
 * Script para crear la tabla empresas_convenio si no existe
 * y agregar datos de ejemplo
 */

header('Content-Type: application/json; charset=UTF-8');

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

function responderJSON($success, $data = null, $message = '') {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // Intentar cargar configuración
    $configFiles = [
        __DIR__ . '/../config/database.php',
        __DIR__ . '/config.php'
    ];
    
    $configLoaded = false;
    foreach ($configFiles as $file) {
        if (file_exists($file)) {
            require_once $file;
            $configLoaded = true;
            break;
        }
    }
    
    if (!$configLoaded) {
        responderJSON(false, null, 'No se encontró archivo de configuración');
    }
    
    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // SQL para crear la tabla
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS empresas_convenio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_empresa VARCHAR(255) NOT NULL,
        descripcion TEXT,
        logo_url VARCHAR(500),
        sitio_web VARCHAR(255),
        telefono VARCHAR(50),
        email VARCHAR(255),
        direccion TEXT,
        categoria VARCHAR(100),
        descuento DECIMAL(5,2) DEFAULT 0.00,
        beneficios TEXT,
        fecha_inicio_convenio DATE,
        fecha_fin_convenio DATE,
        activo TINYINT(1) DEFAULT 1,
        destacado TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_activo (activo),
        INDEX idx_destacado (destacado),
        INDEX idx_categoria (categoria)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Crear la tabla
    $conn->exec($createTableSQL);
    
    // Verificar si ya hay datos
    $countQuery = $conn->query("SELECT COUNT(*) as total FROM empresas_convenio");
    $count = $countQuery->fetch(PDO::FETCH_ASSOC)['total'];
    
    $message = "Tabla verificada/creada correctamente.";
    
    // Si no hay datos, insertar ejemplos
    if ($count == 0) {
        $insertSQL = "
        INSERT INTO empresas_convenio (
            nombre_empresa, descripcion, logo_url, sitio_web, telefono, email, 
            direccion, categoria, descuento, beneficios, fecha_inicio_convenio, 
            fecha_fin_convenio, activo, destacado
        ) VALUES 
        (
            'TechSolutions Mexico',
            'Empresa líder en soluciones tecnológicas y desarrollo de software para el sector automotriz.',
            'https://ui-avatars.com/api/?name=TechSolutions&background=0080ff&color=fff&size=128',
            'https://techsolutions.mx',
            '+52 55 1234 5678',
            'contacto@techsolutions.mx',
            'Av. Tecnológico 1234, CDMX',
            'Tecnología',
            15.0,
            '15% de descuento en todos los servicios de desarrollo y consultoría tecnológica',
            '2024-01-15',
            '2025-12-31',
            1,
            1
        ),
        (
            'AutoServicios Premium',
            'Centro de servicios automotrices especializado en mantenimiento y reparación de vehículos.',
            'https://ui-avatars.com/api/?name=AutoServicios&background=ff6b35&color=fff&size=128',
            'https://autoservicios.com.mx',
            '+52 55 2345 6789',
            'info@autoservicios.com.mx',
            'Blvd. Automotriz 567, Estado de México',
            'Automotriz',
            20.0,
            '20% de descuento en servicios de mantenimiento preventivo y correctivo',
            '2024-02-01',
            '2025-12-31',
            1,
            1
        ),
        (
            'Capacitación Industrial Plus',
            'Instituto especializado en capacitación y desarrollo de habilidades para la industria manufacturera.',
            'https://ui-avatars.com/api/?name=Capacitacion&background=28a745&color=fff&size=128',
            'https://capacitacionplus.edu.mx',
            '+52 55 3456 7890',
            'cursos@capacitacionplus.edu.mx',
            'Zona Industrial Norte, Tlalnepantla',
            'Educación',
            25.0,
            '25% de descuento en cursos de certificación y diplomados especializados',
            '2024-01-10',
            '2025-12-31',
            1,
            1
        ),
        (
            'Logística Global Express',
            'Empresa de logística y transporte especializada en la cadena de suministro automotriz.',
            'https://ui-avatars.com/api/?name=Logistica&background=6c757d&color=fff&size=128',
            'https://logisticaglobal.mx',
            '+52 55 4567 8901',
            'servicios@logisticaglobal.mx',
            'Parque Industrial Cuautitlán',
            'Logística',
            12.0,
            '12% de descuento en servicios de transporte y almacenamiento',
            '2024-03-01',
            '2025-12-31',
            1,
            1
        ),
        (
            'Salud Corporativa Integral',
            'Centro médico especializado en medicina ocupacional y servicios de salud para empresas.',
            'https://ui-avatars.com/api/?name=Salud&background=dc3545&color=fff&size=128',
            'https://saludcorporativa.mx',
            '+52 55 5678 9012',
            'citas@saludcorporativa.mx',
            'Medical Center Plaza, Satélite',
            'Salud',
            18.0,
            '18% de descuento en consultas médicas, exámenes ocupacionales y estudios de laboratorio',
            '2024-01-20',
            '2025-12-31',
            1,
            1
        )";
        
        $conn->exec($insertSQL);
        $message .= " Se insertaron 5 empresas de ejemplo.";
    }
    
    // Obtener estadísticas finales
    $statsQuery = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(activo) as activas,
            SUM(destacado) as destacadas
        FROM empresas_convenio
    ");
    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);
    
    responderJSON(true, $stats, $message);
    
} catch (Exception $e) {
    responderJSON(false, null, 'Error: ' . $e->getMessage());
}
?>