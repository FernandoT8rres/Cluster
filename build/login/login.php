<?php
// login.php - Procesamiento del login
require_once '../assets/conexion/config.php';

iniciarSesion();

// Verificar si ya está logueado
if (verificarSesion()) {
    redirigir('dashboard.html');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingresa un email válido.";
    } else {
        try {
            $usuario = new Usuario();
            $userData = $usuario->login($email, $password);
            
            if ($userData) {
                // Login exitoso
                $_SESSION['usuario_id'] = $userData['id'];
                $_SESSION['usuario_nombre'] = $userData['nombre'];
                $_SESSION['usuario_apellido'] = $userData['apellido'];
                $_SESSION['usuario_email'] = $userData['email'];
                $_SESSION['usuario_rol'] = $userData['rol'];
                
                // Redirigir según el rol
                switch ($userData['rol']) {
                    case 'admin':
                        redirigir('../dashboard.html');
                        break;
                    case 'empresa':
                        redirigir('../dashboard.html');
                        break;
                    default:
                        redirigir('../dashboard.html');
                        break;
                }
            } else {
                $error = "Email o contraseña incorrectos.";
            }
        } catch (Exception $e) {
            $error = "Error en el sistema. Por favor, intenta más tarde.";
            error_log($e->getMessage());
        }
    }
}

// Si hay error, redirigir de vuelta al login con el mensaje
if (isset($error)) {
    $_SESSION['login_error'] = $error;
    redirigir('dashboard.html?error=1');
}
?>