<?php
/**
 * API Simple para Empresas - Compatible con empresas-convenio.html
 * Este archivo actúa como un wrapper simplificado para la API principal
 */

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://intranet.clautmetropolitano.mx');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// RATE LIMITING - Protección contra abuso de API
// ============================================
try {
    require_once dirname(__DIR__) . '/middleware/rate-limiter.php';
    
    $rateLimiter = new RateLimiter();
    $clientIP = getRateLimitIdentifier();
    
    // Verificar límite (100 requests / minuto para APIs públicas)
    $rateLimiter->protect(
        $clientIP,
        RateLimitConfig::API_PUBLIC['max'],
        RateLimitConfig::API_PUBLIC['window'],
        RateLimitConfig::API_PUBLIC['action']
    );
    
} catch (Exception $e) {
    // Si hay error en rate limiter, continuar sin bloquear
    error_log("Error en rate limiter (empresas-simple): " . $e->getMessage());
}
// ============================================

// Definir acceso
define('CLAUT_ACCESS', true);

// Incluir configuración
require_once '../includes/config.php';

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? 'listar';
    $db = Database::getInstance();
    
    switch ($action) {
        case 'listar':
            listarEmpresas($db);
            break;
            
        case 'obtener':
            obtenerEmpresa($db);
            break;
            
        case 'crear':
            crearEmpresa($db);
            break;
            
        case 'actualizar':
            actualizarEmpresa($db);
            break;
            
        case 'eliminar':
            eliminarEmpresa($db);
            break;
            
        default:
            sendResponse(false, 'Acción no válida', null, 400);
    }
    
} catch (Exception $e) {
    error_log("Error en empresas-simple.php: " . $e->getMessage());
    sendResponse(false, 'Error interno del servidor', null, 500);
}

/**
 * Listar todas las empresas activas
 */
function listarEmpresas($db) {
    try {
        // Query para obtener empresas con todos los campos necesarios
        $sql = "SELECT 
                    e.id,
                    COALESCE(e.nombre, e.nombre_empresa) as nombre,
                    e.nombre_empresa,
                    e.descripcion,
                    e.logo_url,
                    e.sitio_web,
                    e.telefono,
                    e.email,
                    e.direccion,
                    COALESCE(e.categoria, e.sector) as sector,
                    e.estado,
                    e.descuento,
                    e.descuento_porcentaje,
                    e.fecha_convenio,
                    e.beneficios,
                    e.condiciones,
                    e.contacto_nombre,
                    e.contacto_cargo,
                    e.contacto_telefono,
                    e.contacto_email,
                    e.activo,
                    e.destacado,
                    e.fecha_inicio_convenio,
                    e.fecha_fin_convenio,
                    e.created_at,
                    e.updated_at,
                    e.admin_usuario_id,
                    CONCAT(u.nombre, ' ', u.apellidos) AS admin_nombre
                FROM empresas_convenio e
                LEFT JOIN usuarios_perfil u ON e.admin_usuario_id = u.id
                WHERE e.activo = 1 
                ORDER BY e.destacado DESC, COALESCE(e.nombre, e.nombre_empresa) ASC";
        
        $empresas = $db->select($sql);
        
        // Formatear datos para el frontend
        $empresasFormateadas = array_map(function($empresa) {
            return [
                'id' => $empresa['id'],
                'nombre' => $empresa['nombre'] ?: $empresa['nombre_empresa'],
                'nombre_empresa' => $empresa['nombre_empresa'],
                'descripcion' => $empresa['descripcion'] ?: '',
                'logo_url' => $empresa['logo_url'] ?: '',
                'sitio_web' => $empresa['sitio_web'] ?: '',
                'telefono' => $empresa['telefono'] ?: '',
                'email' => $empresa['email'] ?: '',
                'direccion' => $empresa['direccion'] ?: '',
                'sector' => $empresa['sector'] ?: 'General',
                'categoria' => $empresa['sector'] ?: 'General',
                'estado' => $empresa['estado'] ?: 'activa',
                'descuento_porcentaje' => $empresa['descuento_porcentaje'] ?? $empresa['descuento'] ?? 0,
                'fecha_convenio' => $empresa['fecha_convenio'] ?: '',
                'beneficios' => $empresa['beneficios'] ?: '',
                'condiciones' => $empresa['condiciones'] ?: '',
                'contacto_nombre' => $empresa['contacto_nombre'] ?: '',
                'contacto_persona' => $empresa['contacto_nombre'] ?: '',
                'contacto_cargo' => $empresa['contacto_cargo'] ?: '',
                'contacto_telefono' => $empresa['contacto_telefono'] ?: '',
                'contacto_email' => $empresa['contacto_email'] ?: '',
                'admin_usuario_id' => $empresa['admin_usuario_id'],
                'admin_nombre' => $empresa['admin_nombre'],
                'activo' => (bool)$empresa['activo'],
                'destacado' => (bool)$empresa['destacado'],
                'fecha_inicio_convenio' => $empresa['fecha_inicio_convenio'],
                'fecha_fin_convenio' => $empresa['fecha_fin_convenio']
            ];
        }, $empresas);
        
        sendResponse(true, 'Empresas obtenidas correctamente', [
            'empresas' => $empresasFormateadas,
            'total' => count($empresasFormateadas)
        ]);
        
    } catch (Exception $e) {
        error_log("Error listando empresas: " . $e->getMessage());
        sendResponse(false, 'Error al obtener empresas: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Obtener una empresa específica
 */
function obtenerEmpresa($db) {
    try {
        $id = $_GET['id'] ?? null;
        
        // OPCIONAL: Validación adicional (no altera funcionamiento)
        if (file_exists(dirname(__DIR__) . '/middleware/api-validator.php')) {
            require_once dirname(__DIR__) . '/middleware/api-validator.php';
            
            $validation = ApiValidator::validateField($id, 'required|int|min:1', 'id');
            
            if (!$validation['valid']) {
                sendResponse(false, $validation['error'], null, 400);
            }
        }
        
        // LÓGICA ORIGINAL
        if (!$id) {
            sendResponse(false, 'ID de empresa requerido', null, 400);
        }
        
        $sql = "SELECT 
                    e.id,
                    COALESCE(e.nombre, e.nombre_empresa) as nombre,
                    e.nombre_empresa,
                    e.descripcion,
                    e.logo_url,
                    e.sitio_web,
                    e.telefono,
                    e.email,
                    e.direccion,
                    COALESCE(e.categoria, e.sector) as sector,
                    e.descuento,
                    e.descuento_porcentaje,
                    e.beneficios,
                    e.contacto_nombre,
                    e.contacto_cargo,
                    e.contacto_telefono,
                    e.contacto_email,
                    e.activo,
                    e.destacado,
                    e.estado,
                    e.fecha_convenio,
                    e.condiciones,
                    e.fecha_inicio_convenio,
                    e.fecha_fin_convenio,
                    e.admin_usuario_id,
                    CONCAT(u.nombre, ' ', u.apellidos) AS admin_nombre
                FROM empresas_convenio e
                LEFT JOIN usuarios_perfil u ON e.admin_usuario_id = u.id
                WHERE e.id = ? AND e.activo = 1";
        
        $empresa = $db->selectOne($sql, [$id]);
        
        if (!$empresa) {
            sendResponse(false, 'Empresa no encontrada', null, 404);
        }
        
        // Formatear datos
        $empresaFormateada = [
            'id' => $empresa['id'],
            'nombre' => $empresa['nombre'] ?: $empresa['nombre_empresa'],
            'nombre_empresa' => $empresa['nombre_empresa'],
            'descripcion' => $empresa['descripcion'] ?: 'Sin descripción disponible',
            'logo_url' => $empresa['logo_url'] ?: '',
            'sitio_web' => $empresa['sitio_web'] ?: '#',
            'telefono' => $empresa['telefono'] ?: 'No disponible',
            'email' => $empresa['email'] ?: 'No disponible',
            'direccion' => $empresa['direccion'] ?: '',
            'sector' => $empresa['sector'] ?: 'General',
            'descuento' => $empresa['descuento'] ?: $empresa['descuento_porcentaje'] ?: 0,
            'beneficios' => $empresa['beneficios'] ?: '',
            'contacto_nombre' => $empresa['contacto_nombre'] ?: 'No especificado',
            'contacto_cargo' => $empresa['contacto_cargo'] ?: '',
            'contacto_telefono' => $empresa['contacto_telefono'] ?: 'No disponible',
            'contacto_email' => $empresa['contacto_email'] ?: 'No disponible',
            'admin_usuario_id' => $empresa['admin_usuario_id'],
            'admin_nombre' => $empresa['admin_nombre'],
            'activo' => (bool)$empresa['activo'],
            'destacado' => (bool)$empresa['destacado'],
            'estado' => $empresa['estado'] ?: 'activa',
            'fecha_convenio' => $empresa['fecha_convenio'] ?: '',
            'condiciones' => $empresa['condiciones'] ?: ''
        ];
        
        sendResponse(true, 'Empresa encontrada', $empresaFormateada);
        
    } catch (Exception $e) {
        error_log("Error obteniendo empresa: " . $e->getMessage());
        sendResponse(false, 'Error al obtener empresa: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Crear nueva empresa
 */
function crearEmpresa($db) {
    try {
        // Recopilar y sanear datos del POST
        $nombre = trim($_POST['nombre'] ?? '');
        $sector = trim($_POST['sector'] ?? 'General');
        $estado = trim($_POST['estado'] ?? 'activa');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $sitio_web = trim($_POST['sitio_web'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $descuento_porcentaje = isset($_POST['descuento_porcentaje']) && $_POST['descuento_porcentaje'] !== ''
            ? floatval($_POST['descuento_porcentaje']) : 0;
        $fecha_convenio_raw = trim($_POST['fecha_convenio'] ?? '');
        $fecha_convenio = ($fecha_convenio_raw !== '' && strtotime($fecha_convenio_raw)) ? $fecha_convenio_raw : null;
        $beneficios = trim($_POST['beneficios'] ?? '');
        $condiciones = trim($_POST['condiciones'] ?? '');
        $contacto_nombre = trim($_POST['contacto_persona'] ?? '');
        $contacto_telefono = trim($_POST['contacto_telefono'] ?? '');
        $contacto_email = trim($_POST['contacto_email'] ?? '');
        $logo_url = trim($_POST['logo_url'] ?? '');
        $admin_usuario_id = !empty($_POST['admin_usuario_id']) ? intval($_POST['admin_usuario_id']) : null;

        // Validar campos requeridos
        if (empty($nombre)) {
            sendResponse(false, 'El nombre de la empresa es requerido', null, 400);
        }
        
        // Preparar SQL de inserción
        $sql = "INSERT INTO empresas_convenio (
                    nombre, sector, estado, email, telefono, sitio_web, direccion,
                    descripcion, descuento_porcentaje, fecha_convenio, beneficios,
                    condiciones, contacto_nombre, contacto_telefono, contacto_email,
                    logo_url, admin_usuario_id, activo, fecha_registro
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
        
        $params = [
            $nombre, $sector, $estado, $email, $telefono, $sitio_web, $direccion,
            $descripcion, $descuento_porcentaje, $fecha_convenio, $beneficios,
            $condiciones, $contacto_nombre, $contacto_telefono, $contacto_email,
            $logo_url, $admin_usuario_id
        ];
        
        $empresaId = $db->insert($sql, $params);
        
        if ($empresaId) {
            // ── Hook de correo: Nueva Empresa Registrada (best-effort) ──
            try {
                require_once dirname(__DIR__) . '/utils/NotificationMailer.php';
                $db2 = Database::getInstance()->getConnection();
                NotificationMailer::dispatch(
                    'nueva_empresa',
                    "🏢 Nueva empresa registrada: $nombre",
                    "Se ha registrado una nueva empresa en el sistema y requiere revisión.\n\n" .
                    "Empresa: $nombre\n" .
                    "Sector: $sector\n" .
                    ($email ? "Email: $email\n" : '') .
                    ($telefono ? "Teléfono: $telefono\n" : '') .
                    "\nAccede al Panel de Administración para revisar y activar la empresa.",
                    $db2
                );
            } catch (Exception $emailEx) {
                error_log('⚠️ [empresas-simple] Hook correo falló: ' . $emailEx->getMessage());
            }
            // ───────────────────────────────────────────────────────────

            // Recargar lista de empresas
            listarEmpresas($db);

        } else {
            sendResponse(false, 'Error al crear la empresa', null, 500);
        }
        
    } catch (Exception $e) {
        error_log("Error creando empresa: " . $e->getMessage());
        sendResponse(false, 'Error al crear empresa: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Actualizar empresa existente
 */
function actualizarEmpresa($db) {
    try {
        // Recopilar datos del POST
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            sendResponse(false, 'ID de empresa requerido', null, 400);
        }
        
        $nombre = trim($_POST['nombre'] ?? '');
        $sector = trim($_POST['sector'] ?? 'General');
        $estado = trim($_POST['estado'] ?? 'activa');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $sitio_web = trim($_POST['sitio_web'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $descuento_porcentaje = isset($_POST['descuento_porcentaje']) && $_POST['descuento_porcentaje'] !== ''
            ? floatval($_POST['descuento_porcentaje']) : 0;
        $fecha_convenio_raw = trim($_POST['fecha_convenio'] ?? '');
        $fecha_convenio = ($fecha_convenio_raw !== '' && strtotime($fecha_convenio_raw)) ? $fecha_convenio_raw : null;
        $beneficios = trim($_POST['beneficios'] ?? '');
        $condiciones = trim($_POST['condiciones'] ?? '');
        $contacto_nombre = trim($_POST['contacto_persona'] ?? '');
        $contacto_telefono = trim($_POST['contacto_telefono'] ?? '');
        $contacto_email = trim($_POST['contacto_email'] ?? '');
        $logo_url = trim($_POST['logo_url'] ?? '');
        $admin_usuario_id = !empty($_POST['admin_usuario_id']) ? intval($_POST['admin_usuario_id']) : null;

        // Validar que la empresa existe
        $empresaExistente = $db->selectOne("SELECT id FROM empresas_convenio WHERE id = ?", [$id]);
        if (!$empresaExistente) {
            sendResponse(false, 'Empresa no encontrada', null, 404);
        }
        
        // Preparar SQL de actualización
        $sql = "UPDATE empresas_convenio SET 
                    nombre = ?,
                    sector = ?,
                    estado = ?,
                    email = ?,
                    telefono = ?,
                    sitio_web = ?,
                    direccion = ?,
                    descripcion = ?,
                    descuento_porcentaje = ?,
                    fecha_convenio = ?,
                    beneficios = ?,
                    condiciones = ?,
                    contacto_nombre = ?,
                    contacto_telefono = ?,
                    contacto_email = ?,
                    logo_url = ?,
                    admin_usuario_id = ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $nombre, $sector, $estado, $email, $telefono, $sitio_web, $direccion,
            $descripcion, $descuento_porcentaje, $fecha_convenio, $beneficios,
            $condiciones, $contacto_nombre, $contacto_telefono, $contacto_email,
            $logo_url, $admin_usuario_id, $id
        ];
        
        $rowsAffected = $db->update($sql, $params);
        
        if ($rowsAffected !== false) {
            // Recargar lista de empresas
            listarEmpresas($db);
        } else {
            sendResponse(false, 'Error al actualizar la empresa', null, 500);
        }
        
    } catch (Exception $e) {
        error_log("Error actualizando empresa: " . $e->getMessage());
        sendResponse(false, 'Error al actualizar empresa: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Eliminar empresa (soft delete - marca activo = 0)
 */
function eliminarEmpresa($db) {
    try {
        $id = $_POST['id'] ?? null;

        if (!$id) {
            sendResponse(false, 'ID de empresa requerido', null, 400);
        }

        $id = intval($id);

        $empresaExistente = $db->selectOne("SELECT id, nombre FROM empresas_convenio WHERE id = ?", [$id]);
        if (!$empresaExistente) {
            sendResponse(false, 'Empresa no encontrada', null, 404);
        }

        $rowsAffected = $db->update(
            "UPDATE empresas_convenio SET activo = 0, estado = 'inactiva', updated_at = NOW() WHERE id = ?",
            [$id]
        );

        if ($rowsAffected !== false) {
            sendResponse(true, 'Empresa eliminada correctamente', ['id' => $id]);
        } else {
            sendResponse(false, 'Error al eliminar la empresa', null, 500);
        }

    } catch (Exception $e) {
        error_log("Error eliminando empresa: " . $e->getMessage());
        sendResponse(false, 'Error al eliminar empresa: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Enviar respuesta JSON
 */
function sendResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}
?>
