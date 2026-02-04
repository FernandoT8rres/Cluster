<?php
/**
 * Corrección directa de estados problemáticos en la tabla eventos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Configuración de base de datos
$config = [
    'host' => '127.0.0.1',
    'username' => 'u695712029_claut_fer', 
    'password' => 'CLAUT@admin_fernando!7',
    'database' => 'u695712029_claut_intranet',
    'charset' => 'utf8mb4'
];

function sendResponse($message, $success = true, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $repairs = [];
    
    // PASO 1: Verificar estructura actual de la columna estado
    $columnInfo = $pdo->query("SHOW COLUMNS FROM eventos LIKE 'estado'")->fetch();
    if ($columnInfo) {
        $repairs[] = "📋 Columna estado encontrada: " . $columnInfo['Type'];
        
        // Verificar si es ENUM y qué valores tiene
        if (strpos($columnInfo['Type'], 'enum') !== false) {
            $repairs[] = "⚠️ Columna estado es ENUM, verificando valores...";
            
            // Obtener valores únicos actuales
            $currentStates = $pdo->query("SELECT DISTINCT estado FROM eventos")->fetchAll(PDO::FETCH_COLUMN);
            $repairs[] = "📊 Estados actuales: " . implode(', ', array_map(function($s) { return "'$s'"; }, $currentStates));
            
            // PASO 2: Cambiar la columna a VARCHAR temporalmente para poder corregir los datos
            $pdo->exec("ALTER TABLE eventos MODIFY COLUMN estado VARCHAR(20)");
            $repairs[] = "🔧 Columna estado cambiada temporalmente a VARCHAR(20)";
            
            // PASO 3: Corregir todos los valores problemáticos
            $corrections = [
                "'proximo'" => "'programado'",
                "'próximo'" => "'programado'", 
                "'activo'" => "'en_curso'",
                "'finaliza'" => "'finalizado'",
                "'terminado'" => "'finalizado'"
            ];
            
            $totalUpdated = 0;
            foreach ($corrections as $from => $to) {
                $stmt = $pdo->prepare("UPDATE eventos SET estado = $to WHERE estado = $from");
                $stmt->execute();
                $updated = $stmt->rowCount();
                if ($updated > 0) {
                    $repairs[] = "✅ Corregidos $updated eventos de $from a $to";
                    $totalUpdated += $updated;
                }
            }
            
            // PASO 4: Volver a convertir a ENUM con los valores correctos
            $pdo->exec("ALTER TABLE eventos MODIFY COLUMN estado ENUM('programado', 'en_curso', 'finalizado', 'cancelado') DEFAULT 'programado'");
            $repairs[] = "🔧 Columna estado restaurada a ENUM con valores correctos";
            
        } else {
            $repairs[] = "ℹ️ Columna estado no es ENUM, probablemente VARCHAR";
        }
        
    } else {
        $repairs[] = "❌ Columna estado no encontrada en tabla eventos";
    }
    
    // PASO 5: Verificar si existe la tabla evento_asistentes
    $checkTable = $pdo->query("SHOW TABLES LIKE 'evento_asistentes'")->fetch();
    
    if (!$checkTable) {
        // Crear la tabla evento_asistentes
        $createTableSQL = "
        CREATE TABLE evento_asistentes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            evento_id INT NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            telefono VARCHAR(50),
            empresa VARCHAR(255),
            cargo VARCHAR(100),
            estado ENUM('registrado', 'confirmado', 'no_asistió', 'cancelado') DEFAULT 'registrado',
            notas TEXT,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_confirmacion TIMESTAMP NULL,
            FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
            INDEX idx_evento_id (evento_id),
            INDEX idx_email (email),
            INDEX idx_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createTableSQL);
        $repairs[] = "✅ Tabla 'evento_asistentes' creada exitosamente";
        
        // PASO 6: Insertar datos de ejemplo - SOLO si tenemos eventos en la tabla
        $eventCount = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
        if ($eventCount > 0) {
            $insertSampleData = "
            INSERT INTO evento_asistentes (evento_id, nombre, email, telefono, empresa, cargo, estado) VALUES
            (1, 'Fernando Torres', 'fernando@clúster.com', '555-1111111', 'Autopartes México SA', 'Desarrollador', 'confirmado'),
            (1, 'María González', 'maria@autopartes.com', '555-2222222', 'Autopartes México SA', 'Gerente de Calidad', 'confirmado')";
            
            // Solo agregar más registros si existen esos eventos
            if ($eventCount >= 2) {
                $insertSampleData .= ",
                (2, 'Carlos Rodríguez', 'carlos@talleres.com', '555-3333333', 'Talleres Unidos SC', 'Técnico Especialista', 'registrado'),
                (2, 'Ana Martínez', 'ana@logistica.com', '555-4444444', 'Logística Automotriz', 'Coordinadora', 'confirmado')";
            }
            
            if ($eventCount >= 3) {
                $insertSampleData .= ",
                (3, 'Luis Pérez', 'luis@autopartes.com', '555-5555555', 'Autopartes México SA', 'Supervisor', 'registrado')";
            }
            
            try {
                $pdo->exec($insertSampleData);
                $repairs[] = "✅ Datos de ejemplo insertados en 'evento_asistentes'";
            } catch (Exception $e) {
                $repairs[] = "⚠️ Error al insertar datos de ejemplo: " . $e->getMessage();
            }
        } else {
            $repairs[] = "ℹ️ No hay eventos en la tabla, omitiendo inserción de datos de ejemplo";
        }
        
    } else {
        $repairs[] = "ℹ️ La tabla 'evento_asistentes' ya existe";
    }
    
    // PASO 7: Verificar que las consultas funcionen
    try {
        $testQuery = $pdo->query("
            SELECT e.id, e.titulo, e.estado, COUNT(ea.id) as asistentes_registrados
            FROM eventos e
            LEFT JOIN evento_asistentes ea ON e.id = ea.evento_id AND ea.estado != 'no_asistió'
            GROUP BY e.id
            LIMIT 3
        ")->fetchAll();
        
        $repairs[] = "✅ Test de consulta JOIN exitoso - " . count($testQuery) . " eventos verificados";
        
    } catch (Exception $e) {
        $repairs[] = "❌ Error en test de consulta: " . $e->getMessage();
    }
    
    // PASO 8: Verificar estados finales
    $finalStates = $pdo->query("SELECT DISTINCT estado FROM eventos")->fetchAll(PDO::FETCH_COLUMN);
    $repairs[] = "📊 Estados finales en eventos: " . implode(', ', array_map(function($s) { return "'$s'"; }, $finalStates));
    
    $summary = [
        'repairs_completed' => count($repairs),
        'repairs' => $repairs,
        'final_states' => $finalStates
    ];
    
    sendResponse("Corrección de estados completada exitosamente", true, $summary);
    
} catch (PDOException $e) {
    sendResponse("Error de base de datos: " . $e->getMessage(), false, [
        'error_code' => $e->getCode(),
        'error_details' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    sendResponse("Error general: " . $e->getMessage(), false, [
        'error_details' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>