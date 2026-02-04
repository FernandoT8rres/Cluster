<?php
/**
 * API para manejo de usuarios autenticados
 */

define('CLAUT_ACCESS', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/models.php';

// Verificar autenticación
$user = requireAuth();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetProfile($user);
        break;
    case 'PUT':
        handleUpdateProfile($user);
        break;
    default:
        jsonError('Método no permitido', 405);
}

function handleGetProfile($user) {
    try {
        $usuarioModel = new Usuario();
        $userData = $usuarioModel->obtenerPorId($user['user_id']);
        
        if (!$userData) {
            jsonError('Usuario no encontrado', 404);
        }
        
        jsonResponse($userData, 200, 'Perfil obtenido exitosamente');
        
    } catch (Exception $e) {
        jsonError('Error al obtener perfil: ' . $e->getMessage(), 500);
    }
}

function handleUpdateProfile($user) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            jsonError('Datos inválidos', 400);
        }
        
        $usuarioModel = new Usuario();
        $updated = $usuarioModel->actualizar($user['user_id'], $input);
        
        if ($updated) {
            $userData = $usuarioModel->obtenerPorId($user['user_id']);
            jsonResponse($userData, 200, 'Perfil actualizado exitosamente');
        } else {
            jsonError('No se pudo actualizar el perfil', 400);
        }
        
    } catch (Exception $e) {
        jsonError('Error al actualizar perfil: ' . $e->getMessage(), 500);
    }
}
?>