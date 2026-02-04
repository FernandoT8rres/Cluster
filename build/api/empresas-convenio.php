<?php
/**
 * API CRUD Completa para Empresas en Convenio
 * Maneja todas las operaciones CRUD para el módulo de gestión de empresas
 */

// Headers CORS para permitir peticiones AJAX
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Definir acceso para el sistema
define('CLAUT_ACCESS', true);

// Incluir configuración y funciones
require_once '../assets/conexion/config.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? '';
    
    // Inicializar base de datos
    $db = Database::getInstance();
    
    // Crear tabla si no existe
    crearTablaEmpresasConvenio($db);
    
    // Enrutar según el método HTTP
    switch ($method) {
        case 'GET':
            handleGet($db, $action, $id);
            break;
        case 'POST':
            handlePost($db, $action);
            break;
        case 'PUT':
            handlePut($db, $id);
            break;
        case 'DELETE':
            handleDelete($db, $id);
            break;
        default:
            sendResponse(false, 'Método HTTP no permitido', null, 405);
    }
    
} catch (Exception $e) {
    error_log("Error en API empresas-convenio: " . $e->getMessage());
    sendResponse(false, 'Error interno del servidor: ' . $e->getMessage(), null, 500);
}

/**
 * Crear tabla de empresas en convenio si no existe
 */
function crearTablaEmpresasConvenio($db) {
    $sql = "CREATE TABLE IF NOT EXISTS empresas_convenio (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_empresa VARCHAR(255) NOT NULL,
        categoria VARCHAR(100),
        email VARCHAR(255),
        telefono VARCHAR(50),
        sitio_web TEXT,
        logo_url TEXT,
        direccion TEXT,
        descripcion TEXT,
        beneficios TEXT,
        descuento DECIMAL(5,2) DEFAULT 0,
        activo TINYINT(1) DEFAULT 1,
        destacado TINYINT(1) DEFAULT 0,
        fecha_inicio_convenio DATE,
        fecha_fin_convenio DATE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        creado_por INT DEFAULT NULL,
        INDEX idx_categoria (categoria),
        INDEX idx_activo (activo),
        INDEX idx_destacado (destacado),
        INDEX idx_nombre (nombre_empresa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $db->query($sql);
        
        // Insertar datos de ejemplo si la tabla está vacía
        $count = $db->query("SELECT COUNT(*) as count FROM empresas_convenio")->fetch_assoc()['count'];
        if ($count == 0) {
            insertarDatosEjemplo($db);
        }
    } catch (Exception $e) {
        error_log("Error creando tabla empresas_convenio: " . $e->getMessage());
    }
}

/**
 * Insertar datos de ejemplo
 */
function insertarDatosEjemplo($db) {
    $empresasEjemplo = [
        [
            'nombre_empresa' => 'TechCorp Solutions',
            'categoria' => 'Tecnología',
            'email' => 'contacto@techcorp.com',
            'telefono' => '+52 55 1234-5678',
            'sitio_web' => 'https://techcorp.com',
            'direccion' => 'Av. Reforma 123, CDMX',
            'descripcion' => 'Empresa líder en soluciones tecnológicas para el sector automotriz',
            'beneficios' => '20% de descuento en servicios de TI y consultoría tecnológica',
            'descuento' => 20.00,
            'activo' => 1,
            'destacado' => 1,
            'fecha_inicio_convenio' => '2024-01-01',
            'fecha_fin_convenio' => '2024-12-31'
        ],
        [
            'nombre_empresa' => 'AutoParts México',
            'categoria' => 'Automotriz',
            'email' => 'ventas@autoparts.mx',
            'telefono' => '+52 55 9876-5432',
            'sitio_web' => 'https://autoparts.mx',
            'direccion' => 'Industrial Norte 456, Guadalajara',
            'descripcion' => 'Distribuidora de autopartes originales y refacciones',
            'beneficios' => '15% de descuento en refacciones y servicios de mantenimiento',
            'descuento' => 15.00,
            'activo' => 1,
            'destacado' => 0,
            'fecha_inicio_convenio' => '2024-02-01',
            'fecha_fin_convenio' => '2025-01-31'
        ],
        [
            'nombre_empresa' => 'LogiTransport',
            'categoria' => 'Logística',
            'email' => 'info@logitransport.com',
            'telefono' => '+52 33 5555-1234',
            'sitio_web' => '',
            'direccion' => 'Zona Industrial 789, Monterrey',
            'descripcion' => 'Servicios especializados de transporte y logística',
            'beneficios' => '10% de descuento en servicios de envío y distribución',
            'descuento' => 10.00,
            'activo' => 0,
            'destacado' => 0,
            'fecha_inicio_convenio' => '2024-03-01',
            'fecha_fin_convenio' => '2024-11-30'
        ]
    ];
    
    foreach ($empresasEjemplo as $empresa) {
        $sql = "INSERT INTO empresas_convenio (
            nombre_empresa, categoria, email, telefono, sitio_web, 
            direccion, descripcion, beneficios, descuento, activo, destacado,
            fecha_inicio_convenio, fecha_fin_convenio
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $empresa['nombre_empresa'],
            $empresa['categoria'],
            $empresa['email'],
            $empresa['telefono'],
            $empresa['sitio_web'],
            $empresa['direccion'],
            $empresa['descripcion'],
            $empresa['beneficios'],
            $empresa['descuento'],
            $empresa['activo'],
            $empresa['destacado'],
            $empresa['fecha_inicio_convenio'],
            $empresa['fecha_fin_convenio']
        ];
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        } catch (Exception $e) {
            error_log("Error insertando empresa ejemplo: " . $e->getMessage());
        }
    }
}

/**
 * Manejar peticiones GET
 */
function handleGet($db, $action, $id) {
    try {
        if ($action === 'stats') {
            // Obtener estadísticas
            $stats = obtenerEstadisticas($db);
            sendResponse(true, 'Estadísticas obtenidas', $stats);
            
        } elseif ($id) {
            // Obtener empresa específica
            $sql = "SELECT * FROM empresas_convenio WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($empresa) {
                sendResponse(true, 'Empresa encontrada', $empresa);
            } else {
                sendResponse(false, 'Empresa no encontrada', null, 404);
            }
            
        } else {
            // Obtener todas las empresas con filtros
            $filtros = obtenerFiltros();
            $empresas = obtenerEmpresas($db, $filtros);
            $stats = obtenerEstadisticas($db);
            
            sendResponse(true, 'Empresas obtenidas', [
                'empresas' => $empresas,
                'estadisticas' => $stats,
                'filtros_aplicados' => $filtros
            ]);
        }
    } catch (Exception $e) {
        sendResponse(false, 'Error al obtener datos: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Manejar peticiones POST (Crear)
 */
function handlePost($db, $action) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            sendResponse(false, 'Datos JSON inválidos', null, 400);
        }
        
        // Validar campos requeridos
        if (empty($data['nombre_empresa'])) {
            sendResponse(false, 'El nombre de la empresa es obligatorio', null, 400);
        }
        
        // Preparar datos para inserción
        $campos = [
            'nombre_empresa', 'categoria', 'email', 'telefono', 'sitio_web',
            'logo_url', 'direccion', 'descripcion', 'beneficios', 'descuento',
            'activo', 'destacado', 'fecha_inicio_convenio', 'fecha_fin_convenio'
        ];
        
        $valores = [];
        $placeholders = [];
        $camposSQL = [];
        
        foreach ($campos as $campo) {
            if (isset($data[$campo])) {
                $camposSQL[] = $campo;
                $placeholders[] = '?';
                
                // Convertir valores booleanos
                if ($campo === 'activo' || $campo === 'destacado') {
                    $valores[] = $data[$campo] ? 1 : 0;
                } else {
                    $valores[] = $data[$campo];
                }
            }
        }
        
        $sql = "INSERT INTO empresas_convenio (" . implode(', ', $camposSQL) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($valores);
        
        $empresaId = $db->lastInsertId();
        
        // Obtener la empresa creada
        $stmt = $db->prepare("SELECT * FROM empresas_convenio WHERE id = ?");
        $stmt->execute([$empresaId]);
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        sendResponse(true, 'Empresa creada exitosamente', $empresa, 201);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error al crear empresa: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Manejar peticiones PUT (Actualizar)
 */
function handlePut($db, $id) {
    try {
        if (!$id) {
            sendResponse(false, 'ID de empresa requerido', null, 400);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            sendResponse(false, 'Datos JSON inválidos', null, 400);
        }
        
        // Verificar que la empresa existe
        $stmt = $db->prepare("SELECT id FROM empresas_convenio WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            sendResponse(false, 'Empresa no encontrada', null, 404);
        }
        
        // Preparar campos para actualización
        $campos = [
            'nombre_empresa', 'categoria', 'email', 'telefono', 'sitio_web',
            'logo_url', 'direccion', 'descripcion', 'beneficios', 'descuento',
            'activo', 'destacado', 'fecha_inicio_convenio', 'fecha_fin_convenio'
        ];
        
        $setClauses = [];
        $valores = [];
        
        foreach ($campos as $campo) {
            if (isset($data[$campo])) {
                $setClauses[] = "$campo = ?";
                
                // Convertir valores booleanos
                if ($campo === 'activo' || $campo === 'destacado') {
                    $valores[] = $data[$campo] ? 1 : 0;
                } else {
                    $valores[] = $data[$campo];
                }
            }
        }
        
        if (empty($setClauses)) {
            sendResponse(false, 'No hay datos para actualizar', null, 400);
        }
        
        $valores[] = $id;
        $sql = "UPDATE empresas_convenio SET " . implode(', ', $setClauses) . " WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($valores);
        
        // Obtener la empresa actualizada
        $stmt = $db->prepare("SELECT * FROM empresas_convenio WHERE id = ?");
        $stmt->execute([$id]);
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        sendResponse(true, 'Empresa actualizada exitosamente', $empresa);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error al actualizar empresa: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Manejar peticiones DELETE (Eliminar)
 */
function handleDelete($db, $id) {
    try {
        if (!$id) {
            sendResponse(false, 'ID de empresa requerido', null, 400);
        }
        
        // Verificar que la empresa existe
        $stmt = $db->prepare("SELECT nombre_empresa FROM empresas_convenio WHERE id = ?");
        $stmt->execute([$id]);
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$empresa) {
            sendResponse(false, 'Empresa no encontrada', null, 404);
        }
        
        // Eliminar físicamente la empresa
        $stmt = $db->prepare("DELETE FROM empresas_convenio WHERE id = ?");
        $stmt->execute([$id]);
        
        sendResponse(true, 'Empresa eliminada exitosamente', [
            'id' => $id,
            'nombre_empresa' => $empresa['nombre_empresa']
        ]);
        
    } catch (Exception $e) {
        sendResponse(false, 'Error al eliminar empresa: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Obtener empresas con filtros
 */
function obtenerEmpresas($db, $filtros) {
    $sql = "SELECT * FROM empresas_convenio WHERE 1=1";
    $params = [];
    
    // Aplicar filtros
    if (!empty($filtros['busqueda'])) {
        $sql .= " AND (nombre_empresa LIKE ? OR categoria LIKE ? OR email LIKE ?)";
        $busqueda = '%' . $filtros['busqueda'] . '%';
        $params[] = $busqueda;
        $params[] = $busqueda;
        $params[] = $busqueda;
    }
    
    if (!empty($filtros['categoria'])) {
        $sql .= " AND categoria = ?";
        $params[] = $filtros['categoria'];
    }
    
    if ($filtros['activo'] !== '') {
        $sql .= " AND activo = ?";
        $params[] = $filtros['activo'];
    }
    
    if ($filtros['destacado'] !== '') {
        $sql .= " AND destacado = ?";
        $params[] = $filtros['destacado'];
    }
    
    $sql .= " ORDER BY destacado DESC, nombre_empresa ASC";
    
    if (!empty($filtros['limit'])) {
        $sql .= " LIMIT " . (int)$filtros['limit'];
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener estadísticas
 */
function obtenerEstadisticas($db) {
    try {
        $stats = [];
        
        // Total empresas
        $result = $db->query("SELECT COUNT(*) as count FROM empresas_convenio")->fetch();
        $stats['total'] = $result['count'];
        
        // Empresas activas
        $result = $db->query("SELECT COUNT(*) as count FROM empresas_convenio WHERE activo = 1")->fetch();
        $stats['activas'] = $result['count'];
        
        // Empresas destacadas
        $result = $db->query("SELECT COUNT(*) as count FROM empresas_convenio WHERE destacado = 1")->fetch();
        $stats['destacadas'] = $result['count'];
        
        // Empresas con descuentos
        $result = $db->query("SELECT COUNT(*) as count FROM empresas_convenio WHERE descuento > 0")->fetch();
        $stats['con_descuentos'] = $result['count'];
        
        return $stats;
        
    } catch (Exception $e) {
        return [
            'total' => 0,
            'activas' => 0,
            'destacadas' => 0,
            'con_descuentos' => 0
        ];
    }
}

/**
 * Obtener filtros de la petición
 */
function obtenerFiltros() {
    return [
        'busqueda' => $_GET['search'] ?? '',
        'categoria' => $_GET['categoria'] ?? '',
        'activo' => $_GET['activo'] ?? '',
        'destacado' => $_GET['destacado'] ?? '',
        'limit' => $_GET['limit'] ?? ''
    ];
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
