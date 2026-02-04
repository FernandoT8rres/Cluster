<?php
// gestionar_usuarios.php - Panel para gestionar usuarios registrados
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/config/database.php';
} catch (Exception $e) {
    die('Error cargando database.php: ' . $e->getMessage());
}

// Iniciar sesión básica
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if ($action === 'crear_admin') {
        // Crear usuario administrador
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Datos predeterminados para el admin
            $admin_email = 'administrador@clúster.com';
            $admin_password = 'admin123';
            
            // Verificar si ya existe un admin con este email
            $stmt = $conn->prepare("SELECT id FROM usuarios_perfil WHERE email = ?");
            $stmt->execute([$admin_email]);
            $existing_admin = $stmt->fetchAll();
            
            if (empty($existing_admin)) {
                // Crear el administrador directamente en la base de datos
                $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO usuarios_perfil (nombre, apellidos, email, password, telefono, rol) VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    'Administrador',
                    'Sistema', 
                    $admin_email,
                    $hashed_password,
                    '0000000000',
                    'admin'
                ]);
                
                $message = $result ? 'Administrador creado exitosamente. Email: administrador@clúster.com, Password: admin123' : 'Error al crear administrador.';
            } else {
                $message = 'Ya existe un administrador con el email administrador@clúster.com';
                $result = false;
            }
        } catch (Exception $e) {
            $message = 'Error al crear administrador: ' . $e->getMessage();
            $result = false;
        }
    } elseif ($user_id > 0) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            switch ($action) {
                case 'activar':
                    $stmt = $conn->prepare("UPDATE usuarios_perfil SET estado_usuario = 'activo', ultima_actividad = NOW() WHERE id = ?");
                    $result = $stmt->execute([$user_id]);
                    $message = $result ? 'Usuario activado exitosamente.' : 'Error al activar usuario.';
                    break;
                    
                case 'desactivar':
                    $stmt = $conn->prepare("UPDATE usuarios_perfil SET estado_usuario = 'inactivo' WHERE id = ?");
                    $result = $stmt->execute([$user_id]);
                    $message = $result ? 'Usuario desactivado exitosamente.' : 'Error al desactivar usuario.';
                    break;
                    
                case 'eliminar':
                    $stmt = $conn->prepare("DELETE FROM usuarios_perfil WHERE id = ?");
                    $result = $stmt->execute([$user_id]);
                    $message = $result ? 'Usuario eliminado exitosamente.' : 'Error al eliminar usuario.';
                    break;
                    
                case 'editar':
                    // Procesar edición de usuario con todos los campos del sign-up
                    $nombre = trim($_POST['nombre'] ?? '');
                    $apellidos = trim($_POST['apellidos'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $telefono = trim($_POST['telefono'] ?? '');
                    $rol = $_POST['rol'] ?? 'empleado';
                    $nueva_password = trim($_POST['nueva_password'] ?? '');
                    $confirmar_password = trim($_POST['confirmar_password'] ?? '');

                    // Campos adicionales del sign-up
                    $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
                    $nombre_empresa = trim($_POST['nombre_empresa'] ?? '');
                    $biografia = trim($_POST['biografia'] ?? '');
                    $direccion = trim($_POST['direccion'] ?? '');
                    $ciudad = trim($_POST['ciudad'] ?? '');
                    $estado = trim($_POST['estado'] ?? '');
                    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
                    $pais = trim($_POST['pais'] ?? 'México');
                    $telefono_emergencia = trim($_POST['telefono_emergencia'] ?? '');
                    $contacto_emergencia = trim($_POST['contacto_emergencia'] ?? '');
                    $empresa_id = !empty($_POST['empresa_id']) ? intval($_POST['empresa_id']) : null;
                    
                    // Validaciones básicas
                    if (empty($nombre) || empty($email)) {
                        $message = 'Nombre y email son obligatorios.';
                        $result = false;
                        break;
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $message = 'Email no válido.';
                        $result = false;
                        break;
                    }
                    
                    // Verificar si el email ya existe (excepto para el usuario actual)
                    $stmt = $conn->prepare("SELECT id FROM usuarios_perfil WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetch()) {
                        $message = 'El email ya está en uso por otro usuario.';
                        $result = false;
                        break;
                    }
                    
                    // Inicializar debug_info
                    $debug_info = "";
                    
                    // Validar cambio de contraseña si se proporcionó
                    $password_hash = null;
                    if (!empty($nueva_password)) {
                        if (strlen($nueva_password) < 6) {
                            $message = 'La nueva contraseña debe tener al menos 6 caracteres.';
                            $result = false;
                            break;
                        }
                        
                        if ($nueva_password !== $confirmar_password) {
                            $message = 'Las contraseñas no coinciden.';
                            $result = false;
                            break;
                        }
                        
                        $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
                        $debug_info .= " [DEBUG: Contraseña será actualizada]";
                    }
                    
                    // Procesar imagen si se subió
                    $avatar_path = null;
                    
                    // Debug: mostrar información de archivos
                    if (isset($_FILES['avatar'])) {
                        $debug_info .= " [DEBUG: Avatar file - name: " . $_FILES['avatar']['name'] . ", error: " . $_FILES['avatar']['error'] . ", size: " . $_FILES['avatar']['size'] . "]";
                    } else {
                        $debug_info .= " [DEBUG: No se recibió archivo avatar]";
                    }
                    
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/avatars/';
                        
                        // Crear directorio si no existe
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $file_info = pathinfo($_FILES['avatar']['name']);
                        $extension = strtolower($file_info['extension']);
                        
                        // Validar tipo de archivo
                        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                        if (!in_array($extension, $allowed_types)) {
                            $message = 'Solo se permiten imágenes JPG, PNG o GIF.';
                            $result = false;
                            break;
                        }
                        
                        // Validar tamaño (máximo 5MB)
                        if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
                            $message = 'La imagen no puede superar los 5MB.';
                            $result = false;
                            break;
                        }
                        
                        // Generar nombre único
                        $avatar_filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
                        $avatar_path = $upload_dir . $avatar_filename;
                        
                        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                            $message = 'Error al subir la imagen.';
                            $result = false;
                            break;
                        } else {
                            $debug_info .= " [DEBUG: Imagen guardada en: $avatar_path]";
                        }
                    }
                    
                    // Actualizar usuario con todos los campos
                    if ($avatar_path && $password_hash) {
                        // Con nueva imagen y contraseña
                        $debug_info .= " [DEBUG: Actualizando con avatar y contraseña]";
                        $stmt = $conn->prepare("UPDATE usuarios_perfil SET nombre = ?, apellidos = ?, email = ?, telefono = ?, rol = ?, avatar = ?, password = ?, fecha_nacimiento = ?, nombre_empresa = ?, biografia = ?, direccion = ?, ciudad = ?, estado = ?, codigo_postal = ?, pais = ?, telefono_emergencia = ?, contacto_emergencia = ?, empresa_id = ? WHERE id = ?");
                        $result = $stmt->execute([$nombre, $apellidos, $email, $telefono, $rol, $avatar_path, $password_hash, $fecha_nacimiento, $nombre_empresa, $biografia, $direccion, $ciudad, $estado, $codigo_postal, $pais, $telefono_emergencia, $contacto_emergencia, $empresa_id, $user_id]);
                    } elseif ($avatar_path) {
                        // Solo nueva imagen
                        $debug_info .= " [DEBUG: Actualizando con avatar: $avatar_path]";
                        $stmt = $conn->prepare("UPDATE usuarios_perfil SET nombre = ?, apellidos = ?, email = ?, telefono = ?, rol = ?, avatar = ?, fecha_nacimiento = ?, nombre_empresa = ?, biografia = ?, direccion = ?, ciudad = ?, estado = ?, codigo_postal = ?, pais = ?, telefono_emergencia = ?, contacto_emergencia = ?, empresa_id = ? WHERE id = ?");
                        $result = $stmt->execute([$nombre, $apellidos, $email, $telefono, $rol, $avatar_path, $fecha_nacimiento, $nombre_empresa, $biografia, $direccion, $ciudad, $estado, $codigo_postal, $pais, $telefono_emergencia, $contacto_emergencia, $empresa_id, $user_id]);
                    } elseif ($password_hash) {
                        // Solo nueva contraseña
                        $debug_info .= " [DEBUG: Actualizando con nueva contraseña]";
                        $stmt = $conn->prepare("UPDATE usuarios_perfil SET nombre = ?, apellidos = ?, email = ?, telefono = ?, rol = ?, password = ?, fecha_nacimiento = ?, nombre_empresa = ?, biografia = ?, direccion = ?, ciudad = ?, estado = ?, codigo_postal = ?, pais = ?, telefono_emergencia = ?, contacto_emergencia = ?, empresa_id = ? WHERE id = ?");
                        $result = $stmt->execute([$nombre, $apellidos, $email, $telefono, $rol, $password_hash, $fecha_nacimiento, $nombre_empresa, $biografia, $direccion, $ciudad, $estado, $codigo_postal, $pais, $telefono_emergencia, $contacto_emergencia, $empresa_id, $user_id]);
                    } else {
                        // Sin cambio de imagen ni contraseña
                        $debug_info .= " [DEBUG: Actualizando datos completos sin avatar ni contraseña]";
                        $stmt = $conn->prepare("UPDATE usuarios_perfil SET nombre = ?, apellidos = ?, email = ?, telefono = ?, rol = ?, fecha_nacimiento = ?, nombre_empresa = ?, biografia = ?, direccion = ?, ciudad = ?, estado = ?, codigo_postal = ?, pais = ?, telefono_emergencia = ?, contacto_emergencia = ?, empresa_id = ? WHERE id = ?");
                        $result = $stmt->execute([$nombre, $apellidos, $email, $telefono, $rol, $fecha_nacimiento, $nombre_empresa, $biografia, $direccion, $ciudad, $estado, $codigo_postal, $pais, $telefono_emergencia, $contacto_emergencia, $empresa_id, $user_id]);
                    }
                    
                    $debug_info .= " [DEBUG: Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "]";
                    
                    if ($result) {
                        $message = 'Usuario actualizado exitosamente.' . $debug_info;
                    } else {
                        $message = 'Error al actualizar usuario.' . $debug_info;
                    }
                    break;
                    
                default:
                    $message = 'Acción no válida.';
                    $result = false;
            }
        } catch (Exception $e) {
            $message = 'Error al procesar la acción: ' . $e->getMessage();
            $result = false;
        }
    }
}

// Obtener usuarios
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT u.id, u.nombre, u.apellidos, u.email, u.telefono, u.rol, 
               u.estado_usuario,
               CASE WHEN u.ultima_actividad IS NOT NULL THEN 1 ELSE 0 END as online_status, 
               COALESCE(u.fecha_ingreso, u.created_at) as fecha_registro, u.user_id, e.nombre_empresa,
               u.departamento
        FROM usuarios_perfil u
        LEFT JOIN empresas_convenio e ON u.user_id = e.id
        ORDER BY u.fecha_ingreso DESC
    ");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al cargar usuarios: " . $e->getMessage();
    $usuarios = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Clúster</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
        }

        .modal-backdrop { backdrop-filter: blur(5px); }
        .slide-in { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0;
        }
        
        .content-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }
        
        .header-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .header-stat-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .users-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .users-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-activo {
            background: #d1f2eb;
            color: #0c5460;
        }
        
        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-inactivo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .search-box {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 300px;
        }
        
        @media (max-width: 768px) {
            .users-table {
                font-size: 0.9rem;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .toolbar {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-box {
                width: 100%;
            }
        }

        /* Porsche-inspired Design System - Responsive Sidebar */
        :root {
            /* Primary Colors - Inspired by Porsche */
            --porsche-black: #1a1a1a;
            --porsche-charcoal: #2d2d2d;
            --porsche-silver: #8a8a8a;
            --porsche-white: #ffffff;
            --porsche-light-gray: #f5f5f5;
            --porsche-accent: #c9302c;
            /* Typography */
            --porsche-font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --porsche-radius-lg: 12px;
        }

        /* === RESPONSIVE SIDEBAR FUNCTIONALITY === */

        /* Porsche-inspired Sidebar */
        .porsche-sidebar {
            background: linear-gradient(180deg, var(--porsche-black) 0%, var(--porsche-charcoal) 100%) !important;
            border-radius: var(--porsche-radius-lg) !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12) !important;
            height: calc(100vh - 2rem) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            font-family: var(--porsche-font) !important;
        }

        .porsche-sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .porsche-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .porsche-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
        }

        .porsche-sidebar .porsche-logo {
            color: var(--porsche-white) !important;
            font-weight: 700 !important;
            font-size: 1.5rem !important;
        }

        .porsche-nav-item {
            color: rgba(255, 255, 255, 0.8) !important;
            border-radius: 12px !important;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
            font-weight: 500 !important;
        }

        .porsche-nav-item:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: var(--porsche-white) !important;
            transform: translateX(4px) !important;
        }

        .porsche-nav-item.active {
            background: rgba(199, 37, 43, 0.2) !important;
            color: var(--porsche-white) !important;
            border-left: 3px solid #C7252B !important;
        }

        /* Sidebar Overlay */
        .sidenav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 998;
            backdrop-filter: blur(4px);
        }

        .sidenav-overlay.show {
            opacity: 1;
            visibility: visible;
            z-index: 998;
        }

        /* Close button styling */
        #sidebarCloseBtn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        #sidebarCloseBtn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        /* Sidebar always retractable - All screens */
        .porsche-sidebar {
            transform: translateX(-100%) !important;
            z-index: 999 !important;
            margin: 0 !important;
            top: 0 !important;
            left: 0 !important;
            border-radius: 0 !important;
            width: 280px !important;
            max-width: 280px !important;
            height: 100vh !important;
            position: fixed !important;
            display: block !important;
        }

        .porsche-sidebar.sidenav-show {
            transform: translateX(0) !important;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.3);
        }

        /* Main content always without sidebar margin */
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }

        .container {
            margin-left: 0 !important;
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        /* Desktop adjustments */
        @media (min-width: 1280px) {
            .container {
                padding-left: 2rem !important;
                padding-right: 2rem !important;
            }
        }

        /* Force sidebar behavior on all screens - Override any conflicting styles */
        .porsche-sidebar.sidebar-enhanced {
            transform: translateX(-100%) !important;
        }

        .porsche-sidebar.sidebar-enhanced.sidenav-show {
            transform: translateX(0) !important;
        }

        /* Override any Tailwind classes that might interfere */
        aside.porsche-sidebar {
            left: 0 !important;
            margin-left: 0 !important;
        }

        /* Ensure hamburger button works on all screens */
        @media (min-width: 1280px) {
            [sidenav-trigger] {
                display: block !important;
                visibility: visible !important;
            }
        }

        /* Hamburger button styling */
        .hamburger-btn {
            transition: all 0.3s ease;
        }

        .hamburger-btn:hover {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.1) !important;
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidenav-overlay" id="sidenavOverlay"></div>
    <!-- Porsche Sidebar -->
    <aside id="porscheSidebar"
        class="porsche-sidebar sidebar-enhanced fixed inset-y-0 flex-wrap items-center justify-between block w-full p-0 m-0 overflow-y-auto overflow-x-hidden antialiased transition-transform duration-200 border-0 max-w-64 ease-nav-brand z-990"
        aria-expanded="false">
        <div class="h-48 py-6 relative mb-4">
            <!-- Botón de cerrar para todas las pantallas -->
            <button class="absolute top-4 right-4 text-white hover:text-gray-300 text-2xl z-50 p-3 bg-black/30 rounded-lg hover:bg-black/50 transition-all"
                    id="sidebarCloseBtn"
                    sidenav-close
                    aria-label="Cerrar menú">
                <i class="fas fa-times"></i>
            </button>
            <a class="porsche-logo block px-6 py-4 m-0 text-center whitespace-nowrap" href="dashboard.html"
              target="_blank">
                <img src="../assets/img/apple-icon.png"
                  class="block mx-auto h-32 w-32 max-w-full transition-all duration-200 ease-nav-brand mb-4"
                  alt="main_logo" />
                <span class="block text-lg transition-all duration-200 ease-nav-brand text-white font-semibold mb-4">Clúster Admin</span>
            </a>
        </div>
        <hr class="h-px mt-4 mb-4 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent" />
        <div class="items-center block w-auto max-h-screen overflow-auto h-sidenav grow basis-full">
            <ul class="flex flex-col pl-0 mb-0 pt-4">
                <!-- Dashboard -->
                <li class="mt-2 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="dashboard.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-tachometer-alt text-blue-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Dashboard</span>
                    </a>
                </li>
                <!-- admin panel -->
                <li class="mt-2 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="admin-panel.html?login=success">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-tachometer-alt text-green-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Panel Administrador</span>
                    </a>
                </li>
                <!-- Usuarios - ACTIVE -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item active py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="gestionar_usuarios.php">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-users text-emerald-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Gestión de Usuarios</span>
                    </a>
                </li>
                <!-- Empresas -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="demo_empresas.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-building text-purple-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Empresas</span>
                    </a>
                </li>
                <!-- Descuentos -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="demo_descuentos.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-tags text-red-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Descuentos</span>
                    </a>
                </li>
                <!-- Comités -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="demo_comite.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-users text-blue-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Comités</span>
                    </a>
                </li>
                <!-- Eventos -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="demo_evento.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-calendar text-orange-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Eventos</span>
                    </a>
                </li>
                <!-- Gestión Gráfico -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="demo_gestion_grafico.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-chart-bar text-teal-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Gestión Gráfico</span>
                    </a>
                </li>
                <!-- Estadísticas Dinámicas -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="demo_estadisticasdinamicas.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-chart-line text-indigo-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Estadísticas Dinámicas</span>
                    </a>
                </li>
                <!-- Visitantes -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="demo_visitante.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-eye text-cyan-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Visitantes</span>
                    </a>
                </li>
                <!-- Banners -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="admin/banner-admin-mejorado.php">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-images text-pink-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Banners</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <div class="main-content">
    <!-- Header -->
    <header class="bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg" style="background: linear-gradient(135deg, #C7252B 0%, #A01E24 100%);">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <button class="hamburger-btn bg-white/10 hover:bg-white/20 p-3 rounded-lg mr-4"
                            sidenav-trigger
                            id="hamburgerBtn"
                            aria-label="Abrir menú">
                        <i class="fas fa-bars text-white"></i>
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold">
                            <i class="fas fa-users mr-3"></i>Gestión de Usuarios
                        </h1>
                        <p class="text-red-200 mt-1">Panel de Administración de Usuarios del Sistema</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="location.href='dashboard.html'" class="bg-white text-red-600 px-4 py-2 rounded-lg hover:bg-red-50 transition">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Estadísticas -->
    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <?php
            $stats = [
                'total' => count($usuarios),
                'activos' => count(array_filter($usuarios, fn($u) => ($u['estado_usuario'] ?? '') === 'activo')),
                'inactivos' => count(array_filter($usuarios, fn($u) => ($u['estado_usuario'] ?? '') !== 'activo')),
                'administradores' => count(array_filter($usuarios, fn($u) => $u['rol'] === 'admin'))
            ];
            ?>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Usuarios</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                    </div>
                    <i class="fas fa-users text-3xl" style="color: #C7252B;"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Usuarios Activos</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['activos']; ?></p>
                    </div>
                    <i class="fas fa-user-check text-3xl text-green-500"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Inactivos</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['inactivos']; ?></p>
                    </div>
                    <i class="fas fa-user-times text-3xl text-red-500"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Administradores</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['administradores']; ?></p>
                    </div>
                    <i class="fas fa-user-shield text-3xl text-purple-500"></i>
                </div>
            </div>
        </div>

    <div class="container mx-auto px-4 max-w-7xl">
        
        <?php if (isset($message)): ?>
            <div class="bg-<?php echo $result ? 'green' : 'red'; ?>-100 border border-<?php echo $result ? 'green' : 'red'; ?>-400 text-<?php echo $result ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-<?php echo $result ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Controles principales -->
        <div class="flex justify-center space-x-4 mb-6">
            <a href="pages/sign-up.html" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition flex items-center">
                <i class="fas fa-plus mr-2"></i>Nuevo Usuario
            </a>
            <button onclick="abrirModalMensajeria()" class="text-white px-6 py-2 rounded-lg hover:opacity-90 transition flex items-center" style="background: linear-gradient(135deg, #C7252B 0%, #A01E24 100%);">
                <i class="fas fa-envelope mr-2"></i>Enviar Mensaje
            </button>
        </div>

        <!-- Sección de Notificaciones de Cambios de Perfil -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-clipboard-list mr-3" style="color: #C7252B;"></i>Solicitudes de Cambios de Perfil
                </h2>
                <button onclick="loadProfileNotifications()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar
                </button>
            </div>
            <div id="profileNotificationsContainer">
                <div class="text-center py-10 text-gray-600">
                    <div class="text-2xl mb-2">⏳</div>
                    <p>Cargando notificaciones...</p>
                </div>
            </div>
        </div>

        <!-- Barra de herramientas -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-table mr-3" style="color: #C7252B;"></i>Lista de Usuarios
                </h2>
                <div class="flex items-center space-x-4">
                    <input type="text" id="searchBox" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Buscar por nombre, email o rol..." onkeyup="filterUsers()" style="--tw-ring-color: #C7252B;">
                </div>
            </div>
        
            <!-- Tabla de usuarios -->
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse" id="usersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Empresa</th>
                    <th>Cargo</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 40px; color: #666;">
                            No hay usuarios registrados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php 
                        // Usar el estado real de la base de datos
                        $estado_real = $usuario['estado_usuario'] ?? 'pendiente';
                        $estado_class = 'status-pendiente'; // Default
                        
                        // Determinar clase basada en el estado
                        if ($estado_real === 'activo') {
                            $estado_class = 'status-activo';
                        } elseif ($estado_real === 'inactivo' || $estado_real === 'rechazado') {
                            $estado_class = 'status-inactivo';
                        }
                        ?>
                        <tr>
                            <td><?php echo $usuario['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($usuario['nombre'] . ' ' . ($usuario['apellidos'] ?? '')); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['telefono'] ?? 'N/A'); ?></td>
                            <td>
                                <span style="text-transform: capitalize; font-weight: 500;">
                                    <?php 
                                    $rol_map = ['admin' => 'Administrador', 'empresa' => 'Empresa', 'empleado' => 'Empleado'];
                                    echo $rol_map[$usuario['rol']] ?? $usuario['rol']; 
                                    ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($usuario['nombre_empresa'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($usuario['cargo'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge <?php echo $estado_class; ?>">
                                    <?php echo ucfirst($estado_real); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($usuario['fecha_registro'] && $usuario['fecha_registro'] != '0000-00-00 00:00:00') {
                                    echo date('d/m/Y', strtotime($usuario['fecha_registro'])); 
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <!-- Botón Editar -->
                                    <button type="button" class="btn btn-primary" onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                        ✏️ Editar
                                    </button>

                                    <!-- Botón Restricciones -->
                                    <button type="button" class="btn btn-warning" onclick="abrirModalRestricciones(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?>')">
                                        🔒 Restricciones
                                    </button>
                                    
                                    <?php if ($estado_real !== 'activo'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="activar">
                                            <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" class="btn btn-success" onclick="return confirm('¿Activar este usuario?')">
                                                ✅ Activar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($estado_real === 'activo'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="desactivar">
                                            <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" class="btn btn-secondary" onclick="return confirm('¿Desactivar este usuario?')">
                                                ❌ Desactivar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="user_id" value="<?php echo $usuario['id']; ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('¿ELIMINAR permanentemente este usuario? Esta acción no se puede deshacer.')">
                                            🗑️ Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        
    </div>
    
    <script>
        function filterUsers() {
            const searchTerm = document.getElementById('searchBox').value.toLowerCase();
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                // Skip if it's the "no users" row
                if (cells.length === 1 && cells[0].getAttribute('colspan')) {
                    continue;
                }
                
                for (let j = 1; j < 5; j++) { // Search in name, email, phone, role
                    if (cells[j] && cells[j].textContent.toLowerCase().includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
        }
        
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Funciones para el modal de edición
        function abrirModalEditar(usuario) {
            console.log('Abriendo modal para usuario:', usuario);
            
            // Llenar los campos del formulario
            document.getElementById('edit_user_id').value = usuario.id;
            document.getElementById('edit_nombre').value = usuario.nombre || '';
            document.getElementById('edit_apellidos').value = usuario.apellidos || '';
            document.getElementById('edit_email').value = usuario.email || '';
            document.getElementById('edit_telefono').value = usuario.telefono || '';
            document.getElementById('edit_rol').value = usuario.rol || 'empleado';
            document.getElementById('edit_fecha_nacimiento').value = usuario.fecha_nacimiento || '';
            document.getElementById('edit_nombre_empresa').value = usuario.nombre_empresa || '';
            document.getElementById('edit_direccion').value = usuario.direccion || '';
            document.getElementById('edit_ciudad').value = usuario.ciudad || '';
            document.getElementById('edit_estado').value = usuario.estado || '';
            document.getElementById('edit_codigo_postal').value = usuario.codigo_postal || '';
            document.getElementById('edit_pais').value = usuario.pais || 'México';
            document.getElementById('edit_telefono_emergencia').value = usuario.telefono_emergencia || '';
            document.getElementById('edit_contacto_emergencia').value = usuario.contacto_emergencia || '';
            document.getElementById('edit_biografia').value = usuario.biografia || '';
            document.getElementById('edit_empresa_id').value = usuario.empresa_id || '';
            
            // Limpiar campos de contraseña
            document.getElementById('nueva_password').value = '';
            document.getElementById('confirmar_password').value = '';
            document.getElementById('mostrar_passwords').checked = false;
            
            // Mostrar imagen actual si existe
            const currentImage = document.getElementById('current_avatar');
            const imagePreview = document.getElementById('avatar_preview');
            
            if (usuario.avatar && usuario.avatar.trim() !== '') {
                currentImage.src = usuario.avatar;
                currentImage.style.display = 'block';
                imagePreview.style.display = 'block';
            } else {
                currentImage.src = '';
                currentImage.style.display = 'none';
                imagePreview.style.display = 'none';
            }
            
            // Cargar opciones de empresas
            cargarEmpresasSelect();

            // Mostrar el modal
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function cerrarModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').style.display = 'none';
            // Limpiar campos de contraseña al cerrar
            if (document.getElementById('nueva_password')) {
                document.getElementById('nueva_password').value = '';
                document.getElementById('confirmar_password').value = '';
                document.getElementById('mostrar_passwords').checked = false;
            }
        }
        
        function togglePasswordVisibility() {
            const checkbox = document.getElementById('mostrar_passwords');
            const password1 = document.getElementById('nueva_password');
            const password2 = document.getElementById('confirmar_password');
            
            if (checkbox.checked) {
                password1.type = 'text';
                password2.type = 'text';
            } else {
                password1.type = 'password';
                password2.type = 'password';
            }
        }
        
        function previewAvatar(input) {
            const preview = document.getElementById('current_avatar');
            const previewContainer = document.getElementById('avatar_preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                cerrarModal();
            }
        }

        // Funciones para gestión de notificaciones de perfil
        async function loadProfileNotifications() {
            try {
                const response = await fetch('./api_notificaciones_perfil.php?action=listar');
                const result = await response.json();

                if (result.success) {
                    renderProfileNotifications(result.notificaciones);
                } else {
                    document.getElementById('profileNotificationsContainer').innerHTML =
                        '<div style="text-align: center; padding: 20px; color: #e74c3c;">❌ Error al cargar notificaciones</div>';
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('profileNotificationsContainer').innerHTML =
                    '<div style="text-align: center; padding: 20px; color: #e74c3c;">❌ Error de conexión</div>';
            }
        }

        function renderProfileNotifications(notificaciones) {
            const container = document.getElementById('profileNotificationsContainer');

            if (notificaciones.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #666;">
                        <div style="font-size: 48px; margin-bottom: 15px;">✅</div>
                        <p>No hay solicitudes de cambio pendientes</p>
                    </div>
                `;
                return;
            }

            let html = `
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                        <thead>
                            <tr style="background-color: #f8f9fa;">
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #495057; font-weight: 600;">Usuario</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #495057; font-weight: 600;">Campo</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #495057; font-weight: 600;">Valor Anterior</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #495057; font-weight: 600;">Valor Nuevo</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #495057; font-weight: 600;">Fecha</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #495057; font-weight: 600;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            notificaciones.forEach(notif => {
                const fieldName = getFieldDisplayName(notif.campo_modificado);
                const fecha = new Date(notif.fecha_solicitud).toLocaleDateString('es-ES', {
                    year: 'numeric', month: 'short', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });

                html += `
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 12px;">
                            <div style="font-weight: 600; color: #333;">${notif.nombre} ${notif.apellidos || ''}</div>
                            <div style="font-size: 0.85rem; color: #666;">${notif.email}</div>
                        </td>
                        <td style="padding: 12px;">
                            <span style="background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 12px; font-size: 0.85rem; font-weight: 500;">
                                ${fieldName}
                            </span>
                        </td>
                        <td style="padding: 12px; max-width: 150px; word-wrap: break-word; color: #666;">
                            ${notif.valor_anterior || '<em style="color: #999;">Vacío</em>'}
                        </td>
                        <td style="padding: 12px; max-width: 150px; word-wrap: break-word; font-weight: 600; color: #333;">
                            ${notif.valor_nuevo || '<em style="color: #999;">Vacío</em>'}
                        </td>
                        <td style="padding: 12px; font-size: 0.9rem; color: #666;">
                            ${fecha}
                        </td>
                        <td style="padding: 12px;">
                            <div style="display: flex; gap: 5px;">
                                <button onclick="approveProfileChange(${notif.id})"
                                        style="background: #4CAF50; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                                    ✓ Aprobar
                                </button>
                                <button onclick="rejectProfileChange(${notif.id})"
                                        style="background: #f44336; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                                    ✗ Rechazar
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = html;
        }

        function getFieldDisplayName(field) {
            const fieldNames = {
                'phone': 'Teléfono',
                'birthDate': 'Fecha Nacimiento',
                'department': 'Departamento',
                'position': 'Cargo',
                'bio': 'Biografía',
                'address': 'Dirección',
                'city': 'Ciudad',
                'state': 'Estado',
                'zipCode': 'Código Postal',
                'country': 'País',
                'emergencyPhone': 'Teléfono Emergencia',
                'emergencyContact': 'Contacto Emergencia'
            };
            return fieldNames[field] || field;
        }

        async function approveProfileChange(notificationId) {
            if (!confirm('¿Está seguro de que desea aprobar este cambio?\\n\\nEsta acción aplicará el cambio en la base de datos.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('notificacion_id', notificationId);
                formData.append('revisado_por', 1);

                const response = await fetch('./api_notificaciones_perfil.php?action=aprobar', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Cambio aprobado exitosamente');
                    loadProfileNotifications();
                } else {
                    alert('❌ Error: ' + (result.message || 'Error al aprobar cambio'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error de conexión');
            }
        }

        async function rejectProfileChange(notificationId) {
            const comentarios = prompt('Comentarios para el rechazo (opcional):');
            if (comentarios === null) return; // Usuario canceló

            try {
                const formData = new FormData();
                formData.append('notificacion_id', notificationId);
                formData.append('revisado_por', 1);
                formData.append('comentarios', comentarios);

                const response = await fetch('./api_notificaciones_perfil.php?action=rechazar', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Cambio rechazado');
                    loadProfileNotifications();
                } else {
                    alert('❌ Error: ' + (result.message || 'Error al rechazar cambio'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error de conexión');
            }
        }

        // Cargar notificaciones al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            loadProfileNotifications();
        });
    </script>

    <!-- Modal de Edición -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 modal-backdrop" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full slide-in max-h-[90vh] overflow-hidden">
                <div class="p-6 border-b">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-edit mr-2" style="color: #C7252B;"></i>Editar Usuario
                        </h2>
                        <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
            
                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="editar">
                <input type="hidden" id="edit_user_id" name="user_id" value="">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nombre *</label>
                        <input type="text" id="edit_nombre" name="nombre" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Apellidos</label>
                        <input type="text" id="edit_apellidos" name="apellidos" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email *</label>
                        <input type="email" id="edit_email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>
                    
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Teléfono</label>
                        <input type="tel" id="edit_telefono" name="telefono" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Fecha de Nacimiento</label>
                        <input type="date" id="edit_fecha_nacimiento" name="fecha_nacimiento" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Rol</label>
                        <select id="edit_rol" name="rol" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                            <option value="empleado">Empleado</option>
                            <option value="empresa">Empresa</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nombre de Empresa</label>
                    <input type="text" id="edit_nombre_empresa" name="nombre_empresa" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Dirección</label>
                    <input type="text" id="edit_direccion" name="direccion" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Ciudad</label>
                        <input type="text" id="edit_ciudad" name="ciudad" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Estado</label>
                        <input type="text" id="edit_estado" name="estado" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Código Postal</label>
                        <input type="text" id="edit_codigo_postal" name="codigo_postal" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">País</label>
                        <input type="text" id="edit_pais" name="pais" value="México" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Teléfono Emergencia</label>
                        <input type="tel" id="edit_telefono_emergencia" name="telefono_emergencia" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Contacto Emergencia</label>
                    <input type="text" id="edit_contacto_emergencia" name="contacto_emergencia" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Biografía</label>
                    <textarea id="edit_biografia" name="biografia" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Empresa Asociada</label>
                    <select id="edit_empresa_id" name="empresa_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                        <option value="">Seleccionar empresa</option>
                        <!-- Se cargarán dinámicamente -->
                    </select>
                </div>
                
                <!-- Sección para cambiar contraseña -->
                <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 15px 0; color: #495057; font-size: 16px;">🔐 Cambiar Contraseña (Opcional)</h4>
                    <p style="margin: 0 0 15px 0; color: #6c757d; font-size: 14px;">Deja en blanco si no deseas cambiar la contraseña</p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nueva Contraseña</label>
                            <input type="password" name="nueva_password" id="nueva_password" placeholder="Mínimo 6 caracteres" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Confirmar Contraseña</label>
                            <input type="password" name="confirmar_password" id="confirmar_password" placeholder="Repetir contraseña" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                        </div>
                    </div>
                    
                    <div style="margin-top: 10px;">
                        <input type="checkbox" id="mostrar_passwords" onchange="togglePasswordVisibility()" style="margin-right: 8px;">
                        <label for="mostrar_passwords" style="font-size: 14px; color: #6c757d; cursor: pointer;">Mostrar contraseñas</label>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Foto de Perfil</label>
                    <input type="file" name="avatar" accept="image/*" onchange="previewAvatar(this)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                    <small style="color: #666; font-size: 12px;">Formatos permitidos: JPG, PNG, GIF. Máximo 5MB.</small>
                    
                    <div id="avatar_preview" style="margin-top: 10px; display: none;">
                        <p style="margin: 10px 0 5px 0; font-weight: bold;">Vista previa:</p>
                        <img id="current_avatar" src="" alt="Vista previa" style="max-width: 150px; max-height: 150px; border: 2px solid #ddd; border-radius: 8px; object-fit: cover;">
                    </div>
                </div>
                
                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <button type="button" onclick="cerrarModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                                <i class="fas fa-times mr-2"></i>Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition" style="background-color: #C7252B;">
                                <i class="fas fa-save mr-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para cargar empresas en el select
        async function cargarEmpresasSelect() {
            try {
                const response = await fetch('./api/empresas.php');
                const result = await response.json();

                const select = document.getElementById('edit_empresa_id');

                if (result.success && result.data) {
                    // Limpiar opciones existentes excepto la primera
                    select.innerHTML = '<option value="">Seleccionar empresa</option>';

                    // Agregar opciones de empresas
                    result.data.forEach(empresa => {
                        const option = document.createElement('option');
                        option.value = empresa.id;
                        option.textContent = empresa.nombre;
                        select.appendChild(option);
                    });
                } else {
                    console.warn('No se pudieron cargar las empresas:', result.message);
                }
            } catch (error) {
                console.error('Error al cargar empresas:', error);
                // Fallback: Cargar empresas desde la tabla de convenios
                try {
                    const fallbackResponse = await fetch('./api/empresas-convenio.php');
                    const fallbackResult = await fallbackResponse.json();

                    const select = document.getElementById('edit_empresa_id');

                    if (fallbackResult.success && fallbackResult.data) {
                        select.innerHTML = '<option value="">Seleccionar empresa</option>';

                        fallbackResult.data.forEach(empresa => {
                            const option = document.createElement('option');
                            option.value = empresa.id;
                            option.textContent = empresa.nombre || empresa.razon_social;
                            select.appendChild(option);
                        });
                    }
                } catch (fallbackError) {
                    console.error('Error en fallback de empresas:', fallbackError);
                }
            }
        }
    </script>

    <!-- Modal de Restricciones de Acceso -->
    <div id="modalRestricciones" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-90vh overflow-y-auto">
            <div class="bg-gradient-to-r from-orange-500 to-red-600 text-white px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-lock mr-3 text-lg"></i>
                        <h3 class="text-lg font-semibold">Gestionar Restricciones de Acceso</h3>
                    </div>
                    <button onclick="cerrarModalRestricciones()" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <p class="text-sm opacity-90 mt-2">Usuario: <span id="nombreUsuarioRestricciones"></span></p>
            </div>

            <div class="p-6">
                <div class="mb-6">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                            <div>
                                <h4 class="text-sm font-medium text-yellow-800">Información sobre las restricciones</h4>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Las páginas marcadas como restringidas mostrarán una advertencia al usuario y lo redirigirán automáticamente al dashboard.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formRestricciones">
                    <input type="hidden" id="usuarioIdRestricciones" name="usuario_id">

                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-800 mb-4">Selecciona las páginas a restringir:</h4>

                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="paginas[]" value="eventos" class="mr-3 h-4 w-4 text-orange-600">
                                <div>
                                    <div class="font-medium">Eventos</div>
                                    <div class="text-sm text-gray-500">eventos.html</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="paginas[]" value="documentacion" class="mr-3 h-4 w-4 text-orange-600">
                                <div>
                                    <div class="font-medium">Documentación</div>
                                    <div class="text-sm text-gray-500">documentacion.html</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="paginas[]" value="boletines" class="mr-3 h-4 w-4 text-orange-600">
                                <div>
                                    <div class="font-medium">Boletines</div>
                                    <div class="text-sm text-gray-500">boletines.html</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="paginas[]" value="comites" class="mr-3 h-4 w-4 text-orange-600">
                                <div>
                                    <div class="font-medium">Comités</div>
                                    <div class="text-sm text-gray-500">comites.html</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="paginas[]" value="contacto" class="mr-3 h-4 w-4 text-orange-600">
                                <div>
                                    <div class="font-medium">Contacto</div>
                                    <div class="text-sm text-gray-500">contacto.html</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="paginas[]" value="empresas-convenio" class="mr-3 h-4 w-4 text-orange-600">
                                <div>
                                    <div class="font-medium">Empresas en Convenio</div>
                                    <div class="text-sm text-gray-500">empresas-convenio.html</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="paginas[]" value="descuentos" class="mr-3 h-4 w-4 text-orange-600">
                                <div>
                                    <div class="font-medium">Descuentos</div>
                                    <div class="text-sm text-gray-500">descuentos.html</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="paginas[]" value="profile" class="mr-3 h-4 w-4 text-orange-600">
                                <div>
                                    <div class="font-medium">Perfil</div>
                                    <div class="text-sm text-gray-500">profile.html</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-8 pt-6 border-t">
                        <button type="button" onclick="cerrarModalRestricciones()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                            <i class="fas fa-save mr-2"></i>Guardar Restricciones
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Función para abrir el modal de restricciones
        function abrirModalRestricciones(usuarioId, nombreUsuario) {
            document.getElementById('usuarioIdRestricciones').value = usuarioId;
            document.getElementById('nombreUsuarioRestricciones').textContent = nombreUsuario;
            document.getElementById('modalRestricciones').classList.remove('hidden');

            // Cargar restricciones actuales del usuario
            cargarRestriccionesUsuario(usuarioId);
        }

        // Función para cerrar el modal de restricciones
        function cerrarModalRestricciones() {
            document.getElementById('modalRestricciones').classList.add('hidden');
        }

        // Función para cargar las restricciones actuales del usuario
        function cargarRestriccionesUsuario(usuarioId) {
            fetch('./api/restricciones.php?action=get&usuario_id=' + usuarioId)
                .then(response => response.json())
                .then(data => {
                    // Limpiar checkboxes
                    const checkboxes = document.querySelectorAll('input[name="paginas[]"]');
                    checkboxes.forEach(checkbox => checkbox.checked = false);

                    if (data.success && data.restricciones) {
                        // Marcar las páginas restringidas
                        data.restricciones.forEach(pagina => {
                            const checkbox = document.querySelector(`input[value="${pagina}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                })
                .catch(error => console.error('Error cargando restricciones:', error));
        }

        // Manejar el envío del formulario de restricciones
        document.getElementById('formRestricciones').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const usuarioId = formData.get('usuario_id');
            const paginasRestringidas = formData.getAll('paginas[]');

            // Enviar datos al API
            fetch('./api/restricciones.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save',
                    usuario_id: usuarioId,
                    paginas: paginasRestringidas
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Restricciones guardadas exitosamente');
                    cerrarModalRestricciones();
                } else {
                    alert('Error al guardar restricciones: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar las restricciones');
            });
        });

        // Cerrar modal al hacer clic fuera de él
        document.getElementById('modalRestricciones').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalRestricciones();
            }
        });
    </script>

    </div>

    <script>
        // === SIDEBAR RETRACTABLE FUNCTIONALITY ===
        function initializeSidebar() {
            const sidebar = document.querySelector('.porsche-sidebar');
            const overlay = document.getElementById('sidenavOverlay');
            const hamburgerBtn = document.querySelector('[sidenav-trigger]');
            const closeBtn = document.getElementById('sidebarCloseBtn');

            console.log('Sidebar elements:', {
                sidebar: !!sidebar,
                overlay: !!overlay,
                hamburgerBtn: !!hamburgerBtn,
                closeBtn: !!closeBtn,
                screenWidth: window.innerWidth,
                isDesktop: window.innerWidth >= 1280
            });

            // Log computed styles for debugging
            if (sidebar) {
                const styles = window.getComputedStyle(sidebar);
                console.log('Sidebar computed styles:', {
                    display: styles.display,
                    transform: styles.transform,
                    left: styles.left,
                    position: styles.position,
                    zIndex: styles.zIndex
                });
            }

            if (!sidebar || !overlay || !hamburgerBtn) {
                console.error('Sidebar elements not found:', {
                    sidebar: !!sidebar,
                    overlay: !!overlay,
                    hamburgerBtn: !!hamburgerBtn
                });
                return;
            }

            // Toggle sidebar function
            function toggleSidebar(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                const isVisible = sidebar.classList.contains('sidenav-show');
                console.log('Toggle sidebar - currently visible:', isVisible);
                if (!isVisible) {
                    // Open sidebar
                    console.log('Opening sidebar');
                    sidebar.classList.add('sidenav-show');
                    sidebar.setAttribute('aria-expanded', 'true');
                    overlay.classList.add('show');
                    document.body.style.overflow = 'hidden';
                    // Focus management for accessibility
                    const firstLink = sidebar.querySelector('.porsche-nav-item');
                    if (firstLink) {
                        setTimeout(() => firstLink.focus(), 100);
                    }
                } else {
                    // Close sidebar
                    console.log('Closing sidebar');
                    closeSidebarInternal();
                }
            }

            // Close sidebar function
            function closeSidebarInternal() {
                console.log('Executing closeSidebarInternal');
                sidebar.classList.remove('sidenav-show');
                sidebar.setAttribute('aria-expanded', 'false');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
                // Return focus to hamburger button
                if (hamburgerBtn) {
                    hamburgerBtn.focus();
                }
            }

            // Event listeners
            console.log('Adding click event to hamburger button');
            hamburgerBtn.addEventListener('click', toggleSidebar);
            hamburgerBtn.addEventListener('touchstart', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSidebar(e);
            }, { passive: false });

            if (overlay) {
                overlay.addEventListener('click', closeSidebarInternal);
                overlay.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    closeSidebarInternal();
                }, { passive: false });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSidebarInternal();
                });
                closeBtn.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSidebarInternal();
                }, { passive: false });
            }

            // Close on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebarInternal();
                }
            });

            // Handle window resize - Keep sidebar closed on all screen sizes
            window.addEventListener('resize', function() {
                // Optional: could add logic here if needed
            });

            // Swipe to close functionality
            let touchStartX = 0;
            let touchStartY = 0;
            sidebar.addEventListener('touchstart', function(e) {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
            }, { passive: true });

            sidebar.addEventListener('touchmove', function(e) {
                if (!touchStartX) return;
                const currentX = e.touches[0].clientX;
                const currentY = e.touches[0].clientY;
                const diffX = touchStartX - currentX;
                const diffY = Math.abs(touchStartY - currentY);

                // Swipe left to close
                if (diffX > 50 && diffY < 100) {
                    closeSidebarInternal();
                    touchStartX = 0;
                    touchStartY = 0;
                }
            }, { passive: true });

            // Keyboard navigation in sidebar
            sidebar.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSidebarInternal();
                }
                // Arrow navigation
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const navItems = Array.from(sidebar.querySelectorAll('.porsche-nav-item:not([style*="display: none"])'));
                    const currentIndex = navItems.indexOf(document.activeElement);
                    if (e.key === 'ArrowDown') {
                        const nextIndex = (currentIndex + 1) % navItems.length;
                        navItems[nextIndex].focus();
                    } else {
                        const prevIndex = currentIndex <= 0 ? navItems.length - 1 : currentIndex - 1;
                        navItems[prevIndex].focus();
                    }
                }
            });

            console.log('✅ Sidebar functionality initialized successfully');
        }

        // Legacy function compatibility
        function closeSidebar() {
            const sidebar = document.querySelector('.porsche-sidebar');
            const overlay = document.getElementById('sidenavOverlay');
            if (sidebar && overlay) {
                sidebar.classList.remove('sidenav-show');
                sidebar.setAttribute('aria-expanded', 'false');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        }

        // Initialize sidebar when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initializeSidebar, 100);
        });
    </script>

    <!-- Modal de Mensajería para Usuarios -->
    <div id="modalMensajeria" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <!-- Header del Modal -->
            <div class="text-white px-6 py-4" style="background: linear-gradient(135deg, #C7252B 0%, #A01E24 100%);">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-envelope-open mr-3 text-xl"></i>
                        <div>
                            <h2 class="text-xl font-bold">Enviar Mensaje a Usuarios</h2>
                            <p class="text-red-100 text-sm">Sistema de mensajería para administradores</p>
                        </div>
                    </div>
                    <button onclick="cerrarModalMensajeria()" class="text-white hover:bg-white/20 rounded-full p-2 transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Contenido del Modal -->
            <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                <form id="mensajeriaForm" class="space-y-6">
                    <!-- Selección de Destinatarios -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="destinatarioSelect" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-users mr-1" style="color: #C7252B;"></i>
                                Destinatarios *
                            </label>
                            <select id="destinatarioSelect" name="destinatario" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="focus:ring-color: #C7252B;">
                                <option value="">Selecciona destinatarios...</option>
                                <option value="todos">Todos los usuarios activos</option>
                                <optgroup label="Usuarios específicos" id="usuariosEspecificos">
                                    <!-- Se llenará dinámicamente -->
                                </optgroup>
                            </select>
                        </div>

                        <div>
                            <label for="tipoMensajeSelect" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-tag mr-1" style="color: #C7252B;"></i>
                                Tipo de Mensaje
                            </label>
                            <select id="tipoMensajeSelect" name="tipo_mensaje"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent"
                                    style="focus:ring-color: #C7252B;"
                                    onchange="cambiarTipoMensaje()">
                                <option value="texto">Mensaje de Texto</option>
                                <option value="link">Enlace/URL</option>
                                <option value="imagen">Imagen</option>
                                <option value="documento">Documento</option>
                            </select>
                        </div>
                    </div>

                    <!-- Asunto -->
                    <div>
                        <label for="asuntoMensaje" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-heading mr-1" style="color: #C7252B;"></i>
                            Asunto del Mensaje *
                        </label>
                        <input type="text" id="asuntoMensaje" name="asunto" required
                               placeholder="Escriba el asunto del mensaje..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent"
                               style="focus:ring-color: #C7252B;">
                    </div>

                    <!-- Contenido Dinámico según Tipo de Mensaje -->
                    <div id="contenidoMensaje">
                        <!-- Contenido de Texto (por defecto) -->
                        <div id="contenidoTexto" class="mensaje-content">
                            <label for="textoMensaje" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-edit mr-1" style="color: #C7252B;"></i>
                                Contenido del Mensaje *
                            </label>
                            <textarea id="textoMensaje" name="contenido_texto" rows="6" required
                                      placeholder="Escriba aquí el contenido del mensaje..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent resize-y"
                                      style="focus:ring-color: #C7252B;"></textarea>
                        </div>

                        <!-- Contenido de Enlace -->
                        <div id="contenidoLink" class="mensaje-content hidden space-y-3">
                            <div>
                                <label for="linkUrl" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-link mr-1" style="color: #C7252B;"></i>
                                    URL del Enlace *
                                </label>
                                <input type="url" id="linkUrl" name="link_url"
                                       placeholder="https://ejemplo.com"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent"
                                       style="focus:ring-color: #C7252B;">
                            </div>
                            <div>
                                <label for="linkTexto" class="block text-sm font-medium text-gray-700 mb-1">
                                    Texto del Enlace
                                </label>
                                <input type="text" id="linkTexto" name="link_texto"
                                       placeholder="Texto que aparecerá como enlace"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent"
                                       style="focus:ring-color: #C7252B;">
                            </div>
                            <div>
                                <label for="linkDescripcion" class="block text-sm font-medium text-gray-700 mb-1">
                                    Descripción
                                </label>
                                <textarea id="linkDescripcion" name="link_descripcion" rows="3"
                                          placeholder="Descripción opcional del enlace"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent resize-y"
                                          style="focus:ring-color: #C7252B;"></textarea>
                            </div>
                        </div>

                        <!-- Contenido de Imagen -->
                        <div id="contenidoImagen" class="mensaje-content hidden space-y-3">
                            <div>
                                <label for="imagenArchivo" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-image mr-1" style="color: #C7252B;"></i>
                                    Seleccionar Imagen *
                                </label>
                                <input type="file" id="imagenArchivo" name="imagen_archivo"
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent"
                                       style="focus:ring-color: #C7252B;">
                                <p class="text-xs text-gray-500 mt-1">Formatos permitidos: JPG, PNG, GIF, WebP. Máximo 5MB.</p>
                            </div>
                            <div>
                                <label for="imagenDescripcion" class="block text-sm font-medium text-gray-700 mb-1">
                                    Descripción de la Imagen
                                </label>
                                <textarea id="imagenDescripcion" name="imagen_descripcion" rows="3"
                                          placeholder="Descripción o contexto de la imagen"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent resize-y"
                                          style="focus:ring-color: #C7252B;"></textarea>
                            </div>
                        </div>

                        <!-- Contenido de Documento -->
                        <div id="contenidoDocumento" class="mensaje-content hidden space-y-3">
                            <div>
                                <label for="documentoArchivo" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-file-alt mr-1" style="color: #C7252B;"></i>
                                    Seleccionar Documento *
                                </label>
                                <input type="file" id="documentoArchivo" name="documento_archivo"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent"
                                       style="focus:ring-color: #C7252B;">
                                <p class="text-xs text-gray-500 mt-1">Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT. Máximo 10MB.</p>
                            </div>
                            <div>
                                <label for="documentoDescripcion" class="block text-sm font-medium text-gray-700 mb-1">
                                    Descripción del Documento
                                </label>
                                <textarea id="documentoDescripcion" name="documento_descripcion" rows="3"
                                          placeholder="Descripción o contexto del documento"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:border-transparent resize-y"
                                          style="focus:ring-color: #C7252B;"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" onclick="cerrarModalMensajeria()"
                                class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" id="enviarMensajeBtn"
                                class="px-6 py-2 text-white rounded-lg hover:opacity-90 transition-colors flex items-center"
                                style="background: linear-gradient(135deg, #C7252B 0%, #A01E24 100%);">
                            <span id="mensajeSpinner" class="hidden animate-spin mr-2">
                                <i class="fas fa-spinner"></i>
                            </span>
                            <i id="mensajeIcon" class="fas fa-paper-plane mr-2"></i>
                            <span id="mensajeText">Enviar Mensaje</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Funciones para el sistema de mensajería
        let usuariosDisponibles = [];

        // Función para abrir el modal de mensajería
        function abrirModalMensajeria() {
            console.log('🔔 Abriendo modal de mensajería...');

            // Cargar usuarios disponibles
            cargarUsuariosDisponibles();

            // Mostrar modal
            const modal = document.getElementById('modalMensajeria');
            modal.classList.remove('hidden');

            // Reset del formulario
            const form = document.getElementById('mensajeriaForm');
            if (form) {
                form.reset();
                // Ocultar contenidos específicos
                document.querySelectorAll('.mensaje-content').forEach(content => {
                    content.classList.add('hidden');
                });
                // Mostrar contenido de texto por defecto
                document.getElementById('contenidoTexto').classList.remove('hidden');
            }
        }

        // Función para cerrar el modal de mensajería
        function cerrarModalMensajeria() {
            console.log('🚪 Cerrando modal de mensajería...');
            document.getElementById('modalMensajeria').classList.add('hidden');

            // Reset del formulario
            const form = document.getElementById('mensajeriaForm');
            if (form) {
                form.reset();
            }
        }

        // Función para cargar usuarios disponibles
        async function cargarUsuariosDisponibles() {
            try {
                console.log('👥 Cargando usuarios disponibles...');
                const response = await fetch('./api/usuarios_mensajes.php?action=obtener_usuarios');
                const data = await response.json();

                if (data.success && data.data) {
                    usuariosDisponibles = data.data;
                    llenarSelectUsuarios(data.data);
                } else {
                    console.error('Error cargando usuarios:', data.message);
                }
            } catch (error) {
                console.error('Error en la petición de usuarios:', error);
            }
        }

        // Función para llenar el select de usuarios
        function llenarSelectUsuarios(usuarios) {
            const optgroup = document.getElementById('usuariosEspecificos');
            if (!optgroup) return;

            // Limpiar opciones anteriores
            optgroup.innerHTML = '';

            // Agregar cada usuario
            usuarios.forEach(usuario => {
                const option = document.createElement('option');
                option.value = usuario.email;
                option.textContent = `${usuario.nombre} ${usuario.apellido} (${usuario.email})`;
                optgroup.appendChild(option);
            });

            console.log(`✅ ${usuarios.length} usuarios cargados en el selector`);
        }

        // Función para cambiar tipo de mensaje
        function cambiarTipoMensaje() {
            const tipoSelect = document.getElementById('tipoMensajeSelect');
            const selectedType = tipoSelect.value;

            console.log('📝 Cambiando tipo de mensaje a:', selectedType);

            // Ocultar todos los contenidos
            document.querySelectorAll('.mensaje-content').forEach(content => {
                content.classList.add('hidden');
                // Limpiar required de campos no visibles
                content.querySelectorAll('[required]').forEach(field => {
                    if (!content.classList.contains('hidden')) return;
                    field.removeAttribute('required');
                });
            });

            // Mostrar contenido correspondiente y establecer required
            let targetContent = null;
            switch (selectedType) {
                case 'texto':
                    targetContent = document.getElementById('contenidoTexto');
                    document.getElementById('textoMensaje').setAttribute('required', 'required');
                    break;
                case 'link':
                    targetContent = document.getElementById('contenidoLink');
                    document.getElementById('linkUrl').setAttribute('required', 'required');
                    break;
                case 'imagen':
                    targetContent = document.getElementById('contenidoImagen');
                    document.getElementById('imagenArchivo').setAttribute('required', 'required');
                    break;
                case 'documento':
                    targetContent = document.getElementById('contenidoDocumento');
                    document.getElementById('documentoArchivo').setAttribute('required', 'required');
                    break;
            }

            if (targetContent) {
                targetContent.classList.remove('hidden');
            }
        }

        // Función para enviar mensaje
        async function enviarMensajeUsuarios(e) {
            e.preventDefault();
            console.log('📤 Iniciando envío de mensaje...');

            const submitBtn = document.getElementById('enviarMensajeBtn');
            const spinner = document.getElementById('mensajeSpinner');
            const icon = document.getElementById('mensajeIcon');
            const text = document.getElementById('mensajeText');

            // Cambiar estado del botón
            submitBtn.disabled = true;
            spinner.classList.remove('hidden');
            icon.classList.add('hidden');
            text.textContent = 'Enviando...';

            try {
                const formData = new FormData(e.target);
                console.log('📋 Datos del formulario:', Object.fromEntries(formData));

                const response = await fetch('./api/usuarios_mensajes.php?action=enviar_mensaje', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                console.log('📨 Respuesta del servidor:', result);

                if (result.success) {
                    // Mostrar mensaje de éxito
                    alert(`✅ Mensaje enviado correctamente a ${result.data.emails_enviados} usuario(s)`);

                    // Cerrar modal y resetear
                    cerrarModalMensajeria();

                    console.log('✅ Mensaje enviado exitosamente');
                } else {
                    throw new Error(result.message || 'Error desconocido');
                }

            } catch (error) {
                console.error('❌ Error enviando mensaje:', error);
                alert('❌ Error al enviar el mensaje: ' + error.message);
            } finally {
                // Restaurar estado del botón
                submitBtn.disabled = false;
                spinner.classList.add('hidden');
                icon.classList.remove('hidden');
                text.textContent = 'Enviar Mensaje';
            }
        }

        // Event listeners al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📋 Inicializando sistema de mensajería...');

            // Listener para el formulario
            const mensajeriaForm = document.getElementById('mensajeriaForm');
            if (mensajeriaForm) {
                mensajeriaForm.addEventListener('submit', enviarMensajeUsuarios);
            }

            // Cerrar modal con ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('modalMensajeria');
                    if (modal && !modal.classList.contains('hidden')) {
                        cerrarModalMensajeria();
                    }
                }
            });

            // Cerrar modal al hacer clic fuera
            document.getElementById('modalMensajeria').addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModalMensajeria();
                }
            });

            console.log('✅ Sistema de mensajería inicializado');
        });
    </script>
</body>
</html>