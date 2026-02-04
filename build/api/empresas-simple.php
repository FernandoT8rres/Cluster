<?php
/**
 * API Simple para Empresas - Compatible con empresas-convenio.html
 * Este archivo actúa como un wrapper simplificado para la API principal
 */

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
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
    $action = $_GET['action'] ?? 'listar';
    $db = Database::getInstance();
    
    switch ($action) {
        case 'listar':
            listarEmpresas($db);
            break;
            
        case 'obtener':
            obtenerEmpresa($db);
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
                    id,
                    COALESCE(nombre, nombre_empresa) as nombre,
                    nombre_empresa,
                    descripcion,
                    logo_url,
                    sitio_web,
                    telefono,
                    email,
                    direccion,
                    COALESCE(categoria, sector) as sector,
                    descuento,
                    descuento_porcentaje,
                    beneficios,
                    contacto_nombre,
                    contacto_cargo,
                    contacto_telefono,
                    contacto_email,
                    activo,
                    destacado,
                    fecha_inicio_convenio,
                    fecha_fin_convenio,
                    created_at,
                    updated_at
                FROM empresas_convenio 
                WHERE activo = 1 
                ORDER BY destacado DESC, COALESCE(nombre, nombre_empresa) ASC";
        
        $empresas = $db->select($sql);
        
        // Formatear datos para el frontend
        $empresasFormateadas = array_map(function($empresa) {
            return [
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
                'categoria' => $empresa['sector'] ?: 'General',
                'descuento' => $empresa['descuento'] ?: $empresa['descuento_porcentaje'] ?: 0,
                'beneficios' => $empresa['beneficios'] ?: '',
                'contacto_nombre' => $empresa['contacto_nombre'] ?: 'No especificado',
                'contacto_persona' => $empresa['contacto_nombre'] ?: 'No especificado',
                'contacto_cargo' => $empresa['contacto_cargo'] ?: '',
                'contacto_telefono' => $empresa['contacto_telefono'] ?: 'No disponible',
                'contacto_email' => $empresa['contacto_email'] ?: 'No disponible',
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
                    id,
                    COALESCE(nombre, nombre_empresa) as nombre,
                    nombre_empresa,
                    descripcion,
                    logo_url,
                    sitio_web,
                    telefono,
                    email,
                    direccion,
                    COALESCE(categoria, sector) as sector,
                    descuento,
                    descuento_porcentaje,
                    beneficios,
                    contacto_nombre,
                    contacto_cargo,
                    contacto_telefono,
                    contacto_email,
                    activo,
                    destacado,
                    fecha_inicio_convenio,
                    fecha_fin_convenio
                FROM empresas_convenio 
                WHERE id = ? AND activo = 1";
        
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
            'activo' => (bool)$empresa['activo'],
            'destacado' => (bool)$empresa['destacado']
        ];
        
        sendResponse(true, 'Empresa encontrada', $empresaFormateada);
        
    } catch (Exception $e) {
        error_log("Error obteniendo empresa: " . $e->getMessage());
        sendResponse(false, 'Error al obtener empresa: ' . $e->getMessage(), null, 500);
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
