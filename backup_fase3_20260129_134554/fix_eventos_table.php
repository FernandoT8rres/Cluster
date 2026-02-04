<?php
/**
 * Script para crear/reparar la tabla de eventos
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
    $stmt = $pdo->query("SHOW TABLES LIKE 'eventos'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Crear la tabla desde cero
        $createTableSQL = "
        CREATE TABLE `eventos` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `titulo` varchar(255) NOT NULL,
            `descripcion` text NOT NULL,
            `fecha_evento` date NOT NULL,
            `hora_evento` time NOT NULL,
            `ubicacion` varchar(255) NOT NULL,
            `categoria` enum('reunion','capacitacion','social','importante','otro') DEFAULT 'otro',
            `estado` enum('proximo','en_curso','finalizado','cancelado') DEFAULT 'proximo',
            `capacidad_maxima` int(11) DEFAULT NULL,
            `asistentes_registrados` int(11) DEFAULT 0,
            `imagen` varchar(255) DEFAULT NULL,
            `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
            `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            `creado_por` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_fecha_evento` (`fecha_evento`),
            KEY `idx_estado` (`estado`),
            KEY `idx_categoria` (`categoria`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createTableSQL);
        
        $response['actions'][] = [
            'action' => 'Crear tabla eventos',
            'status' => 'success',
            'message' => 'Tabla eventos creada exitosamente'
        ];
        
    } else {
        $response['actions'][] = [
            'action' => 'Verificar tabla existente',
            'status' => 'success',
            'message' => 'La tabla eventos ya existe'
        ];
        
        // Verificar columnas existentes
        $stmt = $pdo->query("SHOW COLUMNS FROM eventos");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'Field');
        
        $response['current_columns'] = $columnNames;
        
        // Verificar columnas requeridas
        $requiredColumns = [
            'fecha_creacion' => "datetime DEFAULT CURRENT_TIMESTAMP",
            'fecha_actualizacion' => "datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
            'capacidad_maxima' => "int(11) DEFAULT NULL",
            'asistentes_registrados' => "int(11) DEFAULT 0",
            'imagen' => "varchar(255) DEFAULT NULL",
            'creado_por' => "int(11) DEFAULT NULL"
        ];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $columnNames)) {
                $missingColumns[$column] = $definition;
            }
        }
        
        // Agregar columnas faltantes
        if (!empty($missingColumns)) {
            foreach ($missingColumns as $column => $definition) {
                try {
                    $sql = "ALTER TABLE eventos ADD COLUMN `$column` $definition";
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
    }
    
    // Crear directorio de uploads si no existe
    $uploadDir = '../uploads/eventos/';
    if (!file_exists($uploadDir)) {
        if (@mkdir($uploadDir, 0755, true)) {
            $response['actions'][] = [
                'action' => 'Crear directorio uploads/eventos',
                'status' => 'success',
                'message' => 'Directorio de uploads creado exitosamente'
            ];
        } else {
            $response['actions'][] = [
                'action' => 'Crear directorio uploads/eventos',
                'status' => 'warning',
                'message' => 'No se pudo crear el directorio de uploads'
            ];
        }
    } else {
        $response['actions'][] = [
            'action' => 'Verificar directorio uploads',
            'status' => 'success',
            'message' => 'Directorio de uploads ya existe'
        ];
    }
    
    // Mostrar estructura final
    $stmt = $pdo->query("DESCRIBE eventos");
    $finalStructure = $stmt->fetchAll();
    $response['final_structure'] = $finalStructure;
    
    // Contar registros existentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos");
    $count = $stmt->fetch();
    $response['total_records'] = $count['total'];
    
    // Insertar eventos de ejemplo si la tabla está vacía
    if ($count['total'] == 0) {
        $ejemplos = [
            [
                'titulo' => 'Reunión Mensual de Equipo',
                'descripcion' => 'Reunión mensual para revisar avances y objetivos del mes.',
                'fecha_evento' => date('Y-m-d', strtotime('+1 week')),
                'hora_evento' => '10:00:00',
                'ubicacion' => 'Sala de Conferencias Principal',
                'categoria' => 'reunion',
                'estado' => 'proximo',
                'capacidad_maxima' => 20
            ],
            [
                'titulo' => 'Capacitación en Seguridad',
                'descripcion' => 'Capacitación sobre las nuevas políticas de seguridad de la empresa.',
                'fecha_evento' => date('Y-m-d', strtotime('+2 weeks')),
                'hora_evento' => '14:00:00',
                'ubicacion' => 'Auditorio',
                'categoria' => 'capacitacion',
                'estado' => 'proximo',
                'capacidad_maxima' => 50
            ],
            [
                'titulo' => 'Celebración de Aniversario',
                'descripcion' => 'Celebración del aniversario de la empresa con todo el personal.',
                'fecha_evento' => date('Y-m-d', strtotime('+1 month')),
                'hora_evento' => '18:00:00',
                'ubicacion' => 'Terraza del Edificio',
                'categoria' => 'social',
                'estado' => 'proximo',
                'capacidad_maxima' => null
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO eventos (titulo, descripcion, fecha_evento, hora_evento, ubicacion, 
                               categoria, estado, capacidad_maxima, fecha_creacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $insertedCount = 0;
        foreach ($ejemplos as $ejemplo) {
            try {
                $stmt->execute([
                    $ejemplo['titulo'],
                    $ejemplo['descripcion'],
                    $ejemplo['fecha_evento'],
                    $ejemplo['hora_evento'],
                    $ejemplo['ubicacion'],
                    $ejemplo['categoria'],
                    $ejemplo['estado'],
                    $ejemplo['capacidad_maxima']
                ]);
                $insertedCount++;
            } catch (Exception $e) {
                // Ignorar errores de inserción de ejemplos
            }
        }
        
        if ($insertedCount > 0) {
            $response['actions'][] = [
                'action' => 'Insertar eventos de ejemplo',
                'status' => 'success',
                'message' => "$insertedCount eventos de ejemplo insertados"
            ];
        }
    }
    
    $response['success'] = true;
    $response['summary'] = 'Tabla de eventos verificada y lista para usar';
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['error'] = 'Error de base de datos: ' . $e->getMessage();
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = 'Error general: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
