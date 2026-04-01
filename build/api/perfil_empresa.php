<?php
/**
 * API: User Company Management
 * File: api/perfil_empresa.php
 * Purpose: Handle company data management from user profile
 */

define('CLAUT_ACCESS', true);

// Disable error display to prevent breaking JSON responses
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://intranet.clautmetropolitano.mx');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

// Start session (defer to session-config if available)
if (file_exists(dirname(__DIR__) . '/config/session-config.php')) {
    require_once dirname(__DIR__) . '/config/session-config.php';
    if (!session_id()) {
        SessionConfig::init();
    }
} else {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Check authentication - support all session key naming conventions
$user_id = null;
$user_email = null;

if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $user_id = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id']) {
    $user_id = intval($_SESSION['usuario_id']);
}

// Fallback: get ID from email if user_id not in session
if (!$user_id) {
    $user_email = $_SESSION['user_email'] 
               ?? $_SESSION['usuario_email'] 
               ?? $_SESSION['user_nombre'] // might store email in nombre in some configs
               ?? null;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // If we have email but not ID, get the ID from DB
    if (!$user_id && $user_email) {
        $stmt = $conn->prepare("SELECT id FROM usuarios_perfil WHERE email = ? LIMIT 1");
        $stmt->execute([$user_email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $user_id = intval($u['id']);
        }
    }

    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado', 'session_keys' => array_keys($_SESSION)]);
        exit();
    }

    // Get user's assigned company ID (verifying if they are the admin)
    $stmt = $conn->prepare("SELECT id FROM empresas_convenio WHERE admin_usuario_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $empresaAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empresaAdmin) {
        http_response_code(404);
        echo json_encode([
            'error' => 'No tienes una empresa asignada para administrar',
            'user_id_checked' => $user_id
        ]);
        exit();
    }

    $empresa_id = intval($empresaAdmin['id']);

    switch ($method) {
        case 'GET':
            // Get company data
            $stmt = $conn->prepare("SELECT * FROM empresas_convenio WHERE id = ?");
            $stmt->execute([$empresa_id]);
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$empresa) {
                http_response_code(404);
                echo json_encode(['error' => 'Empresa no encontrada']);
                exit();
            }

            echo json_encode([
                'success' => true,
                'empresa' => $empresa
            ]);
            break;

        case 'PUT':
            // Instead of directly updating, create a change-request for admin review
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos']);
                exit();
            }

            $allowed_fields = [
                'nombre_empresa', 'nombre', 'sector', 'descripcion', 'logo_url', 'email', 'telefono',
                'sitio_web', 'direccion', 'contacto_nombre', 'contacto_persona', 'contacto_telefono', 'contacto_email',
                'descuento_porcentaje', 'beneficios', 'condiciones', 'fecha_convenio'
            ];

            $payload = [];
            foreach ($allowed_fields as $field) {
                if (array_key_exists($field, $data)) {
                    $payload[$field] = $data[$field];
                }
            }

            if (empty($payload)) {
                http_response_code(400);
                echo json_encode(['error' => 'No hay campos para solicitar cambio']);
                exit();
            }

            // Check for existing pending 'actualizar' request for this company
            $stmt = $conn->prepare("SELECT id FROM solicitudes_empresa WHERE usuario_id = ? AND empresa_id = ? AND tipo_solicitud = 'actualizar' AND estado = 'pendiente' LIMIT 1");
            $stmt->execute([$user_id, $empresa_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update the pending request with new data
                $stmt = $conn->prepare("UPDATE solicitudes_empresa SET datos_empresa = ?, fecha_solicitud = NOW() WHERE id = ?");
                $stmt->execute([json_encode($payload), $existing['id']]);
                echo json_encode([
                    'success' => true,
                    'message' => 'Solicitud de cambio actualizada. El administrador revisará los nuevos datos.',
                    'solicitud_id' => $existing['id'],
                    'pending_review' => true
                ]);
            } else {
                // Create new change request
                $stmt = $conn->prepare("INSERT INTO solicitudes_empresa (usuario_id, tipo_solicitud, empresa_id, datos_empresa, estado) VALUES (?, 'actualizar', ?, ?, 'pendiente')");
                $result = $stmt->execute([$user_id, $empresa_id, json_encode($payload)]);

                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Solicitud de cambio enviada al administrador. Los cambios se aplicarán tras su aprobación.',
                        'solicitud_id' => $conn->lastInsertId(),
                        'pending_review' => true
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al enviar la solicitud']);
                }
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor',
        'message' => $e->getMessage()
    ]);
}
