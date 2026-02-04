<?php
// register.php - Procesamiento del registro de usuarios
require_once '../assets/conexion/config.php';

// Configurar headers para respuesta JSON
header('Content-Type: application/json');

iniciarSesion();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener y limpiar datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $telefono = trim($_POST['telefono'] ?? '');
    $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
    $departamento = trim($_POST['departamento'] ?? '');
    $biografia = trim($_POST['biografia'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $pais = trim($_POST['pais'] ?? 'México');
    $telefono_emergencia = trim($_POST['telefono_emergencia'] ?? '');
    $contacto_emergencia = trim($_POST['contacto_emergencia'] ?? '');
    $rol = trim($_POST['rol'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $accept_terms = isset($_POST['accept_terms']);
    
    // Array para errores de validación
    $errors = [];
    
    // Validaciones del lado del servidor
    
    // Validar nombre
    if (empty($nombre)) {
        $errors['nombre'] = 'El nombre es requerido.';
    } elseif (strlen($nombre) < 2) {
        $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres.';
    } elseif (strlen($nombre) > 50) {
        $errors['nombre'] = 'El nombre no puede tener más de 50 caracteres.';
    } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
        $errors['nombre'] = 'El nombre solo puede contener letras y espacios.';
    }
    
    // Validar apellidos
    if (empty($apellidos)) {
        $errors['apellidos'] = 'Los apellidos son requeridos.';
    } elseif (strlen($apellidos) < 2) {
        $errors['apellidos'] = 'Los apellidos deben tener al menos 2 caracteres.';
    } elseif (strlen($apellidos) > 100) {
        $errors['apellidos'] = 'Los apellidos no pueden tener más de 100 caracteres.';
    } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellidos)) {
        $errors['apellidos'] = 'Los apellidos solo pueden contener letras y espacios.';
    }
    
    // Validar email
    if (empty($email)) {
        $errors['email'] = 'El email es requerido.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Por favor, ingresa un email válido.';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'El email no puede tener más de 100 caracteres.';
    } else {
        // Verificar si el email ya existe
        $usuario = new Usuario();
        $emailExiste = $usuario->obtenerPorEmail($email);
        if ($emailExiste) {
            $errors['email'] = 'Este email ya está registrado.';
        }
    }
    
    // Validar teléfono (opcional)
    if (!empty($telefono)) {
        if (!preg_match('/^\+?[\d\s\-\(\)]{8,20}$/', $telefono)) {
            $errors['telefono'] = 'Por favor, ingresa un teléfono válido.';
        }
    }
    
    // Validar rol
    $rolesPermitidos = ['empleado', 'empresa', 'invitado'];
    if (empty($rol)) {
        $errors['rol'] = 'Por favor, selecciona un rol.';
    } elseif (!in_array($rol, $rolesPermitidos)) {
        $errors['rol'] = 'El rol seleccionado no es válido.';
    }
    
    // Validar contraseña
    if (empty($password)) {
        $errors['password'] = 'La contraseña es requerida.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (strlen($password) > 255) {
        $errors['password'] = 'La contraseña es demasiado larga.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $errors['password'] = 'La contraseña debe incluir mayúsculas, minúsculas y números.';
    }
    
    // Validar confirmación de contraseña
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Las contraseñas no coinciden.';
    }
    
    // Validar términos y condiciones
    if (!$accept_terms) {
        $errors['accept_terms'] = 'Debes aceptar los términos y condiciones.';
    }
    
    // Si hay errores, devolver respuesta con errores
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, corrige los errores en el formulario.',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Si no hay errores, crear el usuario
    $usuario = new Usuario();
    
    $datosUsuario = [
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'email' => $email,
        'password' => $password,
        'telefono' => !empty($telefono) ? $telefono : null,
        'fecha_nacimiento' => $fecha_nacimiento,
        'departamento' => !empty($departamento) ? $departamento : null,
        'biografia' => !empty($biografia) ? $biografia : null,
        'direccion' => !empty($direccion) ? $direccion : null,
        'ciudad' => !empty($ciudad) ? $ciudad : null,
        'estado' => !empty($estado) ? $estado : null,
        'codigo_postal' => !empty($codigo_postal) ? $codigo_postal : null,
        'pais' => $pais,
        'telefono_emergencia' => !empty($telefono_emergencia) ? $telefono_emergencia : null,
        'contacto_emergencia' => !empty($contacto_emergencia) ? $contacto_emergencia : null,
        'rol' => $rol,
        'user_id' => null // Se puede asignar para empresas
    ];
    
    $userId = $usuario->crear($datosUsuario);
    
    if ($userId) {
        // Log del registro para auditoría
        error_log("Nuevo usuario registrado: ID $userId, Email: $email, Rol: $rol");
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => 'Cuenta creada exitosamente. Tu cuenta está pendiente de activación.',
            'user_id' => $userId
        ]);
        
        // Opcional: Enviar email de bienvenida/activación
        // enviarEmailBienvenida($email, $nombre);
        
    } else {
        throw new Exception('No se pudo crear la cuenta. Por favor, intenta nuevamente.');
    }
    
} catch (Exception $e) {
    // Log del error
    error_log("Error en registro de usuario: " . $e->getMessage());
    
    // Respuesta de error
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor, intenta más tarde.',
        'debug' => $e->getMessage() // Solo para desarrollo, remover en producción
    ]);
}

// Función opcional para enviar email de bienvenida
function enviarEmailBienvenida($email, $nombre) {
    // Implementar envío de email si es necesario
    // Ejemplo básico con mail()
    /*
    $asunto = "Bienvenido a Clúster Intranet";
    $mensaje = "Hola $nombre,\n\nTu cuenta ha sido creada exitosamente. Está pendiente de activación por un administrador.\n\nSaludos,\nEquipo Clúster";
    $headers = "From: noreply@clúster.com\r\nReply-To: admin@clúster.com";
    
    mail($email, $asunto, $mensaje, $headers);
    */
}
?>