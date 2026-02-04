<?php
/**
 * API de Gestión de Usuarios (Admin)
 */

// Definir constante de acceso
define('CLAUT_ACCESS', true);

// Configuración segura de sesiones
require_once dirname(dirname(__DIR__)) . '/config/session-config.php';
SessionConfig::init();

header('Content-Type: application/json; charset=UTF-8');

// CORS Dinámico para permitir credenciales
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

/**
 * Función para responder en JSON
 */
function responderJSON($success, $data = null, $message = '', $extra = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c'),
        'debug' => [
            'session_id' => session_id(),
            'session_vars' => array_keys($_SESSION)
        ]
    ];
    
    foreach ($extra as $key => $value) {
        $response[$key] = $value;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Verificar que el usuario es administrador - ORDEN CORREGIDO
 */
function verificarAdmin() {
    // PRIORIDAD 1: Verificar formato session.php (el que se usa en login)
    if (isset($_SESSION['user_email']) && isset($_SESSION['user_rol'])) {
        if ($_SESSION['user_rol'] === 'admin') {
            error_log("✅ Admin verificado users.php - Email: " . $_SESSION['user_email']);
            return true;
        }
    }
    
    // PRIORIDAD 2: Verificar formato antiguo (user_data)
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_data'])) {
        $userData = $_SESSION['user_data'];
        if (isset($userData['rol']) && $userData['rol'] === 'admin') {
            error_log("✅ Admin verificado users.php con formato user_data");
            return true;
        }
    }
    
    error_log("❌ Verificación admin users.php FALLÓ - Session: " . print_r($_SESSION, true));
    responderJSON(false, null, 'Acceso denegado. Solo administradores');
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Obtener todos los usuarios
 */
function obtenerUsuarios() {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Usar la misma consulta que funciona en gestionar_usuarios.php
        $query = "SELECT u.id, u.nombre, u.apellidos, u.email, u.telefono, u.rol,
                         CASE WHEN u.ultima_actividad IS NOT NULL THEN 1 ELSE 0 END as estado,
                         COALESCE(u.fecha_ingreso, u.created_at) as fecha_registro, u.user_id, e.nombre_empresa,
                         u.departamento
                  FROM usuarios_perfil u
                  LEFT JOIN empresas_convenio e ON u.user_id = e.id
                  ORDER BY COALESCE(u.fecha_ingreso, u.created_at) DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();

        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Log para debugging
        error_log("Usuarios encontrados en API: " . count($usuarios));

        return $usuarios;

    } catch (Exception $e) {
        error_log("Error obteniendo usuarios: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener un usuario por ID
 */
function obtenerUsuarioPorId($userId) {
    // OPCIONAL: Validación adicional (no altera funcionamiento)
    if (file_exists(dirname(dirname(__DIR__)) . '/middleware/api-validator.php')) {
        require_once dirname(dirname(__DIR__)) . '/middleware/api-validator.php';
        
        $validation = ApiValidator::validateField($userId, 'required|int|min:1', 'user_id');
        
        if (!$validation['valid']) {
            responderJSON(false, null, $validation['error']);
        }
    }
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $query = "SELECT u.*, e.nombre_empresa 
                  FROM usuarios_perfil u
                  LEFT JOIN empresas_convenio e ON u.empresa_id = e.id
                  WHERE u.id = :user_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error obteniendo usuario: " . $e->getMessage());
        return null;
    }
}

/**
 * Crear un nuevo usuario
 */
function crearUsuario($data) {
    // OPCIONAL: Validación adicional (no altera funcionamiento)
    if (file_exists(dirname(dirname(__DIR__)) . '/middleware/api-validator.php')) {
        require_once dirname(dirname(__DIR__)) . '/middleware/api-validator.php';
        
        $rules = [
            'nombre' => 'required|string|min:2|max:100',
            'apellido' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'rol' => 'required|in:admin,empleado,usuario',
            'telefono' => 'string|min:10|max:15',
            'cargo' => 'string|max:100',
            'departamento' => 'string|max:100'
        ];
        
        $validation = ApiValidator::validateAndSanitize($data, $rules);
        
        if (!$validation['valid']) {
            responderJSON(false, null, 'Errores de validación', ['validation_errors' => $validation['errors']]);
        }
        
        // Usar datos sanitizados
        $data = $validation['data'];
    }
    
    // LÓGICA ORIGINAL
    if (!validarEmail($data['email'])) {
        responderJSON(false, null, 'Email inválido');
    }
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar si el email ya existe
        $checkQuery = "SELECT id FROM usuarios_perfil WHERE email = :email";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $data['email']);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            responderJSON(false, null, 'El email ya está registrado');
        }
        
        // Hashear contraseña
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO usuarios_perfil 
                  (nombre, apellido, email, password, rol, telefono, cargo, departamento, empresa_id, activo)
                  VALUES 
                  (:nombre, :apellido, :email, :password, :rol, :telefono, :cargo, :departamento, :empresa_id, 1)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':apellido', $data['apellido']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $passwordHash);
        $stmt->bindParam(':rol', $data['rol']);
        $stmt->bindParam(':telefono', $data['telefono']);
        $stmt->bindParam(':cargo', $data['cargo']);
        $stmt->bindParam(':departamento', $data['departamento']);
        $stmt->bindParam(':empresa_id', $data['empresa_id']);
        
        $stmt->execute();
        
        return $conn->lastInsertId();
        
    } catch (Exception $e) {
        error_log("Error creando usuario: " . $e->getMessage());
        responderJSON(false, null, 'Error al crear usuario');
    }
}

/**
 * Actualizar un usuario
 */
function actualizarUsuario($userId, $data) {
    if (!validarEmail($data['email'])) {
        responderJSON(false, null, 'Email inválido');
    }
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Verificar si el email ya existe (excepto el usuario actual)
        $checkQuery = "SELECT id FROM usuarios_perfil WHERE email = :email AND id != :user_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $data['email']);
        $checkStmt->bindParam(':user_id', $userId);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            responderJSON(false, null, 'El email ya está en uso');
        }
        
        // Construir query de actualización
        $query = "UPDATE usuarios_perfil SET 
                  nombre = :nombre,
                  apellido = :apellido,
                  email = :email,
                  rol = :rol,
                  telefono = :telefono,
                  cargo = :cargo,
                  departamento = :departamento,
                  empresa_id = :empresa_id";
        
        // Si se proporciona contraseña, actualizarla
        if (!empty($data['password'])) {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id = :user_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':apellido', $data['apellido']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':rol', $data['rol']);
        $stmt->bindParam(':telefono', $data['telefono']);
        $stmt->bindParam(':cargo', $data['cargo']);
        $stmt->bindParam(':departamento', $data['departamento']);
        $stmt->bindParam(':empresa_id', $data['empresa_id']);
        $stmt->bindParam(':user_id', $userId);
        
        if (!empty($data['password'])) {
            $stmt->bindParam(':password', $passwordHash);
        }
        
        $stmt->execute();
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error actualizando usuario: " . $e->getMessage());
        responderJSON(false, null, 'Error al actualizar usuario');
    }
}

/**
 * Cambiar estado de un usuario
 */
function cambiarEstadoUsuario($userId, $activo) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $query = "UPDATE usuarios_perfil SET activo = :activo WHERE id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':user_id', $userId);
        
        $stmt->execute();
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error cambiando estado de usuario: " . $e->getMessage());
        responderJSON(false, null, 'Error al cambiar estado');
    }
}

// Verificar permisos de administrador
verificarAdmin();

// Manejar las diferentes operaciones
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $user = obtenerUsuarioPorId($_GET['id']);
            if ($user) {
                responderJSON(true, $user, 'Usuario obtenido');
            } else {
                responderJSON(false, null, 'Usuario no encontrado');
            }
        } else {
            $users = obtenerUsuarios();
            responderJSON(true, $users, 'Usuarios obtenidos');
        }
        break;
        
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            responderJSON(false, null, 'Datos inválidos');
        }
        
        $userId = crearUsuario($input);
        responderJSON(true, ['id' => $userId], 'Usuario creado correctamente');
        break;
        
    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($_GET['id'])) {
            responderJSON(false, null, 'Datos inválidos');
        }
        
        actualizarUsuario($_GET['id'], $input);
        responderJSON(true, null, 'Usuario actualizado correctamente');
        break;
        
    case 'DELETE':
        if (!isset($_GET['id'])) {
            responderJSON(false, null, 'ID de usuario requerido');
        }
        
        cambiarEstadoUsuario($_GET['id'], 0);
        responderJSON(true, null, 'Usuario desactivado correctamente');
        break;
        
    default:
        http_response_code(405);
        responderJSON(false, null, 'Método no permitido');
}
?>
