<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Forzar conexión MySQL remota
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

echo "<h2>🔍 Explorando Base de Datos Real</h2>";
echo "<p><strong>Base de datos:</strong> u695712029_claut_intranet</p>";
echo "<p><strong>Host:</strong> 127.0.0.1</p>";

try {
    // Obtener todas las tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>📋 Tablas encontradas (" . count($tables) . "):</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    
    // Buscar tablas que puedan contener usuarios
    $userTables = [];
    foreach ($tables as $table) {
        if (stripos($table, 'user') !== false || 
            stripos($table, 'usuario') !== false || 
            stripos($table, 'empleado') !== false ||
            stripos($table, 'trabajador') !== false ||
            stripos($table, 'personal') !== false ||
            stripos($table, 'admin') !== false ||
            stripos($table, 'auth') !== false ||
            stripos($table, 'login') !== false ||
            stripos($table, 'member') !== false ||
            stripos($table, 'cliente') !== false) {
            $userTables[] = $table;
        }
    }
    
    echo "<h3>👥 Posibles tablas de usuarios:</h3>";
    if (empty($userTables)) {
        echo "<p><strong>❌ No se encontraron tablas evidentes de usuarios</strong></p>";
    } else {
        foreach ($userTables as $table) {
            echo "<h4>📊 Tabla: $table</h4>";
            
            // Estructura
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin-bottom: 15px;'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<p><strong>Registros:</strong> $count</p>";
            
            // Mostrar algunos datos de muestra (sin datos sensibles)
            if ($count > 0) {
                $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
                $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($samples)) {
                    echo "<h5>Datos de muestra:</h5>";
                    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 15px;'>";
                    
                    // Headers
                    echo "<tr>";
                    foreach (array_keys($samples[0]) as $key) {
                        echo "<th>" . htmlspecialchars($key) . "</th>";
                    }
                    echo "</tr>";
                    
                    // Data (mask sensitive fields)
                    foreach ($samples as $row) {
                        echo "<tr>";
                        foreach ($row as $key => $value) {
                            // Mask potentially sensitive data
                            if (stripos($key, 'password') !== false || 
                                stripos($key, 'pass') !== false ||
                                stripos($key, 'token') !== false) {
                                $displayValue = '***HIDDEN***';
                            } else {
                                $displayValue = $value === null ? 'NULL' : (string)$value;
                                if (strlen($displayValue) > 30) {
                                    $displayValue = substr($displayValue, 0, 27) . '...';
                                }
                            }
                            echo "<td>" . htmlspecialchars($displayValue) . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
            echo "<hr>";
        }
    }
    
    // Revisar otras tablas importantes
    echo "<h3>📊 Otras tablas importantes:</h3>";
    $importantTables = ['empresas_convenio', 'eventos', 'comites', 'descuentos'];
    
    foreach ($importantTables as $tableName) {
        if (in_array($tableName, $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $tableName");
            $count = $stmt->fetch()['count'];
            echo "<p>✅ <strong>$tableName:</strong> $count registros</p>";
        } else {
            echo "<p>❌ <strong>$tableName:</strong> No existe</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>