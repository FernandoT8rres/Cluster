<?php
/**
 * API para verificar sesión activa
 */

// Headers para AJAX
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// La función respuestaJSON ya está definida en config.php

try {
    // Iniciar sesión
    iniciarSesion();
    
    // Verificar si hay sesión activa
    if (verificarSesion()) {
        // Obtener datos del usuario actual
        $usuarioActual = obtenerUsuarioActual();
        
        if ($usuarioActual) {
            // Preparar datos para el frontend
            $userData = [
                'id' => $usuarioActual['id'],
                'nombre' => $usuarioActual['nombre'],
                'apellido' => $usuarioActual['apellido'] ?? '',
                'email' => $usuarioActual['email'],
                'rol' => $usuarioActual['rol'],
                'telefono' => $usuarioActual['telefono'] ?? '',
                'empresa_id' => $usuarioActual['empresa_id'] ?? null,
                'avatar' => $usuarioActual['avatar'] ?? null,
                'estado' => $usuarioActual['estado'] ?? 'activo'
            ];
            
            respuestaJSON([
                'success' => true,
                'user' => $userData,
                'message' => 'Sesión activa'
            ], 200);
        } else {
            respuestaJSON([
                'success' => false,
                'user' => null,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
    } else {
        respuestaJSON([
            'success' => false,
            'user' => null,
            'message' => 'No hay sesión activa'
        ], 401);
    }
    
} catch (Exception $e) {
    error_log("Error en check-session.php: " . $e->getMessage());
    respuestaJSON([
        'success' => false,
        'user' => null,
        'message' => 'Error verificando sesión: ' . $e->getMessage()
    ], 500);
}
?>