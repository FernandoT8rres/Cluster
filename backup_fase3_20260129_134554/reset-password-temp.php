<?php
/**
 * Endpoint temporal para resetear contraseña de usuarios existentes
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Solo método POST']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email']) || !isset($input['new_password'])) {
        echo json_encode(['success' => false, 'message' => 'Email y nueva contraseña requeridos']);
        exit;
    }
    
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Hash de la nueva contraseña
    $hashedPassword = password_hash($input['new_password'], PASSWORD_DEFAULT);
    
    // Actualizar contraseña
    $query = "UPDATE usuarios_perfil SET password = :password WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':email', $input['email']);
    
    if ($stmt->execute()) {
        $rowsAffected = $stmt->rowCount();
        
        if ($rowsAffected > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Contraseña actualizada exitosamente',
                'email' => $input['email']
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Usuario no encontrado'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar contraseña']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>