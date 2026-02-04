<?php
// Panel de administración sin middleware (acceso directo)
require_once '../assets/conexion/config.php';

iniciarSesion();

// Manejar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: acceso-directo.php');
    exit;
}

// Verificación simple sin middleware
if (!isset($_SESSION['admin_directo']) || !isset($_SESSION['usuario_id'])) {
    header('Location: acceso-directo.php');
    exit;
}

$usuario = new Usuario();
$adminUser = $usuario->obtenerPorId($_SESSION['usuario_id']);

if (!$adminUser || $adminUser['rol'] !== 'admin') {
    header('Location: acceso-directo.php');
    exit;
}

// Obtener estadísticas (con manejo de errores)
try {
    $db = Database::getInstance();
    
    $totalUsuarios = $db->selectOne("SELECT COUNT(*) as total FROM usuarios")['total'] ?? 0;
    $usuariosActivos = $db->selectOne("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'")['total'] ?? 0;
    $usuariosPendientes = $db->selectOne("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'pendiente'")['total'] ?? 0;
    
    $totalBanners = 0;
    $bannersActivos = 0;
    $totalBoletines = 0;
    $totalEventos = 0;
    $totalEmpresas = 0;
    
    // Intentar obtener estadísticas de otras tablas (pueden no existir)
    try {
        $totalBanners = $db->selectOne("SELECT COUNT(*) as total FROM banners")['total'] ?? 0;
        $bannersActivos = $db->selectOne("SELECT COUNT(*) as total FROM banners WHERE estado = 'activo'")['total'] ?? 0;
    } catch (Exception $e) {}
    
    try {
        $totalBoletines = $db->selectOne("SELECT COUNT(*) as total FROM boletines")['total'] ?? 0;
    } catch (Exception $e) {}
    
    try {
        $totalEventos = $db->selectOne("SELECT COUNT(*) as total FROM eventos")['total'] ?? 0;
    } catch (Exception $e) {}
    
    try {
        $totalEmpresas = $db->selectOne("SELECT COUNT(*) as total FROM empresas_convenio")['total'] ?? 0;
    } catch (Exception $e) {}
    
} catch (Exception $e) {
    $error = "Error al cargar estadísticas: " . $e->getMessage();
    $totalUsuarios = $usuariosActivos = $usuariosPendientes = 0;
    $totalBanners = $bannersActivos = 0;
    $totalBoletines = $totalEventos = $totalEmpresas = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin Directo - Clúster</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .alert {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
            margin-bottom: 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        
        .modules {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .module-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .module-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .logout {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <a href="?logout=1" class="logout" onclick="return confirm('¿Cerrar sesión?')">
        <i class="fas fa-sign-out-alt"></i> Salir
    </a>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-crown"></i> Panel de Administración</h1>
            <p>Acceso Directo - Bienvenido, <?php echo htmlspecialchars($adminUser['nombre']); ?></p>
        </div>

        <div class="success">
            <strong>✅ ¡Acceso exitoso!</strong> Estás usando el panel de administración en modo directo.
        </div>

        <?php if (isset($error)): ?>
            <div class="alert">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <h3>👥 Usuarios</h3>
                <div class="stat-number"><?php echo $totalUsuarios; ?></div>
                <p><?php echo $usuariosActivos; ?> activos, <?php echo $usuariosPendientes; ?> pendientes</p>
            </div>
            
            <div class="stat-card">
                <h3>🖼️ Banners</h3>
                <div class="stat-number"><?php echo $totalBanners; ?></div>
                <p><?php echo $bannersActivos; ?> activos</p>
            </div>
            
            <div class="stat-card">
                <h3>📄 Contenido</h3>
                <div class="stat-number"><?php echo $totalBoletines + $totalEventos; ?></div>
                <p><?php echo $totalBoletines; ?> boletines, <?php echo $totalEventos; ?> eventos</p>
            </div>
            
            <div class="stat-card">
                <h3>🏢 Empresas</h3>
                <div class="stat-number"><?php echo $totalEmpresas; ?></div>
                <p>convenios activos</p>
            </div>
        </div>

        <!-- Módulos -->
        <div class="modules">
            <div class="module-card">
                <div class="module-icon"><i class="fas fa-images"></i></div>
                <h3>Gestión de Banners</h3>
                <p>Administra banners del carrusel</p>
                <a href="banner-admin-mejorado.php" class="btn">Gestionar</a>
            </div>

            <div class="module-card">
                <div class="module-icon"><i class="fas fa-newspaper"></i></div>
                <h3>Gestión de Boletines</h3>
                <p>Crear y publicar boletines</p>
                <a href="../demo_boletines.html" class="btn">Gestionar</a>
            </div>

            <div class="module-card">
                <div class="module-icon"><i class="fas fa-users-cog"></i></div>
                <h3>Gestión de Comités</h3>
                <p>Administrar comités y grupos</p>
                <a href="../demo_comite.html" class="btn">Gestionar</a>
            </div>

            <div class="module-card">
                <div class="module-icon"><i class="fas fa-tags"></i></div>
                <h3>Gestión de Descuentos</h3>
                <p>Configurar promociones</p>
                <a href="../demo_descuentos.html" class="btn">Gestionar</a>
            </div>

            <div class="module-card">
                <div class="module-icon"><i class="fas fa-building"></i></div>
                <h3>Gestión de Empresas</h3>
                <p>Empresas y convenios</p>
                <a href="../demo_empresas.html" class="btn">Gestionar</a>
            </div>

            <div class="module-card">
                <div class="module-icon"><i class="fas fa-calendar-alt"></i></div>
                <h3>Gestión de Eventos</h3>
                <p>Crear y administrar eventos</p>
                <a href="../demo_evento.html" class="btn">Gestionar</a>
            </div>

            <div class="module-card">
                <div class="module-icon"><i class="fas fa-chart-bar"></i></div>
                <h3>Gestión de Gráficos</h3>
                <p>Dashboards y visualizaciones</p>
                <a href="../demo_gestion_grafico.html" class="btn">Gestionar</a>
            </div>

            <div class="module-card">
                <div class="module-icon"><i class="fas fa-users"></i></div>
                <h3>Gestión de Usuarios</h3>
                <p>Administrar usuarios y permisos</p>
                <a href="../gestionar_usuarios.php" class="btn">Gestionar</a>
            </div>
        </div>

        <!-- Enlaces de diagnóstico -->
        <div style="margin-top: 40px; padding: 20px; background: white; border-radius: 10px;">
            <h3>🛠️ Herramientas de Diagnóstico</h3>
            <p>Enlaces útiles para debugging y mantenimiento:</p>
            <div style="margin-top: 15px;">
                <a href="verificar-admin.php" class="btn" style="margin-right: 10px;">🔍 Verificar Admin BD</a>
                <a href="diagnostico-rutas.php" class="btn" style="margin-right: 10px;">📂 Diagnóstico Rutas</a>
                <a href="login-debug.html" class="btn" style="margin-right: 10px;">🐛 Login Debug</a>
                <a href="../pages/sign-in.html" class="btn">🔐 Login Normal</a>
            </div>
        </div>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 40px; color: #666;">
            <p>&copy; <?php echo date('Y'); ?> Clúster Intranet - Panel de Administración Directo</p>
            <p><small>Acceso directo sin middleware | <?php echo date('d/m/Y H:i'); ?></small></p>
        </div>
    </div>
</body>
</html>