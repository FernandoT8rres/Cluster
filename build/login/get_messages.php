<?php
// get_messages.php - API para obtener mensajes de la sesión
require_once '../assets/conexion/config.php';

iniciarSesion();

$response = [];

// Verificar mensajes de error
if (isset($_SESSION['login_error'])) {
    $response['error'] = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Verificar mensajes de éxito
if (isset($_SESSION['login_success'])) {
    $response['success'] = $_SESSION['login_success'];
    unset($_SESSION['login_success']);
}

// Verificar otros mensajes
if (isset($_SESSION['message'])) {
    $response['message'] = $_SESSION['message'];
    unset($_SESSION['message']);
}

respuestaJSON($response);
?>