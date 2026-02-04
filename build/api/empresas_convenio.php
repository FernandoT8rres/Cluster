<?php
/**
 * API para gestión de Empresas en Convenio
 * Maneja operaciones CRUD para la tabla empresas_convenio
 */

// Headers CORS y JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Usar configuración de base de datos remota únicamente
require_once '../config/database.php';

/**
 * Función para responder en JSON
 */
function responderJSON($success, $data = null, $message = '', $extra = []) {
    http_response_code($success ? 200 : 400);
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ];

    // Agregar datos extra si existen
    foreach ($extra as $key => $value) {
        $response[$key] = $value;
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Obtener empresas con filtros opcionales
 */
function obtenerEmpresas($conn, $filtros = []) {
    try {
        $query = "SELECT
                    id,
                    nombre_empresa,
                    descripcion,
                    logo_url,
                    sitio_web,
                    telefono,
                    email,
                    direccion,
                    categoria,
                    descuento,
                    beneficios,
                    fecha_inicio_convenio,
                    fecha_fin_convenio,
                    activo,
                    destacado,
                    created_at,
                    updated_at
                  FROM empresas_convenio";

        $conditions = [];
        $params = [];

        // Filtro por estado activo
        if (isset($filtros['activo'])) {
            $conditions[] = "activo = :activo";
            $params[':activo'] = $filtros['activo'] ? 1 : 0;
        }

        // Filtro por destacado
        if (isset($filtros['destacado'])) {
            $conditions[] = "destacado = :destacado";
            $params[':destacado'] = $filtros['destacado'] ? 1 : 0;
        }

        // Filtro por categoría
        if (!empty($filtros['categoria'])) {
            $conditions[] = "categoria = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }

        // Filtro por vigencia
        if (isset($filtros['vigente']) && $filtros['vigente']) {
            $conditions[] = "(fecha_fin_convenio IS NULL OR fecha_fin_convenio >= CURDATE())";
            $conditions[] = "(fecha_inicio_convenio IS NULL OR fecha_inicio_convenio <= CURDATE())";
        }

        // Aplicar condiciones
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        // Ordenamiento
        $orderBy = $filtros['orderBy'] ?? 'created_at';
        $order = $filtros['order'] ?? 'DESC';
        $query .= " ORDER BY $orderBy $order";

        // Límite
        if (isset($filtros['limit'])) {
            $query .= " LIMIT " . intval($filtros['limit']);

            if (isset($filtros['offset'])) {
                $query .= " OFFSET " . intval($filtros['offset']);
            }
        }

        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Procesar URLs y datos
        foreach ($empresas as &$empresa) {
            // Asegurar que logo_url sea una URL válida
            if (!empty($empresa['logo_url']) && !filter_var($empresa['logo_url'], FILTER_VALIDATE_URL)) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $basePath = dirname(dirname($_SERVER['REQUEST_URI']));
                $empresa['logo_url'] = $protocol . '://' . $host . $basePath . '/' . ltrim($empresa['logo_url'], '/');
            }

            // Convertir campos numéricos
            $empresa['id'] = intval($empresa['id']);
            $empresa['activo'] = intval($empresa['activo']);
            $empresa['destacado'] = intval($empresa['destacado']);
            if ($empresa['descuento']) {
                $empresa['descuento'] = floatval($empresa['descuento']);
            }

            // Formatear fechas
            if ($empresa['fecha_inicio_convenio']) {
                $empresa['fecha_inicio_convenio'] = date('Y-m-d', strtotime($empresa['fecha_inicio_convenio']));
            }
            if ($empresa['fecha_fin_convenio']) {
                $empresa['fecha_fin_convenio'] = date('Y-m-d', strtotime($empresa['fecha_fin_convenio']));
            }
        }

        return $empresas;

    } catch (PDOException $e) {
        error_log("Error al obtener empresas: " . $e->getMessage());
        return [];
    }
}


/**
 * Obtener una empresa por ID
 */
function obtenerEmpresaPorId($conn, $id) {
    try {
        $query = "SELECT * FROM empresas_convenio WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error al obtener empresa por ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Crear nueva empresa
 */
function crearEmpresa($conn, $datos) {
    try {
        $campos = [
            'nombre_empresa',
            'descripcion',
            'logo_url',
            'sitio_web',
            'telefono',
            'email',
            'direccion',
            'categoria',
            'descuento',
            'beneficios',
            'fecha_inicio_convenio',
            'fecha_fin_convenio',
            'activo',
            'destacado'
        ];

        $valores = [];
        $params = [];

        foreach ($campos as $campo) {
            if (isset($datos[$campo])) {
                $valores[] = ":$campo";
                $params[":$campo"] = $datos[$campo];
            } else {
                $valores[] = "NULL";
            }
        }

        $query = "INSERT INTO empresas_convenio (" . implode(", ", $campos) . ")
                  VALUES (" . implode(", ", $valores) . ")";

        $stmt = $conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if ($stmt->execute()) {
            $id = $conn->lastInsertId();
            return obtenerEmpresaPorId($conn, $id);
        }

        return false;

    } catch (PDOException $e) {
        error_log("Error al crear empresa: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualizar empresa existente
 */
function actualizarEmpresa($conn, $id, $datos) {
    try {
        $campos = [];
        $params = [':id' => $id];

        $camposPermitidos = [
            'nombre_empresa',
            'descripcion',
            'logo_url',
            'sitio_web',
            'telefono',
            'email',
            'direccion',
            'categoria',
            'descuento',
            'beneficios',
            'fecha_inicio_convenio',
            'fecha_fin_convenio',
            'activo',
            'destacado'
        ];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $campos[] = "$campo = :$campo";
                $params[":$campo"] = $datos[$campo];
            }
        }

        if (empty($campos)) {
            return false;
        }

        $query = "UPDATE empresas_convenio SET " . implode(", ", $campos) . ", updated_at = NOW() WHERE id = :id";

        $stmt = $conn->prepare($query);

        if ($stmt->execute($params)) {
            return obtenerEmpresaPorId($conn, $id);
        }

        return false;

    } catch (PDOException $e) {
        error_log("Error al actualizar empresa: " . $e->getMessage());
        return false;
    }
}

/**
 * Eliminar empresa
 */
function eliminarEmpresa($conn, $id) {
    try {
        $query = "DELETE FROM empresas_convenio WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();

    } catch (PDOException $e) {
        error_log("Error al eliminar empresa: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener estadísticas
 */
function obtenerEstadisticas($conn) {
    try {
        $stats = [];

        // Total de empresas
        $stmt = $conn->query("SELECT COUNT(*) as total FROM empresas_convenio");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Empresas activas
        $stmt = $conn->query("SELECT COUNT(*) as activas FROM empresas_convenio WHERE activo = 1");
        $stats['activas'] = $stmt->fetch(PDO::FETCH_ASSOC)['activas'];

        // Empresas destacadas
        $stmt = $conn->query("SELECT COUNT(*) as destacadas FROM empresas_convenio WHERE destacado = 1");
        $stats['destacadas'] = $stmt->fetch(PDO::FETCH_ASSOC)['destacadas'];

        // Por categoría
        $stmt = $conn->query("SELECT categoria, COUNT(*) as cantidad FROM empresas_convenio GROUP BY categoria");
        $stats['por_categoria'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;

    } catch (PDOException $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        return [];
    }
}

// Inicializar conexión a la base de datos
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    responderJSON(false, null, 'Error de conexión a la base de datos');
}

// Obtener método y acción
$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['action'] ?? '';

switch ($metodo) {
    case 'GET':
        if (!empty($_GET['id'])) {
            // Obtener empresa específica
            $empresa = obtenerEmpresaPorId($conn, $_GET['id']);

            if ($empresa) {
                responderJSON(true, $empresa, 'Empresa obtenida correctamente');
            } else {
                responderJSON(false, null, 'Empresa no encontrada');
            }

        } elseif ($accion === 'stats') {
            // Obtener estadísticas
            $stats = obtenerEstadisticas($conn);
            responderJSON(true, $stats, 'Estadísticas obtenidas correctamente');

        } elseif ($accion === 'destacadas') {
            // Obtener solo empresas destacadas activas
            $filtros = [
                'activo' => true,
                'destacado' => true
            ];

            if (isset($_GET['limit'])) {
                $filtros['limit'] = (int)$_GET['limit'];
            }

            $empresas = obtenerEmpresas($conn, $filtros);

            // Obtener total de destacadas
            $totalQuery = "SELECT COUNT(*) as total FROM empresas_convenio WHERE activo = 1 AND destacado = 1";
            $totalStmt = $conn->query($totalQuery);
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            responderJSON(true, $empresas, 'Empresas destacadas obtenidas correctamente', ['total' => $total]);

        } else {
            // Obtener lista de empresas con filtros
            $filtros = [];

            if (isset($_GET['activo'])) {
                $filtros['activo'] = $_GET['activo'] === '1';
            }

            if (isset($_GET['destacado'])) {
                $filtros['destacado'] = $_GET['destacado'] === '1';
            }

            if (isset($_GET['vigente'])) {
                $filtros['vigente'] = $_GET['vigente'] === '1';
            }

            if (!empty($_GET['categoria'])) {
                $filtros['categoria'] = $_GET['categoria'];
            }

            if (!empty($_GET['limit'])) {
                $filtros['limit'] = intval($_GET['limit']);
            }

            if (!empty($_GET['offset'])) {
                $filtros['offset'] = intval($_GET['offset']);
            }

            if (!empty($_GET['orderBy'])) {
                $filtros['orderBy'] = $_GET['orderBy'];
            }

            if (!empty($_GET['order'])) {
                $filtros['order'] = $_GET['order'];
            }

            $empresas = obtenerEmpresas($conn, $filtros);

            // Obtener total para paginación
            $totalQuery = "SELECT COUNT(*) as total FROM empresas_convenio";
            if (isset($filtros['activo'])) {
                $totalQuery .= " WHERE activo = " . ($filtros['activo'] ? 1 : 0);
            }
            $totalStmt = $conn->query($totalQuery);
            $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

            responderJSON(true, $empresas, 'Empresas obtenidas correctamente', ['total' => $total]);
        }
        break;

    case 'POST':
        $datos = json_decode(file_get_contents('php://input'), true);

        if (!$datos) {
            responderJSON(false, null, 'Datos inválidos');
        }

        // Validación básica
        if (empty($datos['nombre_empresa'])) {
            responderJSON(false, null, 'El nombre de la empresa es requerido');
        }

        $empresa = crearEmpresa($conn, $datos);

        if ($empresa) {
            responderJSON(true, $empresa, 'Empresa creada correctamente');
        } else {
            responderJSON(false, null, 'Error al crear la empresa');
        }
        break;

    case 'PUT':
        $datos = json_decode(file_get_contents('php://input'), true);

        if (!$datos || !isset($datos['id'])) {
            responderJSON(false, null, 'Datos inválidos o ID no proporcionado');
        }

        $id = $datos['id'];
        unset($datos['id']);

        $empresa = actualizarEmpresa($conn, $id, $datos);

        if ($empresa) {
            responderJSON(true, $empresa, 'Empresa actualizada correctamente');
        } else {
            responderJSON(false, null, 'Error al actualizar la empresa');
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;

        if (!$id) {
            responderJSON(false, null, 'ID no proporcionado');
        }

        if (eliminarEmpresa($conn, $id)) {
            responderJSON(true, null, 'Empresa eliminada correctamente');
        } else {
            responderJSON(false, null, 'Error al eliminar la empresa');
        }
        break;

    default:
        http_response_code(405);
        responderJSON(false, null, 'Método no permitido');
}
?>