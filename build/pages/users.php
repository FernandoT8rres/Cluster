<?php
require_once '../assets/conexion/config.php';

// Verificar que el usuario esté logueado y sea admin
if (!verificarSesion()) {
    redirigir('dashboard.html');
}

$usuarioActual = obtenerUsuarioActual();
if (!$usuarioActual || $usuarioActual['rol'] !== 'admin') {
    redirigir('../dashboard.php');
}

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $usuario = new Usuario();
    $action = $_POST['action'];
    $userId = (int)($_POST['user_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'cambiar_estado':
                $nuevoEstado = $_POST['estado'] ?? '';
                if (in_array($nuevoEstado, ['activo', 'inactivo', 'pendiente'])) {
                    $resultado = $usuario->cambiarEstado($userId, $nuevoEstado);
                    if ($resultado) {
                        respuestaJSON(['success' => true, 'message' => 'Estado actualizado correctamente']);
                    } else {
                        respuestaJSON(['success' => false, 'message' => 'Error al actualizar el estado']);
                    }
                } else {
                    respuestaJSON(['success' => false, 'message' => 'Estado no válido']);
                }
                break;
                
            case 'eliminar':
                $resultado = $usuario->eliminar($userId);
                if ($resultado) {
                    respuestaJSON(['success' => true, 'message' => 'Usuario eliminado correctamente']);
                } else {
                    respuestaJSON(['success' => false, 'message' => 'Error al eliminar el usuario']);
                }
                break;
                
            default:
                respuestaJSON(['success' => false, 'message' => 'Acción no válida']);
        }
    } catch (Exception $e) {
        respuestaJSON(['success' => false, 'message' => 'Error interno del servidor']);
    }
    exit;
}

// Obtener filtros
$filtroRol = $_GET['rol'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';

$filtros = [];
if ($filtroRol) $filtros['rol'] = $filtroRol;
if ($filtroEstado) $filtros['estado'] = $filtroEstado;

// Obtener usuarios
$usuario = new Usuario();
$usuarios = $usuario->obtenerTodos($filtros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Clúster Intranet</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="../assets/css/argon-dashboard-tailwind.css?v=1.0.1" rel="stylesheet" />
    <style>
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #344767;
            position: fixed;
            left: 0;
            top: 0;
            color: white;
            overflow-y: auto;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            background: #f8f9fa;
            min-height: 100vh;
        }
        .nav-item {
            padding: 0.75rem 1.5rem;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .table-container {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-activo { background: #d1fae5; color: #065f46; }
        .status-inactivo { background: #fee2e2; color: #991b1b; }
        .status-pendiente { background: #fef3c7; color: #92400e; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.75rem;
            max-width: 500px;
            width: 90%;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="p-4 border-b border-gray-600">
            <h2 class="text-xl font-bold">Clúster Intranet</h2>
        </div>
        
        <div class="p-4 border-b border-gray-600">
            <div class="flex items-center space-x-3">
                <div class="user-avatar">
                    <?= strtoupper(substr($usuarioActual['nombre'], 0, 1) . substr($usuarioActual['apellido'], 0, 1)) ?>
                </div>
                <div>
                    <div class="font-semibold"><?= htmlspecialchars($usuarioActual['nombre'] . ' ' . $usuarioActual['apellido']) ?></div>
                    <div class="text-sm text-gray-300">Administrador</div>
                </div>
            </div>
        </div>
        
        <ul class="py-4">
            <li><a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt mr-3"></i>Dashboard</a></li>
            <li><a href="users.php" class="nav-item active"><i class="fas fa-users mr-3"></i>Usuarios</a></li>
            <li><a href="companies.php" class="nav-item"><i class="fas fa-building mr-3"></i>Empresas</a></li>
            <li><a href="reports.php" class="nav-item"><i class="fas fa-chart-bar mr-3"></i>Reportes</a></li>
            <li><a href="profile.php" class="nav-item"><i class="fas fa-user mr-3"></i>Mi Perfil</a></li>
            <li><a href="settings.php" class="nav-item"><i class="fas fa-cog mr-3"></i>Configuración</a></li>
            <li><a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt mr-3"></i>Cerrar Sesión</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Gestión de Usuarios</h1>
                <p class="text-gray-600">Administra todos los usuarios del sistema</p>
            </div>
            <button onclick="location.href='register.html'" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-plus mr-2"></i>Nuevo Usuario
            </button>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Rol</label>
                    <select id="filtroRol" class="w-full p-2 border rounded-lg">
                        <option value="">Todos los roles</option>
                        <option value="admin" <?= $filtroRol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                        <option value="empresa" <?= $filtroRol === 'empresa' ? 'selected' : '' ?>>Empresa</option>
                        <option value="empleado" <?= $filtroRol === 'empleado' ? 'selected' : '' ?>>Empleado</option>
                        <option value="invitado" <?= $filtroRol === 'invitado' ? 'selected' : '' ?>>Invitado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filtrar por Estado</label>
                    <select id="filtroEstado" class="w-full p-2 border rounded-lg">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?= $filtroEstado === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= $filtroEstado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        <option value="pendiente" <?= $filtroEstado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button onclick="aplicarFiltros()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg mr-2">
                        Aplicar Filtros
                    </button>
                    <button onclick="limpiarFiltros()" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="table-container">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($usuarios as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="user-avatar mr-3" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                        <?= strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?>
                                        </div>
                                        <?php if ($user['telefono']): ?>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($user['telefono']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($user['email']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">
                                <?= htmlspecialchars($user['rol']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="status-badge status-<?= $user['estado'] ?>">
                                    <?= ucfirst($user['estado']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d/m/Y', strtotime($user['fecha_registro'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <select onchange="cambiarEstado(<?= $user['id'] ?>, this.value)" class="text-xs border rounded px-2 py-1">
                                        <option value="activo" <?= $user['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                                        <option value="inactivo" <?= $user['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                        <option value="pendiente" <?= $user['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                    </select>
                                    <button onclick="eliminarUsuario(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?>')" 
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No se encontraron usuarios
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de Confirmación -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3 class="text-lg font-semibold mb-4">Confirmar Acción</h3>
            <p id="confirmMessage" class="text-gray-600 mb-6"></p>
            <div class="flex justify-end space-x-4">
                <button onclick="cerrarModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Cancelar
                </button>
                <button id="confirmButton" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <script>
        function aplicarFiltros() {
            const rol = document.getElementById('filtroRol').value;
            const estado = document.getElementById('filtroEstado').value;
            
            const params = new URLSearchParams();
            if (rol) params.set('rol', rol);
            if (estado) params.set('estado', estado);
            
            window.location.href = 'users.php?' + params.toString();
        }
        
        function limpiarFiltros() {
            window.location.href = 'users.php';
        }
        
        function cambiarEstado(userId, nuevoEstado) {
            const formData = new FormData();
            formData.append('action', 'cambiar_estado');
            formData.append('user_id', userId);
            formData.append('estado', nuevoEstado);
            
            fetch('users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                    // Recargar para revertir el cambio visual
                    setTimeout(() => location.reload(), 1000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error de conexión', 'error');
                setTimeout(() => location.reload(), 1000);
            });
        }
        
        function eliminarUsuario(userId, nombreUsuario) {
            document.getElementById('confirmMessage').textContent = 
                `¿Estás seguro de que deseas eliminar al usuario "${nombreUsuario}"? Esta acción no se puede deshacer.`;
            
            document.getElementById('confirmButton').onclick = function() {
                const formData = new FormData();
                formData.append('action', 'eliminar');
                formData.append('user_id', userId);
                
                fetch('users.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    cerrarModal();
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    cerrarModal();
                    console.error('Error:', error);
                    showNotification('Error de conexión', 'error');
                });
            };
            
            document.getElementById('confirmModal').classList.add('show');
        }
        
        function cerrarModal() {
            document.getElementById('confirmModal').classList.remove('show');
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
            } text-white`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>