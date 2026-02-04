<?php
/**
 * API para registro de usuarios con sistema de aprobación FORZADO
 * TODOS los usuarios nuevos quedan como "pendiente"
 */

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
session_start();

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

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

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
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        responderJSON(false, null, 'Datos JSON inválidos');
    }

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

    if ($user_id === 0) {
        $user_id = null;
    }

    // Validaciones básicas
    if (empty($nombre)) {
        responderJSON(false, null, 'El nombre es requerido');
    }

    if (empty($apellidos)) {
        responderJSON(false, null, 'Los apellidos son requeridos');
    }

    if (empty($email)) {
        responderJSON(false, null, 'El email es requerido');
    }

    if (!validarEmail($email)) {
        responderJSON(false, null, 'Email inválido');
    }

    if (empty($password)) {
        responderJSON(false, null, 'La contraseña es requerida');
    }

    if (!validarPassword($password)) {
        responderJSON(false, null, 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas y números');
    }

    if (!in_array($rol, ['admin', 'empresa', 'empleado'])) {
        responderJSON(false, null, 'Rol inválido');
    }

    if ($rol === 'empresa' && !$user_id) {
        responderJSON(false, null, 'Debes seleccionar una empresa para el rol de empresa');
    }

    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Crear campo estado_usuario si no existe
    try {
        $checkColumn = $conn->query("SHOW COLUMNS FROM usuarios_perfil LIKE 'estado_usuario'");
        if ($checkColumn->rowCount() == 0) {
            $conn->exec("ALTER TABLE usuarios_perfil ADD COLUMN estado_usuario ENUM('activo', 'pendiente', 'rechazado', 'lista_espera') DEFAULT 'pendiente'");
            error_log("Campo estado_usuario creado automáticamente");
        }
    } catch (Exception $e) {
        error_log("Error creando campo estado_usuario: " . $e->getMessage());
    }

    // Verificar que el email no exista
    $existingQuery = "SELECT id FROM usuarios_perfil WHERE email = :email";
    $existingStmt = $conn->prepare($existingQuery);
    $existingStmt->bindParam(':email', $email);
    $existingStmt->execute();

    if ($existingStmt->fetch()) {
        responderJSON(false, null, 'El email ya está registrado');
    }

    // Verificar empresa si se proporcionó
    if ($user_id) {
        $empresaQuery = "SELECT id, nombre_empresa FROM empresas_convenio WHERE id = :empresa_id AND activo = 1";
        $empresaStmt = $conn->prepare($empresaQuery);
        $empresaStmt->bindParam(':empresa_id', $user_id);
        $empresaStmt->execute();

        $empresa = $empresaStmt->fetch(PDO::FETCH_ASSOC);
        if (!$empresa) {
            responderJSON(false, null, 'La empresa seleccionada no existe o no está activa');
        }
    }

    // Hash de la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // FORZAR estado pendiente - NO negociable
    $estado_usuario = 'pendiente';

    // Insertar usuario CON estado_usuario explícito
    $insertQuery = "INSERT INTO usuarios_perfil
                    (empresa_id, nombre, apellidos, email, password, telefono, fecha_nacimiento,
                     nombre_empresa, rol, biografia, direccion, ciudad, estado, codigo_postal,
                     pais, telefono_emergencia, contacto_emergencia, estado_usuario)
                    VALUES (:empresa_id, :nombre, :apellidos, :email, :password, :telefono, :fecha_nacimiento,
                            :nombre_empresa, :rol, :biografia, :direccion, :ciudad, :estado, :codigo_postal,
                            :pais, :telefono_emergencia, :contacto_emergencia, :estado_usuario)";

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
    $insertStmt->bindParam(':estado_usuario', $estado_usuario);

    if ($insertStmt->execute()) {
        $userId = $conn->lastInsertId();

        // VERIFICAR que el usuario se guardó como pendiente
        $verifyQuery = "SELECT estado_usuario FROM usuarios_perfil WHERE id = :user_id";
        $verifyStmt = $conn->prepare($verifyQuery);
        $verifyStmt->bindParam(':user_id', $userId);
        $verifyStmt->execute();
        $userStatus = $verifyStmt->fetch(PDO::FETCH_ASSOC);

        // Si por alguna razón no es pendiente, FORZAR actualización
        if (!$userStatus || $userStatus['estado_usuario'] !== 'pendiente') {
            $forceUpdateQuery = "UPDATE usuarios_perfil SET estado_usuario = 'pendiente' WHERE id = :user_id";
            $forceStmt = $conn->prepare($forceUpdateQuery);
            $forceStmt->bindParam(':user_id', $userId);
            $forceStmt->execute();
            error_log("FORZADO estado pendiente para usuario ID: $userId");
        }

        // Obtener datos del usuario para respuesta
        $userQuery = "SELECT up.id, up.empresa_id, up.nombre, up.apellidos, up.email, up.rol,
                             up.telefono, up.fecha_nacimiento, up.nombre_empresa, up.biografia,
                             up.direccion, up.ciudad, up.estado, up.codigo_postal, up.pais,
                             up.telefono_emergencia, up.contacto_emergencia, up.estado_usuario,
                             ec.nombre_empresa as empresa_convenio_nombre
                      FROM usuarios_perfil up
                      LEFT JOIN empresas_convenio ec ON up.empresa_id = ec.id
                      WHERE up.id = :user_id";

        $userStmt = $conn->prepare($userQuery);
        $userStmt->bindParam(':user_id', $userId);
        $userStmt->execute();
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

        responderJSON(true, $userData,
            'Registro enviado exitosamente. Tu cuenta está PENDIENTE DE APROBACIÓN por un administrador. ' .
            'No podrás acceder hasta que sea aprobada. Te notificaremos cuando esto ocurra.',
            ['approval_required' => true]);

    } else {
        $errorInfo = $insertStmt->errorInfo();
        error_log("Error insertando usuario: " . json_encode($errorInfo));
        responderJSON(false, null, 'Error al registrar usuario');
    }

} catch (Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    responderJSON(false, null, 'Error interno del servidor');
}
?>