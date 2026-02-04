<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Generar código PHP para mapeo de campos basado en la estructura real de la BD

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

    echo "<h2>🔧 Generador de Mapeo de Campos</h2>";
    
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

    $bestMapping = [];
    $bestTable = null;
    $bestScore = 0;

    // Analizar cada tabla para encontrar la mejor para perfiles
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $mapping = [];
            $score = 0;
            
            foreach ($columns as $column) {
                $fieldName = strtolower($column['Field']);
                
                // Verificar si este campo corresponde a alguno requerido
                foreach ($requiredFields as $profileField => $possibleNames) {
                    if (in_array($fieldName, $possibleNames)) {
                        $mapping[$profileField] = $column['Field'];
                        $score++;
                        break;
                    }
                }
            }
            
            // Si esta tabla tiene mejor score, usarla
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTable = $table;
                $bestMapping = $mapping;
            }
            
        } catch (Exception $e) {
            continue;
        }
    }

    if ($bestTable && $bestScore > 0) {
        echo "<h3>✅ Mejor tabla encontrada: <strong>$bestTable</strong></h3>";
        echo "<p>Campos mapeados: $bestScore de " . count($requiredFields) . "</p>";
        
        // Generar código PHP para el mapeo
        echo "<h4>📄 Código PHP Generado:</h4>";
        
        echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;'>";
        echo "<h5>1. Constantes de mapeo para tu API:</h5>";
        echo "<pre style='background-color: #ffffff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>";
        echo "<?php\n";
        echo "// Tabla principal para perfiles de usuario\n";
        echo "const USER_PROFILE_TABLE = '$bestTable';\n\n";
        
        echo "// Mapeo de campos del frontend a la base de datos\n";
        echo "const FIELD_MAPPING = [\n";
        foreach ($requiredFields as $profileField => $possibleNames) {
            if (isset($bestMapping[$profileField])) {
                echo "    '$profileField' => '{$bestMapping[$profileField]}',\n";
            } else {
                echo "    // '$profileField' => 'campo_no_encontrado', // ⚠️ FALTA\n";
            }
        }
        echo "];\n";
        echo "?>";
        echo "</pre>";
        
        echo "<h5>2. Función para obtener perfil:</h5>";
        echo "<pre style='background-color: #ffffff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>";
        echo "<?php\n";
        echo "function getUserProfile(\$pdo, \$userId) {\n";
        echo "    \$sql = \"SELECT * FROM " . $bestTable . " WHERE ";
        
        // Determinar campo ID
        if (isset($bestMapping['id'])) {
            echo "{$bestMapping['id']} = ? LIMIT 1\";\n";
        } else {
            echo "id = ? LIMIT 1\"; // ⚠️ Ajustar campo ID\n";
        }
        
        echo "    \$stmt = \$pdo->prepare(\$sql);\n";
        echo "    \$stmt->execute([\$userId]);\n";
        echo "    \$userData = \$stmt->fetch(PDO::FETCH_ASSOC);\n";
        echo "    \n";
        echo "    if (!\$userData) {\n";
        echo "        return null;\n";
        echo "    }\n";
        echo "    \n";
        echo "    // Mapear campos a estructura estándar\n";
        echo "    return [\n";
        
        foreach ($requiredFields as $profileField => $possibleNames) {
            if (isset($bestMapping[$profileField])) {
                echo "        '$profileField' => \$userData['{$bestMapping[$profileField]}'] ?? '',\n";
            } else {
                echo "        '$profileField' => '', // ⚠️ Campo no disponible\n";
            }
        }
        
        echo "    ];\n";
        echo "}\n";
        echo "?>";
        echo "</pre>";
        
        echo "<h5>3. Función para actualizar perfil:</h5>";
        echo "<pre style='background-color: #ffffff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>";
        echo "<?php\n";
        echo "function updateUserProfile(\$pdo, \$userId, \$profileData) {\n";
        echo "    \$updateFields = [];\n";
        echo "    \$values = [];\n";
        echo "    \n";
        echo "    // Mapear solo campos editables (excluyendo nombre y email)\n";
        echo "    \$editableFields = [\n";
        
        foreach ($requiredFields as $profileField => $possibleNames) {
            if (isset($bestMapping[$profileField]) && 
                !in_array($profileField, ['firstName', 'email', 'id'])) {
                echo "        '$profileField' => '{$bestMapping[$profileField]}',\n";
            }
        }
        
        echo "    ];\n";
        echo "    \n";
        echo "    foreach (\$editableFields as \$frontendField => \$dbField) {\n";
        echo "        if (isset(\$profileData[\$frontendField])) {\n";
        echo "            \$updateFields[] = \"\$dbField = ?\";\n";
        echo "            \$values[] = \$profileData[\$frontendField];\n";
        echo "        }\n";
        echo "    }\n";
        echo "    \n";
        echo "    if (empty(\$updateFields)) {\n";
        echo "        return false;\n";
        echo "    }\n";
        echo "    \n";
        echo "    \$values[] = \$userId;\n";
        echo "    \$sql = \"UPDATE " . $bestTable . " SET \" . implode(', ', \$updateFields) . \" WHERE ";
        
        if (isset($bestMapping['id'])) {
            echo "{$bestMapping['id']} = ?\";\n";
        } else {
            echo "id = ?\"; // ⚠️ Ajustar campo ID\n";
        }
        
        echo "    \n";
        echo "    \$stmt = \$pdo->prepare(\$sql);\n";
        echo "    return \$stmt->execute(\$values);\n";
        echo "}\n";
        echo "?>";
        echo "</pre>";
        echo "</div>";
        
        // Mostrar campos faltantes
        $missingFields = [];
        foreach ($requiredFields as $profileField => $possibleNames) {
            if (!isset($bestMapping[$profileField])) {
                $missingFields[] = $profileField;
            }
        }
        
        if (!empty($missingFields)) {
            echo "<h4>⚠️ Campos Faltantes</h4>";
            echo "<p>Los siguientes campos no se encontraron en tu tabla <code>$bestTable</code>:</p>";
            echo "<ul>";
            foreach ($missingFields as $field) {
                $suggestions = implode(', ', $requiredFields[$field]);
                echo "<li><strong>$field</strong> - Podrías agregar alguno de estos campos: <code>$suggestions</code></li>";
            }
            echo "</ul>";
            
            echo "<h5>SQL para agregar campos faltantes:</h5>";
            echo "<pre style='background-color: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; overflow-x: auto;'>";
            foreach ($missingFields as $field) {
                $firstSuggestion = $requiredFields[$field][0];
                $dataType = match($field) {
                    'bio', 'address' => 'TEXT',
                    'birthDate', 'joinDate' => 'DATE',
                    'lastActivity' => 'TIMESTAMP',
                    'zipCode', 'phone', 'emergencyPhone' => 'VARCHAR(20)',
                    'email' => 'VARCHAR(255)',
                    default => 'VARCHAR(100)'
                };
                
                echo "ALTER TABLE $bestTable ADD COLUMN $firstSuggestion $dataType NULL;\n";
            }
            echo "</pre>";
        } else {
            echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb;'>";
            echo "<h4>🎉 ¡Perfecto!</h4>";
            echo "<p>Tu tabla <code>$bestTable</code> tiene todos los campos necesarios para el perfil de usuario.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background-color: #f8d7da; padding: 15px; border-radius: 8px; border: 1px solid #f5c6cb;'>";
        echo "<h3>❌ No se encontró tabla adecuada</h3>";
        echo "<p>No se encontraron tablas con campos de usuario. Necesitarás crear una tabla específica para perfiles.</p>";
        
        echo "<h4>SQL para crear tabla de perfiles:</h4>";
        echo "<pre style='background-color: #ffffff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>";
        echo "CREATE TABLE usuarios_perfil (\n";
        echo "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
        echo "    user_id INT NOT NULL UNIQUE,\n";
        echo "    nombre VARCHAR(100),\n";
        echo "    apellidos VARCHAR(100),\n";
        echo "    email VARCHAR(255),\n";
        echo "    telefono VARCHAR(20),\n";
        echo "    fecha_nacimiento DATE,\n";
        echo "    departamento VARCHAR(100),\n";
        echo "    puesto VARCHAR(100),\n";
        echo "    biografia TEXT,\n";
        echo "    direccion TEXT,\n";
        echo "    ciudad VARCHAR(100),\n";
        echo "    estado VARCHAR(100),\n";
        echo "    codigo_postal VARCHAR(20),\n";
        echo "    pais VARCHAR(100) DEFAULT 'México',\n";
        echo "    telefono_emergencia VARCHAR(20),\n";
        echo "    contacto_emergencia VARCHAR(255),\n";
        echo "    avatar VARCHAR(500) DEFAULT './assets/img/team-2.jpg',\n";
        echo "    fecha_ingreso DATE,\n";
        echo "    ultima_actividad TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        echo "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        echo "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n";
        echo ");\n";
        echo "</pre>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<h3>❌ Error de conexión:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    pre { font-size: 12px; line-height: 1.4; }
    h3, h4, h5 { color: #333; }
</style>