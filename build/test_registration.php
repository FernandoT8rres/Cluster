<?php
/**
 * Script de prueba para el registro de usuarios
 * Este script simula el registro para probar la funcionalidad
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();

    echo "✅ Conexión exitosa\n";
    echo "Tipo de BD: " . ($db->isUsingSQLite() ? 'SQLite' : 'MySQL') . "\n";

    // Probar la estructura de la tabla
    if ($db->isUsingSQLite()) {
        $stmt = $conn->query("PRAGMA table_info(usuarios_perfil)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n📋 Columnas en tabla usuarios_perfil:\n";
        foreach ($columns as $col) {
            echo "- {$col['name']} ({$col['type']})\n";
        }
    } else {
        $stmt = $conn->query("DESCRIBE usuarios_perfil");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n📋 Columnas en tabla usuarios_perfil:\n";
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})\n";
        }
    }

    // Datos de prueba
    $testData = [
        'nombre' => 'Test',
        'apellidos' => 'Usuario',
        'email' => 'test@example.com',
        'password' => 'TestPass123',
        'rol' => 'empleado',
        'telefono' => '555-1234',
        'fecha_nacimiento' => '1990-01-01',
        'nombre_empresa' => 'Empresa Test',
        'biografia' => 'Usuario de prueba',
        'direccion' => 'Dirección de prueba',
        'ciudad' => 'Ciudad de México',
        'estado' => 'CDMX',
        'codigo_postal' => '12345',
        'pais' => 'México',
        'telefono_emergencia' => '555-5678',
        'contacto_emergencia' => 'Contacto de emergencia'
    ];

    echo "\n🧪 Probando inserción de datos...\n";

    // Preparar consulta de inserción
    $insertQuery = "INSERT INTO usuarios_perfil
                    (nombre, apellido, email, password, telefono, fecha_nacimiento,
                     nombre_empresa, rol, biografia, direccion, ciudad, estado_geografico, codigo_postal,
                     pais, telefono_emergencia, contacto_emergencia, fecha_registro)
                    VALUES (:nombre, :apellido, :email, :password, :telefono, :fecha_nacimiento,
                            :nombre_empresa, :rol, :biografia, :direccion, :ciudad, :estado_geografico, :codigo_postal,
                            :pais, :telefono_emergencia, :contacto_emergencia, CURRENT_TIMESTAMP)";

    $insertStmt = $conn->prepare($insertQuery);

    // Verificar si el email ya existe
    $checkStmt = $conn->prepare("SELECT id FROM usuarios_perfil WHERE email = :email");
    $checkStmt->bindParam(':email', $testData['email']);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        echo "❌ El email de prueba ya existe, eliminando...\n";
        $deleteStmt = $conn->prepare("DELETE FROM usuarios_perfil WHERE email = :email");
        $deleteStmt->bindParam(':email', $testData['email']);
        $deleteStmt->execute();
    }

    // Bind parameters
    $hashedPassword = password_hash($testData['password'], PASSWORD_DEFAULT);
    $insertStmt->bindParam(':nombre', $testData['nombre']);
    $insertStmt->bindParam(':apellido', $testData['apellidos']);
    $insertStmt->bindParam(':email', $testData['email']);
    $insertStmt->bindParam(':password', $hashedPassword);
    $insertStmt->bindParam(':telefono', $testData['telefono']);
    $insertStmt->bindParam(':fecha_nacimiento', $testData['fecha_nacimiento']);
    $insertStmt->bindParam(':nombre_empresa', $testData['nombre_empresa']);
    $insertStmt->bindParam(':rol', $testData['rol']);
    $insertStmt->bindParam(':biografia', $testData['biografia']);
    $insertStmt->bindParam(':direccion', $testData['direccion']);
    $insertStmt->bindParam(':ciudad', $testData['ciudad']);
    $insertStmt->bindParam(':estado_geografico', $testData['estado']);
    $insertStmt->bindParam(':codigo_postal', $testData['codigo_postal']);
    $insertStmt->bindParam(':pais', $testData['pais']);
    $insertStmt->bindParam(':telefono_emergencia', $testData['telefono_emergencia']);
    $insertStmt->bindParam(':contacto_emergencia', $testData['contacto_emergencia']);

    if ($insertStmt->execute()) {
        $userId = $conn->lastInsertId();
        echo "✅ Usuario registrado exitosamente con ID: $userId\n";

        // Verificar el registro
        $selectStmt = $conn->prepare("SELECT * FROM usuarios_perfil WHERE id = :id");
        $selectStmt->bindParam(':id', $userId);
        $selectStmt->execute();

        $user = $selectStmt->fetch(PDO::FETCH_ASSOC);
        echo "\n👤 Datos del usuario registrado:\n";
        foreach ($user as $key => $value) {
            if ($key !== 'password') {
                echo "- $key: " . ($value ?? 'NULL') . "\n";
            }
        }

        // Limpiar datos de prueba
        $deleteStmt = $conn->prepare("DELETE FROM usuarios_perfil WHERE id = :id");
        $deleteStmt->bindParam(':id', $userId);
        $deleteStmt->execute();
        echo "\n🧹 Datos de prueba eliminados\n";

    } else {
        echo "❌ Error al insertar usuario\n";
        print_r($insertStmt->errorInfo());
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>