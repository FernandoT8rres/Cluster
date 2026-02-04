<?php
/**
 * Versión de debug del API de registro
 * Muestra errores detallados para diagnóstico
 */

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
session_start();

// Habilitar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

/**
 * Función para responder en JSON con más detalles
 */
function responderJSON($success, $data = null, $message = '', $extra = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c'),
        'debug_info' => $extra
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
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

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    responderJSON(false, null, 'Método no permitido');
}

try {
    $debugInfo = ['step' => 'initialization'];

    // Obtener datos JSON del cuerpo de la petición
    $input = file_get_contents('php://input');
    $debugInfo['raw_input'] = $input;

    $data = json_decode($input, true);
    $debugInfo['parsed_data'] = $data;

    if (!$data) {
        responderJSON(false, null, 'Datos JSON inválidos', $debugInfo);
    }

    $debugInfo['step'] = 'data_validation';

    // Validar campos requeridos
    $nombre = trim($data['nombre'] ?? '');
    $apellidos = trim($data['apellidos'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $rol = $data['rol'] ?? 'empleado';
    $telefono = trim($data['telefono'] ?? '');
    $fecha_nacimiento = !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null;
    $nombre_empresa = trim($data['nombre_empresa'] ?? '');
    $biografia = trim($data['biografia'] ?? '');
    $direccion = trim($data['direccion'] ?? '');
    $ciudad = trim($data['ciudad'] ?? '');
    $estado = trim($data['estado'] ?? '');
    $codigo_postal = trim($data['codigo_postal'] ?? '');
    $pais = trim($data['pais'] ?? 'México');
    $telefono_emergencia = trim($data['telefono_emergencia'] ?? '');
    $contacto_emergencia = trim($data['contacto_emergencia'] ?? '');
    $user_id = !empty($data['empresa_id']) ? intval($data['empresa_id']) : null;

    // Evitar el valor 0 en user_id ya que causa problemas de constraint único
    if ($user_id === 0) {
        $user_id = null;
    }

    $debugInfo['processed_fields'] = [
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'email' => $email,
        'rol' => $rol,
        'empresa_id' => $user_id
    ];

    // Validaciones básicas
    if (empty($nombre)) {
        responderJSON(false, null, 'El nombre es requerido', $debugInfo);
    }

    if (empty($apellidos)) {
        responderJSON(false, null, 'Los apellidos son requeridos', $debugInfo);
    }

    if (empty($email)) {
        responderJSON(false, null, 'El email es requerido', $debugInfo);
    }

    if (!validarEmail($email)) {
        responderJSON(false, null, 'Email inválido', $debugInfo);
    }

    if (empty($password)) {
        responderJSON(false, null, 'La contraseña es requerida', $debugInfo);
    }

    if (!validarPassword($password)) {
        responderJSON(false, null, 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas y números', $debugInfo);
    }

    if (!in_array($rol, ['admin', 'empresa', 'empleado'])) {
        responderJSON(false, null, 'Rol inválido', $debugInfo);
    }

    // Validar empresa si es rol empresa
    if ($rol === 'empresa' && !$user_id) {
        responderJSON(false, null, 'Debes seleccionar una empresa para el rol de empresa', $debugInfo);
    }

    $debugInfo['step'] = 'database_connection';

    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $debugInfo['database_type'] = $db->isUsingSQLite() ? 'SQLite' : 'MySQL';
    $debugInfo['environment_info'] = $db->getEnvironmentInfo();

    $debugInfo['step'] = 'email_check';

    // Verificar que el email no exista
    $existingQuery = "SELECT id FROM usuarios_perfil WHERE email = :email";
    $existingStmt = $conn->prepare($existingQuery);
    $existingStmt->bindParam(':email', $email);
    $existingStmt->execute();

    if ($existingStmt->fetch()) {
        responderJSON(false, null, 'El email ya está registrado', $debugInfo);
    }

    $debugInfo['step'] = 'empresa_validation';

    // Verificar que la empresa existe si se proporcionó
    if ($user_id) {
        $empresaQuery = "SELECT id, nombre_empresa FROM empresas_convenio WHERE id = :empresa_id AND activo = 1";
        $empresaStmt = $conn->prepare($empresaQuery);
        $empresaStmt->bindParam(':empresa_id', $user_id);
        $empresaStmt->execute();

        $empresa = $empresaStmt->fetch(PDO::FETCH_ASSOC);
        if (!$empresa) {
            responderJSON(false, null, 'La empresa seleccionada no existe o no está activa', $debugInfo);
        }
        $debugInfo['empresa_found'] = $empresa;
    }

    $debugInfo['step'] = 'password_hashing';

    // Hash de la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $debugInfo['step'] = 'user_insertion';

    // Insertar usuario
    $insertQuery = "INSERT INTO usuarios_perfil
                    (empresa_id, nombre, apellidos, email, password, telefono, fecha_nacimiento,
                     nombre_empresa, rol, biografia, direccion, ciudad, estado, codigo_postal,
                     pais, telefono_emergencia, contacto_emergencia)
                    VALUES (:empresa_id, :nombre, :apellidos, :email, :password, :telefono, :fecha_nacimiento,
                            :nombre_empresa, :rol, :biografia, :direccion, :ciudad, :estado, :codigo_postal,
                            :pais, :telefono_emergencia, :contacto_emergencia)";

    $debugInfo['insert_query'] = $insertQuery;

    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bindParam(':empresa_id', $user_id);
    $insertStmt->bindParam(':nombre', $nombre);
    $insertStmt->bindParam(':apellidos', $apellidos);
    $insertStmt->bindParam(':email', $email);
    $insertStmt->bindParam(':password', $hashedPassword);
    $insertStmt->bindParam(':telefono', $telefono);
    $insertStmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
    $insertStmt->bindParam(':nombre_empresa', $nombre_empresa);
    $insertStmt->bindParam(':rol', $rol);
    $insertStmt->bindParam(':biografia', $biografia);
    $insertStmt->bindParam(':direccion', $direccion);
    $insertStmt->bindParam(':ciudad', $ciudad);
    $insertStmt->bindParam(':estado', $estado);
    $insertStmt->bindParam(':codigo_postal', $codigo_postal);
    $insertStmt->bindParam(':pais', $pais);
    $insertStmt->bindParam(':telefono_emergencia', $telefono_emergencia);
    $insertStmt->bindParam(':contacto_emergencia', $contacto_emergencia);

    if ($insertStmt->execute()) {
        $userId = $conn->lastInsertId();
        $debugInfo['user_id'] = $userId;
        $debugInfo['step'] = 'user_data_retrieval';

        // Obtener datos completos del usuario registrado
        $userQuery = "SELECT up.id, up.empresa_id, up.nombre, up.apellidos, up.email, up.rol,
                             up.telefono, up.fecha_nacimiento, up.nombre_empresa, up.biografia,
                             up.direccion, up.ciudad, up.estado, up.codigo_postal, up.pais,
                             up.telefono_emergencia, up.contacto_emergencia,
                             ec.nombre_empresa as empresa_convenio_nombre
                      FROM usuarios_perfil up
                      LEFT JOIN empresas_convenio ec ON up.empresa_id = ec.id
                      WHERE up.id = :user_id";

        $userStmt = $conn->prepare($userQuery);
        $userStmt->bindParam(':user_id', $userId);
        $userStmt->execute();

        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        $userData['avatar_url'] = './api/get-avatar.php?user_id=' . $userId;

        $debugInfo['step'] = 'success';
        responderJSON(true, $userData, 'Usuario registrado exitosamente', $debugInfo);
    } else {
        $debugInfo['insert_error'] = $insertStmt->errorInfo();
        responderJSON(false, null, 'Error al registrar usuario', $debugInfo);
    }

} catch (Exception $e) {
    $debugInfo['step'] = 'exception';
    $debugInfo['exception'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];

    error_log("Error en registro: " . $e->getMessage());
    responderJSON(false, null, 'Error interno del servidor', $debugInfo);
}
?>