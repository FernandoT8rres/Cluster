<?php
/**
 * Verificar Usuario Admin - Script de Diagnóstico
 */

require_once '../config/database.php';

echo "<h1>🔍 Verificación de Usuario Administrador</h1>";
echo "<style>body{font-family:Arial;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    $db = new DatabaseWrapper();
    
    // Buscar usuarios admin
    echo "<h2>👤 Usuarios con rol 'admin':</h2>";
    $admins = $db->select("SELECT id, nombre, apellido, email, rol, estado, fecha_registro FROM usuarios WHERE rol = 'admin'");
    
    if (empty($admins)) {
        echo "<span class='error'>❌ No se encontraron usuarios administradores</span><br><br>";
        
        // Crear usuario admin automáticamente
        echo "<h3>🛠️ Creando usuario administrador...</h3>";
        $usuario = new Usuario();
        
        $datosAdmin = [
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@clúster.com',
            'password' => 'admin123',
            'telefono' => '0000000000',
            'rol' => 'admin',
            'estado' => 'activo'
        ];
        
        $adminId = $usuario->crear($datosAdmin);
        
        if ($adminId) {
            echo "<span class='success'>✅ Usuario administrador creado con ID: $adminId</span><br>";
            echo "<span class='info'>📧 Email: admin@clúster.com</span><br>";
            echo "<span class='info'>🔑 Password: admin123</span><br>";
        } else {
            echo "<span class='error'>❌ Error al crear usuario administrador</span><br>";
        }
    } else {
        foreach ($admins as $admin) {
            echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<span class='success'>✅ Admin encontrado:</span><br>";
            echo "<strong>ID:</strong> {$admin['id']}<br>";
            echo "<strong>Nombre:</strong> {$admin['nombre']} {$admin['apellido']}<br>";
            echo "<strong>Email:</strong> {$admin['email']}<br>";
            echo "<strong>Estado:</strong> {$admin['estado']}<br>";
            echo "<strong>Registro:</strong> {$admin['fecha_registro']}<br>";
            echo "</div>";
        }
    }
    
    // Probar login programáticamente
    echo "<h2>🔐 Prueba de Login Programático</h2>";
    $usuario = new Usuario();
    $loginResult = $usuario->login('admin@clúster.com', 'admin123');
    
    if ($loginResult) {
        echo "<span class='success'>✅ Login programático exitoso</span><br>";
        echo "<strong>Datos del usuario:</strong><br>";
        echo "<pre>" . print_r($loginResult, true) . "</pre>";
    } else {
        echo "<span class='error'>❌ Login programático falló</span><br>";
        echo "<span class='info'>Verificando si el email existe...</span><br>";
        
        $usuarioEmail = $usuario->obtenerPorEmail('admin@clúster.com');
        if ($usuarioEmail) {
            echo "<span class='info'>✅ Usuario encontrado por email</span><br>";
            echo "<span class='info'>Estado: {$usuarioEmail['estado']}</span><br>";
            echo "<span class='info'>Rol: {$usuarioEmail['rol']}</span><br>";
            
            if ($usuarioEmail['estado'] !== 'activo') {
                echo "<span class='error'>❌ Usuario no está activo</span><br>";
                // Activar usuario
                $usuario->cambiarEstado($usuarioEmail['id'], 'activo');
                echo "<span class='success'>✅ Usuario activado automáticamente</span><br>";
            }
        } else {
            echo "<span class='error'>❌ Usuario no encontrado por email</span><br>";
        }
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error: " . $e->getMessage() . "</span>";
}

echo "<hr>";
echo "<h3>🔗 Enlaces de Prueba</h3>";
echo "<a href='../pages/sign-in.html'>🔐 Ir al Login</a><br>";
echo "<a href='admin-dashboard.php'>🏠 Ir al Panel de Admin (requiere autenticación)</a><br>";
echo "<a href='diagnostico-rutas.php'>🔍 Diagnóstico de Rutas</a><br>";

echo "<hr>";
echo "<p><small>Verificación ejecutada el " . date('d/m/Y H:i:s') . "</small></p>";
?>