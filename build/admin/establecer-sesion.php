<?php
/**
 * Script para establecer sesión después del login exitoso
 * Se llama via AJAX después del login
 */

require_once '../assets/conexion/config.php';
require_once 'middleware/auth-admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['token']) || !isset($input['user'])) {
        throw new Exception('Token y datos de usuario requeridos');
    }
    
    $token = $input['token'];
    $userData = $input['user'];
    
    // Verificar que el token es válido
    $payload = verifyJWT($token);
    if (!$payload) {
        throw new Exception('Token inválido');
    }
    
    // Verificar que es admin
    if ($userData['rol'] !== 'admin') {
        throw new Exception('No es administrador');
    }
    
    // Establecer sesión
    establecerSesionAdmin($userData, $token);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sesión de admin establecida',
        'redirect_url' => './admin-dashboard.php'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>