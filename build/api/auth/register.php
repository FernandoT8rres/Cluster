<?php
/**
 * API para registro de usuarios con sistema de aprobación
 * Todos los usuarios nuevos quedan pendientes de aprobación
 */

// Definir constante de acceso
define('CLAUT_ACCESS', true);

// Configuración segura de sesiones
require_once dirname(dirname(__DIR__)) . '/config/session-config.php';
SessionConfig::init();

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

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    responderJSON(false, null, 'Método no permitido');
}

// ============================================
// RATE LIMITING - Protección contra spam de registros
// ============================================
try {
    require_once dirname(dirname(__DIR__)) . '/middleware/rate-limiter.php';
    
    $rateLimiter = new RateLimiter();
    $clientIP = getRateLimitIdentifier();
    
    // Verificar límite (3 registros / hora)
    $status = $rateLimiter->getStatus(
        $clientIP,
        RateLimitConfig::REGISTER['max'],
        RateLimitConfig::REGISTER['window'],
        RateLimitConfig::REGISTER['action']
    );
    
    // Si se excedió el límite, bloquear
    if (!$status['allowed']) {
        http_response_code(429);
        responderJSON(false, null, 
            'Demasiados intentos de registro. Intenta de nuevo en ' . 
            ceil($status['retry_after'] / 60) . ' minutos.',
            ['retry_after' => $status['retry_after']]
        );
    }
    
    // Registrar intento
    $rateLimiter->recordAttempt($clientIP, 'register');
    
} catch (Exception $e) {
    // Si hay error en rate limiter, continuar sin bloquear
    error_log("Error en rate limiter (register): " . $e->getMessage());
}
// ============================================

try {
    // Obtener datos JSON del cuerpo de la petición
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        responderJSON(false, null, 'Datos JSON inválidos');
    }
    
    // ============================================
    // VALIDACIÓN CON API VALIDATOR
    // ============================================
    require_once dirname(dirname(__DIR__)) . '/middleware/api-validator.php';
    
    $validation = ApiValidator::validateAndSanitize($data, [
        'nombre' => 'required|string|min:2|max:100',
        'apellidos' => 'required|string|min:2|max:100',
        'email' => 'required|email|max:255',
        'password' => 'required|string|min:8|max:255',
        'rol' => 'string|in:admin,empresa,empleado',
        'telefono' => 'string|min:10|max:15',
        'fecha_nacimiento' => 'string',
        'nombre_empresa' => 'string|max:255',
        'biografia' => 'string|max:1000',
        'direccion' => 'string|max:255',
        'ciudad' => 'string|max:100',
        'estado' => 'string|max:100',
        'codigo_postal' => 'string|max:10',
        'pais' => 'string|max:100',
        'telefono_emergencia' => 'string|min:10|max:15',
        'contacto_emergencia' => 'string|max:255',
        'empresa_id' => 'int|min:1'
    ]);
    
    if (!$validation['valid']) {
        ApiValidator::errorResponse($validation['errors']);
    }
    
    $cleanData = $validation['data'];
    // ============================================
    
    // Extraer datos validados y sanitizados
    $nombre = $cleanData['nombre'];
    $apellidos = $cleanData['apellidos'];
    $email = $cleanData['email'];
    $password = $data['password']; // Password no sanitizado para validación
    $rol = $cleanData['rol'] ?? 'empleado';
    $telefono = $cleanData['telefono'] ?? '';
    $fecha_nacimiento = $cleanData['fecha_nacimiento'] ?? null;
    $nombre_empresa = $cleanData['nombre_empresa'] ?? '';
    $biografia = $cleanData['biografia'] ?? '';
    $direccion = $cleanData['direccion'] ?? '';
    $ciudad = $cleanData['ciudad'] ?? '';
    $estado = $cleanData['estado'] ?? '';
    $codigo_postal = $cleanData['codigo_postal'] ?? '';
    $pais = $cleanData['pais'] ?? 'México';
    $telefono_emergencia = $cleanData['telefono_emergencia'] ?? '';
    $contacto_emergencia = $cleanData['contacto_emergencia'] ?? '';
    $user_id = $cleanData['empresa_id'] ?? null;

    // Evitar el valor 0 en user_id ya que causa problemas de constraint único
    if ($user_id === 0) {
        $user_id = null;
    }
    
    // Validación adicional de contraseña (seguridad)
    if (!validarPassword($password)) {
        responderJSON(false, null, 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas y números');
    }
    
    // Validar empresa si es rol empresa
    if ($rol === 'empresa' && !$user_id) {
        responderJSON(false, null, 'Debes seleccionar una empresa para el rol de empresa');
    }
    
    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Crear campo estado_usuario si no existe (para sistema de aprobación)
    try {
        $checkColumn = $conn->query("SHOW COLUMNS FROM usuarios_perfil LIKE 'estado_usuario'");
        if ($checkColumn->rowCount() == 0) {
            $conn->exec("ALTER TABLE usuarios_perfil ADD COLUMN estado_usuario ENUM('activo', 'pendiente', 'rechazado', 'lista_espera') DEFAULT 'pendiente'");
            error_log("Campo estado_usuario creado automáticamente");
        }
    } catch (Exception $e) {
        error_log("Error verificando campo estado_usuario: " . $e->getMessage());
    }
    
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
    
    // Insertar usuario con estado "pendiente" para aprobación
    $estado_usuario = 'pendiente'; // TODOS los nuevos usuarios requieren aprobación

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

        // Verificar que el usuario se registró como pendiente
        $verifyQuery = "SELECT estado_usuario FROM usuarios_perfil WHERE id = :user_id";
        $verifyStmt = $conn->prepare($verifyQuery);
        $verifyStmt->bindParam(':user_id', $userId);
        $verifyStmt->execute();
        $userStatus = $verifyStmt->fetch(PDO::FETCH_ASSOC);

        // Si no es pendiente, forzar actualización
        if (!$userStatus || $userStatus['estado_usuario'] !== 'pendiente') {
            $forceUpdateQuery = "UPDATE usuarios_perfil SET estado_usuario = 'pendiente' WHERE id = :user_id";
            $forceStmt = $conn->prepare($forceUpdateQuery);
            $forceStmt->bindParam(':user_id', $userId);
            $forceStmt->execute();
            error_log("Forzando estado pendiente para usuario ID: $userId");
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
        $userData['avatar_url'] = './api/get-avatar.php?user_id=' . $userId;
        
        // Resetear contador de rate limiting después de registro exitoso
        try {
            if (isset($rateLimiter) && isset($clientIP)) {
                $rateLimiter->reset($clientIP, 'register');
            }
        } catch (Exception $e) {
            error_log("Error reseteando rate limiter (register): " . $e->getMessage());
        }
        
        responderJSON(true, $userData, 'Registro enviado exitosamente. Tu cuenta está PENDIENTE DE APROBACIÓN por un administrador. No podrás acceder hasta que sea aprobada. Te notificaremos cuando esto ocurra.', ['approval_required' => true, 'status' => 'pending']);
    } else {
        responderJSON(false, null, 'Error al registrar usuario');
    }
    
} catch (Exception $e) {
    error_log("Error en registro: " . $e->getMessage());
    responderJSON(false, null, 'Error interno del servidor');
}
?>