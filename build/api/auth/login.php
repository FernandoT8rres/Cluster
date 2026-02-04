<?php
// API de login mejorada con mejor manejo de errores
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

// Función para respuesta JSON limpia
function jsonResponse($data, $httpCode = 200) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false, 
        'message' => 'Método no permitido. Use POST.'
    ], 405);
}

// ============================================
// RATE LIMITING - Protección contra fuerza bruta
// ============================================
try {
    require_once dirname(dirname(__DIR__)) . '/middleware/rate-limiter.php';
    
    $rateLimiter = new RateLimiter();
    $clientIP = getRateLimitIdentifier();
    
    // Verificar límite (5 intentos / 5 minutos)
    $status = $rateLimiter->getStatus(
        $clientIP,
        RateLimitConfig::LOGIN['max'],
        RateLimitConfig::LOGIN['window'],
        RateLimitConfig::LOGIN['action']
    );
    
    // Si se excedió el límite, bloquear
    if (!$status['allowed']) {
        jsonResponse([
            'success' => false,
            'error' => 'too_many_attempts',
            'message' => 'Demasiados intentos de login. Intenta de nuevo en ' . 
                         ceil($status['retry_after'] / 60) . ' minutos.',
            'retry_after' => $status['retry_after']
        ], 429);
    }
    
    // Registrar intento
    $rateLimiter->recordAttempt($clientIP, 'login');
    
} catch (Exception $e) {
    // Si hay error en rate limiter, continuar sin bloquear
    // Esto asegura que el login siga funcionando aunque falle el rate limiter
    error_log("Error en rate limiter: " . $e->getMessage());
}
// ============================================

try {
    // Incluir archivos necesarios
    $basePath = dirname(dirname(__DIR__));
    $configPath = $basePath . '/assets/conexion/config.php';
    $jwtPath = dirname(__FILE__) . '/jwt_helper.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("Archivo de configuración no encontrado: $configPath");
    }
    
    if (!file_exists($jwtPath)) {
        throw new Exception("JWT helper no encontrado: $jwtPath");
    }
    
    require_once $configPath;
    require_once $jwtPath;
    
    // Obtener datos de entrada
    $rawInput = file_get_contents('php://input');
    
    if (empty($rawInput)) {
        throw new Exception('No se recibieron datos de entrada');
    }
    
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    if (!isset($input['email']) || !isset($input['password'])) {
        throw new Exception('Email y contraseña son requeridos');
    }
    
    $email = trim($input['email']);
    $password = $input['password'];
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        throw new Exception('Email y contraseña no pueden estar vacíos');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Formato de email inválido');
    }
    
    // Intentar login
    $usuario = new Usuario();
    $userData = $usuario->login($email, $password);
    
    if (!$userData) {
        // Log del intento fallido
        error_log("Intento de login fallido para email: $email desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        jsonResponse([
            'success' => false,
            'message' => 'Credenciales incorrectas o cuenta inactiva'
        ], 401);
    }
    
    
    // Login exitoso, generar tokens JWT mejorados
    try {
        // Cargar nuevo sistema JWT si está disponible
        $jwtValidatorPath = dirname(dirname(__DIR__)) . '/middleware/jwt-validator.php';
        $useNewJWT = file_exists($jwtValidatorPath);
        
        if ($useNewJWT) {
            require_once $jwtValidatorPath;
            
            // Obtener secreto desde .env o usar default
            $jwtSecret = getenv('JWT_SECRET') ?: 'CLAUT_SECRET_KEY_2024_SECURE';
            
            // Payload para los tokens
            $payload = [
                'user_id' => $userData['id'],
                'email' => $userData['email'],
                'rol' => $userData['rol']
            ];
            
            // Generar access token (15 minutos)
            $accessToken = JwtValidator::generate($payload, $jwtSecret, JwtConfig::ACCESS_TOKEN_EXPIRY);
            
            // Generar refresh token (7 días)
            $refreshToken = JwtValidator::generateRefreshToken($payload, $jwtSecret);
            
            if (!$accessToken || !$refreshToken) {
                throw new Exception('Error generando tokens de autenticación');
            }
            
            $token = $accessToken; // Para retrocompatibilidad
            $hasRefreshToken = true;
            
        } else {
            // Fallback al sistema JWT antiguo
            $token = generateJWT([
                'user_id' => $userData['id'],
                'email' => $userData['email'],
                'rol' => $userData['rol'],
                'iat' => time(),
                'exp' => time() + 3600 // 1 hora
            ]);
            
            if (!$token) {
                throw new Exception('Error generando token de autenticación');
            }
            
            $hasRefreshToken = false;
        }
        
    } catch (Exception $e) {
        error_log("Error generando JWT: " . $e->getMessage());
        throw new Exception('Error en el sistema de autenticación');
    }
    
    // Preparar datos del usuario (sin contraseña)
    unset($userData['password']);
    
    // Log del login exitoso
    error_log("Login exitoso para usuario: {$userData['email']} (ID: {$userData['id']}, Rol: {$userData['rol']})");
    
    // ============================================
    // IMPORTANTE: Guardar datos en sesión para autenticación basada en sesiones
    // ============================================
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_nombre'] = $userData['nombre'];
    $_SESSION['user_apellido'] = $userData['apellido'] ?? '';
    $_SESSION['user_rol'] = $userData['rol'];
    $_SESSION['user_empresa'] = $userData['nombre_empresa'] ?? '';
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Regenerar session ID después de login exitoso (seguridad)
    if (class_exists('SessionConfig')) {
        SessionConfig::regenerate();
    }
    
    error_log("Sesión configurada - Email: {$_SESSION['user_email']}, Rol: {$_SESSION['user_rol']}");
    // ============================================
    
    
    // Resetear contador de rate limiting después de login exitoso
    try {
        if (isset($rateLimiter) && isset($clientIP)) {
            $rateLimiter->reset($clientIP, 'login');
        }
    } catch (Exception $e) {
        error_log("Error reseteando rate limiter: " . $e->getMessage());
    }
    
    
    // Respuesta exitosa
    $response = [
        'success' => true,
        'message' => 'Login exitoso',
        'token' => $token,
        'user' => [
            'id' => $userData['id'],
            'nombre' => $userData['nombre'],
            'apellido' => $userData['apellido'],
            'email' => $userData['email'],
            'rol' => $userData['rol'],
            'estado' => $userData['estado'],
            'telefono' => $userData['telefono'] ?? null,
            'avatar' => $userData['avatar'] ?? null
        ]
    ];
    
    // Agregar refresh token si está disponible
    if (isset($hasRefreshToken) && $hasRefreshToken && isset($refreshToken)) {
        $response['refresh_token'] = $refreshToken;
        $response['token_type'] = 'Bearer';
        $response['expires_in'] = 900; // 15 minutos para access token
    }
    
    jsonResponse($response);
    
} catch (Exception $e) {
    error_log("Error en login API: " . $e->getMessage());
    
    jsonResponse([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ], 400);
}
?>