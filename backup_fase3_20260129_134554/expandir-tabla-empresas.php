<?php
/**
 * Script para expandir la tabla empresas_convenio con campos adicionales
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
    
    $cambiosRealizados = [];
    
    // Campos adicionales que necesitamos para la funcionalidad completa
    $nuevoscampos = [
        'descripcion' => 'TEXT NULL',
        'logo_url' => 'VARCHAR(500) NULL',
        'sitio_web' => 'VARCHAR(300) NULL',
        'email' => 'VARCHAR(150) NULL',
        'telefono' => 'VARCHAR(50) NULL',
        'direccion' => 'TEXT NULL',
        'sector' => 'VARCHAR(100) NULL',
        'estado' => 'VARCHAR(20) DEFAULT "activa"',
        'fecha_convenio' => 'DATE NULL',
        'contacto_nombre' => 'VARCHAR(150) NULL',
        'contacto_cargo' => 'VARCHAR(100) NULL',
        'contacto_telefono' => 'VARCHAR(50) NULL',
        'contacto_email' => 'VARCHAR(150) NULL',
        'contacto_persona' => 'VARCHAR(255) NULL',
        'beneficios' => 'TEXT NULL',
        'descuento_porcentaje' => 'DECIMAL(5,2) NULL',
        'condiciones' => 'TEXT NULL',
        'fecha_actualizacion' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
    ];
    
    // Verificar qué campos ya existen
    $stmt = $pdo->query("PRAGMA table_info(empresas_convenio)");
    $columnasExistentes = array_column($stmt->fetchAll(), 'name');
    
    // Agregar campos faltantes
    foreach ($nuevoscampos as $campo => $definicion) {
        if (!in_array($campo, $columnasExistentes)) {
            try {
                $alterSQL = "ALTER TABLE empresas_convenio ADD COLUMN $campo $definicion";
                $pdo->exec($alterSQL);
                $cambiosRealizados[] = "Agregado campo: $campo ($definicion)";
            } catch (Exception $e) {
                $cambiosRealizados[] = "Error agregando $campo: " . $e->getMessage();
            }
        } else {
            $cambiosRealizados[] = "Campo $campo ya existe";
        }
    }
    
    // Insertar datos de ejemplo si la tabla está casi vacía
    $countStmt = $pdo->query("SELECT COUNT(*) FROM empresas_convenio WHERE descripcion IS NOT NULL");
    $empresasConDatos = $countStmt->fetchColumn();
    
    if ($empresasConDatos < 3) {
        $empresasEjemplo = [
            [
                'nombre' => 'TechSolutions México',
                'descripcion' => 'Empresa líder en soluciones tecnológicas empresariales, especializada en desarrollo de software, consultoría IT y transformación digital.',
                'logo_url' => 'https://via.placeholder.com/200x120/2563eb/ffffff?text=TechSolutions',
                'sitio_web' => 'https://techsolutions.mx',
                'email' => 'info@techsolutions.mx',
                'telefono' => '+52 55 1234 5678',
                'direccion' => 'Av. Reforma 123, Ciudad de México',
                'sector' => 'Tecnología',
                'estado' => 'activa',
                'fecha_convenio' => '2024-01-15',
                'contacto_nombre' => 'Ana García',
                'contacto_cargo' => 'Gerente de Alianzas',
                'contacto_telefono' => '+52 55 1234 5679',
                'contacto_email' => 'ana.garcia@techsolutions.mx',
                'beneficios' => 'Descuento del 15% en todos los servicios de desarrollo, consultoría gratuita inicial, soporte técnico prioritario.',
                'descuento_porcentaje' => 15.00,
                'condiciones' => 'Válido para empleados activos de Clúster con credencial vigente. No acumulable con otras promociones.',
                'activo' => 1
            ],
            [
                'nombre' => 'Salud Integral Plus',
                'descripcion' => 'Centro médico especializado en medicina preventiva y ocupacional, ofreciendo servicios integrales de salud para empresas.',
                'logo_url' => 'https://via.placeholder.com/200x120/059669/ffffff?text=Salud+Plus',
                'sitio_web' => 'https://saludintegralplus.com',
                'email' => 'contacto@saludintegralplus.com',
                'telefono' => '+52 55 9876 5432',
                'direccion' => 'Calle Médicos 456, Guadalajara, Jalisco',
                'sector' => 'Salud',
                'estado' => 'activa',
                'fecha_convenio' => '2024-02-20',
                'contacto_nombre' => 'Dr. Carlos Mendoza',
                'contacto_cargo' => 'Director Médico',
                'contacto_telefono' => '+52 55 9876 5433',
                'contacto_email' => 'carlos.mendoza@saludintegralplus.com',
                'beneficios' => '20% de descuento en consultas médicas, estudios de laboratorio con precio especial, revisiones médicas ocupacionales.',
                'descuento_porcentaje' => 20.00,
                'condiciones' => 'Cita previa necesaria. Descuento aplicable presentando credencial de empleado.',
                'activo' => 1
            ],
            [
                'nombre' => 'Fitness & Wellness Center',
                'descripcion' => 'Gimnasio y centro de bienestar con instalaciones de primer nivel, clases grupales, entrenamiento personal y spa.',
                'logo_url' => 'https://via.placeholder.com/200x120/dc2626/ffffff?text=Fitness+Center',
                'sitio_web' => 'https://fitnesswellness.mx',
                'email' => 'info@fitnesswellness.mx',
                'telefono' => '+52 55 5555 1234',
                'direccion' => 'Plaza Deportiva 789, Monterrey, N.L.',
                'sector' => 'Deportes y Bienestar',
                'estado' => 'activa',
                'fecha_convenio' => '2024-03-10',
                'contacto_nombre' => 'María López',
                'contacto_cargo' => 'Coordinadora Corporativa',
                'contacto_telefono' => '+52 55 5555 1235',
                'contacto_email' => 'maria.lopez@fitnesswellness.mx',
                'beneficios' => 'Membresía mensual con 25% de descuento, clases grupales incluidas, evaluación física gratuita.',
                'descuento_porcentaje' => 25.00,
                'condiciones' => 'Membresía mínima de 3 meses. Beneficio intransferible.',
                'activo' => 1
            ]
        ];
        
        $insertSQL = "UPDATE empresas_convenio SET 
            descripcion = ?, logo_url = ?, sitio_web = ?, email = ?, telefono = ?, 
            direccion = ?, sector = ?, estado = ?, fecha_convenio = ?, 
            contacto_nombre = ?, contacto_cargo = ?, contacto_telefono = ?, contacto_email = ?,
            beneficios = ?, descuento_porcentaje = ?, condiciones = ?
            WHERE nombre = ?";
        
        $stmt = $pdo->prepare($insertSQL);
        $datosInsertados = 0;
        
        foreach ($empresasEjemplo as $empresa) {
            // Verificar si ya existe una empresa con este nombre
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM empresas_convenio WHERE nombre = ?");
            $checkStmt->execute([$empresa['nombre']]);
            
            if ($checkStmt->fetchColumn() == 0) {
                // Insertar nueva empresa
                $insertNewSQL = "INSERT INTO empresas_convenio (nombre, descripcion, logo_url, sitio_web, email, telefono, direccion, sector, estado, fecha_convenio, contacto_nombre, contacto_cargo, contacto_telefono, contacto_email, beneficios, descuento_porcentaje, condiciones, activo, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))";
                $insertNewStmt = $pdo->prepare($insertNewSQL);
                $insertNewStmt->execute([
                    $empresa['nombre'], $empresa['descripcion'], $empresa['logo_url'], 
                    $empresa['sitio_web'], $empresa['email'], $empresa['telefono'],
                    $empresa['direccion'], $empresa['sector'], $empresa['estado'],
                    $empresa['fecha_convenio'], $empresa['contacto_nombre'], $empresa['contacto_cargo'],
                    $empresa['contacto_telefono'], $empresa['contacto_email'], $empresa['beneficios'],
                    $empresa['descuento_porcentaje'], $empresa['condiciones'], $empresa['activo']
                ]);
            } else {
                // Actualizar empresa existente
                $stmt->execute([
                    $empresa['descripcion'], $empresa['logo_url'], $empresa['sitio_web'],
                    $empresa['email'], $empresa['telefono'], $empresa['direccion'],
                    $empresa['sector'], $empresa['estado'], $empresa['fecha_convenio'],
                    $empresa['contacto_nombre'], $empresa['contacto_cargo'], $empresa['contacto_telefono'],
                    $empresa['contacto_email'], $empresa['beneficios'], $empresa['descuento_porcentaje'],
                    $empresa['condiciones'], $empresa['nombre']
                ]);
            }
            $datosInsertados++;
        }
        
        $cambiosRealizados[] = "Insertados/actualizados $datosInsertados registros de ejemplo";
    }
    
    // Verificar estructura final
    $stmt = $pdo->query("PRAGMA table_info(empresas_convenio)");
    $estructuraFinal = $stmt->fetchAll();
    
    sendJsonResponse([
        'mensaje' => 'Tabla empresas_convenio expandida exitosamente',
        'cambios_realizados' => $cambiosRealizados,
        'campos_totales' => count($estructuraFinal),
        'estructura_final' => array_column($estructuraFinal, 'name')
    ]);
    
} catch (Exception $e) {
    error_log("Error expandiendo tabla empresas: " . $e->getMessage());
    sendJsonResponse('Error expandiendo tabla: ' . $e->getMessage(), false);
}
?>