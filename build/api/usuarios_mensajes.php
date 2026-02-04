<?php
/**
 * API de Mensajería para Usuarios
 * Basada en la lógica de comites.php para mantener consistencia
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

function responderJSON($success, $data = null, $message = '', $debug = null) {
    http_response_code($success ? 200 : 400);
    $response = [
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('c')
    ];

    if ($debug && isset($_GET['debug']) && $_GET['debug'] === '1') {
        $response['debug'] = $debug;
    }

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}

// Función para subir archivos de mensajería (reutilizada de comites.php)
function subirArchivoMensaje($archivo, $tipo) {
    $directorioBase = __DIR__ . '/../uploads/mensajes/';
    $directorioSubida = $directorioBase . $tipo . '/';

    // Crear directorios si no existen
    if (!file_exists($directorioBase)) {
        if (!mkdir($directorioBase, 0755, true)) {
            return ['success' => false, 'message' => 'No se pudo crear el directorio base'];
        }
    }

    if (!file_exists($directorioSubida)) {
        if (!mkdir($directorioSubida, 0755, true)) {
            return ['success' => false, 'message' => 'No se pudo crear el directorio de ' . $tipo];
        }
    }

    if (!is_writable($directorioSubida)) {
        return ['success' => false, 'message' => 'El directorio no tiene permisos de escritura'];
    }

    // Validar tipos de archivo según el tipo
    $extensionesPermitidas = [];
    $tamanoMaximo = 5 * 1024 * 1024; // 5MB por defecto

    if ($tipo === 'imagenes') {
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $tamanoMaximo = 5 * 1024 * 1024; // 5MB para imágenes
    } elseif ($tipo === 'documentos') {
        $extensionesPermitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        $tamanoMaximo = 10 * 1024 * 1024; // 10MB para documentos
    }

    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas)) {
        return ['success' => false, 'message' => 'Formato de archivo no permitido para ' . $tipo];
    }

    if ($archivo['size'] > $tamanoMaximo) {
        $maxMB = $tamanoMaximo / (1024 * 1024);
        return ['success' => false, 'message' => "El archivo es demasiado grande (máximo {$maxMB}MB)"];
    }

    $nombreArchivo = 'usuario_mensaje_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $rutaCompleta = $directorioSubida . $nombreArchivo;

    if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
        $rutaRelativa = 'uploads/mensajes/' . $tipo . '/' . $nombreArchivo;
        return ['success' => true, 'ruta' => $rutaRelativa, 'nombre' => $nombreArchivo];
    } else {
        return ['success' => false, 'message' => 'Error al mover el archivo'];
    }
}

// Función para enviar emails (simulación - en producción usar PHPMailer o similar)
function enviarEmail($destinatario, $asunto, $contenido, $esHTML = false) {
    // En un entorno de producción, aquí iría la lógica real de envío de email
    // Por ahora solo registramos en logs para demostrar funcionalidad
    error_log("Email enviado a: $destinatario, Asunto: $asunto");
    return true; // Simular éxito
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $action = $_GET['action'] ?? $_POST['action'] ?? 'unknown';

    switch ($action) {
        case 'obtener_usuarios':
            // Obtener lista de usuarios registrados en el sistema para el selector
            try {
                $stmt = $conn->prepare("
                    SELECT id, nombre, apellidos as apellido, email,
                           COALESCE(fecha_ingreso, created_at) as fecha_registro
                    FROM usuarios_perfil
                    WHERE email IS NOT NULL AND email != ''
                    ORDER BY nombre, apellidos
                ");
                $stmt->execute();
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

                responderJSON(true, $usuarios, 'Usuarios obtenidos correctamente');
            } catch (Exception $e) {
                error_log("Error obteniendo usuarios: " . $e->getMessage());
                responderJSON(false, null, 'Error al obtener usuarios');
            }
            break;

        case 'enviar_mensaje':
            // Enviar mensaje a usuarios
            $destinatario = $_POST['destinatario'] ?? null;
            $tipo_mensaje = $_POST['tipo_mensaje'] ?? 'texto';
            $asunto = trim($_POST['asunto'] ?? '');

            if (!$destinatario || !$asunto) {
                responderJSON(false, null, 'Destinatario y asunto son requeridos');
            }

            try {
                // Obtener destinatarios
                $destinatarios = [];
                if ($destinatario === 'todos') {
                    // Obtener todos los usuarios registrados
                    $stmt = $conn->prepare("
                        SELECT DISTINCT email, nombre, apellidos as apellido
                        FROM usuarios_perfil
                        WHERE email IS NOT NULL AND email != ''
                    ");
                    $stmt->execute();
                    $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    // Destinatario específico
                    $stmt = $conn->prepare("
                        SELECT email, nombre, apellidos as apellido
                        FROM usuarios_perfil
                        WHERE email = ?
                    ");
                    $stmt->execute([$destinatario]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($usuario) {
                        $destinatarios = [$usuario];
                    }
                }

                if (empty($destinatarios)) {
                    responderJSON(false, null, 'No se encontraron destinatarios válidos');
                }

                // Preparar contenido del mensaje según tipo
                $contenido_mensaje = '';
                $archivo_adjunto = null;

                switch ($tipo_mensaje) {
                    case 'texto':
                        $contenido_mensaje = trim($_POST['contenido_texto'] ?? '');
                        break;

                    case 'link':
                        $link_url = trim($_POST['link_url'] ?? '');
                        $link_texto = trim($_POST['link_texto'] ?? '');
                        $link_descripcion = trim($_POST['link_descripcion'] ?? '');

                        if (!$link_url) {
                            responderJSON(false, null, 'URL del enlace es requerida');
                        }

                        $contenido_mensaje = json_encode([
                            'url' => $link_url,
                            'texto' => $link_texto ?: 'Enlace',
                            'descripcion' => $link_descripcion
                        ]);
                        break;

                    case 'imagen':
                        $imagen_descripcion = trim($_POST['imagen_descripcion'] ?? '');

                        if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
                            $archivo_adjunto = subirArchivoMensaje($_FILES['imagen_archivo'], 'imagenes');
                            if (!$archivo_adjunto['success']) {
                                responderJSON(false, null, 'Error al subir imagen: ' . $archivo_adjunto['message']);
                            }
                            $contenido_mensaje = json_encode([
                                'archivo' => $archivo_adjunto['ruta'],
                                'descripcion' => $imagen_descripcion
                            ]);
                        } else {
                            responderJSON(false, null, 'Imagen es requerida');
                        }
                        break;

                    case 'documento':
                        $documento_descripcion = trim($_POST['documento_descripcion'] ?? '');

                        if (isset($_FILES['documento_archivo']) && $_FILES['documento_archivo']['error'] === UPLOAD_ERR_OK) {
                            $archivo_adjunto = subirArchivoMensaje($_FILES['documento_archivo'], 'documentos');
                            if (!$archivo_adjunto['success']) {
                                responderJSON(false, null, 'Error al subir documento: ' . $archivo_adjunto['message']);
                            }
                            $contenido_mensaje = json_encode([
                                'archivo' => $archivo_adjunto['ruta'],
                                'nombre' => $_FILES['documento_archivo']['name'],
                                'descripcion' => $documento_descripcion
                            ]);
                        } else {
                            responderJSON(false, null, 'Documento es requerido');
                        }
                        break;
                }

                if (empty($contenido_mensaje)) {
                    responderJSON(false, null, 'Contenido del mensaje es requerido');
                }

                // Crear tabla de mensajes de usuarios si no existe
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS usuarios_mensajes (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        asunto VARCHAR(255) NOT NULL,
                        contenido TEXT NOT NULL,
                        tipo_mensaje ENUM('texto', 'link', 'imagen', 'documento') DEFAULT 'texto',
                        destinatarios_count INT DEFAULT 0,
                        fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        estado ENUM('enviado', 'error') DEFAULT 'enviado',
                        remitente_info TEXT,
                        INDEX idx_fecha_envio (fecha_envio)
                    )
                ");

                // Crear tabla de mensajes individuales si no existe
                $conn->exec("
                    CREATE TABLE IF NOT EXISTS usuarios_mensajes_individuales (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        mensaje_id INT,
                        email_destinatario VARCHAR(255) NOT NULL,
                        nombre_destinatario VARCHAR(255),
                        estado ENUM('enviado', 'leido', 'error') DEFAULT 'enviado',
                        fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        fecha_lectura TIMESTAMP NULL,
                        FOREIGN KEY (mensaje_id) REFERENCES usuarios_mensajes(id) ON DELETE CASCADE,
                        INDEX idx_email (email_destinatario),
                        INDEX idx_mensaje (mensaje_id)
                    )
                ");

                // Insertar mensaje principal
                $stmt = $conn->prepare("
                    INSERT INTO usuarios_mensajes (asunto, contenido, tipo_mensaje, destinatarios_count, remitente_info)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $remitente_info = json_encode([
                    'tipo' => 'administrador',
                    'timestamp' => date('c')
                ]);

                $stmt->execute([
                    $asunto,
                    $contenido_mensaje,
                    $tipo_mensaje,
                    count($destinatarios),
                    $remitente_info
                ]);

                $mensaje_id = $conn->lastInsertId();

                // Insertar mensajes individuales y enviar emails
                $emails_enviados = 0;
                $emails_fallidos = 0;

                foreach ($destinatarios as $destinatario_info) {
                    try {
                        // Insertar registro individual
                        $stmt = $conn->prepare("
                            INSERT INTO usuarios_mensajes_individuales (mensaje_id, email_destinatario, nombre_destinatario)
                            VALUES (?, ?, ?)
                        ");

                        $nombre_completo = trim(($destinatario_info['nombre'] ?? '') . ' ' . ($destinatario_info['apellido'] ?? ''));

                        $stmt->execute([
                            $mensaje_id,
                            $destinatario_info['email'],
                            $nombre_completo
                        ]);

                        // Enviar email (en producción usar PHPMailer)
                        $exito_email = enviarEmail(
                            $destinatario_info['email'],
                            $asunto,
                            $contenido_mensaje,
                            true
                        );

                        if ($exito_email) {
                            $emails_enviados++;
                        } else {
                            $emails_fallidos++;
                            // Marcar como error en BD
                            $conn->prepare("UPDATE usuarios_mensajes_individuales SET estado = 'error' WHERE mensaje_id = ? AND email_destinatario = ?")
                                  ->execute([$mensaje_id, $destinatario_info['email']]);
                        }

                    } catch (Exception $e) {
                        error_log("Error enviando mensaje a {$destinatario_info['email']}: " . $e->getMessage());
                        $emails_fallidos++;
                    }
                }

                // Actualizar estado del mensaje principal si hay errores
                if ($emails_fallidos > 0 && $emails_enviados === 0) {
                    $conn->prepare("UPDATE usuarios_mensajes SET estado = 'error' WHERE id = ?")
                          ->execute([$mensaje_id]);
                }

                $resultado = [
                    'mensaje_id' => $mensaje_id,
                    'emails_enviados' => $emails_enviados,
                    'emails_fallidos' => $emails_fallidos,
                    'total_destinatarios' => count($destinatarios)
                ];

                if ($emails_enviados > 0) {
                    responderJSON(true, $resultado, "Mensaje enviado correctamente a $emails_enviados destinatario(s)");
                } else {
                    responderJSON(false, $resultado, 'Error al enviar el mensaje');
                }

            } catch (Exception $e) {
                error_log("Error enviando mensaje de usuarios: " . $e->getMessage());
                responderJSON(false, null, 'Error interno al procesar el mensaje');
            }
            break;

        case 'obtener_mensajes':
            // Obtener mensajes enviados para un email específico
            $email = $_GET['email'] ?? null;

            if (!$email) {
                responderJSON(false, null, 'Email requerido');
            }

            try {
                $stmt = $conn->prepare("
                    SELECT
                        um.id,
                        um.asunto,
                        um.contenido,
                        um.tipo_mensaje,
                        um.fecha_envio,
                        umi.estado,
                        umi.fecha_lectura,
                        'sistema' as comite_nombre
                    FROM usuarios_mensajes um
                    INNER JOIN usuarios_mensajes_individuales umi ON um.id = umi.mensaje_id
                    WHERE umi.email_destinatario = ?
                    ORDER BY um.fecha_envio DESC
                    LIMIT 50
                ");

                $stmt->execute([$email]);
                $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                responderJSON(true, ['mensajes' => $mensajes], 'Mensajes obtenidos correctamente');

            } catch (Exception $e) {
                error_log("Error obteniendo mensajes para usuario: " . $e->getMessage());
                responderJSON(false, null, 'Error al obtener mensajes');
            }
            break;

        case 'marcar_mensaje_leido':
            // Marcar mensaje como leído
            $mensaje_id = $_POST['mensaje_id'] ?? null;
            $email = $_POST['email'] ?? null;

            if (!$mensaje_id) {
                responderJSON(false, null, 'ID de mensaje requerido');
            }

            try {
                $stmt = $conn->prepare("
                    UPDATE usuarios_mensajes_individuales
                    SET estado = 'leido', fecha_lectura = NOW()
                    WHERE mensaje_id = ? AND (email_destinatario = ? OR ? IS NULL)
                ");

                $stmt->execute([$mensaje_id, $email, $email]);

                responderJSON(true, null, 'Mensaje marcado como leído');

            } catch (Exception $e) {
                error_log("Error marcando mensaje como leído: " . $e->getMessage());
                responderJSON(false, null, 'Error al marcar mensaje como leído');
            }
            break;

        case 'estadisticas':
            // Obtener estadísticas de mensajería
            try {
                $stmt = $conn->prepare("
                    SELECT
                        COUNT(*) as total_mensajes,
                        SUM(destinatarios_count) as total_envios,
                        COUNT(CASE WHEN estado = 'enviado' THEN 1 END) as mensajes_exitosos,
                        COUNT(CASE WHEN estado = 'error' THEN 1 END) as mensajes_fallidos
                    FROM usuarios_mensajes
                    WHERE DATE(fecha_envio) >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ");

                $stmt->execute();
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);

                responderJSON(true, $stats, 'Estadísticas obtenidas correctamente');

            } catch (Exception $e) {
                error_log("Error obteniendo estadísticas: " . $e->getMessage());
                responderJSON(false, null, 'Error al obtener estadísticas');
            }
            break;

        default:
            responderJSON(false, null, 'Acción no válida');
    }

} catch (Exception $e) {
    error_log("Error en usuarios_mensajes API: " . $e->getMessage());
    responderJSON(false, null, 'Error interno del servidor');
}
?>