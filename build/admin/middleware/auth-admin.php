<?php
/**
 * Middleware de autenticación mejorado para área de administración
 */

// Detectar rutas automáticamente
$basePath = dirname(dirname(__DIR__));
$configPath = $basePath . '/assets/conexion/config.php';
$jwtPath = $basePath . '/api/auth/jwt_helper.php';

// Verificar que los archivos existan antes de incluirlos
if (!file_exists($configPath)) {
    die('Error: No se puede encontrar config.php en: ' . $configPath);
}
if (!file_exists($jwtPath)) {
    die('Error: No se puede encontrar jwt_helper.php en: ' . $jwtPath);
}

require_once $configPath;
require_once $jwtPath;

function verificarAdminAcceso($redirigir = true) {
    $esAdmin = false;
    $usuario = null;
    
    // Debug: Iniciar sesión para logs
    iniciarSesion();
    
    // 1. Intentar obtener token desde múltiples fuentes
    $token = null;
    
    // Buscar en headers Authorization
    $headers = getallheaders();
    if ($headers && isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }
    
    // Buscar en cookies
    if (!$token && isset($_COOKIE['clúster_token'])) {
        $token = $_COOKIE['clúster_token'];
    }
    
    // Buscar en sesión
    if (!$token && isset($_SESSION['clúster_token'])) {
        $token = $_SESSION['clúster_token'];
    }
    
    // Buscar en GET/POST (para debugging)
    if (!$token && isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    // 2. Si hay token, intentar verificarlo
    if ($token) {
        try {
            $payload = verifyJWT($token);
            if ($payload && isset($payload['rol']) && $payload['rol'] === 'admin') {
                // Obtener datos completos del usuario desde BD
                $usuarioObj = new Usuario();
                $usuario = $usuarioObj->obtenerPorId($payload['user_id']);
                if ($usuario && $usuario['rol'] === 'admin' && $usuario['estado'] === 'activo') {
                    $esAdmin = true;
                }
            }
        } catch (Exception $e) {
            // Token inválido, continuar con verificación de sesión
            error_log("Error verificando JWT: " . $e->getMessage());
        }
    }
    
    // 3. Verificar por sesión tradicional como fallback
    if (!$esAdmin && isset($_SESSION['usuario_id'])) {
        $usuarioObj = new Usuario();
        $usuario = $usuarioObj->obtenerPorId($_SESSION['usuario_id']);
        if ($usuario && $usuario['rol'] === 'admin' && $usuario['estado'] === 'activo') {
            $esAdmin = true;
        }
    }
    
    // 4. Si no es admin y se debe redirigir
    if (!$esAdmin && $redirigir) {
        // Registrar intento de acceso no autorizado
        error_log("Intento de acceso no autorizado al área de administración desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Construir URL de redirección dinámicamente
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptPath = dirname(dirname($_SERVER['SCRIPT_NAME'])); // Subir dos niveles desde /admin/
        
        // Mensaje específico según el caso
        if (!$token) {
            $mensaje = urlencode('Sesión expirada. Por favor, inicia sesión nuevamente.');
        } else {
            $mensaje = urlencode('Acceso denegado. Se requieren permisos de administrador.');
        }
        
        $redirectUrl = $protocol . '://' . $host . $scriptPath . '/pages/sign-in.html?error=admin_required&message=' . $mensaje;
        
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    return $esAdmin ? $usuario : false;
}

function verificarSesionAdmin() {
    return verificarAdminAcceso(false);
}

function requiereAdmin() {
    $admin = verificarAdminAcceso(true);
    if (!$admin) {
        exit; // La función ya redirige
    }
    return $admin;
}

function esAdmin() {
    return verificarAdminAcceso(false) !== false;
}

// Función para establecer sesión de admin con token
function establecerSesionAdmin($userData, $token) {
    iniciarSesion();
    $_SESSION['usuario_id'] = $userData['id'];
    $_SESSION['clúster_token'] = $token;
    
    // También establecer cookie
    setcookie('clúster_token', $token, time() + 3600, '/'); // 1 hora
}

// Auto-ejecutar verificación si se incluye este archivo
if (!defined('ADMIN_MIDDLEWARE_SKIP_AUTO')) {
    $adminUser = requiereAdmin();
}
?>