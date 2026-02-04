<?php
// admin-dashboard.php - Panel Principal de Administración

// Definir que no se ejecute automáticamente la verificación del middleware
define('ADMIN_MIDDLEWARE_SKIP_AUTO', true);

require_once 'middleware/auth-admin.php';
require_once '../assets/conexion/config.php';

// Verificar autenticación de admin usando el middleware
$adminUser = requiereAdmin();

// Obtener estadísticas del sistema
try {
    $db = new DatabaseWrapper();
    
    // Estadísticas de usuarios
    $totalUsuarios = $db->selectOne("SELECT COUNT(*) as total FROM usuarios")['total'];
    $usuariosActivos = $db->selectOne("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'")['total'];
    $usuariosPendientes = $db->selectOne("SELECT COUNT(*) as total FROM usuarios WHERE estado = 'pendiente'")['total'];
    
    // Estadísticas de contenido
    $totalBanners = $db->selectOne("SELECT COUNT(*) as total FROM banners")['total'] ?? 0;
    $bannersActivos = $db->selectOne("SELECT COUNT(*) as total FROM banners WHERE estado = 'activo'")['total'] ?? 0;
    
    $totalBoletines = $db->selectOne("SELECT COUNT(*) as total FROM boletines")['total'] ?? 0;
    $totalEventos = $db->selectOne("SELECT COUNT(*) as total FROM eventos")['total'] ?? 0;
    $totalEmpresas = $db->selectOne("SELECT COUNT(*) as total FROM empresas_convenio")['total'] ?? 0;
    
    // Actividad reciente (compatible con MariaDB/MySQL)
    $actividadReciente = $db->select("
        SELECT 'usuario' as tipo, 
               CONCAT(nombre, ' ', apellido) as descripcion, 
               fecha_registro as fecha 
        FROM usuarios 
        WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY fecha_registro DESC 
        LIMIT 5
    ");
    
} catch (Exception $e) {
    $error = "Error al cargar estadísticas: " . $e->getMessage();
    // Valores por defecto
    $totalUsuarios = $usuariosActivos = $usuariosPendientes = 0;
    $totalBanners = $bannersActivos = 0;
    $totalBoletines = $totalEventos = $totalEmpresas = 0;
    $actividadReciente = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Clúster</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="../assets/css/argon-dashboard-tailwind.css" rel="stylesheet" />
    <style>
        /* Variables CSS para el tema */
        :root {
            --primary-color: #C7252B;
            --primary-dark: #A01E23;
            --primary-light: #D94449;
            --secondary-color: #2C3E50;
            --accent-color: #E74C3C;
            --success-color: #27AE60;
            --warning-color: #F39C12;
            --info-color: #3498DB;
            --light-bg: #F8F9FA;
            --card-shadow: 0 4px 20px rgba(199, 37, 43, 0.1);
            --card-shadow-hover: 0 8px 30px rgba(199, 37, 43, 0.15);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #2c3e50;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            filter: blur(60px);
        }
        
        .admin-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 300;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(199, 37, 43, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--primary-light));
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--primary-color);
        }
        
        .stat-card.users::before { background: linear-gradient(to bottom, var(--info-color), #5DADE2); }
        .stat-card.content::before { background: linear-gradient(to bottom, var(--success-color), #58D68D); }
        .stat-card.activity::before { background: linear-gradient(to bottom, var(--warning-color), #F8C471); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0.5rem 0;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        }
        
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .module-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(199, 37, 43, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        }
        
        .module-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(199, 37, 43, 0.2);
        }
        
        .module-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 1.5rem;
            position: relative;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            box-shadow: 0 8px 20px rgba(199, 37, 43, 0.3);
        }
        
        .module-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }
        
        .module-card p {
            color: #7f8c8d;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .btn-admin {
            width: 100%;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(199, 37, 43, 0.3);
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(199, 37, 43, 0.4);
            text-decoration: none;
            color: white;
        }
        
        .btn-admin:active {
            transform: translateY(0);
        }
        
        .activity-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-top: 3rem;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #ecf0f1;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1rem;
            color: white;
        }
        
        .logout-btn {
            position: fixed;
            top: 30px;
            right: 30px;
            background: linear-gradient(135deg, var(--accent-color), #E55353);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .logout-btn:hover {
            background: linear-gradient(135deg, #E55353, var(--accent-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
            color: white;
            text-decoration: none;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .welcome-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            margin-top: 1rem;
            backdrop-filter: blur(10px);
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 2px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .admin-header {
                padding: 2rem 1.5rem;
                text-align: center;
            }
            
            .admin-header h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .module-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .logout-btn {
                top: 15px;
                right: 15px;
                padding: 0.75rem 1rem;
            }
        }
        
        /* Animaciones */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Pulso para notificaciones */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Botón de cerrar sesión -->
    <a href="../api/auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
    </a>

    <div class="container">
        <!-- Header -->
        <div class="admin-header fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1>
                        <i class="fas fa-crown mr-3"></i>Panel de Administración
                    </h1>
                    <p>Bienvenido, <?php echo htmlspecialchars($adminUser['nombre'] ?? 'Administrador'); ?></p>
                    <div class="welcome-badge">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Administrador del Sistema
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold"><?php echo date('d/m/Y'); ?></div>
                    <div class="text-lg opacity-80"><?php echo date('H:i'); ?></div>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <h2 class="section-title">Estadísticas del Sistema</h2>
        <div class="stats-grid">
            <div class="stat-card users fade-in">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-600 text-sm font-medium mb-2">Total Usuarios</p>
                        <div class="stat-number"><?php echo $totalUsuarios; ?></div>
                        <p class="text-xs text-gray-500">
                            <?php echo $usuariosActivos; ?> activos • <?php echo $usuariosPendientes; ?> pendientes
                        </p>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--info-color), #5DADE2);">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card content fade-in" style="animation-delay: 0.1s;">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-600 text-sm font-medium mb-2">Banners</p>
                        <div class="stat-number"><?php echo $totalBanners; ?></div>
                        <p class="text-xs text-gray-500"><?php echo $bannersActivos; ?> activos</p>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--success-color), #58D68D);">
                        <i class="fas fa-images"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card content fade-in" style="animation-delay: 0.2s;">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-600 text-sm font-medium mb-2">Contenido</p>
                        <div class="stat-number"><?php echo $totalBoletines + $totalEventos; ?></div>
                        <p class="text-xs text-gray-500"><?php echo $totalBoletines; ?> boletines • <?php echo $totalEventos; ?> eventos</p>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning-color), #F8C471);">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card activity fade-in" style="animation-delay: 0.3s;">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-600 text-sm font-medium mb-2">Empresas</p>
                        <div class="stat-number"><?php echo $totalEmpresas; ?></div>
                        <p class="text-xs text-gray-500">convenios activos</p>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-light));">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulos de Administración -->
        <h2 class="section-title">Módulos de Gestión</h2>
        <div class="module-grid">
            <!-- Gestión de Banners -->
            <div class="module-card fade-in" style="animation-delay: 0.4s;">
                <div class="module-icon">
                    <i class="fas fa-images"></i>
                </div>
                <h3>Gestión de Banners</h3>
                <p>Administra los banners del carrusel principal y las imágenes promocionales del sitio web.</p>
                <a href="banner-admin-mejorado.php" class="btn-admin">
                    <i class="fas fa-edit mr-2"></i>Gestionar Banners
                </a>
            </div>

            <!-- Gestión de Boletines -->
            <div class="module-card fade-in" style="animation-delay: 0.5s;">
                <div class="module-icon">
                    <i class="fas fa-newspaper"></i>
                </div>
                <h3>Gestión de Boletines</h3>
                <p>Crea, edita y publica boletines informativos para mantener informada a la comunidad.</p>
                <a href="../demo_boletines.html" class="btn-admin">
                    <i class="fas fa-file-pdf mr-2"></i>Gestionar Boletines
                </a>
            </div>

            <!-- Gestión de Comités -->
            <div class="module-card fade-in" style="animation-delay: 0.6s;">
                <div class="module-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3>Gestión de Comités</h3>
                <p>Administra los comités y grupos de trabajo de la organización automotriz.</p>
                <a href="../demo_comite.html" class="btn-admin">
                    <i class="fas fa-sitemap mr-2"></i>Gestionar Comités
                </a>
            </div>

            <!-- Gestión de Descuentos -->
            <div class="module-card fade-in" style="animation-delay: 0.7s;">
                <div class="module-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <h3>Gestión de Descuentos</h3>
                <p>Configura descuentos y promociones especiales para los miembros de la comunidad.</p>
                <a href="../demo_descuentos.html" class="btn-admin">
                    <i class="fas fa-percent mr-2"></i>Gestionar Descuentos
                </a>
            </div>

            <!-- Gestión de Empresas -->
            <div class="module-card fade-in" style="animation-delay: 0.8s;">
                <div class="module-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3>Gestión de Empresas</h3>
                <p>Administra las empresas afiliadas y sus convenios comerciales especiales.</p>
                <a href="../demo_empresas.html" class="btn-admin">
                    <i class="fas fa-handshake mr-2"></i>Gestionar Empresas
                </a>
            </div>

            <!-- Gestión de Eventos -->
            <div class="module-card fade-in" style="animation-delay: 0.9s;">
                <div class="module-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3>Gestión de Eventos</h3>
                <p>Crea y administra eventos, conferencias y actividades de la comunidad automotriz.</p>
                <a href="../demo_evento.html" class="btn-admin">
                    <i class="fas fa-calendar-plus mr-2"></i>Gestionar Eventos
                </a>
            </div>

            <!-- Gestión de Gráficos -->
            <div class="module-card fade-in" style="animation-delay: 1.0s;">
                <div class="module-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3>Gestión de Gráficos</h3>
                <p>Configura dashboards y visualizaciones de datos para el análisis del sistema.</p>
                <a href="../demo_gestion_grafico.html" class="btn-admin">
                    <i class="fas fa-chart-line mr-2"></i>Gestionar Gráficos
                </a>
            </div>

            <!-- Gestión de Estadísticas Dinámicas -->
            <div class="module-card fade-in" style="animation-delay: 1.1s;">
                <div class="module-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <h3>Estadísticas Dinámicas</h3>
                <p>Personaliza las estadísticas que aparecen en el dashboard principal del sistema.</p>
                <a href="../demo_estadisticasdinamicas.html" class="btn-admin">
                    <i class="fas fa-cogs mr-2"></i>Configurar Estadísticas
                </a>
            </div>

            <!-- Gestión de Usuarios -->
            <div class="module-card fade-in" style="animation-delay: 1.2s;">
                <div class="module-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Gestión de Usuarios</h3>
                <p>Administra usuarios, permisos, roles y configuraciones de acceso al sistema.</p>
                <a href="../gestionar_usuarios.php" class="btn-admin">
                    <i class="fas fa-user-cog mr-2"></i>Gestionar Usuarios
                </a>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="activity-section fade-in" style="animation-delay: 1.2s;">
            <h3 class="text-2xl font-bold mb-4 text-gray-800">
                <i class="fas fa-clock mr-3 text-red-600"></i>Actividad Reciente
            </h3>
            
            <?php if (empty($actividadReciente)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-history text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">No hay actividad reciente registrada.</p>
                    <p class="text-gray-400 text-sm mt-2">Las nuevas actividades aparecerán aquí cuando ocurran.</p>
                </div>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($actividadReciente as $actividad): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">
                                    <strong><?php echo htmlspecialchars($actividad['descripcion']); ?></strong>
                                    se registró en el sistema
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?>
                                </p>
                            </div>
                            <div class="text-xs text-gray-400">
                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-circle text-xs mr-1"></i>Nuevo
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer del Admin -->
        <div class="text-center mt-12 py-8 border-t border-gray-200">
            <div class="mb-4">
                <h4 class="text-lg font-bold text-gray-700 mb-2">
                    <i class="fas fa-crown text-red-600 mr-2"></i>
                    Panel de Administración Clúster
                </h4>
                <p class="text-gray-500">&copy; <?php echo date('Y'); ?> Clúster Intranet - Todos los derechos reservados</p>
            </div>
            
            <div class="flex justify-center items-center space-x-4 text-sm text-gray-400">
                <span>
                    <i class="fas fa-code mr-1"></i>
                    Versión 2.0
                </span>
                <span>•</span>
                <span>
                    <i class="fas fa-clock mr-1"></i>
                    Último acceso: <?php echo date('d/m/Y H:i'); ?>
                </span>
                <span>•</span>
                <span>
                    <i class="fas fa-user-shield mr-1"></i>
                    Administrador: <?php echo htmlspecialchars($adminUser['nombre']); ?>
                </span>
            </div>
            
            <div class="mt-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-red-100 text-red-800">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Sesión Segura
                </span>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh de estadísticas cada 10 minutos
        setInterval(() => {
            // Solo refrescar si el usuario está activo
            if (document.visibilityState === 'visible') {
                location.reload();
            }
        }, 600000);

        // Animaciones mejoradas al cargar
        document.addEventListener('DOMContentLoaded', function() {
            // Añadir efecto de aparición progresiva
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observar todos los elementos con animación
            document.querySelectorAll('.fade-in').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'all 0.6s ease-out';
                observer.observe(el);
            });

            // Efecto hover mejorado para las tarjetas
            document.querySelectorAll('.module-card, .stat-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Contador animado para las estadísticas
            function animateCounters() {
                document.querySelectorAll('.stat-number').forEach(counter => {
                    const target = parseInt(counter.textContent);
                    const increment = target / 50;
                    let current = 0;
                    
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            counter.textContent = target;
                            clearInterval(timer);
                        } else {
                            counter.textContent = Math.ceil(current);
                        }
                    }, 20);
                });
            }

            // Iniciar animación de contadores después de un retraso
            setTimeout(animateCounters, 800);

            // Efecto de pulso para botones importantes
            const importantButtons = document.querySelectorAll('.btn-admin');
            importantButtons.forEach((btn, index) => {
                setTimeout(() => {
                    btn.style.animation = 'pulse 0.6s ease-in-out';
                    setTimeout(() => {
                        btn.style.animation = '';
                    }, 600);
                }, index * 100 + 1500);
            });

            // Añadir indicador de carga para los enlaces
            document.querySelectorAll('.btn-admin').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-spinner fa-spin mr-2';
                        this.style.opacity = '0.7';
                        this.style.pointerEvents = 'none';
                        
                        // Restaurar después de 3 segundos por si acaso
                        setTimeout(() => {
                            this.style.opacity = '1';
                            this.style.pointerEvents = 'auto';
                        }, 3000);
                    }
                });
            });
        });

        // Confirmación mejorada antes de cerrar sesión
        document.querySelector('.logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Crear modal de confirmación personalizado
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeInUp 0.3s ease-out;
            `;
            
            modal.innerHTML = `
                <div style="
                    background: white;
                    padding: 2rem;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 400px;
                    transform: scale(0.9);
                    animation: scaleIn 0.3s ease-out forwards;
                ">
                    <div style="font-size: 3rem; color: #C7252B; margin-bottom: 1rem;">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <h3 style="color: #2C3E50; margin-bottom: 1rem; font-size: 1.5rem;">
                        ¿Cerrar Sesión?
                    </h3>
                    <p style="color: #7f8c8d; margin-bottom: 2rem;">
                        ¿Estás seguro de que quieres cerrar la sesión de administrador?
                    </p>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button id="cancelBtn" style="
                            padding: 0.75rem 1.5rem;
                            background: #95a5a6;
                            color: white;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 600;
                        ">Cancelar</button>
                        <button id="confirmBtn" style="
                            padding: 0.75rem 1.5rem;
                            background: linear-gradient(135deg, #C7252B, #A01E23);
                            color: white;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 600;
                        ">Cerrar Sesión</button>
                    </div>
                </div>
            `;
            
            // Añadir estilos de animación
            const style = document.createElement('style');
            style.textContent = `
                @keyframes scaleIn {
                    to { transform: scale(1); }
                }
            `;
            document.head.appendChild(style);
            
            document.body.appendChild(modal);
            
            // Event listeners
            modal.querySelector('#cancelBtn').addEventListener('click', () => {
                modal.remove();
                style.remove();
            });
            
            modal.querySelector('#confirmBtn').addEventListener('click', () => {
                window.location.href = this.href;
            });
            
            // Cerrar al hacer clic fuera
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                    style.remove();
                }
            });
        });

        // Mostrar notificación de bienvenida
        setTimeout(() => {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 30px;
                background: linear-gradient(135deg, #27AE60, #2ECC71);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 10px;
                box-shadow: 0 4px 20px rgba(39, 174, 96, 0.3);
                z-index: 1000;
                animation: slideInRight 0.5s ease-out;
                cursor: pointer;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center;">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>Panel cargado exitosamente</span>
                    <i class="fas fa-times ml-3" style="opacity: 0.7;"></i>
                </div>
            `;
            
            // Añadir animación
            const notificationStyle = document.createElement('style');
            notificationStyle.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(notificationStyle);
            
            document.body.appendChild(notification);
            
            // Auto-remover después de 4 segundos
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.5s ease-out';
                setTimeout(() => {
                    notification.remove();
                    notificationStyle.remove();
                }, 500);
            }, 4000);
            
            // Remover al hacer clic
            notification.addEventListener('click', () => {
                notification.style.animation = 'slideOutRight 0.5s ease-out';
                setTimeout(() => {
                    notification.remove();
                    notificationStyle.remove();
                }, 500);
            });
        }, 2000);
    </script>
</body>
</html>