<?php
/**
 * API para registrar usuarios a eventos
 * Maneja el registro de usuarios/empresas en eventos específicos
 * Incluye validación de cupo, duplicados y notificaciones
 */

// Configurar headers para JSON y CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Incluir configuración de base de datos
require_once __DIR__ . '/config/database.php';

try {
    // Obtener instancia de base de datos
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    // Log de información del entorno para debug
    error_log("register_evento.php: Procesando registro desde " .
              ($database->isUsingRemoteDB() ? "MySQL remoto" : "SQLite local"));

    // Obtener datos de la petición
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Si no hay JSON válido, intentar obtener de $_POST
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST;
        error_log("register_evento.php: Usando datos $_POST en lugar de JSON");
    }

    // Validar campos requeridos
    $requiredFields = ['evento_id', 'nombre_usuario', 'email_contacto'];
    $missingFields = [];

    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missingFields[] = $field;
        }
    }

    if (!empty($missingFields)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Campos requeridos faltantes: ' . implode(', ', $missingFields)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Sanitizar y validar datos
    $evento_id = (int) $data['evento_id'];
    $empresa_id = !empty($data['empresa_id']) ? (int) $data['empresa_id'] : null;
    $usuario_id = !empty($data['usuario_id']) ? (int) $data['usuario_id'] : null;
    $nombre_empresa = trim($data['nombre_empresa'] ?? '');
    $nombre_usuario = trim($data['nombre_usuario']);
    $email_contacto = filter_var(trim($data['email_contacto']), FILTER_VALIDATE_EMAIL);
    $telefono_contacto = trim($data['telefono_contacto'] ?? '');
    $comentarios = trim($data['comentarios'] ?? '');

    // Validar email
    if (!$email_contacto) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Email no válido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar que el evento existe y está activo
    $sqlEvento = "SELECT id, titulo, capacidad_maxima, capacidad_actual, estado FROM eventos WHERE id = ? AND estado IN ('programado', 'en_curso')";
    $stmtEvento = $pdo->prepare($sqlEvento);
    $stmtEvento->execute([$evento_id]);
    $evento = $stmtEvento->fetch(PDO::FETCH_ASSOC);

    if (!$evento) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Evento no encontrado o no está disponible'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar capacidad disponible
    if ($evento['capacidad_actual'] >= $evento['capacidad_maxima']) {
        echo json_encode([
            'status' => 'full',
            'message' => 'Cupo agotado. No hay espacios disponibles para este evento.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar si ya existe un registro
    // Buscar por evento_id + email_contacto para evitar duplicados por email
    // También buscar por evento_id + usuario_id si se proporciona usuario_id
    $sqlExiste = "
        SELECT id, fecha_registro, estado_registro
        FROM evento_registros
        WHERE evento_id = ? AND (email_contacto = ?" .
        ($usuario_id ? " OR usuario_id = ?" : "") . ")
    ";

    $params = [$evento_id, $email_contacto];
    if ($usuario_id) {
        $params[] = $usuario_id;
    }

    $stmtExiste = $pdo->prepare($sqlExiste);
    $stmtExiste->execute($params);
    $registroExistente = $stmtExiste->fetch(PDO::FETCH_ASSOC);

    if ($registroExistente) {
        echo json_encode([
            'status' => 'exists',
            'message' => 'Ya estás registrado a este evento',
            'data' => [
                'fecha_registro' => $registroExistente['fecha_registro'],
                'estado' => $registroExistente['estado_registro']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Iniciar transacción para consistencia de datos
    $pdo->beginTransaction();

    try {
        // Insertar nuevo registro
        $sqlInsert = "
            INSERT INTO evento_registros
            (evento_id, empresa_id, usuario_id, nombre_empresa, nombre_usuario,
             email_contacto, telefono_contacto, comentarios, fecha_registro, estado_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pendiente')
        ";

        // Para SQLite, usar datetime('now') en lugar de NOW()
        if ($database->isUsingSQLite()) {
            $sqlInsert = str_replace("NOW()", "datetime('now')", $sqlInsert);
        }

        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            $evento_id,
            $empresa_id,
            $usuario_id,
            $nombre_empresa,
            $nombre_usuario,
            $email_contacto,
            $telefono_contacto,
            $comentarios
        ]);

        $registro_id = $pdo->lastInsertId();

        // Actualizar capacidad actual del evento
        $sqlUpdateCapacidad = "UPDATE eventos SET capacidad_actual = capacidad_actual + 1 WHERE id = ?";
        $stmtUpdateCapacidad = $pdo->prepare($sqlUpdateCapacidad);
        $stmtUpdateCapacidad->execute([$evento_id]);

        // Confirmar transacción
        $pdo->commit();

        // Log del registro exitoso
        error_log("register_evento.php: Usuario {$nombre_usuario} ({$email_contacto}) registrado al evento {$evento_id}");

        // Enviar notificación a demo_evento.html
        try {
            sendEventNotification($evento_id, $registro_id, $nombre_usuario, $evento['titulo'], $email_contacto);
        } catch (Exception $notificationError) {
            // No fallar por errores de notificación, solo logear
            error_log("register_evento.php: Error enviando notificación: " . $notificationError->getMessage());
        }

        // Respuesta exitosa
        echo json_encode([
            'status' => 'ok',
            'message' => 'Registrado exitosamente',
            'data' => [
                'registro_id' => $registro_id,
                'evento_id' => $evento_id,
                'evento_titulo' => $evento['titulo'],
                'usuario' => $nombre_usuario,
                'email' => $email_contacto,
                'fecha_registro' => date('Y-m-d H:i:s'),
                'capacidad_restante' => $evento['capacidad_maxima'] - ($evento['capacidad_actual'] + 1)
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Rollback en caso de error
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    error_log("register_evento.php - Error PDO: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos',
        'error_code' => 'DB_ERROR'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("register_evento.php - Error general: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error interno del servidor',
        'error_code' => 'INTERNAL_ERROR'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Función para enviar notificación a demo_evento.html
 * En producción esto podría ser una notificación real, webhook, email, etc.
 *
 * @param int $evento_id ID del evento
 * @param int $registro_id ID del registro creado
 * @param string $nombre_usuario Nombre del usuario registrado
 * @param string $evento_titulo Título del evento
 * @param string $email_contacto Email del usuario
 */
function sendEventNotification($evento_id, $registro_id, $nombre_usuario, $evento_titulo, $email_contacto) {
    try {
        // OPCIÓN 1: Enviar un fetch/POST a demo_evento.html con información de notificación
        // Esto simula cómo demo_evento.html podría recibir notificaciones

        // En un entorno real, esto podría ser:
        // - Una cola de mensajes (Redis, RabbitMQ)
        // - Un webhook
        // - Una notificación push
        // - Un email
        // - Una actualización en base de datos que demo_evento.html pueda leer

        // Para propósitos de demostración, creamos una URL que demo_evento.html pueda procesar
        $notificationData = [
            'type' => 'new_registration',
            'registro_id' => $registro_id,
            'evento_id' => $evento_id,
            'evento_titulo' => $evento_titulo,
            'usuario_nombre' => $nombre_usuario,
            'usuario_email' => $email_contacto,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'pendiente',
            'message' => "Nuevo registro: {$nombre_usuario} se registró al evento '{$evento_titulo}' y está pendiente de aprobación"
        ];

        // MÉTODO SIMULADO: Hacer un POST a demo_evento.html
        // En la vida real, demo_evento.html estaría en un servidor y podríamos hacer un POST real
        $notificationUrl = 'demo_evento.html?notified=1&id=' . $evento_id;

        // Log de la "notificación"
        error_log("register_evento.php: Enviando notificación a {$notificationUrl}");
        error_log("register_evento.php: Datos de notificación: " . json_encode($notificationData, JSON_UNESCAPED_UNICODE));

        // OPCIÓN 2: En un entorno real con servidor web, hacer el POST real
        /*
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://your-domain.com/demo_evento.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Error en notificación HTTP: " . $httpCode);
        }
        */

        // OPCIÓN 3: Guardar en base de datos para que demo_evento.html pueda leer
        // Esto lo implementarías si quieres que demo_evento.html haga polling o use AJAX para ver nuevas notificaciones

        return true;

    } catch (Exception $e) {
        error_log("sendEventNotification: Error - " . $e->getMessage());
        throw $e;
    }
}

/**
 * INSTRUCCIONES PARA demo_evento.html:
 *
 * Para que demo_evento.html reciba y muestre estas notificaciones, puedes:
 *
 * 1. Agregar JavaScript que verifique URL parameters:
 *    if (window.location.search.includes('notified=1')) {
 *        const urlParams = new URLSearchParams(window.location.search);
 *        const eventId = urlParams.get('id');
 *        showNotification(`Nuevo registro al evento ${eventId}`);
 *    }
 *
 * 2. Implementar polling para verificar nuevos registros:
 *    setInterval(() => {
 *        fetch(`check_new_registrations.php?evento_id=${currentEventId}`)
 *        .then(response => response.json())
 *        .then(data => {
 *            if (data.new_registrations > 0) {
 *                showNotification(`${data.new_registrations} nuevos registros`);
 *            }
 *        });
 *    }, 30000); // Cada 30 segundos
 *
 * 3. Usar WebSockets para notificaciones en tiempo real (avanzado)
 *
 * 4. Verificar localStorage para mensajes pendientes:
 *    localStorage.setItem('eventNotification', JSON.stringify(notificationData));
 *    // demo_evento.html puede leer esto al cargar
 */
?>