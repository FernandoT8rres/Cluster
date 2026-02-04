<?php
// redirect-handler.php
// Archivo para manejar las redirecciones del sistema

// Verificar si es la página raíz
if ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php') {
    // Verificar si hay sesión activa o parámetros de autenticación
    session_start();
    
    // Si no hay sesión activa, redirigir al login
    if (!isset($_SESSION['user_id']) && !isset($_GET['authenticated'])) {
        header('Location: /pages/sign-in.html');
        exit();
    }
}

// Si acceden directamente al dashboard.html sin autenticación
if (strpos($_SERVER['REQUEST_URI'], 'dashboard.html') !== false) {
    session_start();
    
    // Verificar autenticación
    if (!isset($_SESSION['user_id']) && !isset($_GET['authenticated'])) {
        header('Location: /pages/sign-in.html');
        exit();
    }
}

// Función para verificar token JWT
function verifyJWTToken($token) {
    // Esta función debería verificar el token JWT
    // Por ahora, simplemente verificamos si existe
    return !empty($token);
}

// Si hay parámetros de token en la URL, validar
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    if (verifyJWTToken($token)) {
        session_start();
        $_SESSION['authenticated'] = true;
        $_SESSION['token'] = $token;
        
        // Limpiar URL y redirigir
        $clean_url = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: $clean_url");
        exit();
    }
}
?>
