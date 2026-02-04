<?php
/**
 * Solucionador específico para crear usuario admin y arreglar credenciales
 */

header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>🔧 Crear Usuario Admin - Clúster</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; background: #f5f5f5; }
.container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
h1 { color: #C7252B; border-bottom: 3px solid #C7252B; padding-bottom: 10px; }
.status { padding: 10px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.btn { padding: 8px 16px; background: #C7252B; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
.btn:hover { background: #8B1538; }
.credentials { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 15px 0; }
.credentials h4 { margin-top: 0; color: #1976D2; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Crear Usuario Admin y Solucionar Login</h1>";
echo "<p><strong>Problema identificado:</strong> No hay usuarios admin válidos en la base de datos</p>";

$fixes = [];
$errors = [];

try {
    // Conectar a la base de datos
    echo "<h2>📊 Paso 1: Conexión a Base de Datos</h2>";
    
    require_once '../assets/conexion/config.php';
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<div class='status success'>✅ Conexión exitosa a la base de datos</div>";
    
    // Verificar usuarios existentes
    echo "<h2>👥 Paso 2: Verificar Usuarios Existentes</h2>";
    
    $stmt = $conn->prepare("SELECT id, nombre, apellido, email, rol, estado FROM usuarios ORDER BY id");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='status info'>👥 Total de usuarios en la BD: " . count($usuarios) . "</div>";
    
    if (count($usuarios) > 0) {
        echo "<h3>Usuarios actuales:</h3>";
        echo "<table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>ID</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Nombre</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Email</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Rol</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Estado</th>";
        echo "</tr>";
        
        $adminCount = 0;
        foreach ($usuarios as $user) {
            $rowColor = $user['rol'] === 'admin' ? '#e8f5e8' : 'white';
            echo "<tr style='background: $rowColor;'>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $user['id'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $user['rol'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $user['estado'] . "</td>";
            echo "</tr>";
            
            if ($user['rol'] === 'admin') {
                $adminCount++;
            }
        }
        echo "</table>";
        
        if ($adminCount === 0) {
            echo "<div class='status warning'>⚠️ No hay usuarios con rol 'admin'</div>";
        } else {
            echo "<div class='status info'>ℹ️ Se encontraron $adminCount usuario(s) admin</div>";
        }
    } else {
        echo "<div class='status warning'>⚠️ No hay usuarios en la base de datos</div>";
    }
    
    // Crear o actualizar usuario admin
    echo "<h2>👑 Paso 3: Crear/Actualizar Usuario Admin</h2>";
    
    // Verificar si ya existe admin@test.com
    $stmt = $conn->prepare("SELECT id, email, rol, estado FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@test.com']);
    $adminExistente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($adminExistente) {
        echo "<div class='status info'>ℹ️ Usuario admin@test.com ya existe</div>";
        
        // Actualizar a admin si no lo es
        if ($adminExistente['rol'] !== 'admin' || $adminExistente['estado'] !== 'activo') {
            $stmt = $conn->prepare("UPDATE usuarios SET rol = 'admin', estado = 'activo' WHERE email = ?");
            if ($stmt->execute(['admin@test.com'])) {
                echo "<div class='status success'>✅ Usuario actualizado a admin activo</div>";
                $fixes[] = 'Usuario admin@test.com actualizado';
            }
        }
        
        // Actualizar contraseña
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
        if ($stmt->execute([$hashedPassword, 'admin@test.com'])) {
            echo "<div class='status success'>✅ Contraseña actualizada</div>";
            $fixes[] = 'Contraseña actualizada para admin@test.com';
        }
        
    } else {
        // Crear nuevo usuario admin
        echo "<div class='status info'>🔄 Creando nuevo usuario admin...</div>";
        
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO usuarios (nombre, apellido, email, password, rol, estado, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([
            'Admin',
            'Sistema',
            'admin@test.com',
            $hashedPassword,
            'admin',
            'activo'
        ])) {
            echo "<div class='status success'>✅ Usuario admin creado exitosamente</div>";
            $fixes[] = 'Usuario admin@test.com creado';
        } else {
            echo "<div class='status error'>❌ Error creando usuario admin</div>";
            $errors[] = 'No se pudo crear usuario admin';
        }
    }
    
    // Crear usuario admin alternativo
    echo "<h3>Creando usuario admin alternativo:</h3>";
    
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute(['admin@clúster.com']);
    
    if (!$stmt->fetch()) {
        $hashedPassword = password_hash('claut2024', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO usuarios (nombre, apellido, email, password, rol, estado, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([
            'Administrador',
            'Clúster',
            'admin@clúster.com',
            $hashedPassword,
            'admin',
            'activo'
        ])) {
            echo "<div class='status success'>✅ Usuario admin@clúster.com creado</div>";
            $fixes[] = 'Usuario admin@clúster.com creado';
        }
    } else {
        echo "<div class='status info'>ℹ️ Usuario admin@clúster.com ya existe</div>";
    }
    
    // Verificar resultado final
    echo "<h2>✅ Paso 4: Verificación Final</h2>";
    
    $stmt = $conn->prepare("SELECT id, nombre, email, rol, estado FROM usuarios WHERE rol = 'admin' AND estado = 'activo'");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($admins) > 0) {
        echo "<div class='status success'>✅ " . count($admins) . " usuario(s) admin activo(s) disponible(s)</div>";
        
        echo "<div class='credentials'>";
        echo "<h4>🔑 Credenciales de Admin Disponibles:</h4>";
        foreach ($admins as $admin) {
            echo "<strong>Email:</strong> " . $admin['email'] . "<br>";
            if ($admin['email'] === 'admin@test.com') {
                echo "<strong>Contraseña:</strong> admin123<br>";
            } elseif ($admin['email'] === 'admin@clúster.com') {
                echo "<strong>Contraseña:</strong> claut2024<br>";
            }
            echo "<em>Rol:</em> " . $admin['rol'] . " | <em>Estado:</em> " . $admin['estado'] . "<br><br>";
        }
        echo "</div>";
        
    } else {
        echo "<div class='status error'>❌ No se encontraron usuarios admin activos</div>";
        $errors[] = 'No hay usuarios admin disponibles';
    }
    
} catch (Exception $e) {
    echo "<div class='status error'>❌ Error: " . $e->getMessage() . "</div>";
    $errors[] = 'Error de conexión: ' . $e->getMessage();
}

// Test de login
echo "<h2>🧪 Paso 5: Test de Login</h2>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h4>Probar Login con Credenciales:</h4>";
echo "<button onclick=\"testLoginCredentials('admin@test.com', 'admin123')\" class='btn'>🧪 Probar admin@test.com</button>";
echo "<button onclick=\"testLoginCredentials('admin@clúster.com', 'claut2024')\" class='btn'>🧪 Probar admin@clúster.com</button>";
echo "<div id='testResult' style='margin-top: 15px;'></div>";
echo "</div>";

echo "<script>
async function testLoginCredentials(email, password) {
    const resultDiv = document.getElementById('testResult');
    resultDiv.innerHTML = '<div style=\"padding: 10px; background: #f0f0f0;\">🔄 Probando login...</div>';
    
    try {
        const response = await fetch('../api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = '<div style=\"padding: 10px; background: #d4edda; color: #155724; border-radius: 4px;\">✅ <strong>Login exitoso!</strong><br>Usuario: ' + data.user.nombre + '<br>Rol: ' + data.user.rol + '<br>Token generado correctamente</div>';
        } else {
            resultDiv.innerHTML = '<div style=\"padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;\">❌ Error: ' + data.message + '</div>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<div style=\"padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;\">❌ Error de conexión: ' + error.message + '</div>';
    }
}
</script>";

// Resumen
echo "<h2>📋 Resumen</h2>";

if (count($fixes) > 0) {
    echo "<div class='status success'>";
    echo "<h3>✅ Correcciones Aplicadas:</h3>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<div class='status info'>";
echo "<h3>🚀 Próximos Pasos:</h3>";
echo "<ol>";
echo "<li>Usa los botones de arriba para probar el login</li>";
echo "<li>Si funciona, ve al formulario de login principal</li>";
echo "<li>Usa las credenciales mostradas arriba</li>";
echo "<li>Deberías poder acceder como administrador</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='../pages/sign-in.html' target='_blank' class='btn'>🔐 Ir al Login Principal</a>";
echo "<button onclick='window.location.reload()' class='btn'>🔄 Ejecutar Nuevamente</button>";
echo "</div>";

echo "</div></body></html>";
?>