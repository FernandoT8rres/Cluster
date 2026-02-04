<?php
/**
 * API para verificar token de autenticación
 * Endpoint: /api/auth/verify.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Desactivar errores en pantalla
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once '../config/database.php';

/**
 * Función para verificar el token JWT
 */
function verifyToken($token) {
    // Si tienes una librería JWT, úsala aquí
    // Por ahora, implementación simple
    
    try {
        // Separar las partes del token
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        // Decodificar el payload
        $payload = json_decode(base64_decode($parts[1]), true);
        
        if (!$payload) {
            return false;
        }
        
        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        // Verificar que el usuario existe en la BD
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $query = "SELECT id, nombre, apellido, email, rol, avatar 
                  FROM usuarios_perfil 
                  WHERE id = :user_id AND activo = 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $payload['user_id']);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        return $user;
        
    } catch (Exception $e) {
        error_log("Error verificando token: " . $e->getMessage());
        return false;
    }
}

/**
 * Función para obtener el token del header
 */
function getBearerToken() {
    $headers = getallheaders();
    
    // Buscar el header Authorization
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
    } else {
        return null;
    }
    
    // Extraer el token
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }
    
    return null;
}

// Obtener el token
$token = getBearerToken();

if (!$token) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Token no proporcionado'
    ]);
    exit();
}

// Verificar el token
$user = verifyToken($token);

if ($user) {
    // Token válido
    echo json_encode([
        'success' => true,
        'message' => 'Token válido',
        'user' => $user
    ]);
} else {
    // Token inválido
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Token inválido o expirado'
    ]);
}
?>