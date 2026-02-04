<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Forzar conexión MySQL remota
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=u695712029_claut_intranet;charset=utf8mb4",
        'u695712029_claut_fer',
        'CLAUT@admin_fernando!7',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
        ]
    );

    echo "<h2>🔍 Verificación de Campos para Perfil de Usuario</h2>";
    echo "<p><strong>Base de datos:</strong> u695712029_claut_intranet</p>";

    // Campos que necesitamos para el perfil
    $requiredFields = [
        'id' => ['id', 'user_id', 'usuario_id'],
        'firstName' => ['nombre', 'first_name', 'name', 'nombres'],
        'lastName' => ['apellidos', 'last_name', 'apellido', 'surname'],
        'email' => ['email', 'correo', 'mail'],
        'phone' => ['telefono', 'phone', 'tel', 'celular'],
        'birthDate' => ['fecha_nacimiento', 'birth_date', 'nacimiento', 'fecha_nac'],
        'department' => ['departamento', 'department', 'area', 'division'],
        'position' => ['puesto', 'position', 'cargo', 'job_title'],
        'bio' => ['biografia', 'bio', 'descripcion', 'about'],
        'address' => ['direccion', 'address', 'domicilio'],
        'city' => ['ciudad', 'city', 'localidad'],
        'state' => ['estado', 'state', 'provincia'],
        'zipCode' => ['codigo_postal', 'zip_code', 'cp', 'postal_code'],
        'country' => ['pais', 'country', 'nacionalidad'],
        'emergencyPhone' => ['telefono_emergencia', 'emergency_phone', 'tel_emergencia'],
        'emergencyContact' => ['contacto_emergencia', 'emergency_contact', 'contacto_emerg'],
        'avatar' => ['avatar', 'photo', 'imagen', 'foto', 'picture'],
        'joinDate' => ['fecha_ingreso', 'join_date', 'created_at', 'fecha_registro'],
        'lastActivity' => ['ultima_actividad', 'last_activity', 'updated_at', 'last_login']
    ];

    // Obtener todas las tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h3>📋 Análisis de Tablas (" . count($tables) . " encontradas)</h3>";

    $possibleUserTables = [];
    $fieldMapping = [];

    // Analizar cada tabla
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $tableFields = [];
            $userFieldCount = 0;
            
            foreach ($columns as $column) {
                $fieldName = strtolower($column['Field']);
                $tableFields[] = [
                    'name' => $column['Field'],
                    'type' => $column['Type'],
                    'null' => $column['Null'],
                    'key' => $column['Key'],
                    'default' => $column['Default']
                ];
                
                // Verificar si este campo corresponde a alguno de nuestros campos requeridos
                foreach ($requiredFields as $profileField => $possibleNames) {
                    if (in_array($fieldName, $possibleNames)) {
                        $fieldMapping[$table][$profileField] = $column['Field'];
                        $userFieldCount++;
                        break;
                    }
                }
            }
            
            // Si la tabla tiene varios campos de usuario, es candidata
            if ($userFieldCount >= 3 || 
                stripos($table, 'user') !== false || 
                stripos($table, 'usuario') !== false ||
                stripos($table, 'empleado') !== false ||
                stripos($table, 'personal') !== false) {
                
                $possibleUserTables[$table] = [
                    'fields' => $tableFields,
                    'user_field_count' => $userFieldCount,
                    'total_fields' => count($tableFields)
                ];
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error analizando tabla $table: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h3>👥 Tablas Candidatas para Usuarios</h3>";
    
    if (empty($possibleUserTables)) {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ No se encontraron tablas de usuarios obvias</h4>";
        echo "<p>Revisemos todas las tablas para crear una tabla de perfil...</p>";
        echo "</div>";
        
        // Mostrar todas las tablas para referencia
        echo "<h4>📊 Todas las tablas disponibles:</h4>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li><strong>$table</strong></li>";
        }
        echo "</ul>";
        
    } else {
        foreach ($possibleUserTables as $tableName => $tableInfo) {
            echo "<div class='table-analysis'>";
            echo "<h4>📊 Tabla: <strong>$tableName</strong></h4>";
            echo "<p><strong>Campos de usuario identificados:</strong> {$tableInfo['user_field_count']} de " . count($requiredFields) . "</p>";
            
            // Mostrar estructura de la tabla
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th style='padding: 8px;'>Campo</th>";
            echo "<th style='padding: 8px;'>Tipo</th>";
            echo "<th style='padding: 8px;'>Nulo</th>";
            echo "<th style='padding: 8px;'>Clave</th>";
            echo "<th style='padding: 8px;'>Default</th>";
            echo "<th style='padding: 8px;'>Mapea a</th>";
            echo "</tr>";
            
            foreach ($tableInfo['fields'] as $field) {
                echo "<tr>";
                echo "<td style='padding: 8px;'><strong>" . htmlspecialchars($field['name']) . "</strong></td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($field['type']) . "</td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($field['null']) . "</td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($field['key']) . "</td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($field['default'] ?? '') . "</td>";
                
                // Mostrar a qué campo del perfil mapea
                $mapsTo = '';
                if (isset($fieldMapping[$tableName])) {
                    foreach ($fieldMapping[$tableName] as $profileField => $dbField) {
                        if ($dbField === $field['name']) {
                            $mapsTo = "👤 $profileField";
                            break;
                        }
                    }
                }
                echo "<td style='padding: 8px; color: green;'>$mapsTo</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Contar registros
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $tableName");
                $count = $stmt->fetch()['count'];
                echo "<p><strong>Registros existentes:</strong> $count</p>";
                
                // Mostrar datos de muestra (sin datos sensibles)
                if ($count > 0) {
                    echo "<h5>🔍 Datos de muestra:</h5>";
                    $stmt = $pdo->query("SELECT * FROM $tableName LIMIT 2");
                    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($samples)) {
                        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
                        
                        // Headers
                        echo "<tr style='background-color: #f8f9fa;'>";
                        foreach (array_keys($samples[0]) as $key) {
                            echo "<th style='padding: 6px;'>" . htmlspecialchars($key) . "</th>";
                        }
                        echo "</tr>";
                        
                        // Data (mask sensitive)
                        foreach ($samples as $row) {
                            echo "<tr>";
                            foreach ($row as $key => $value) {
                                // Mask sensitive data
                                if (stripos($key, 'password') !== false || 
                                    stripos($key, 'pass') !== false ||
                                    stripos($key, 'token') !== false) {
                                    $displayValue = '***HIDDEN***';
                                } else {
                                    $displayValue = $value === null ? 'NULL' : (string)$value;
                                    if (strlen($displayValue) > 25) {
                                        $displayValue = substr($displayValue, 0, 22) . '...';
                                    }
                                }
                                echo "<td style='padding: 6px;'>" . htmlspecialchars($displayValue) . "</td>";
                            }
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error contando registros: " . $e->getMessage() . "</p>";
            }
            
            echo "</div><hr>";
        }
    }

    // Mostrar resumen de mapeo
    echo "<h3>🗺️ Resumen de Mapeo de Campos</h3>";
    
    if (!empty($fieldMapping)) {
        foreach ($fieldMapping as $tableName => $mapping) {
            echo "<h4>Tabla: $tableName</h4>";
            echo "<ul>";
            foreach ($mapping as $profileField => $dbField) {
                echo "<li><strong>$profileField</strong> → <code>$dbField</code></li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p>❌ No se encontraron campos mapeables automáticamente.</p>";
    }

    // Recomendaciones
    echo "<h3>💡 Recomendaciones</h3>";
    
    if (!empty($possibleUserTables)) {
        $bestTable = array_keys($possibleUserTables)[0];
        $bestTableInfo = $possibleUserTables[$bestTable];
        
        echo "<div class='recommendation'>";
        echo "<h4>✅ Tabla recomendada: <strong>$bestTable</strong></h4>";
        echo "<p>Esta tabla tiene {$bestTableInfo['user_field_count']} campos que coinciden con los campos de perfil.</p>";
        
        if (isset($fieldMapping[$bestTable])) {
            echo "<p><strong>Campos disponibles para almacenar:</strong></p>";
            echo "<ul>";
            foreach ($fieldMapping[$bestTable] as $profileField => $dbField) {
                echo "<li>$profileField</li>";
            }
            echo "</ul>";
            
            echo "<p><strong>Campos que necesitarías agregar:</strong></p>";
            $missingFields = array_diff(array_keys($requiredFields), array_keys($fieldMapping[$bestTable]));
            if (!empty($missingFields)) {
                echo "<ul>";
                foreach ($missingFields as $missing) {
                    echo "<li style='color: orange;'>$missing</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color: green;'>✅ Todos los campos están disponibles!</p>";
            }
        }
        echo "</div>";
    } else {
        echo "<div class='recommendation'>";
        echo "<h4>🔧 Necesitas crear una tabla de perfil</h4>";
        echo "<p>No se encontraron tablas existentes adecuadas. Recomiendo crear una tabla <code>usuarios_perfil</code> con los campos necesarios.</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<h3>❌ Error de conexión:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .table-analysis { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .recommendation { background-color: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0; }
    .alert-warning { background-color: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7; }
    .error { color: red; }
    table { font-size: 12px; }
    th { background-color: #f8f9fa !important; }
</style>