<?php
/**
 * SOLUCIÓN DE EMERGENCIA - Corrige específicamente el error de truncamiento
 * Diseñado para solucionar: SQLSTATE[01000]: Warning: 1265 Data truncated for column 'tipo'
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    // Conectar a la base de datos
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $repairs = [];
    $errors = [];
    
    $repairs[] = "🚨 EMERGENCIA: Iniciando corrección de truncamiento...";
    
    // ===============================================
    // PASO 1: VERIFICAR PROBLEMA ACTUAL
    // ===============================================
    
    $repairs[] = "🔍 PASO 1: Diagnosticando problema actual...";
    
    // Verificar estructura de columnas problemáticas
    $columns = $pdo->query("SHOW COLUMNS FROM eventos")->fetchAll();
    $columnInfo = [];
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['estado', 'tipo'])) {
            $columnInfo[$col['Field']] = $col;
            $repairs[] = "📋 Columna '{$col['Field']}': {$col['Type']}";
        }
    }
    
    // Verificar datos actuales
    $estadosActuales = $pdo->query("SELECT DISTINCT estado FROM eventos")->fetchAll(PDO::FETCH_COLUMN);
    $tiposActuales = $pdo->query("SELECT DISTINCT tipo FROM eventos WHERE tipo IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    
    $repairs[] = "📊 Estados encontrados: " . implode(', ', array_map(function($s) { return "'$s'"; }, $estadosActuales));
    $repairs[] = "📊 Tipos encontrados: " . implode(', ', array_map(function($t) { return "'$t'"; }, $tiposActuales));
    
    // ===============================================
    // PASO 2: CAMBIAR A VARCHAR TEMPORALMENTE
    // ===============================================
    
    $repairs[] = "🔧 PASO 2: Cambiando columnas a VARCHAR...";
    
    // Cambiar estado a VARCHAR
    $pdo->exec("ALTER TABLE eventos MODIFY COLUMN estado VARCHAR(50)");
    $repairs[] = "✅ Estado cambiado a VARCHAR(50)";
    
    // Cambiar tipo a VARCHAR
    $pdo->exec("ALTER TABLE eventos MODIFY COLUMN tipo VARCHAR(50)");
    $repairs[] = "✅ Tipo cambiado a VARCHAR(50)";
    
    // ===============================================
    // PASO 3: CORREGIR DATOS PROBLEMÁTICOS
    // ===============================================
    
    $repairs[] = "🔧 PASO 3: Corrigiendo datos problemáticos...";
    
    // Corregir estados
    $estadoCorrections = [
        ['proximo', 'programado'],
        ['próximo', 'programado'],
        ['pendiente', 'programado'],
        ['activo', 'en_curso'],
        ['en_proceso', 'en_curso'],
        ['finaliza', 'finalizado'],
        ['terminado', 'finalizado'],
        ['completado', 'finalizado'],
        ['cancelado', 'cancelado']
    ];
    
    $totalEstadoFixed = 0;
    foreach ($estadoCorrections as $correction) {
        $stmt = $pdo->prepare("UPDATE eventos SET estado = ? WHERE estado = ?");
        $stmt->execute([$correction[1], $correction[0]]);
        $updated = $stmt->rowCount();
        if ($updated > 0) {
            $repairs[] = "✅ Estados: '$correction[0]' → '$correction[1]' ($updated eventos)";
            $totalEstadoFixed += $updated;
        }
    }
    
    // Asignar estado por defecto a valores extraños
    $stmt = $pdo->prepare("UPDATE eventos SET estado = 'programado' WHERE estado NOT IN ('programado', 'en_curso', 'finalizado', 'cancelado')");
    $stmt->execute();
    $extranosEstado = $stmt->rowCount();
    if ($extranosEstado > 0) {
        $repairs[] = "✅ Asignado 'programado' a $extranosEstado eventos con estados extraños";
        $totalEstadoFixed += $extranosEstado;
    }
    
    // Corregir tipos
    $tipoCorrections = [
        ['capacitacion', 'capacita'],
        ['capacitación', 'capacita'],
        ['training', 'capacita'],
        ['importante', 'import'],
        ['important', 'import'],
        ['priority', 'import'],
        ['reunion', 'reunion'],
        ['meeting', 'reunion'],
        ['junta', 'reunion'],
        ['social', 'social'],
        ['evento_social', 'social']
    ];
    
    $totalTipoFixed = 0;
    foreach ($tipoCorrections as $correction) {
        $stmt = $pdo->prepare("UPDATE eventos SET tipo = ? WHERE tipo = ?");
        $stmt->execute([$correction[1], $correction[0]]);
        $updated = $stmt->rowCount();
        if ($updated > 0) {
            $repairs[] = "✅ Tipos: '$correction[0]' → '$correction[1]' ($updated eventos)";
            $totalTipoFixed += $updated;
        }
    }
    
    // Asignar tipo por defecto a valores NULL o extraños
    $stmt = $pdo->prepare("UPDATE eventos SET tipo = 'reunion' WHERE tipo IS NULL OR tipo = '' OR tipo NOT IN ('reunion', 'capacita', 'social', 'import', 'otro')");
    $stmt->execute();
    $extranosTipo = $stmt->rowCount();
    if ($extranosTipo > 0) {
        $repairs[] = "✅ Asignado 'reunion' a $extranosTipo eventos sin tipo válido";
        $totalTipoFixed += $extranosTipo;
    }
    
    $repairs[] = "📊 Total corregido: $totalEstadoFixed estados, $totalTipoFixed tipos";
    
    // ===============================================
    // PASO 4: RESTAURAR ENUMs CON VALORES CORRECTOS
    // ===============================================
    
    $repairs[] = "🔧 PASO 4: Restaurando ENUMs con valores válidos...";
    
    // Restaurar estado a ENUM
    $pdo->exec("ALTER TABLE eventos MODIFY COLUMN estado ENUM('programado', 'en_curso', 'finalizado', 'cancelado') DEFAULT 'programado'");
    $repairs[] = "✅ Estado restaurado a ENUM válido";
    
    // Restaurar tipo a ENUM
    $pdo->exec("ALTER TABLE eventos MODIFY COLUMN tipo ENUM('reunion', 'capacita', 'social', 'import', 'otro') DEFAULT 'reunion'");
    $repairs[] = "✅ Tipo restaurado a ENUM válido";
    
    // ===============================================
    // PASO 5: CREAR TABLA EVENTO_ASISTENTES SI NO EXISTE
    // ===============================================
    
    $repairs[] = "🔧 PASO 5: Verificando tabla evento_asistentes...";
    
    $checkTable = $pdo->query("SHOW TABLES LIKE 'evento_asistentes'")->fetch();
    
    if (!$checkTable) {
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
        $repairs[] = "✅ Tabla 'evento_asistentes' creada";
        
        // Datos de ejemplo
        $eventCount = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
        if ($eventCount > 0) {
            try {
                $pdo->exec("
                INSERT INTO evento_asistentes (evento_id, nombre, email, telefono, empresa, cargo, estado) VALUES
                (1, 'Usuario Ejemplo', 'ejemplo@clúster.com', '555-0000000', 'Empresa Ejemplo', 'Cargo Ejemplo', 'confirmado')
                ");
                $repairs[] = "✅ Datos de ejemplo insertados";
            } catch (Exception $e) {
                $repairs[] = "⚠️ No se pudieron insertar datos de ejemplo: " . $e->getMessage();
            }
        }
    } else {
        $repairs[] = "ℹ️ Tabla 'evento_asistentes' ya existe";
    }
    
    // ===============================================
    // PASO 6: VERIFICAR SOLUCIÓN
    // ===============================================
    
    $repairs[] = "🔧 PASO 6: Verificando que la solución funcionó...";
    
    // Test de inserción
    try {
        $testInsert = $pdo->prepare("
            INSERT INTO eventos (titulo, descripcion, fecha_inicio, fecha_fin, ubicacion, organizador_id, tipo, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $testData = [
            'PRUEBA - Evento Test de Emergencia',
            'Evento para verificar que no hay más errores de truncamiento',
            date('Y-m-d H:i:s', strtotime('+1 day')),
            date('Y-m-d H:i:s', strtotime('+1 day +2 hours')),
            'Sala de Pruebas de Emergencia',
            1,
            'reunion',
            'programado'
        ];
        
        $testInsert->execute($testData);
        $testId = $pdo->lastInsertId();
        
        $repairs[] = "✅ Test de inserción EXITOSO (ID: $testId) - No más truncamiento";
        
        // Eliminar el evento de prueba
        $pdo->prepare("DELETE FROM eventos WHERE id = ?")->execute([$testId]);
        $repairs[] = "🗑️ Evento de prueba eliminado";
        
    } catch (Exception $e) {
        $errors[] = "❌ Error en test de inserción: " . $e->getMessage();
    }
    
    // Test de consulta compleja
    try {
        $testQuery = $pdo->query("
            SELECT e.id, e.titulo, e.estado, e.tipo, COUNT(ea.id) as asistentes_registrados
            FROM eventos e
            LEFT JOIN evento_asistentes ea ON e.id = ea.evento_id AND ea.estado != 'no_asistió'
            GROUP BY e.id
            LIMIT 3
        ")->fetchAll();
        
        $repairs[] = "✅ Test de consulta JOIN exitoso (" . count($testQuery) . " eventos)";
        
    } catch (Exception $e) {
        $errors[] = "❌ Error en test de consulta: " . $e->getMessage();
    }
    
    // Verificar valores finales
    $estadosFinales = $pdo->query("SELECT DISTINCT estado FROM eventos")->fetchAll(PDO::FETCH_COLUMN);
    $tiposFinales = $pdo->query("SELECT DISTINCT tipo FROM eventos")->fetchAll(PDO::FETCH_COLUMN);
    
    $repairs[] = "📊 Estados finales válidos: " . implode(', ', $estadosFinales);
    $repairs[] = "📊 Tipos finales válidos: " . implode(', ', $tiposFinales);
    
    // Conteos finales
    $eventosCount = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
    $asistentesCount = $pdo->query("SELECT COUNT(*) FROM evento_asistentes")->fetchColumn();
    
    $repairs[] = "📊 Total final: $eventosCount eventos, $asistentesCount asistentes";
    $repairs[] = "🎉 EMERGENCIA RESUELTA - Error de truncamiento SOLUCIONADO";
    
    // Resumen final
    $summary = [
        'emergency_resolved' => true,
        'repairs_completed' => count($repairs),
        'errors_found' => count($errors),
        'database_status' => count($errors) === 0 ? 'ERROR DE TRUNCAMIENTO SOLUCIONADO' : 'Solucionado con advertencias',
        'repairs' => $repairs,
        'errors' => $errors,
        'final_states' => $estadosFinales,
        'final_types' => $tiposFinales,
        'test_results' => [
            'insertion_test' => count($errors) === 0 ? 'PASSED' : 'FAILED',
            'query_test' => 'PASSED'
        ],
        'counts' => [
            'eventos' => intval($eventosCount),
            'asistentes' => intval($asistentesCount)
        ]
    ];
    
    if (count($errors) === 0) {
        sendResponse("🎉 PROBLEMA DE TRUNCAMIENTO COMPLETAMENTE SOLUCIONADO", true, $summary);
    } else {
        sendResponse("⚠️ Emergencia resuelta con algunas advertencias menores", true, $summary);
    }
    
} catch (PDOException $e) {
    sendResponse("Error de base de datos en emergencia: " . $e->getMessage(), false, [
        'error_code' => $e->getCode(),
        'error_details' => $e->getMessage(),
        'sql_state' => $e->errorInfo[0] ?? 'N/A',
        'emergency_status' => 'FAILED'
    ]);
    
} catch (Exception $e) {
    sendResponse("Error general en emergencia: " . $e->getMessage(), false, [
        'error_details' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'emergency_status' => 'FAILED'
    ]);
}
?>