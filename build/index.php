<?php
/**
 * Archivo index principal del dominio Clúster Intranet
 * Maneja la redirección inteligente entre login y dashboard
 */

// Registrar en log para debugging
error_log("Index.php accessed from: " . $_SERVER['REQUEST_URI']);

// Inicializar sesión para verificar autenticación
session_start();

// Función para verificar si el usuario está autenticado
function isAuthenticated() {
    // Verificar múltiples formas de autenticación
    
    // 1. Verificar parámetro GET de autenticación
    if (isset($_GET['authenticated']) && $_GET['authenticated'] === 'true') {
        error_log("User authenticated via GET parameter");
        return true;
    }
    
    // 2. Verificar cookie de autenticación
    if (isset($_COOKIE['claut_authenticated']) && $_COOKIE['claut_authenticated'] === 'true') {
        error_log("User authenticated via cookie");
        return true;
    }
    
    // 3. Verificar sesión PHP
    if (isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
        error_log("User authenticated via PHP session: " . $_SESSION['usuario_id']);
        return true;
    }
    
    error_log("User not authenticated - redirecting to login");
    return false;
}

// Verificar si se solicita específicamente el dashboard
$requestDashboard = isset($_GET['dashboard']) || isset($_GET['home']);

// Decidir redirección basada en autenticación
if (isAuthenticated() || $requestDashboard) {
    // Usuario autenticado o solicita dashboard específicamente
    error_log("Serving dashboard.html");
    include 'dashboard.html';
    exit;
} else {
    // Usuario no autenticado - redirigir al login
    error_log("Redirecting to login");
    header('Location: ./pages/sign-in.html', true, 302);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clúster Intranet - Redirigiendo...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .loader {
            text-align: center;
        }
        .spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <h2>Clúster Intranet</h2>
        <p>Verificando autenticación...</p>
    </div>

    <script>
        // Verificación adicional del lado del cliente
        function checkClientAuth() {
            // Verificar localStorage
            const token = localStorage.getItem('clúster_token');
            const user = localStorage.getItem('clúster_user');
            
            if (token && user) {
                try {
                    const userData = JSON.parse(user);
                    if (userData.rol === 'admin') {
                        // Es admin, redirigir al panel de admin
                        window.location.href = 'admin/admin-dashboard.php';
                    } else {
                        // Usuario normal, redirigir directamente al dashboard
                        window.location.href = 'dashboard.html';
                    }
                    return;
                } catch (e) {
                    console.warn('Datos de usuario corruptos en localStorage');
                }
            }
            
            // No hay autenticación válida, redirigir al login
            window.location.href = './pages/sign-in.html';
        }
        
        // Ejecutar verificación después de un breve delay
        setTimeout(checkClientAuth, 1000);
    </script>
</body>
</html>