<?php
/**
 * Panel de Administración de Banners Mejorado
 * Con soporte completo para subida de imágenes locales
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Función para subir imagen local
function subirImagenLocal($archivo) {
    $directorioSubida = __DIR__ . '/../uploads/banners/';

    error_log("DEBUG: Directorio de subida: " . $directorioSubida);
    error_log("DEBUG: Archivo recibido: " . print_r($archivo, true));

    if (!file_exists($directorioSubida)) {
        error_log("DEBUG: Directorio no existe, creando...");
        if (!mkdir($directorioSubida, 0755, true)) {
            error_log("DEBUG: Error al crear directorio");
            return ['success' => false, 'message' => 'No se pudo crear el directorio de uploads'];
        }
        error_log("DEBUG: Directorio creado exitosamente");
    } else {
        error_log("DEBUG: Directorio ya existe");
    }

    // Verificar permisos
    if (!is_writable($directorioSubida)) {
        error_log("DEBUG: Directorio no tiene permisos de escritura");
        return ['success' => false, 'message' => 'El directorio no tiene permisos de escritura'];
    }
    
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $extensionesPermitidas)) {
        return ['success' => false, 'message' => 'Formato de imagen no permitido'];
    }
    
    if ($archivo['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande (máximo 5MB)'];
    }
    
    $nombreArchivo = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $rutaCompleta = $directorioSubida . $nombreArchivo;
    
    error_log("DEBUG: Intentando mover archivo de {$archivo['tmp_name']} a $rutaCompleta");

    if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        error_log("DEBUG: Archivo movido exitosamente a: $rutaCompleta");
        // Verificar que el archivo realmente existe
        if (file_exists($rutaCompleta)) {
            error_log("DEBUG: Archivo confirmado en: $rutaCompleta");
            return ['success' => true, 'url' => 'uploads/banners/' . $nombreArchivo];
        } else {
            error_log("DEBUG: ERROR - Archivo no existe después de mover");
            return ['success' => false, 'message' => 'Error: el archivo no se creó correctamente'];
        }
    }

    error_log("DEBUG: move_uploaded_file falló. Error: " . error_get_last()['message'] ?? 'desconocido');
    return ['success' => false, 'message' => 'Error al subir la imagen: no se pudo mover el archivo'];
}

// Función para obtener banners
function obtenerBanners($conn) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'banner_carrusel'");
        if ($stmt->rowCount() == 0) {
            crearTablaBanners($conn);
        }
        
        $stmt = $conn->query("
            SELECT id, titulo, descripcion, imagen_url, posicion, activo, 
                   fecha_inicio, fecha_fin, fecha_creacion 
            FROM banner_carrusel 
            ORDER BY posicion ASC, fecha_creacion DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener banners: " . $e->getMessage());
        return [];
    }
}

// Función para crear tabla
function crearTablaBanners($conn) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS banner_carrusel (
            id INT PRIMARY KEY AUTO_INCREMENT,
            titulo VARCHAR(255) NOT NULL,
            descripcion TEXT,
            imagen_url VARCHAR(500) NOT NULL,
            posicion INT DEFAULT 1,
            activo BOOLEAN DEFAULT TRUE,
            fecha_inicio DATETIME DEFAULT NULL,
            fecha_fin DATETIME DEFAULT NULL,
            creado_por INT DEFAULT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Error al crear tabla: " . $e->getMessage());
        return false;
    }
}

// Función para guardar banner
function guardarBanner($conn, $datos) {
    try {
        if (empty($datos['imagen_url'])) {
            throw new Exception("La imagen es requerida");
        }
        
        if (isset($datos['id']) && $datos['id']) {
            $sql = "UPDATE banner_carrusel 
                    SET titulo = :titulo, descripcion = :descripcion, imagen_url = :imagen_url, 
                        posicion = :posicion, activo = :activo, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin
                    WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $params = [
                ':titulo' => $datos['titulo'],
                ':descripcion' => $datos['descripcion'],
                ':imagen_url' => $datos['imagen_url'],
                ':posicion' => $datos['posicion'],
                ':activo' => $datos['activo'] ? 1 : 0,
                ':fecha_inicio' => !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : null,
                ':fecha_fin' => !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null,
                ':id' => $datos['id']
            ];
        } else {
            $sql = "INSERT INTO banner_carrusel (titulo, descripcion, imagen_url, posicion, activo, fecha_inicio, fecha_fin)
                    VALUES (:titulo, :descripcion, :imagen_url, :posicion, :activo, :fecha_inicio, :fecha_fin)";
            
            $stmt = $conn->prepare($sql);
            $params = [
                ':titulo' => $datos['titulo'],
                ':descripcion' => $datos['descripcion'],
                ':imagen_url' => $datos['imagen_url'],
                ':posicion' => $datos['posicion'],
                ':activo' => $datos['activo'] ? 1 : 0,
                ':fecha_inicio' => !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : null,
                ':fecha_fin' => !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null
            ];
        }
        
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log("Error al guardar banner: " . $e->getMessage());
        throw $e;
    }
}

// Función para eliminar banner
function eliminarBanner($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT imagen_url FROM banner_carrusel WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch();
        
        if ($banner && strpos($banner['imagen_url'], 'uploads/banners/') !== false) {
            $rutaImagen = __DIR__ . '/../' . $banner['imagen_url'];
            if (file_exists($rutaImagen)) {
                unlink($rutaImagen);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM banner_carrusel WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error al eliminar banner: " . $e->getMessage());
        return false;
    }
}

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $accion = $_POST['accion'] ?? '';
        
        if ($accion === 'guardar') {
            $imagen_url = $_POST['imagen_url_actual'] ?? '';

            // Debug removido - funcionando correctamente

            if (isset($_FILES['imagen_local']) && $_FILES['imagen_local']['error'] === UPLOAD_ERR_OK) {
                error_log("DEBUG: Procesando archivo local");
                $resultadoSubida = subirImagenLocal($_FILES['imagen_local']);
                if ($resultadoSubida['success']) {
                    $imagen_url = $resultadoSubida['url'];
                    error_log("DEBUG: Imagen subida exitosamente: " . $imagen_url);
                } else {
                    error_log("DEBUG: Error en subida: " . $resultadoSubida['message']);
                    throw new Exception($resultadoSubida['message']);
                }
            } elseif (!empty($_POST['imagen_url'])) {
                $imagen_url = $_POST['imagen_url'];
                error_log("DEBUG: Usando URL externa: " . $imagen_url);
            } elseif (isset($_FILES['imagen_local'])) {
                // Diagnosticar error de archivo
                $error_messages = [
                    UPLOAD_ERR_INI_SIZE => 'El archivo supera el tamaño máximo permitido por PHP',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo supera el tamaño máximo del formulario',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                    UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal',
                    UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo',
                    UPLOAD_ERR_EXTENSION => 'Extensión bloqueada'
                ];
                $error_code = $_FILES['imagen_local']['error'];
                $error_msg = $error_messages[$error_code] ?? "Error desconocido: $error_code";
                error_log("DEBUG: Error de archivo: $error_msg");
                throw new Exception("Error al subir archivo: $error_msg");
            }
            
            if (empty($imagen_url)) {
                throw new Exception("Debe proporcionar una imagen");
            }
            
            $datos = [
                'id' => $_POST['id'] ?? null,
                'titulo' => $_POST['titulo'] ?? '',
                'descripcion' => $_POST['descripcion'] ?? '',
                'imagen_url' => $imagen_url,
                'posicion' => intval($_POST['posicion'] ?? 1),
                'activo' => isset($_POST['activo']),
                'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
                'fecha_fin' => $_POST['fecha_fin'] ?? ''
            ];
            
            guardarBanner($conn, $datos);
            $mensaje = $datos['id'] ? 'Banner actualizado correctamente' : 'Banner creado correctamente';
            $tipo_mensaje = 'success';
            
        } elseif ($accion === 'eliminar') {
            $id = $_POST['id'] ?? 0;
            if (eliminarBanner($conn, $id)) {
                $mensaje = 'Banner eliminado correctamente';
                $tipo_mensaje = 'success';
            } else {
                throw new Exception("Error al eliminar el banner");
            }
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener banners
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $banners = obtenerBanners($conn);
} catch (Exception $e) {
    $banners = [];
    if (empty($mensaje)) {
        $mensaje = "Error de conexión: " . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Banners - CRUD Completo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Porsche-inspired Design System */
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

        .modal-backdrop { backdrop-filter: blur(5px); }
        .slide-in { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }
        .banner-preview {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        .drag-drop-area {
            border: 2px dashed #cbd5e1;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .drag-drop-area:hover,
        .drag-drop-area.dragover {
            border-color: #C7252B;
            background-color: #fef2f2;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
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

        .sidenav-overlay.show {
            opacity: 1;
            visibility: visible;
            z-index: 998;
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

        /* Enhanced sidebar behavior for desktop */
        @media (min-width: 1280px) {
            .sidebar-enhanced {
                transform: translateX(0) !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        }

        /* Responsive grid adjustments */
        @media (max-width: 768px) {
            header .flex {
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            header .flex.items-center.space-x-4 {
                margin-top: 1rem !important;
                align-self: flex-end !important;
            }

            .grid {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 640px) {
            .porsche-sidebar {
                width: 100% !important;
                max-width: 100% !important;
            }

            header .container {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
        }

        /* Ensure proper z-index hierarchy */
        .porsche-sidebar {
            z-index: 999 !important;
        }

        .sidenav-overlay {
            z-index: 998 !important;
        }

        header {
            z-index: 997 !important;
        }

        .main-content {
            z-index: 1 !important;
        }

        /* Hamburger button styling */
        .hamburger-btn {
            transition: all 0.3s ease;
        }

        .hamburger-btn:hover {
            background: rgba(255, 255, 255, 0.1) !important;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidenav-overlay" id="sidenavOverlay"></div>

    <!-- Sidebar -->
    <aside
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

            <a class="porsche-logo block px-6 py-4 m-0 text-center whitespace-nowrap" href="../dashboard.html"
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
                        href="../dashboard.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-tachometer-alt text-blue-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Dashboard</span>
                    </a>
                </li>

                <!-- admin panel -->
                <li class="mt-2 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="../admin-panel.html?login=success">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-tachometer-alt text-green-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Panel Administrador</span>
                    </a>
                </li>

                <!-- Usuarios -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="gestionar_usuarios.php">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-users text-green-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Usuarios</span>
                    </a>
                </li>

                <!-- Empresas -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="../demo_empresas.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-building text-purple-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Empresas</span>
                    </a>
                </li>

                <!-- Eventos -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="../demo_evento.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-calendar-alt text-orange-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Eventos</span>
                    </a>
                </li>

                <!-- Boletines -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="../demo_boletines.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-newspaper text-red-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Boletines</span>
                    </a>
                </li>

                <!-- Banners -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg active"
                        href="banner-admin-mejorado.php">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-images text-red-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Banners</span>
                    </a>
                </li>

                <!-- Descuentos -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="../demo_descuentos.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-tags text-red-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Descuentos</span>
                    </a>
                </li>

                <!-- Perfil -->
                <li class="mt-0.5 w-full">
                    <a class="porsche-nav-item py-2.5 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors rounded-lg"
                        href="../profile.html">
                        <div class="shadow-md mr-3 flex h-8 w-8 items-center justify-center rounded-lg bg-white">
                            <i class="fas fa-user-cog text-gray-500"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100">Perfil</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="mx-4">
            <button onclick="window.location.href='../pages/sign-in.html'"
                class="py-2.7 text-sm ease-nav-brand my-0 mx-4 flex items-center whitespace-nowrap px-4 transition-colors text-red-600 hover:text-red-800">
                <div
                    class="shadow-soft-2xl mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-white bg-center stroke-0 text-center xl:p-2.5">
                    <i class="fas fa-sign-out-alt text-red-500" style="font-size: 16px;"></i>
                </div>
                <span class="ml-1 duration-300 opacity-100 pointer-events-none ease-soft">Cerrar Sesión</span>
            </button>
        </div>

    </aside>

    <!-- Header -->
    <header class="main-content bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg" style="background: linear-gradient(135deg, #C7252B 0%, #A01E24 100%);">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <!-- Hamburger Menu Button for All Screens -->
                    <button class="mr-3 p-2 text-white hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-white/20 rounded-md hamburger-btn"
                            sidenav-trigger
                            aria-label="Abrir menú de navegación">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-3xl font-bold">Administrador de Banners</h1>
                        <p class="text-red-200 mt-1">CRUD Completo - Gestión de Carrusel</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Estadísticas -->
    <div class="main-content container mx-auto px-4 py-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Banners</p>
                        <p class="text-2xl font-bold text-gray-800"><?= count($banners) ?></p>
                    </div>
                    <i class="fas fa-images text-3xl text-red-500" style="color: #C7252B;"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Banners Activos</p>
                        <p class="text-2xl font-bold text-green-600"><?= count(array_filter($banners, fn($b) => $b['activo'])) ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Banners Inactivos</p>
                        <p class="text-2xl font-bold text-red-600"><?= count(array_filter($banners, fn($b) => !$b['activo'])) ?></p>
                    </div>
                    <i class="fas fa-times-circle text-3xl text-red-500"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Última Actualización</p>
                        <p class="text-2xl font-bold text-blue-600"><?= !empty($banners) ? date('d/m') : '--' ?></p>
                    </div>
                    <i class="fas fa-clock text-3xl text-blue-500"></i>
                </div>
            </div>
        </div>

        <!-- Controles -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <button onclick="abrirModalNuevo()" class="text-white px-4 py-2 rounded-lg hover:opacity-90 transition" style="background: #C7252B;">
                        <i class="fas fa-plus mr-2"></i>Crear Banner
                    </button>
                    <button onclick="location.reload()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                        <i class="fas fa-sync-alt mr-2"></i>Actualizar
                    </button>
                </div>

                <div class="flex items-center space-x-4">
                    <select id="filterEstado" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" onchange="filtrarBanners()">
                        <option value="">Todos los estados</option>
                        <option value="activo">Activos</option>
                        <option value="inactivo">Inactivos</option>
                    </select>
                    <a href="../pages/sign-in.html" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-eye mr-2"></i>Vista Previa
                    </a>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="mb-6 p-4 rounded-lg shadow <?= $tipo_mensaje === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-700' : 'bg-red-50 border-l-4 border-red-500 text-red-700' ?>">
                <div class="flex items-center">
                    <i class="fas <?= $tipo_mensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class=" mx-auto px-4">

            <!-- Lista de Banners -->
            <section class="mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-images mr-2" style="color: #C7252B;"></i>
                            Lista de Banners
                        </h2>
                        <div class="text-sm text-gray-600">
                            <?= count($banners) ?> banner(es) total
                        </div>
                    </div>

                    <div id="banner-list" class="space-y-4">
                        <?php if (empty($banners)): ?>
                            <div class="text-center py-12">
                                <div class="text-6xl mb-4">📸</div>
                                <h3 class="text-xl font-semibold mb-2 text-gray-700">No hay banners configurados</h3>
                                <p class="text-gray-500 mb-4">Crea tu primer banner para comenzar</p>
                                <button onclick="abrirModalNuevo()" class="text-white px-6 py-3 rounded-lg hover:opacity-90 transition" style="background: #C7252B;">
                                    <i class="fas fa-plus mr-2"></i>Crear Primer Banner
                                </button>
                            </div>
                        <?php else: ?>
                            <?php foreach ($banners as $banner): ?>
                                <?php
                                $imagenUrl = $banner['imagen_url'];
                                if (strpos($imagenUrl, 'http') !== 0) {
                                    // Verificar si el archivo existe físicamente
                                    $rutaFisica = __DIR__ . '/../' . $imagenUrl;
                                    if (file_exists($rutaFisica)) {
                                        // Para el navegador, usar ruta absoluta incluyendo /build/
                                        $imagenUrl = '/build/' . $imagenUrl;
                                    } else {
                                        // Usar imagen por defecto si no existe
                                        $imagenUrl = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlbiBubyBlbmNvbnRyYWRhPC90ZXh0Pjwvc3ZnPg==';
                                    }
                                }
                                $fechaCreacion = date('d/m/Y H:i', strtotime($banner['fecha_creacion']));
                                ?>
                                <div class="banner-item bg-white border rounded-lg p-4 hover:shadow-md transition-shadow" data-estado="<?= $banner['activo'] ? 'activo' : 'inactivo' ?>">
                                    <div class="flex gap-4">
                                        <div class="w-32 h-24 rounded-lg banner-preview flex-shrink-0" style="background-image: url('<?= htmlspecialchars($imagenUrl) ?>')">
                                            <?php if (!$banner['imagen_url']): ?>
                                                <div class="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400 text-2xl"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex-1">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-semibold text-gray-800 mb-1"><?= htmlspecialchars($banner['titulo']) ?></h3>
                                                    <p class="text-gray-600 text-sm"><?= htmlspecialchars($banner['descripcion'] ?: 'Sin descripción') ?></p>
                                                </div>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium ml-3 <?= $banner['activo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                    <?= $banner['activo'] ? '✅ ACTIVO' : '❌ INACTIVO' ?>
                                                </span>
                                            </div>

                                            <div class="flex justify-between items-center text-sm text-gray-500 mb-3">
                                                <div class="flex items-center space-x-4">
                                                    <span><i class="fas fa-sort-numeric-up mr-1"></i>Posición <?= $banner['posicion'] ?></span>
                                                    <span><i class="fas fa-calendar mr-1"></i><?= $fechaCreacion ?></span>
                                                </div>
                                                <span class="text-xs">#<?= $banner['id'] ?></span>
                                            </div>

                                            <div class="flex justify-between items-center pt-3 border-t">
                                                <div class="text-sm text-gray-500">
                                                    <?php if ($banner['fecha_inicio'] || $banner['fecha_fin']): ?>
                                                        <i class="fas fa-clock mr-1"></i>
                                                        <?php if ($banner['fecha_inicio']): ?>
                                                            Desde: <?= date('d/m/Y', strtotime($banner['fecha_inicio'])) ?>
                                                        <?php endif; ?>
                                                        <?php if ($banner['fecha_fin']): ?>
                                                            Hasta: <?= date('d/m/Y', strtotime($banner['fecha_fin'])) ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span>Sin límites de fecha</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <button onclick='editarBanner(<?= htmlspecialchars(json_encode($banner)) ?>)'
                                                            class="px-3 py-1 bg-yellow-600 text-white rounded text-xs hover:bg-yellow-700 transition-colors">
                                                        <i class="fas fa-edit mr-1"></i>Editar
                                                    </button>
                                                    <form method="post" class="inline" onsubmit="return confirm('¿Eliminar este banner?\n\nEsta acción no se puede deshacer.')">
                                                        <input type="hidden" name="accion" value="eliminar">
                                                        <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                                                        <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700 transition-colors">
                                                            <i class="fas fa-trash mr-1"></i>Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal Crear/Editar Banner -->
    <div id="modalBanner" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 modal-backdrop">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto slide-in">
                <div class="p-6 border-b">
                    <h2 id="modalTitulo" class="text-2xl font-bold text-gray-800">Nuevo Banner</h2>
                </div>

                <form method="post" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="accion" value="guardar">
                <input type="hidden" name="id" id="bannerId">
                <input type="hidden" name="imagen_url_actual" id="imagenUrlActual">
                
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                            <input type="text" name="titulo" id="titulo" required
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea name="descripcion" id="descripcion" rows="3"
                                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                                      placeholder="Descripción del banner (opcional)"></textarea>
                        </div>
                
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Imagen *</label>
                            <div class="flex gap-4 mb-4">
                                <label class="flex items-center">
                                    <input type="radio" name="tipo_imagen" value="local" checked onchange="cambiarTipoImagen('local')" class="mr-2">
                                    <span class="text-sm">Subir imagen</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="tipo_imagen" value="url" onchange="cambiarTipoImagen('url')" class="mr-2">
                                    <span class="text-sm">URL externa</span>
                                </label>
                            </div>
                    
                    <div id="imagenLocal" class="mb-4">
                        <div class="drag-drop-area" id="dropArea">
                            <input type="file" name="imagen_local" id="imagenFile" accept="image/*" class="hidden" onchange="previewImagen(this)">
                            <div class="text-gray-600">
                                <div class="text-4xl mb-2">📤</div>
                                <p>Click o arrastra una imagen aquí</p>
                            </div>
                        </div>
                    </div>
                    
                    <div id="imagenUrl" class="mb-4 hidden">
                        <input type="url" name="imagen_url" id="imagenUrlInput" class="w-full px-4 py-2 border rounded-lg" 
                               placeholder="https://ejemplo.com/imagen.jpg" onchange="previewImagenUrl(this.value)">
                    </div>
                    
                    <div id="previewContainer" class="hidden">
                        <label class="block text-gray-700 font-semibold mb-2">Vista Previa</label>
                        <div id="imagePreview" class="w-full h-48 border rounded-lg bg-gray-100 bg-cover bg-center"></div>
                    </div>
                </div>
                
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Posición</label>
                                <input type="number" name="posicion" id="posicion" min="1" value="1"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="flex items-center">
                                <label class="flex items-center">
                                    <input type="checkbox" name="activo" id="activo" checked class="mr-2">
                                    <span class="text-sm">Banner Activo</span>
                                </label>
                            </div>
                        </div>

                        <!-- Fechas (opcional) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio (opcional)</label>
                                <input type="datetime-local" name="fecha_inicio" id="fecha_inicio"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin (opcional)</label>
                                <input type="datetime-local" name="fecha_fin" id="fecha_fin"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="cerrarModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" id="submitBtn" class="px-4 py-2 text-white rounded-lg hover:opacity-90 transition" style="background: #C7252B;">
                            <i class="fas fa-save mr-2"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalNuevo() {
            document.getElementById('modalBanner').classList.remove('hidden');
            document.getElementById('modalTitulo').textContent = 'Nuevo Banner';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Crear Banner';
            document.getElementById('bannerId').value = '';
            document.getElementById('titulo').value = '';
            document.getElementById('descripcion').value = '';
            document.getElementById('imagenUrlInput').value = '';
            document.getElementById('imagenUrlActual').value = '';
            document.getElementById('posicion').value = '1';
            document.getElementById('fecha_inicio').value = '';
            document.getElementById('fecha_fin').value = '';
            document.getElementById('activo').checked = true;
            document.getElementById('imagePreview').style.backgroundImage = '';
            document.getElementById('previewContainer').classList.add('hidden');
            document.getElementById('imagenFile').value = '';
            cambiarTipoImagen('local');
        }
        
        function editarBanner(banner) {
            document.getElementById('modalBanner').classList.remove('hidden');
            document.getElementById('modalTitulo').textContent = 'Editar Banner';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Actualizar Banner';
            document.getElementById('bannerId').value = banner.id;
            document.getElementById('titulo').value = banner.titulo;
            document.getElementById('descripcion').value = banner.descripcion || '';
            document.getElementById('imagenUrlActual').value = banner.imagen_url;
            document.getElementById('posicion').value = banner.posicion;
            document.getElementById('fecha_inicio').value = banner.fecha_inicio || '';
            document.getElementById('fecha_fin').value = banner.fecha_fin || '';
            document.getElementById('activo').checked = banner.activo == 1;

            if (banner.imagen_url) {
                let imageUrl = banner.imagen_url;
                if (!imageUrl.startsWith('http')) {
                    imageUrl = '/build/' + imageUrl;
                }
                document.getElementById('imagePreview').style.backgroundImage = 'url(' + imageUrl + ')';
                document.getElementById('previewContainer').classList.remove('hidden');

                if (banner.imagen_url.startsWith('http')) {
                    cambiarTipoImagen('url');
                    document.getElementById('imagenUrlInput').value = banner.imagen_url;
                    document.querySelector('input[name="tipo_imagen"][value="url"]').checked = true;
                } else {
                    cambiarTipoImagen('local');
                    document.querySelector('input[name="tipo_imagen"][value="local"]').checked = true;
                }
            }
        }

        function cerrarModal() {
            document.getElementById('modalBanner').classList.add('hidden');
        }

        // Función para filtrar banners
        function filtrarBanners() {
            const filtro = document.getElementById('filterEstado').value;
            const bannerItems = document.querySelectorAll('.banner-item');

            bannerItems.forEach(item => {
                const estado = item.getAttribute('data-estado');
                if (filtro === '' || estado === filtro) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        function cambiarTipoImagen(tipo) {
            if (tipo === 'local') {
                document.getElementById('imagenLocal').classList.remove('hidden');
                document.getElementById('imagenUrl').classList.add('hidden');
            } else {
                document.getElementById('imagenLocal').classList.add('hidden');
                document.getElementById('imagenUrl').classList.remove('hidden');
            }
        }
        
        function previewImagen(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').style.backgroundImage = 'url(' + e.target.result + ')';
                    document.getElementById('previewContainer').classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function previewImagenUrl(url) {
            if (url) {
                document.getElementById('imagePreview').style.backgroundImage = 'url(' + url + ')';
                document.getElementById('previewContainer').classList.remove('hidden');
            }
        }
        
        // Drag and Drop
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('imagenFile');
        
        if (dropArea && fileInput) {
            dropArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            dropArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                dropArea.classList.add('dragover');
            });
            
            dropArea.addEventListener('dragleave', function() {
                dropArea.classList.remove('dragover');
            });
            
            dropArea.addEventListener('drop', function(e) {
                e.preventDefault();
                dropArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    previewImagen(fileInput);
                }
            });
        }
        
        // Cerrar modal al hacer clic fuera o con ESC
        document.getElementById('modalBanner').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });

        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('modalBanner').classList.contains('hidden')) {
                cerrarModal();
            }
        });
    </script>

    <!-- Sidebar JavaScript Functionality -->
    <script>
        // === SIDEBAR RETRACTABLE FUNCTIONALITY ===
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initializeSidebar, 100); // Pequeño delay para asegurar que el DOM esté listo
        });

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
    </script>
</body>
</html>