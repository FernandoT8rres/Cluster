<?php
/**
 * Script para verificar y corregir la estructura de la tabla boletines
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$config = [
    'host' => '127.0.0.1',
    'username' => 'u695712029_claut_fer', 
    'password' => 'CLAUT@admin_fernando!7',
    'database' => 'u695712029_claut_intranet',
    'charset' => 'utf8mb4'
];

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'actions' => []
];

try {
    // Conectar a la base de datos
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // 1. Verificar si la tabla existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'boletines'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        $response['actions'][] = [
            'action' => 'Verificar tabla existente',
            'status' => 'success',
            'message' => 'La tabla boletines existe'
        ];
        
        // 2. Obtener estructura actual de la tabla
        $stmt = $pdo->query("SHOW COLUMNS FROM boletines");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'Field');
        
        $response['current_columns'] = $columnNames;
        
        // 3. Verificar columnas faltantes
        $requiredColumns = [
            'fecha_creacion' => "datetime DEFAULT CURRENT_TIMESTAMP",
            'fecha_publicacion' => "datetime DEFAULT NULL",
            'archivo_adjunto' => "varchar(255) DEFAULT NULL",
            'visualizaciones' => "int(11) DEFAULT 0",
            'autor_id' => "int(11) DEFAULT NULL"
        ];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $columnNames)) {
                $missingColumns[$column] = $definition;
            }
        }
        
        // 4. Agregar columnas faltantes
        if (!empty($missingColumns)) {
            foreach ($missingColumns as $column => $definition) {
                try {
                    $sql = "ALTER TABLE boletines ADD COLUMN `$column` $definition";
                    $pdo->exec($sql);
                    $response['actions'][] = [
                        'action' => "Agregar columna $column",
                        'status' => 'success',
                        'message' => "Columna $column agregada exitosamente"
                    ];
                } catch (Exception $e) {
                    $response['actions'][] = [
                        'action' => "Agregar columna $column",
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                }
            }
        } else {
            $response['actions'][] = [
                'action' => 'Verificar columnas',
                'status' => 'success',
                'message' => 'Todas las columnas requeridas existen'
            ];
        }
        
        // 5. Verificar y actualizar el tipo de la columna estado si es necesario
        $estadoColumn = null;
        foreach ($columns as $col) {
            if ($col['Field'] === 'estado') {
                $estadoColumn = $col;
                break;
            }
        }
        
        if ($estadoColumn && strpos($estadoColumn['Type'], 'enum') === false) {
            try {
                $sql = "ALTER TABLE boletines MODIFY COLUMN `estado` enum('borrador','publicado','archivado') DEFAULT 'borrador'";
                $pdo->exec($sql);
                $response['actions'][] = [
                    'action' => 'Actualizar columna estado',
                    'status' => 'success',
                    'message' => 'Columna estado actualizada a ENUM'
                ];
            } catch (Exception $e) {
                $response['actions'][] = [
                    'action' => 'Actualizar columna estado',
                    'status' => 'warning',
                    'message' => 'No se pudo actualizar columna estado: ' . $e->getMessage()
                ];
            }
        }
        
    } else {
        // Crear la tabla desde cero con la estructura correcta
        $createTableSQL = "
        CREATE TABLE `boletines` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `titulo` varchar(255) NOT NULL,
            `contenido` text NOT NULL,
            `estado` enum('borrador','publicado','archivado') DEFAULT 'borrador',
            `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
            `fecha_publicacion` datetime DEFAULT NULL,
            `archivo_adjunto` varchar(255) DEFAULT NULL,
            `visualizaciones` int(11) DEFAULT 0,
            `autor_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_estado` (`estado`),
            KEY `idx_fecha_publicacion` (`fecha_publicacion`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createTableSQL);
        
        $response['actions'][] = [
            'action' => 'Crear tabla boletines',
            'status' => 'success',
            'message' => 'Tabla boletines creada exitosamente con todas las columnas'
        ];
    }
    
    // 6. Mostrar estructura final de la tabla
    $stmt = $pdo->query("DESCRIBE boletines");
    $finalStructure = $stmt->fetchAll();
    $response['final_structure'] = $finalStructure;
    
    // 7. Contar registros existentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM boletines");
    $count = $stmt->fetch();
    $response['total_records'] = $count['total'];
    
    $response['success'] = true;
    $response['summary'] = 'Tabla boletines verificada y actualizada correctamente';
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['error'] = 'Error de base de datos: ' . $e->getMessage();
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = 'Error general: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
