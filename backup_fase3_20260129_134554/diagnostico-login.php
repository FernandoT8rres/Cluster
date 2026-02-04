<?php
/**
 * Diagnóstico específico para la API de login
 * Identifica por qué devuelve HTML en lugar de JSON
 */

header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>🔧 Diagnóstico API Login - Clúster</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; background: #f5f5f5; }
.container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
h1 { color: #C7252B; border-bottom: 3px solid #C7252B; padding-bottom: 10px; }
h2 { color: #333; margin-top: 30px; }
.status { padding: 10px; margin: 10px 0; border-radius: 5px; }
.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.btn { padding: 8px 16px; background: #C7252B; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
.btn:hover { background: #8B1538; }
pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; max-height: 400px; }
code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
.test-form { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
.test-form input { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Diagnóstico de la API de Login</h1>";
echo "<p><strong>Problema:</strong> La API devuelve HTML en lugar de JSON</p>";

$fixes = [];
$errors = [];

// Paso 1: Verificar archivos de la API
echo "<h2>📁 Paso 1: Verificación de Archivos</h2>";

$apiFiles = [
    'auth/login.php' => 'API principal de login',
    'auth/jwt_helper.php' => 'Helper para JWT',
    'auth/login-fixed.php' => 'API corregida (si existe)',
    '../assets/conexion/config.php' => 'Configuración de BD'
];

foreach ($apiFiles as $file => $desc) {
    $fullPath = $file;
    if (file_exists($fullPath)) {
        $size = round(filesize($fullPath) / 1024, 2);
        echo "<div class='status success'>✅ $desc - $fullPath ($size KB)</div>";
    } else {
        echo "<div class='status error'>❌ $desc - $fullPath (NO EXISTE)</div>";
        $errors[] = "$desc no encontrado";
    }
}

// Paso 2: Test de conectividad de la API
echo "<h2>🔗 Paso 2: Test de Conectividad</h2>";

$apiUrl = '/build/api/auth/login.php';
$fullApiUrl = 'https://intranet.clústermetropolitano.mx' . $apiUrl;

echo "<div class='status info'>🔗 URL de la API: <code>$fullApiUrl</code></div>";

// Test básico de la API
echo "<h3>Test básico con cURL:</h3>";

try {
    $testData = json_encode([
        'email' => 'test@test.com',
        'password' => 'test123'
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullApiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($testData)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<div class='status error'>❌ Error de cURL: $error</div>";
    } else {
        echo "<div class='status info'>📡 Código HTTP: $httpCode</div>";
        
        // Mostrar respuesta
        echo "<h4>Respuesta recibida:</h4>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . (strlen($response) > 1000 ? '...' : '') . "</pre>";
        
        // Analizar la respuesta
        if (strpos($response, '<!DOCTYPE') === 0 || strpos($response, '<html') !== false) {
            echo "<div class='status error'>❌ PROBLEMA: La API está devolviendo HTML</div>";
            
            // Buscar errores de PHP en la respuesta
            if (strpos($response, 'Fatal error:') !== false) {
                echo "<div class='status error'>🐛 Error fatal de PHP detectado</div>";
                $errors[] = 'Error fatal de PHP en la API';
            }
            if (strpos($response, 'Warning:') !== false) {
                echo "<div class='status warning'>⚠️ Warning de PHP detectado</div>";
                $errors[] = 'Warning de PHP en la API';
            }
            if (strpos($response, 'Notice:') !== false) {
                echo "<div class='status info'>ℹ️ Notice de PHP detectado</div>";
            }
        } else {
            // Intentar decodificar como JSON
            $jsonData = json_decode($response, true);
            if ($jsonData !== null) {
                echo "<div class='status success'>✅ Respuesta JSON válida</div>";
                echo "<pre>" . json_encode($jsonData, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<div class='status error'>❌ Respuesta no es JSON válido</div>";
                $errors[] = 'API no devuelve JSON válido';
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div class='status error'>❌ Error en test cURL: " . $e->getMessage() . "</div>";
    $errors[] = 'Error en test de API: ' . $e->getMessage();
}

// Paso 3: Verificar configuración PHP
echo "<h2>⚙️ Paso 3: Configuración PHP</h2>";

$phpConfig = [
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => error_reporting(),
    'log_errors' => ini_get('log_errors'),
    'error_log' => ini_get('error_log'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];

echo "<div class='status info'>";
echo "<h4>Configuración PHP actual:</h4>";
foreach ($phpConfig as $key => $value) {
    echo "<strong>$key:</strong> $value<br>";
}
echo "</div>";

// Paso 4: Test directo del archivo PHP
echo "<h2>🧪 Paso 4: Test Directo del Archivo</h2>";

if (file_exists('auth/login.php')) {
    echo "<div class='status info'>🔍 Probando inclusión directa del archivo...</div>";
    
    // Capturar output para verificar si hay output antes del JSON
    ob_start();
    
    try {
        // Simular variables de entorno para el test
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = []; // Limpiar POST
        
        // Crear entrada JSON simulada
        $testInput = json_encode([
            'email' => 'admin@test.com',
            'password' => 'admin123'
        ]);
        
        // Simular php://input
        file_put_contents('php://memory', $testInput);
        
        // Incluir el archivo
        $output = '';
        try {
            include 'auth/login.php';
        } catch (Exception $e) {
            $output = "Error incluyendo archivo: " . $e->getMessage();
        }
        
        $capturedOutput = ob_get_contents();
        ob_end_clean();
        
        if (!empty($capturedOutput)) {
            echo "<div class='status error'>❌ Output capturado (no debería haber nada antes del JSON):</div>";
            echo "<pre>" . htmlspecialchars($capturedOutput) . "</pre>";
            $errors[] = 'La API produce output antes del JSON';
        } else {
            echo "<div class='status success'>✅ No hay output inesperado</div>";
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "<div class='status error'>❌ Error en test directo: " . $e->getMessage() . "</div>";
        $errors[] = 'Error en test directo: ' . $e->getMessage();
    }
    
} else {
    echo "<div class='status error'>❌ No se puede probar: archivo login.php no encontrado</div>";
}

// Paso 5: Crear API de login simplificada
echo "<h2>🛠️ Paso 5: API de Login Simplificada</h2>";

$simpleApiContent = '<?php
/**
 * API de login simplificada para diagnóstico
 */

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_clean();
}

// Headers estrictos
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Desactivar errores visibles
ini_set("display_errors", 0);
error_reporting(0);

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

try {
    // Log del intento
    error_log("API Login: Intento de acceso " . date("Y-m-d H:i:s"));
    
    // Verificar método
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido");
    }
    
    // Obtener datos
    $input = file_get_contents("php://input");
    error_log("API Login: Input recibido: " . $input);
    
    if (empty($input)) {
        throw new Exception("No se recibieron datos");
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido: " . json_last_error_msg());
    }
    
    if (!isset($data["email"]) || !isset($data["password"])) {
        throw new Exception("Email y contraseña requeridos");
    }
    
    $email = trim($data["email"]);
    $password = $data["password"];
    
    error_log("API Login: Intento con email: " . $email);
    
    // Validación básica
    if (empty($email) || empty($password)) {
        throw new Exception("Credenciales vacías");
    }
    
    // Login temporal para testing
    $validCredentials = [
        "admin@test.com" => "admin123",
        "admin@clúster.com" => "admin123",
        "test@test.com" => "test123"
    ];
    
    if (!isset($validCredentials[$email]) || $validCredentials[$email] !== $password) {
        error_log("API Login: Credenciales incorrectas para: " . $email);
        
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Credenciales incorrectas"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Login exitoso
    error_log("API Login: Login exitoso para: " . $email);
    
    $userData = [
        "id" => 1,
        "nombre" => "Usuario",
        "apellido" => "Prueba", 
        "email" => $email,
        "rol" => $email === "admin@test.com" || $email === "admin@clúster.com" ? "admin" : "empleado",
        "estado" => "activo"
    ];
    
    $token = base64_encode(json_encode([
        "user_id" => $userData["id"],
        "email" => $userData["email"],
        "rol" => $userData["rol"],
        "exp" => time() + 3600
    ]));
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Login exitoso (API de prueba)",
        "token" => $token,
        "user" => $userData
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("API Login Error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "debug" => [
            "timestamp" => date("c"),
            "error_type" => "api_test"
        ]
    ], JSON_UNESCAPED_UNICODE);
}

exit;
?>';

try {
    if (file_put_contents('auth/login-simple.php', $simpleApiContent)) {
        echo "<div class='status success'>✅ API simplificada creada: auth/login-simple.php</div>";
        $fixes[] = 'API de login simplificada creada';
        
        echo "<div class='status info'>";
        echo "<strong>🧪 Para probar la API simplificada:</strong><br>";
        echo "URL: <code>https://intranet.clústermetropolitano.mx/build/api/auth/login-simple.php</code><br>";
        echo "<strong>Credenciales de prueba:</strong><br>";
        echo "• admin@test.com / admin123 (admin)<br>";
        echo "• test@test.com / test123 (empleado)";
        echo "</div>";
    } else {
        echo "<div class='status error'>❌ Error creando API simplificada</div>";
        $errors[] = 'No se pudo crear API simplificada';
    }
} catch (Exception $e) {
    echo "<div class='status error'>❌ Error: " . $e->getMessage() . "</div>";
    $errors[] = 'Error creando API: ' . $e->getMessage();
}

// Paso 6: Formulario de prueba
echo "<h2>🧪 Paso 6: Formulario de Prueba</h2>";

echo "<div class='test-form'>";
echo "<h4>Probar API de Login</h4>";
echo "<form onsubmit='testLogin(event)'>";
echo "<input type='email' id='testEmail' placeholder='Email' value='admin@test.com' required>";
echo "<input type='password' id='testPassword' placeholder='Contraseña' value='admin123' required>";
echo "<button type='submit' class='btn'>🧪 Probar Login</button>";
echo "</form>";
echo "<div id='testResult' style='margin-top: 15px;'></div>";
echo "</div>";

// JavaScript para el test
echo "<script>
async function testLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('testEmail').value;
    const password = document.getElementById('testPassword').value;
    const resultDiv = document.getElementById('testResult');
    
    resultDiv.innerHTML = '<div style=\"padding: 10px; background: #f0f0f0; border-radius: 4px;\">🔄 Probando...</div>';
    
    try {
        const response = await fetch('./auth/login-simple.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        try {
            const data = JSON.parse(responseText);
            
            if (data.success) {
                resultDiv.innerHTML = '<div style=\"padding: 10px; background: #d4edda; color: #155724; border-radius: 4px;\">✅ Login exitoso<br>Token: ' + data.token.substring(0, 50) + '...</div>';
            } else {
                resultDiv.innerHTML = '<div style=\"padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;\">❌ Error: ' + data.message + '</div>';
            }
        } catch (parseError) {
            resultDiv.innerHTML = '<div style=\"padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;\">❌ Respuesta no es JSON válido:<br><pre>' + responseText.substring(0, 500) + '</pre></div>';
        }
        
    } catch (error) {
        resultDiv.innerHTML = '<div style=\"padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;\">❌ Error de red: ' + error.message + '</div>';
    }
}
</script>";

// Resumen
echo "<h2>📋 Resumen del Diagnóstico</h2>";

if (count($fixes) > 0) {
    echo "<div class='status success'>";
    echo "<h3>✅ Soluciones Aplicadas:</h3>";
    echo "<ul>";
    foreach ($fixes as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (count($errors) > 0) {
    echo "<div class='status error'>";
    echo "<h3>❌ Problemas Identificados:</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<h3>🎯 Próximos Pasos:</h3>";
echo "<div class='status info'>";
echo "<ol>";
echo "<li><strong>Probar API simplificada:</strong> Usa el formulario de arriba</li>";
echo "<li><strong>Si funciona:</strong> El problema está en la API original</li>";
echo "<li><strong>Si no funciona:</strong> Hay un problema de configuración del servidor</li>";
echo "<li><strong>Reemplazar API:</strong> Si la simple funciona, úsala temporalmente</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<button onclick='window.location.reload()' class='btn'>🔄 Ejecutar Diagnóstico Nuevamente</button>";
echo "<a href='../pages/sign-in.html' target='_blank' class='btn'>🔐 Probar Login</a>";
echo "</div>";

echo "</div></body></html>";
?>