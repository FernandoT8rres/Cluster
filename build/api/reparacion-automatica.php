<?php
/**
 * Script de reparación automática para el error de patrón
 * Ejecuta las correcciones necesarias automáticamente
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>🔧 Reparación Automática - Clúster</title></head><body>";
echo "<h1>🔧 Reparación Automática del Error de Patrón</h1>";

$repairs = [];
$success = true;

try {
    // 1. Verificar y corregir usuario admin
    echo "<h2>👑 Verificando Usuario Admin</h2>";
    
    if (file_exists('../assets/conexion/config.php')) {
        require_once '../assets/conexion/config.php';
        
        $db = Database::getInstance();
        $adminUser = $db->selectOne("SELECT * FROM usuarios WHERE rol = 'admin' LIMIT 1");
        
        if (!$adminUser) {
            // Crear usuario admin
            echo "<p>❌ No se encontró usuario admin. Creando...</p>";
            
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nombre, apellido, email, password, rol, estado, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $result = $db->insert($sql, [
                'Admin',
                'Sistema', 
                'admin@test.com',
                $adminPassword,
                'admin',
                'activo'
            ]);
            
            if ($result) {
                echo "<p>✅ Usuario admin creado exitosamente</p>";
                echo "<p><strong>Email:</strong> admin@test.com</p>";
                echo "<p><strong>Contraseña:</strong> admin123</p>";
                $repairs[] = 'Usuario admin creado';
            } else {
                echo "<p>❌ Error creando usuario admin</p>";
                $success = false;
            }
            
        } else {
            echo "<p>✅ Usuario admin encontrado: {$adminUser['email']}</p>";
            
            // Verificar si el email tiene patrones problemáticos
            if (!filter_var($adminUser['email'], FILTER_VALIDATE_EMAIL) || 
                preg_match('/[^\w@.-]/', $adminUser['email'])) {
                
                echo "<p>⚠️ Email con patrón problemático detectado. Actualizando...</p>";
                
                $newEmail = 'admin@test.com';
                $updateResult = $db->update(
                    "UPDATE usuarios SET email = ? WHERE id = ?",
                    [$newEmail, $adminUser['id']]
                );
                
                if ($updateResult) {
                    echo "<p>✅ Email actualizado a: $newEmail</p>";
                    $repairs[] = 'Email admin corregido';
                } else {
                    echo "<p>❌ Error actualizando email</p>";
                }
            }
        }
        
    } else {
        echo "<p>❌ Archivo config.php no encontrado</p>";
        $success = false;
    }

    // 2. Reemplazar archivos problemáticos
    echo "<h2>📁 Actualizando Archivos del Sistema</h2>";
    
    $filesToReplace = [
        'auth/login.php' => 'auth/login-fixed.php',
        'auth/jwt_helper.php' => 'auth/jwt_helper_fixed.php'
    ];
    
    foreach ($filesToReplace as $target => $source) {
        if (file_exists($source)) {
            if (copy($source, $target)) {
                echo "<p>✅ $target actualizado exitosamente</p>";
                $repairs[] = "Archivo $target actualizado";
            } else {
                echo "<p>❌ Error actualizando $target</p>";
                $success = false;
            }
        } else {
            echo "<p>⚠️ Archivo fuente $source no encontrado</p>";
        }
    }

    // 3. Verificar permisos de archivos
    echo "<h2>🔐 Verificando Permisos</h2>";
    
    $criticalFiles = [
        'auth/login.php',
        'auth/jwt_helper.php',
        '../assets/conexion/config.php'
    ];
    
    foreach ($criticalFiles as $file) {
        if (file_exists($file)) {
            $perms = fileperms($file);
            if (is_readable($file) && is_writable($file)) {
                echo "<p>✅ $file - Permisos correctos</p>";
            } else {
                echo "<p>⚠️ $file - Permisos limitados</p>";
                chmod($file, 0644);
                echo "<p>🔧 Permisos corregidos para $file</p>";
                $repairs[] = "Permisos corregidos para $file";
            }
        }
    }

    // 4. Limpiar cache y sesiones
    echo "<h2>🧹 Limpiando Cache y Sesiones</h2>";
    
    // Limpiar sesiones PHP
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    echo "<p>✅ Sesiones PHP limpiadas</p>";
    
    // Generar script para limpiar localStorage
    echo "<p>🌐 Limpiando localStorage del navegador...</p>";
    echo "<script>
        try {
            localStorage.removeItem('clúster_token');
            localStorage.removeItem('clúster_user');
            sessionStorage.clear();
            console.log('✅ localStorage limpiado');
        } catch(e) {
            console.error('Error limpiando localStorage:', e);
        }
    </script>";
    
    $repairs[] = 'Cache y sesiones limpiados';

    // 5. Test final del sistema
    echo "<h2>🧪 Test Final del Sistema</h2>";
    
    if (file_exists('auth/jwt_helper.php')) {
        require_once 'auth/jwt_helper.php';
        
        $testPayload = [
            'user_id' => 1,
            'email' => 'admin@test.com',
            'rol' => 'admin'
        ];
        
        $testToken = generateJWT($testPayload);
        $tokenValid = preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $testToken);
        $tokenVerified = verifyJWT($testToken);
        
        if ($testToken && $tokenValid && $tokenVerified) {
            echo "<p>✅ Sistema JWT funcionando correctamente</p>";
            $repairs[] = 'Sistema JWT validado';
        } else {
            echo "<p>❌ Sistema JWT aún tiene problemas</p>";
            echo "<p>Token generado: " . substr($testToken, 0, 50) . "...</p>";
            echo "<p>Patrón válido: " . ($tokenValid ? 'Sí' : 'No') . "</p>";
            echo "<p>Verificación: " . ($tokenVerified ? 'Sí' : 'No') . "</p>";
            $success = false;
        }
    }

} catch (Exception $e) {
    echo "<p>❌ Error durante la reparación: " . $e->getMessage() . "</p>";
    $success = false;
}

// Resumen final
echo "<h2>📋 Resumen de Reparaciones</h2>";

if ($success) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>✅ Reparación Completada Exitosamente</h3>";
    echo "<ul>";
    foreach ($repairs as $repair) {
        echo "<li>$repair</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🚀 Próximos Pasos</h3>";
    echo "<ol>";
    echo "<li><strong>Probar Login:</strong> <a href='../pages/sign-in.html' target='_blank'>Ir al formulario de login</a></li>";
    echo "<li><strong>Usar Credenciales:</strong> admin@test.com / admin123</li>";
    echo "<li><strong>Panel Admin:</strong> <a href='../admin/admin-dashboard.php' target='_blank'>Acceder al panel</a></li>";
    echo "</ol>";
    
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Reparación Incompleta</h3>";
    echo "<p>Algunos problemas no pudieron ser corregidos automáticamente.</p>";
    echo "<p><strong>Acciones manuales requeridas:</strong></p>";
    echo "<ul>";
    echo "<li>Verificar permisos de archivos y carpetas</li>";
    echo "<li>Revisar configuración de base de datos</li>";
    echo "<li>Ejecutar diagnóstico completo: <a href='diagnostico-patron.php' target='_blank'>Diagnóstico</a></li>";
    echo "</ul>";
    echo "</div>";
}

echo "<h3>🆘 Enlaces de Ayuda</h3>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px;'>";
echo "<ul>";
echo "<li><a href='diagnostico-patron.php' target='_blank'>🔍 Ejecutar Diagnóstico Completo</a></li>";
echo "<li><a href='../admin/admin-dashboard-directo.php' target='_blank'>🚀 Panel Admin Directo</a></li>";
echo "<li><a href='../pages/sign-in.html' target='_blank'>🔐 Formulario de Login</a></li>";
echo "<li><a href='debug/test-login.php' target='_blank'>🧪 Test de Login</a></li>";
echo "</ul>";
echo "</div>";

echo "<script>
// Auto-refresh después de 5 segundos si fue exitoso
if (" . ($success ? 'true' : 'false') . ") {
    setTimeout(function() {
        if (confirm('Reparación completada. ¿Desea ir al formulario de login para probar?')) {
            window.location.href = '../pages/sign-in.html';
        }
    }, 3000);
}
</script>";

echo "</body></html>";
?>