<?php
/**
 * API de registro con sistema de aprobación obligatorio
 * Todos los usuarios quedan en estado "pendiente"
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

/**
 * Verificar y crear campo estado_usuario si no existe
 */
function verificarCampoEstado($conn) {
    try {
        // Verificar si la columna existe
        $checkQuery = "SHOW COLUMNS FROM usuarios_perfil LIKE 'estado_usuario'";
        $result = $conn->query($checkQuery);

        if ($result->rowCount() == 0) {
            // Crear el campo si no existe
            $addColumnQuery = "ALTER TABLE usuarios_perfil ADD COLUMN estado_usuario ENUM('activo', 'pendiente', 'rechazado', 'lista_espera') DEFAULT 'pendiente'";
            $conn->exec($addColumnQuery);
            error_log("Campo estado_usuario creado automáticamente");
        }

        return true;
    } catch (Exception $e) {
        error_log("Error verificando campo estado_usuario: " . $e->getMessage());
        return false;
    }
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    responderJSON(false, null, 'Método no permitido');
}

try {
    // Obtener datos JSON del cuerpo de la petición
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

    // Evitar el valor 0 en user_id ya que causa problemas de constraint único
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

    // Validar empresa si es rol empresa
    if ($rol === 'empresa' && !$user_id) {
        responderJSON(false, null, 'Debes seleccionar una empresa para el rol de empresa');
    }

    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Verificar y crear campo estado_usuario si no existe
    verificarCampoEstado($conn);

    // Verificar que el email no exista
    $existingQuery = "SELECT id FROM usuarios_perfil WHERE email = :email";
    $existingStmt = $conn->prepare($existingQuery);
    $existingStmt->bindParam(':email', $email);
    $existingStmt->execute();

    if ($existingStmt->fetch()) {
        responderJSON(false, null, 'El email ya está registrado');
    }

    // Verificar que la empresa existe si se proporcionó
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

    // FORZAR estado pendiente para todos los nuevos usuarios
    $estado_usuario = 'pendiente';

    // Preparar consulta de inserción
    $insertFields = [
        'empresa_id', 'nombre', 'apellidos', 'email', 'password', 'telefono',
        'fecha_nacimiento', 'nombre_empresa', 'rol', 'biografia', 'direccion',
        'ciudad', 'estado', 'codigo_postal', 'pais', 'telefono_emergencia',
        'contacto_emergencia', 'estado_usuario'
    ];

    $insertPlaceholders = ':' . implode(', :', $insertFields);
    $insertFieldsStr = implode(', ', $insertFields);

    $insertQuery = "INSERT INTO usuarios_perfil ({$insertFieldsStr})
                    VALUES ({$insertPlaceholders})";

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

        // Verificar que el usuario se registró con estado pendiente
        $checkQuery = "SELECT estado_usuario FROM usuarios_perfil WHERE id = :user_id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':user_id', $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // Si no es pendiente, forzar actualización
        if (!$checkResult || $checkResult['estado_usuario'] !== 'pendiente') {
            $updateQuery = "UPDATE usuarios_perfil SET estado_usuario = 'pendiente' WHERE id = :user_id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':user_id', $userId);
            $updateStmt->execute();
        }

        // Obtener datos completos del usuario registrado
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
            '¡Registro enviado exitosamente! Tu cuenta está pendiente de aprobación por un administrador. ' .
            'Te notificaremos por email cuando sea aprobada y puedas acceder al sistema.',
            ['status' => 'pending_approval']);

    } else {
        $errorInfo = $insertStmt->errorInfo();
        error_log("Error en insert: " . json_encode($errorInfo));
        responderJSON(false, null, 'Error al registrar usuario: ' . ($errorInfo[2] ?? 'Error desconocido'));
    }

} catch (Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    responderJSON(false, null, 'Error interno del servidor: ' . $e->getMessage());
}
?>