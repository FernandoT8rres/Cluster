<?php
/**
 * API para gestión de usuarios en el panel de administración
 */

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
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
        'timestamp' => date('c')
    ];
    
    foreach ($extra as $key => $value) {
        $response[$key] = $value;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Verificar que el usuario es administrador
 */
function verificarAdmin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_data'])) {
        responderJSON(false, null, 'No hay sesión activa');
    }
    
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $query = "SELECT rol FROM usuarios_perfil WHERE id = :user_id AND activo = 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || $user['rol'] !== 'admin') {
            responderJSON(false, null, 'Acceso denegado. Solo administradores');
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error verificando admin: " . $e->getMessage());
        responderJSON(false, null, 'Error de verificación');
    }
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar contraseña
 */
function validarPassword($password) {
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

try {
    // Verificar permisos de administrador
    verificarAdmin();
    
    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Obtener todos los usuarios
            $query = "SELECT up.id, up.nombre, up.apellido, up.email, up.rol, 
                             up.empresa_id, up.telefono, up.cargo, up.departamento, up.activo,
                             up.fecha_registro, up.fecha_ultimo_acceso,
                             ec.nombre_empresa
                      FROM usuarios_perfil up
                      LEFT JOIN empresas_convenio ec ON up.empresa_id = ec.id
                      ORDER BY up.fecha_registro DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agregar URL del avatar para cada usuario
            foreach ($users as &$user) {
                $user['avatar_url'] = './api/get-avatar.php?user_id=' . $user['id'];
                $user['activo'] = (bool)$user['activo'];
            }
            
            responderJSON(true, $users, 'Usuarios obtenidos correctamente');
            break;
            
        case 'POST':
            // Crear nuevo usuario
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                responderJSON(false, null, 'Datos JSON inválidos');
            }
            
            // Validar campos requeridos
            $nombre = trim($data['nombre'] ?? '');
            $apellido = trim($data['apellido'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $rol = $data['rol'] ?? 'empleado';
            $telefono = trim($data['telefono'] ?? '');
            $cargo = trim($data['cargo'] ?? '');
            $departamento = trim($data['departamento'] ?? '');
            $empresa_id = !empty($data['empresa_id']) ? intval($data['empresa_id']) : null;
            
            // Validaciones
            if (empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
                responderJSON(false, null, 'Nombre, apellido, email y contraseña son requeridos');
            }
            
            if (!validarEmail($email)) {
                responderJSON(false, null, 'Email inválido');
            }
            
            if (!validarPassword($password)) {
                responderJSON(false, null, 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas y números');
            }
            
            if (!in_array($rol, ['admin', 'empresa', 'empleado'])) {
                responderJSON(false, null, 'Rol inválido');
            }
            
            // Verificar que el email no exista
            $existingQuery = "SELECT id FROM usuarios_perfil WHERE email = :email";
            $existingStmt = $conn->prepare($existingQuery);
            $existingStmt->bindParam(':email', $email);
            $existingStmt->execute();
            
            if ($existingStmt->fetch()) {
                responderJSON(false, null, 'El email ya está registrado');
            }
            
            // Hash de la contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $insertQuery = "INSERT INTO usuarios_perfil 
                            (nombre, apellido, email, password, rol, empresa_id, telefono, cargo, departamento, activo, fecha_registro) 
                            VALUES (:nombre, :apellido, :email, :password, :rol, :empresa_id, :telefono, :cargo, :departamento, 1, NOW())";
            
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->bindParam(':nombre', $nombre);
            $insertStmt->bindParam(':apellido', $apellido);
            $insertStmt->bindParam(':email', $email);
            $insertStmt->bindParam(':password', $hashedPassword);
            $insertStmt->bindParam(':rol', $rol);
            $insertStmt->bindParam(':empresa_id', $empresa_id);
            $insertStmt->bindParam(':telefono', $telefono);
            $insertStmt->bindParam(':cargo', $cargo);
            $insertStmt->bindParam(':departamento', $departamento);
            
            if ($insertStmt->execute()) {
                $userId = $conn->lastInsertId();
                responderJSON(true, ['id' => $userId], 'Usuario creado correctamente');
            } else {
                responderJSON(false, null, 'Error al crear usuario');
            }
            break;
            
        case 'PUT':
            // Actualizar usuario existente
            $userId = $_GET['id'] ?? null;
            
            if (!$userId) {
                responderJSON(false, null, 'ID de usuario requerido');
            }
            
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                responderJSON(false, null, 'Datos JSON inválidos');
            }
            
            // Validar campos
            $nombre = trim($data['nombre'] ?? '');
            $apellido = trim($data['apellido'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $rol = $data['rol'] ?? '';
            $telefono = trim($data['telefono'] ?? '');
            $cargo = trim($data['cargo'] ?? '');
            $departamento = trim($data['departamento'] ?? '');
            $empresa_id = !empty($data['empresa_id']) ? intval($data['empresa_id']) : null;
            
            if (empty($nombre) || empty($apellido) || empty($email)) {
                responderJSON(false, null, 'Nombre, apellido y email son requeridos');
            }
            
            if (!validarEmail($email)) {
                responderJSON(false, null, 'Email inválido');
            }
            
            if (!in_array($rol, ['admin', 'empresa', 'empleado'])) {
                responderJSON(false, null, 'Rol inválido');
            }
            
            // Verificar que el email no exista en otro usuario
            $existingQuery = "SELECT id FROM usuarios_perfil WHERE email = :email AND id != :user_id";
            $existingStmt = $conn->prepare($existingQuery);
            $existingStmt->bindParam(':email', $email);
            $existingStmt->bindParam(':user_id', $userId);
            $existingStmt->execute();
            
            if ($existingStmt->fetch()) {
                responderJSON(false, null, 'El email ya está registrado en otro usuario');
            }
            
            // Construir query de actualización
            $updateFields = [
                'nombre = :nombre',
                'apellido = :apellido', 
                'email = :email',
                'rol = :rol',
                'empresa_id = :empresa_id',
                'telefono = :telefono',
                'cargo = :cargo',
                'departamento = :departamento'
            ];
            
            $params = [
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':email' => $email,
                ':rol' => $rol,
                ':empresa_id' => $empresa_id,
                ':telefono' => $telefono,
                ':cargo' => $cargo,
                ':departamento' => $departamento,
                ':user_id' => $userId
            ];
            
            // Agregar contraseña si se proporcionó
            if (!empty($password)) {
                if (!validarPassword($password)) {
                    responderJSON(false, null, 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas y números');
                }
                $updateFields[] = 'password = :password';
                $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $updateQuery = "UPDATE usuarios_perfil SET " . implode(', ', $updateFields) . " WHERE id = :user_id";
            
            $updateStmt = $conn->prepare($updateQuery);
            
            if ($updateStmt->execute($params)) {
                responderJSON(true, null, 'Usuario actualizado correctamente');
            } else {
                responderJSON(false, null, 'Error al actualizar usuario');
            }
            break;
            
        case 'DELETE':
            // Desactivar usuario (no eliminar completamente)
            $userId = $_GET['id'] ?? null;
            
            if (!$userId) {
                responderJSON(false, null, 'ID de usuario requerido');
            }
            
            // No permitir eliminar el propio usuario admin
            if ($userId == $_SESSION['user_id']) {
                responderJSON(false, null, 'No puedes desactivar tu propia cuenta');
            }
            
            $deleteQuery = "UPDATE usuarios_perfil SET activo = 0 WHERE id = :user_id";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':user_id', $userId);
            
            if ($deleteStmt->execute()) {
                responderJSON(true, null, 'Usuario desactivado correctamente');
            } else {
                responderJSON(false, null, 'Error al desactivar usuario');
            }
            break;
            
        default:
            http_response_code(405);
            responderJSON(false, null, 'Método no permitido');
    }
    
} catch (Exception $e) {
    error_log("Error en gestión de usuarios: " . $e->getMessage());
    responderJSON(false, null, 'Error interno del servidor');
}
?>