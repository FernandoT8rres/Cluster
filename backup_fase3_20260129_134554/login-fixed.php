<?php
// API de login mejorada con mejor manejo de errores de patrón
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Desactivar output de errores para evitar contaminar el JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para respuesta JSON limpia con validación de patrones
function jsonResponse($data, $httpCode = 200) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Validar que los datos son seguros para JSON
    if (isset($data['message'])) {
        // Limpiar caracteres que pueden causar problemas de patrón
        $data['message'] = preg_replace('/[^\p{L}\p{N}\s\-_.@!?,:;]/u', '', $data['message']);
    }
    
    if (isset($data['user']['email'])) {
        // Validar email con patrón estricto
        if (!filter_var($data['user']['email'], FILTER_VALIDATE_EMAIL)) {
            $data['user']['email'] = 'email_invalido@clúster.com';
        }
    }
    
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false, 
        'message' => 'Método no permitido. Use POST.'
    ], 405);
}

try {
    // Incluir archivos necesarios
    $basePath = dirname(dirname(__DIR__));
    $configPath = $basePath . '/assets/conexion/config.php';
    $jwtPath = dirname(__FILE__) . '/jwt_helper.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("Archivo de configuración no encontrado");
    }
    
    if (!file_exists($jwtPath)) {
        throw new Exception("JWT helper no encontrado");
    }
    
    require_once $configPath;
    require_once $jwtPath;
    
    // Obtener datos de entrada con validación de patrón
    $rawInput = file_get_contents('php://input');
    
    if (empty($rawInput)) {
        throw new Exception('No se recibieron datos de entrada');
    }
    
    // Limpiar input de caracteres problemáticos
    $cleanInput = preg_replace('/[\x00-\x1F\x7F]/', '', $rawInput);
    
    $input = json_decode($cleanInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: formato no reconocido');
    }
    
    if (!isset($input['email']) || !isset($input['password'])) {
        throw new Exception('Email y contraseña son requeridos');
    }
    
    $email = trim($input['email']);
    $password = $input['password'];
    
    // Validaciones básicas con patrones seguros
    if (empty($email) || empty($password)) {
        throw new Exception('Email y contraseña no pueden estar vacíos');
    }
    
    // Validación de email con patrón más flexible
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        throw new Exception('Formato de email inválido');
    }
    
    // Intentar login
    $usuario = new Usuario();
    $userData = $usuario->login($email, $password);
    
    if (!$userData) {
        // Log del intento fallido sin caracteres especiales
        error_log("Login fallido para: " . preg_replace('/[^a-zA-Z0-9@._-]/', '', $email));
        
        jsonResponse([
            'success' => false,
            'message' => 'Credenciales incorrectas o cuenta inactiva'
        ], 401);
    }
    
    // Login exitoso, generar token JWT con validación de patrón
    try {
        // Limpiar datos antes de generar JWT
        $tokenPayload = [
            'user_id' => (int)$userData['id'],
            'email' => filter_var($userData['email'], FILTER_SANITIZE_EMAIL),
            'rol' => preg_replace('/[^a-zA-Z0-9_-]/', '', $userData['rol']),
            'iat' => time(),
            'exp' => time() + 3600
        ];
        
        $token = generateJWT($tokenPayload);
        
        if (!$token || !is_string($token)) {
            throw new Exception('Error generando token de autenticación');
        }
        
        // Validar que el token tiene formato correcto
        if (!preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $token)) {
            throw new Exception('Token generado con formato inválido');
        }
        
    } catch (Exception $e) {
        error_log("Error generando JWT: " . $e->getMessage());
        throw new Exception('Error en el sistema de autenticación');
    }
    
    // Preparar datos del usuario (sin contraseña) con validación de patrones
    unset($userData['password']);
    
    // Limpiar todos los campos del usuario
    $cleanUserData = [
        'id' => (int)$userData['id'],
        'nombre' => preg_replace('/[^\p{L}\s\-]/u', '', $userData['nombre'] ?? ''),
        'apellido' => preg_replace('/[^\p{L}\s\-]/u', '', $userData['apellido'] ?? ''),
        'email' => filter_var($userData['email'], FILTER_SANITIZE_EMAIL),
        'rol' => preg_replace('/[^a-zA-Z0-9_-]/', '', $userData['rol']),
        'estado' => preg_replace('/[^a-zA-Z0-9_-]/', '', $userData['estado']),
        'telefono' => preg_replace('/[^0-9\s\-+()]/', '', $userData['telefono'] ?? ''),
        'avatar' => filter_var($userData['avatar'] ?? '', FILTER_SANITIZE_URL)
    ];
    
    // Log del login exitoso
    error_log("Login exitoso para usuario: {$cleanUserData['email']} (ID: {$cleanUserData['id']}, Rol: {$cleanUserData['rol']})");
    
    // Respuesta exitosa con datos limpios
    jsonResponse([
        'success' => true,
        'message' => 'Login exitoso',
        'token' => $token,
        'user' => $cleanUserData
    ]);
    
} catch (Exception $e) {
    error_log("Error en login API: " . $e->getMessage());
    
    // Limpiar mensaje de error de caracteres problemáticos
    $cleanMessage = preg_replace('/[^\p{L}\p{N}\s\-_.@!?,:;]/u', '', $e->getMessage());
    
    jsonResponse([
        'success' => false,
        'message' => $cleanMessage ?: 'Error interno del servidor',
        'debug' => [
            'error_type' => 'pattern_validation_error',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], 400);
}
?>