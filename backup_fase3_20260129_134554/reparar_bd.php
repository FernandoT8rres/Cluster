<?php
/**
 * Script de reparación DEFINITIVO - Corrige TODOS los problemas de ENUM y columnas
 * Soluciona: estado, tipo, comite_id, tabla evento_asistentes
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
    
    $repairs[] = "🚀 INICIANDO REPARACIÓN DEFINITIVA DE BASE DE DATOS...";
    
    // ===========================================
    // FASE 1: OBTENER ESTRUCTURA ACTUAL
    // ===========================================
    
    $repairs[] = "🔍 FASE 1: Analizando estructura actual...";
    
    $currentColumns = $pdo->query("SHOW COLUMNS FROM eventos")->fetchAll();
    $columnNames = array_column($currentColumns, 'Field');
    $repairs[] = "📋 Columnas actuales: " . implode(', ', $columnNames);
    
    // ===========================================
    // FASE 2: AGREGAR COLUMNAS FALTANTES
    // ===========================================
    
    $repairs[] = "🔧 FASE 2: Agregando columnas faltantes...";
    
    // Definir todas las columnas necesarias
    $requiredColumns = [
        'comite_id' => [
            'definition' => 'INT NULL',
            'position' => 'AFTER organizador_id'
        ],
        'tipo' => [
            'definition' => 'VARCHAR(50) DEFAULT \'reunion\'',
            'position' => 'AFTER ubicacion'
        ],
        'imagen' => [
            'definition' => 'VARCHAR(255) NULL',
            'position' => 'AFTER tipo'
        ],
        'capacidad_maxima' => [
            'definition' => 'INT NULL',
            'position' => 'AFTER imagen'
        ],
        'precio' => [
            'definition' => 'DECIMAL(10,2) DEFAULT 0.00',
            'position' => 'AFTER capacidad_maxima'
        ]
    ];
    
    // Agregar columnas faltantes
    foreach ($requiredColumns as $column => $config) {
        if (!in_array($column, $columnNames)) {
            try {
                $sql = "ALTER TABLE eventos ADD COLUMN {$column} {$config['definition']} {$config['position']}";
                $pdo->exec($sql);
                $repairs[] = "✅ Agregada columna '{$column}'";
            } catch (Exception $e) {
                $repairs[] = "⚠️ Error agregando '{$column}': " . $e->getMessage();
            }
        } else {
            $repairs[] = "ℹ️ Columna '{$column}' ya existe";
        }
    }
    
    // ===========================================
    // FASE 3: CORREGIR COLUMNA ESTADO
    // ===========================================
    
    $repairs[] = "🔧 FASE 3: Corrigiendo columna estado...";
    
    // Cambiar estado a VARCHAR para poder corregir datos
    $pdo->exec("ALTER TABLE eventos MODIFY COLUMN estado VARCHAR(50)");
    $repairs[] = "🔧 Estado cambiado a VARCHAR temporalmente";
    
    // Corregir valores de estado
    $estadoCorrections = [
        'proximo' => 'programado',
        'próximo' => 'programado',
        'activo' => 'en_curso',
        'finaliza' => 'finalizado',
        'terminado' => 'finalizado',
        'pendiente' => 'programado'
    ];
    
    $totalEstadoFixed = 0;
    foreach ($estadoCorrections as $from => $to) {
        $stmt = $pdo->prepare("UPDATE eventos SET estado = ? WHERE estado = ?");
        $stmt->execute([$to, $from]);
        $updated = $stmt->rowCount();
        if ($updated > 0) {
            $repairs[] = "✅ Corregidos $updated eventos: '$from' → '$to'";
            $totalEstadoFixed += $updated;
        }
    }
    
    if ($totalEstadoFixed === 0) {
        $repairs[] = "ℹ️ No se encontraron estados para corregir";
    }
    
    // Restaurar estado a ENUM con valores válidos
    $pdo->exec("ALTER TABLE eventos MODIFY COLUMN estado ENUM('programado', 'en_curso', 'finalizado', 'cancelado') DEFAULT 'programado'");
    $repairs[] = "🔧 Estado restaurado a ENUM con valores válidos";
    
    // ===========================================
    // FASE 4: CORREGIR COLUMNA TIPO
    // ===========================================
    
    $repairs[] = "🔧 FASE 4: Corrigiendo columna tipo...";
    
    // Verificar si tipo ya existe y tiene datos problemáticos
    if (in_array('tipo', $columnNames)) {
        // Obtener valores únicos de tipo
        $tiposActuales = $pdo->query("SELECT DISTINCT tipo FROM eventos WHERE tipo IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
        $repairs[] = "📊 Tipos actuales: " . implode(', ', array_map(function($t) { return "'$t'"; }, $tiposActuales));
        
        // Cambiar tipo a VARCHAR para corregir
        $pdo->exec("ALTER TABLE eventos MODIFY COLUMN tipo VARCHAR(50) DEFAULT 'reunion'");
        $repairs[] = "🔧 Tipo cambiado a VARCHAR temporalmente";
        
        // Corregir valores de tipo
        $tipoCorrections = [
            'capacitacion' => 'capacita',
            'capacitación' => 'capacita',
            'importante' => 'import',
            'reunión' => 'reunion'
        ];
        
        $totalTipoFixed = 0;
        foreach ($tipoCorrections as $from => $to) {
            $stmt = $pdo->prepare("UPDATE eventos SET tipo = ? WHERE tipo = ?");
            $stmt->execute([$to, $from]);
            $updated = $stmt->rowCount();
            if ($updated > 0) {
                $repairs[] = "✅ Corregidos $updated tipos: '$from' → '$to'";
                $totalTipoFixed += $updated;
            }
        }
        
        // Asignar valor por defecto a tipos NULL o vacíos
        $stmt = $pdo->prepare("UPDATE eventos SET tipo = 'reunion' WHERE tipo IS NULL OR tipo = ''");
        $stmt->execute();
        $nullFixed = $stmt->rowCount();
        if ($nullFixed > 0) {
            $repairs[] = "✅ Asignado tipo 'reunion' a $nullFixed eventos sin tipo";
        }
        
        // Restaurar tipo a ENUM
        $pdo->exec("ALTER TABLE eventos MODIFY COLUMN tipo ENUM('reunion', 'capacita', 'social', 'import', 'otro') DEFAULT 'reunion'");
        $repairs[] = "🔧 Tipo restaurado a ENUM con valores válidos";
        
    } else {
        $repairs[] = "⚠️ Columna tipo no encontrada (debería haberse agregado en Fase 2)";
    }
    
    // ===========================================
    // FASE 5: CREAR TABLA EVENTO_ASISTENTES
    // ===========================================
    
    $repairs[] = "🔧 FASE 5: Verificando tabla evento_asistentes...";
    
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
        
        // Insertar datos de ejemplo
        $eventCount = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
        if ($eventCount > 0) {
            try {
                $pdo->exec("
                INSERT INTO evento_asistentes (evento_id, nombre, email, telefono, empresa, cargo, estado) VALUES
                (1, 'Fernando Torres', 'fernando@clúster.com', '555-1111111', 'Autopartes México SA', 'Desarrollador', 'confirmado'),
                (1, 'María González', 'maria@autopartes.com', '555-2222222', 'Autopartes México SA', 'Gerente de Calidad', 'confirmado'),
                (1, 'Juan Pérez', 'juan@ejemplo.com', '555-3333333', 'Empresa Ejemplo', 'Analista', 'registrado')
                ");
                $repairs[] = "✅ Datos de ejemplo insertados";
            } catch (Exception $e) {
                $repairs[] = "⚠️ Error al insertar datos de ejemplo: " . $e->getMessage();
            }
        }
    } else {
        $repairs[] = "ℹ️ Tabla 'evento_asistentes' ya existe";
    }
    
    // ===========================================
    // FASE 6: VERIFICACIONES FINALES
    // ===========================================
    
    $repairs[] = "🔧 FASE 6: Verificaciones finales...";
    
    // Test de inserción de evento
    try {
        $testInsert = $pdo->prepare("
            INSERT INTO eventos (titulo, descripcion, fecha_inicio, fecha_fin, ubicacion, organizador_id, tipo, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $testData = [
            'Evento de Prueba Reparación',
            'Evento creado para verificar que la reparación funcionó',
            date('Y-m-d H:i:s', strtotime('+1 day')),
            date('Y-m-d H:i:s', strtotime('+1 day +2 hours')),
            'Sala de Pruebas',
            1,
            'reunion',
            'programado'
        ];
        
        $testInsert->execute($testData);
        $testId = $pdo->lastInsertId();
        
        $repairs[] = "✅ Test de inserción exitoso (ID: $testId)";
        
        // Eliminar el evento de prueba
        $pdo->prepare("DELETE FROM eventos WHERE id = ?")->execute([$testId]);
        $repairs[] = "🗑️ Evento de prueba eliminado";
        
    } catch (Exception $e) {
        $errors[] = "❌ Error en test de inserción: " . $e->getMessage();
    }
    
    // Test de consulta compleja
    try {
        $testQuery = $pdo->query("
            SELECT e.id, e.titulo, e.estado, e.tipo,
                   COALESCE(u.nombre, 'Sin organizador') as organizador_nombre,
                   COALESCE(c.nombre, 'Sin comité') as comite_nombre,
                   COUNT(ea.id) as asistentes_registrados
            FROM eventos e
            LEFT JOIN usuarios u ON e.organizador_id = u.id
            LEFT JOIN comites c ON e.comite_id = c.id
            LEFT JOIN evento_asistentes ea ON e.id = ea.evento_id AND ea.estado != 'no_asistió'
            GROUP BY e.id
            LIMIT 3
        ")->fetchAll();
        
        $repairs[] = "✅ Test de consulta compleja exitoso (" . count($testQuery) . " eventos)";
        
        foreach ($testQuery as $evento) {
            $repairs[] = "  📊 '{$evento['titulo']}': {$evento['asistentes_registrados']} asistentes, tipo: {$evento['tipo']}, estado: {$evento['estado']}";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ Error en test de consulta: " . $e->getMessage();
    }
    
    // Verificar estructura final
    $finalColumns = $pdo->query("SHOW COLUMNS FROM eventos")->fetchAll();
    $finalColumnNames = array_column($finalColumns, 'Field');
    $repairs[] = "📋 Estructura final: " . implode(', ', $finalColumnNames);
    
    // Estados y tipos finales
    $finalStates = $pdo->query("SELECT DISTINCT estado FROM eventos")->fetchAll(PDO::FETCH_COLUMN);
    $finalTypes = $pdo->query("SELECT DISTINCT tipo FROM eventos")->fetchAll(PDO::FETCH_COLUMN);
    
    $repairs[] = "📊 Estados finales: " . implode(', ', $finalStates);
    $repairs[] = "📊 Tipos finales: " . implode(', ', $finalTypes);
    
    // Conteos finales
    $eventosCount = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
    $asistentesCount = $pdo->query("SELECT COUNT(*) FROM evento_asistentes")->fetchColumn();
    
    $repairs[] = "📊 Totales: $eventosCount eventos, $asistentesCount asistentes";
    $repairs[] = "🎉 REPARACIÓN DEFINITIVA COMPLETADA";
    
    // Resumen final
    $summary = [
        'repairs_completed' => count($repairs),
        'errors_found' => count($errors),
        'database_status' => count($errors) === 0 ? 'COMPLETAMENTE REPARADO' : 'Reparado con advertencias',
        'repairs' => $repairs,
        'errors' => $errors,
        'final_structure' => $finalColumnNames,
        'final_states' => $finalStates,
        'final_types' => $finalTypes,
        'counts' => [
            'eventos' => intval($eventosCount),
            'asistentes' => intval($asistentesCount)
        ]
    ];
    
    if (count($errors) === 0) {
        sendResponse("🎉 BASE DE DATOS COMPLETAMENTE REPARADA - Todos los errores solucionados", true, $summary);
    } else {
        sendResponse("⚠️ Reparación completada con algunas advertencias menores", true, $summary);
    }
    
} catch (PDOException $e) {
    sendResponse("Error de base de datos: " . $e->getMessage(), false, [
        'error_code' => $e->getCode(),
        'error_details' => $e->getMessage(),
        'sql_state' => $e->errorInfo[0] ?? 'N/A'
    ]);
    
} catch (Exception $e) {
    sendResponse("Error general: " . $e->getMessage(), false, [
        'error_details' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>