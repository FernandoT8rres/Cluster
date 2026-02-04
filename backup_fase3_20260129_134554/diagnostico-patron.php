<?php
/**
 * Diagnóstico específico para el error "The string did not match the expected pattern"
 * Ejecutar este archivo para identificar y solucionar el problema
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

function diagnosticResponse($data) {
    echo "<pre style='background:#f4f4f4; padding:20px; border-radius:8px;'>" . 
         json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . 
         "</pre>";
}

$diagnostic = [
    'timestamp' => date('Y-m-d H:i:s'),
    'error_analysis' => 'Pattern validation error diagnosis',
    'tests' => [],
    'solutions' => [],
    'status' => 'running'
];

echo "<h1>🔍 Diagnóstico del Error de Patrón - Clúster</h1>";

try {
    // Test 1: Verificar archivos necesarios
    $diagnostic['tests']['file_check'] = [];
    
    $requiredFiles = [
        'config' => '../assets/conexion/config.php',
        'jwt_helper' => './auth/jwt_helper.php',
        'login_api' => './auth/login.php'
    ];

    foreach ($requiredFiles as $name => $path) {
        $diagnostic['tests']['file_check'][$name] = [
            'path' => $path,
            'exists' => file_exists($path),
            'readable' => file_exists($path) ? is_readable($path) : false
        ];
    }

    // Test 2: Conectar a base de datos
    if (file_exists('../assets/conexion/config.php')) {
        require_once '../assets/conexion/config.php';
        
        $db = Database::getInstance();
        $diagnostic['tests']['database'] = [
            'connection' => 'success',
            'message' => 'Conexión a BD exitosa'
        ];

        // Test 3: Verificar usuario admin
        $adminQuery = "SELECT id, email, password, rol, estado FROM usuarios WHERE rol = 'admin' LIMIT 1";
        $adminUser = $db->selectOne($adminQuery);

        if ($adminUser) {
            $diagnostic['tests']['admin_user'] = [
                'found' => true,
                'id' => $adminUser['id'],
                'email' => $adminUser['email'],
                'rol' => $adminUser['rol'],
                'estado' => $adminUser['estado'],
                'email_pattern_valid' => filter_var($adminUser['email'], FILTER_VALIDATE_EMAIL) ? true : false,
                'password_hash_valid' => !empty($adminUser['password']) && strlen($adminUser['password']) > 50
            ];

            // Test 4: Verificar patrones problemáticos en email
            $email = $adminUser['email'];
            $diagnostic['tests']['email_patterns'] = [
                'original' => $email,
                'has_special_chars' => preg_match('/[^\w@.-]/', $email) ? true : false,
                'has_unicode' => mb_strlen($email, 'UTF-8') !== strlen($email),
                'filter_sanitized' => filter_var($email, FILTER_SANITIZE_EMAIL),
                'regex_cleaned' => preg_replace('/[^\w@.-]/', '', $email)
            ];

        } else {
            $diagnostic['tests']['admin_user'] = [
                'found' => false,
                'message' => 'No se encontró usuario admin'
            ];

            // Crear usuario admin de prueba
            $diagnostic['solutions'][] = [
                'type' => 'create_admin',
                'description' => 'Crear usuario admin temporal',
                'sql' => "INSERT INTO usuarios (nombre, apellido, email, password, rol, estado) VALUES ('Admin', 'Test', 'admin@test.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin', 'activo')"
            ];
        }

    } else {
        $diagnostic['tests']['database'] = [
            'connection' => 'failed',
            'message' => 'Archivo config.php no encontrado'
        ];
    }

    // Test 5: Verificar JWT helper
    if (file_exists('./auth/jwt_helper.php')) {
        require_once './auth/jwt_helper.php';

        $testPayload = [
            'user_id' => 1,
            'email' => 'test@test.com',
            'rol' => 'admin'
        ];

        $token = generateJWT($testPayload);
        $diagnostic['tests']['jwt'] = [
            'generation' => !empty($token),
            'token_pattern' => preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $token) ? true : false,
            'verification' => verifyJWT($token) ? true : false,
            'sample_token' => substr($token, 0, 50) . '...'
        ];

        // Test patrones problemáticos en JWT
        $diagnostic['tests']['jwt_patterns'] = [];
        
        // Test con datos que pueden causar problemas de patrón
        $problematicData = [
            'email_with_unicode' => 'admin@clúster.com',
            'email_with_special' => 'admin+test@clúster.com',
            'name_with_accents' => 'José María',
            'name_with_symbols' => 'Admin-Test#1'
        ];

        foreach ($problematicData as $testName => $testValue) {
            $testPayload = [
                'user_id' => 1,
                'email' => $testValue,
                'rol' => 'admin'
            ];

            try {
                $testToken = generateJWT($testPayload);
                $verified = verifyJWT($testToken);
                
                $diagnostic['tests']['jwt_patterns'][$testName] = [
                    'input' => $testValue,
                    'token_generated' => !empty($testToken),
                    'token_verified' => $verified ? true : false,
                    'pattern_valid' => preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $testToken) ? true : false
                ];

            } catch (Exception $e) {
                $diagnostic['tests']['jwt_patterns'][$testName] = [
                    'input' => $testValue,
                    'error' => $e->getMessage()
                ];
            }
        }

    } else {
        $diagnostic['tests']['jwt'] = [
            'error' => 'jwt_helper.php no encontrado'
        ];
    }

    // Test 6: Simular login completo
    if (isset($adminUser) && $adminUser) {
        $diagnostic['tests']['login_simulation'] = [];

        // Test con contraseñas comunes
        $commonPasswords = ['admin', 'admin123', '123456', 'password'];
        $loginSuccess = false;

        foreach ($commonPasswords as $testPass) {
            if (password_verify($testPass, $adminUser['password'])) {
                $diagnostic['tests']['login_simulation']['password_found'] = $testPass;
                $loginSuccess = true;

                // Simular login completo
                try {
                    $usuario = new Usuario();
                    $loginResult = $usuario->login($adminUser['email'], $testPass);

                    if ($loginResult) {
                        $cleanUserData = [
                            'id' => $loginResult['id'],
                            'email' => filter_var($loginResult['email'], FILTER_SANITIZE_EMAIL),
                            'rol' => preg_replace('/[^a-zA-Z0-9_-]/', '', $loginResult['rol'])
                        ];

                        $testToken = generateJWT($cleanUserData);
                        
                        $diagnostic['tests']['login_simulation']['full_test'] = [
                            'login_success' => true,
                            'token_generated' => !empty($testToken),
                            'token_pattern_valid' => preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $testToken) ? true : false,
                            'user_data_clean' => $cleanUserData
                        ];
                    }

                } catch (Exception $e) {
                    $diagnostic['tests']['login_simulation']['full_test'] = [
                        'error' => $e->getMessage(),
                        'likely_pattern_error' => strpos($e->getMessage(), 'pattern') !== false
                    ];
                }
                
                break;
            }
        }

        if (!$loginSuccess) {
            $diagnostic['tests']['login_simulation']['password_test'] = 'No se pudo determinar contraseña con contraseñas comunes';
        }
    }

    $diagnostic['status'] = 'completed';

    // Generar soluciones basadas en los tests
    $diagnostic['solutions'] = [];

    // Solución 1: Archivos faltantes
    $missingFiles = array_filter($diagnostic['tests']['file_check'], function($file) {
        return !$file['exists'];
    });

    if (!empty($missingFiles)) {
        $diagnostic['solutions'][] = [
            'priority' => 'high',
            'type' => 'missing_files',
            'description' => 'Archivos necesarios no encontrados',
            'files' => array_keys($missingFiles),
            'action' => 'Verificar que los archivos existen en las rutas correctas'
        ];
    }

    // Solución 2: Usuario admin
    if (isset($diagnostic['tests']['admin_user']) && !$diagnostic['tests']['admin_user']['found']) {
        $diagnostic['solutions'][] = [
            'priority' => 'high',
            'type' => 'create_admin',
            'description' => 'Crear usuario administrador',
            'action' => 'Ejecutar SQL para crear admin',
            'sql' => "INSERT INTO usuarios (nombre, apellido, email, password, rol, estado) VALUES ('Admin', 'Sistema', 'admin@test.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin', 'activo')"
        ];
    }

    // Solución 3: Patrones de email problemáticos
    if (isset($diagnostic['tests']['email_patterns'])) {
        $emailTest = $diagnostic['tests']['email_patterns'];
        if ($emailTest['has_special_chars'] || $emailTest['has_unicode']) {
            $diagnostic['solutions'][] = [
                'priority' => 'medium',
                'type' => 'email_pattern_fix',
                'description' => 'Email con caracteres especiales detectado',
                'original_email' => $emailTest['original'],
                'suggested_email' => $emailTest['filter_sanitized'],
                'action' => 'Actualizar email del admin a formato más simple'
            ];
        }
    }

    // Solución 4: Problemas de JWT
    if (isset($diagnostic['tests']['jwt']) && !$diagnostic['tests']['jwt']['token_pattern']) {
        $diagnostic['solutions'][] = [
            'priority' => 'high',
            'type' => 'jwt_pattern_fix',
            'description' => 'Token JWT con patrón inválido',
            'action' => 'Usar archivos jwt_helper_fixed.php y login-fixed.php',
            'files_to_replace' => [
                'jwt_helper.php' => 'jwt_helper_fixed.php',
                'login.php' => 'login-fixed.php'
            ]
        ];
    }

    // Solución 5: Error de patrón en login
    if (isset($diagnostic['tests']['login_simulation']['full_test']['likely_pattern_error']) && 
        $diagnostic['tests']['login_simulation']['full_test']['likely_pattern_error']) {
        
        $diagnostic['solutions'][] = [
            'priority' => 'critical',
            'type' => 'pattern_validation_fix',
            'description' => 'Error de patrón confirmado en proceso de login',
            'action' => 'Implementar fixes de validación de patrón',
            'steps' => [
                '1. Usar archivos corregidos (login-fixed.php, jwt_helper_fixed.php)',
                '2. Limpiar localStorage del navegador',
                '3. Crear usuario admin con email simple (admin@test.com)',
                '4. Usar auth-pattern-fix.js en el frontend'
            ]
        ];
    }

} catch (Exception $e) {
    $diagnostic['status'] = 'error';
    $diagnostic['error'] = $e->getMessage();
    $diagnostic['error_file'] = $e->getFile();
    $diagnostic['error_line'] = $e->getLine();
}

// Mostrar resultados
echo "<h2>📊 Resultados del Diagnóstico</h2>";
diagnosticResponse($diagnostic);

// Mostrar soluciones recomendadas
if (!empty($diagnostic['solutions'])) {
    echo "<h2>🛠️ Soluciones Recomendadas</h2>";
    foreach ($diagnostic['solutions'] as $index => $solution) {
        $priority_color = [
            'critical' => '#dc3545',
            'high' => '#fd7e14', 
            'medium' => '#ffc107',
            'low' => '#28a745'
        ];
        
        $color = $priority_color[$solution['priority']] ?? '#6c757d';
        
        echo "<div style='border-left: 4px solid $color; padding: 15px; margin: 15px 0; background: #f8f9fa;'>";
        echo "<h3 style='color: $color; margin-top: 0;'>Solución " . ($index + 1) . " - Prioridad: " . ucfirst($solution['priority']) . "</h3>";
        echo "<p><strong>Tipo:</strong> " . $solution['type'] . "</p>";
        echo "<p><strong>Descripción:</strong> " . $solution['description'] . "</p>";
        if (isset($solution['action'])) {
            echo "<p><strong>Acción:</strong> " . $solution['action'] . "</p>";
        }
        if (isset($solution['sql'])) {
            echo "<p><strong>SQL a ejecutar:</strong></p>";
            echo "<code style='background: #e9ecef; padding: 10px; display: block; border-radius: 4px;'>" . htmlspecialchars($solution['sql']) . "</code>";
        }
        if (isset($solution['steps'])) {
            echo "<p><strong>Pasos:</strong></p><ul>";
            foreach ($solution['steps'] as $step) {
                echo "<li>" . $step . "</li>";
            }
            echo "</ul>";
        }
        echo "</div>";
    }
}

echo "<h2>🚀 Acciones Rápidas</h2>";
echo "<div style='padding: 20px; background: #e7f3ff; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Para Solución Inmediata:</h3>";
echo "<ol>";
echo "<li><strong>Limpiar navegador:</strong> Abre F12, ve a Console y ejecuta: <code>localStorage.clear(); sessionStorage.clear(); location.reload();</code></li>";
echo "<li><strong>Usar archivos corregidos:</strong> Reemplaza login.php con login-fixed.php</li>";
echo "<li><strong>Crear admin simple:</strong> Usa email como admin@test.com y contraseña admin123</li>";
echo "<li><strong>Acceso directo:</strong> <a href='../admin/admin-dashboard-directo.php' target='_blank'>Panel Admin Directo</a></li>";
echo "</ol>";
echo "</div>";

?>

<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Completo - Clúster</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1 { color: #C7252B; }
        h2 { color: #333; border-bottom: 2px solid #C7252B; padding-bottom: 5px; }
        code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
</html>