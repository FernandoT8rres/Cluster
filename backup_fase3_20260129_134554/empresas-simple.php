<?php
/**
 * API simplificada para empresas de convenio
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../assets/conexion/config.php';

function sendJsonResponse($data, $success = true) {
    echo json_encode([
        'success' => $success,
        'data' => $success ? $data : null,
        'message' => $success ? 'OK' : $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? 'listar';
    
    switch ($action) {
        case 'destacadas':
            // Logging para debug
            error_log("API destacadas - Iniciando carga de empresas destacadas");
            
            try {
                // Consulta solo empresas destacadas y activas
                $sql = "SELECT *, 
                        COALESCE(NULLIF(nombre_empresa, ''), NULLIF(descripcion, ''), 'Empresa ID ' || id) as nombre_display
                        FROM empresas_convenio WHERE activo = 1 AND destacado = 1 ORDER BY nombre_display ASC LIMIT 5";
                error_log("API destacadas - SQL: $sql");
                
                $stmt = $pdo->prepare($sql);
                $executeResult = $stmt->execute();
                
                error_log("API destacadas - Execute result: " . ($executeResult ? 'true' : 'false'));
                
                $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $totalEmpresas = count($empresas);
                
                error_log("API destacadas - Empresas destacadas encontradas: $totalEmpresas");
                
                if ($totalEmpresas > 0) {
                    error_log("API destacadas - Primera empresa: " . ($empresas[0]['nombre_empresa'] ?? 'Sin nombre'));
                }
                
            } catch (Exception $e) {
                error_log("API destacadas - Exception: " . $e->getMessage());
                sendJsonResponse('Error cargando empresas destacadas: ' . $e->getMessage(), false);
            }
            
            // Procesar datos para la vista
            foreach ($empresas as &$empresa) {
                // Asegurar que tenemos un nombre válido para mostrar
                $nombreDisplay = $empresa['nombre_display'] ?? null;
                $nombreEmpresa = !empty($empresa['nombre_empresa']) ? $empresa['nombre_empresa'] : null;
                $descripcion = !empty($empresa['descripcion']) ? $empresa['descripcion'] : null;
                
                $empresa['nombre_mostrar'] = $nombreDisplay ?? $nombreEmpresa ?? $descripcion ?? "Empresa ID " . $empresa['id'];
                
                // Convertir campos numéricos
                if (isset($empresa['descuento_porcentaje'])) {
                    $empresa['descuento_porcentaje'] = (float) $empresa['descuento_porcentaje'];
                }
                
                // Generar URL de logo por defecto si no existe
                if (empty($empresa['logo_url'])) {
                    $nombreSeguro = urlencode($empresa['nombre_mostrar']);
                    $empresa['logo_url'] = "https://via.placeholder.com/300x200/6366f1/ffffff?text=$nombreSeguro";
                }
                
                // Calcular estado visual basado en activo
                $empresa['estado_visual'] = ($empresa['activo'] == 1) ? 'success' : 'warning';
                
                // Preparar beneficios como array si es string
                if (!empty($empresa['beneficios']) && is_string($empresa['beneficios'])) {
                    $empresa['beneficios_array'] = explode('.', $empresa['beneficios']);
                    $empresa['beneficios_array'] = array_filter(array_map('trim', $empresa['beneficios_array']));
                }
                
                // Descripción corta para vista de tabla
                if (!empty($empresa['descripcion'])) {
                    $empresa['descripcion_corta'] = mb_substr($empresa['descripcion'], 0, 60) . (mb_strlen($empresa['descripcion']) > 60 ? '...' : '');
                } else {
                    $empresa['descripcion_corta'] = $empresa['nombre_mostrar'];
                }
            }
            
            sendJsonResponse([
                'empresas' => $empresas,
                'total' => $totalEmpresas
            ]);
            break;
            
        case 'listar':
            // Logging para debug
            error_log("API listar - Iniciando carga de empresas");
            
            try {
                // Consulta directa y simple - usar nombre_empresa que es el campo correcto
                $sql = "SELECT *, 
                        COALESCE(NULLIF(nombre_empresa, ''), NULLIF(descripcion, ''), 'Empresa ID ' || id) as nombre_display
                        FROM empresas_convenio WHERE activo = 1 ORDER BY nombre_display ASC";
                error_log("API listar - SQL: $sql");
                
                $stmt = $pdo->prepare($sql);
                $executeResult = $stmt->execute();
                
                error_log("API listar - Execute result: " . ($executeResult ? 'true' : 'false'));
                
                if (!$executeResult) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("API listar - SQL Error: " . print_r($errorInfo, true));
                    sendJsonResponse('Error ejecutando consulta: ' . $errorInfo[2], false);
                }
                
                $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $totalEmpresas = count($empresas);
                
                error_log("API listar - Empresas encontradas: $totalEmpresas");
                
                if ($totalEmpresas > 0) {
                    error_log("API listar - Primera empresa: " . ($empresas[0]['nombre_empresa'] ?? 'Sin nombre'));
                }
                
            } catch (Exception $e) {
                error_log("API listar - Exception: " . $e->getMessage());
                sendJsonResponse('Error cargando empresas: ' . $e->getMessage(), false);
            }
            
            // Procesar datos para la vista
            foreach ($empresas as &$empresa) {
                // Asegurar que tenemos un nombre válido para mostrar
                $nombreDisplay = $empresa['nombre_display'] ?? null;
                $nombreEmpresa = !empty($empresa['nombre_empresa']) ? $empresa['nombre_empresa'] : null;
                $descripcion = !empty($empresa['descripcion']) ? $empresa['descripcion'] : null;
                
                $empresa['nombre_mostrar'] = $nombreDisplay ?? $nombreEmpresa ?? $descripcion ?? "Empresa ID " . $empresa['id'];
                
                // Log para debug
                error_log("Empresa ID {$empresa['id']}: nombre_display='$nombreDisplay', nombre_empresa='$nombreEmpresa', descripcion='$descripcion' -> nombre_mostrar='{$empresa['nombre_mostrar']}'");
                
                // Convertir campos numéricos
                if (isset($empresa['descuento_porcentaje'])) {
                    $empresa['descuento_porcentaje'] = (float) $empresa['descuento_porcentaje'];
                }
                
                // Generar URL de logo por defecto si no existe
                if (empty($empresa['logo_url'])) {
                    $nombreSeguro = urlencode($empresa['nombre_mostrar']);
                    $empresa['logo_url'] = "https://via.placeholder.com/300x200/6366f1/ffffff?text=$nombreSeguro";
                }
                
                // Formatear fecha de convenio
                if (!empty($empresa['fecha_convenio'])) {
                    $fecha = new DateTime($empresa['fecha_convenio']);
                    $empresa['fecha_convenio_formateada'] = $fecha->format('d/m/Y');
                }
                
                // Calcular estado visual basado en activo
                $empresa['estado_visual'] = ($empresa['activo'] == 1) ? 'success' : 'warning';
                
                // Preparar beneficios como array si es string
                if (!empty($empresa['beneficios']) && is_string($empresa['beneficios'])) {
                    $empresa['beneficios_array'] = explode('.', $empresa['beneficios']);
                    $empresa['beneficios_array'] = array_filter(array_map('trim', $empresa['beneficios_array']));
                }
                
                // Preparar descripción resumida para cards
                if (!empty($empresa['descripcion'])) {
                    $empresa['descripcion_corta'] = strlen($empresa['descripcion']) > 150 
                        ? substr($empresa['descripcion'], 0, 150) . '...' 
                        : $empresa['descripcion'];
                }
            }
            
            // Agregar nombre_mostrar a cada empresa antes de enviar respuesta
            foreach ($empresas as &$empresa) {
                if (empty($empresa['nombre_mostrar'])) {
                    $empresa['nombre_mostrar'] = $empresa['nombre'] ?? $empresa['nombre_empresa'] ?? $empresa['descripcion'] ?? "Empresa ID " . $empresa['id'];
                }
            }
            
            sendJsonResponse([
                'empresas' => $empresas,
                'total' => count($empresas)
            ]);
            break;
            
        case 'obtener':
            // Obtener una empresa específica
            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendJsonResponse('ID de empresa requerido', false);
            }
            
            $stmt = $pdo->prepare("SELECT * FROM empresas_convenio WHERE id = ? AND activo = 1");
            $stmt->execute([$id]);
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$empresa) {
                sendJsonResponse('Empresa no encontrada', false);
            }
            
            // Procesar datos igual que en listar
            if (isset($empresa['descuento_porcentaje'])) {
                $empresa['descuento_porcentaje'] = (float) $empresa['descuento_porcentaje'];
            }
            
            if (empty($empresa['logo_url'])) {
                $nombreSeguro = urlencode($empresa['nombre']);
                $empresa['logo_url'] = "https://via.placeholder.com/300x200/6366f1/ffffff?text=$nombreSeguro";
            }
            
            if (!empty($empresa['fecha_convenio'])) {
                $fecha = new DateTime($empresa['fecha_convenio']);
                $empresa['fecha_convenio_formateada'] = $fecha->format('d/m/Y');
            }
            
            if (!empty($empresa['beneficios']) && is_string($empresa['beneficios'])) {
                $empresa['beneficios_array'] = explode('.', $empresa['beneficios']);
                $empresa['beneficios_array'] = array_filter(array_map('trim', $empresa['beneficios_array']));
            }
            
            sendJsonResponse(['empresa' => $empresa]);
            break;
            
        case 'buscar':
            // Buscar empresas por término
            $termino = $_GET['termino'] ?? '';
            if (empty($termino)) {
                sendJsonResponse('Término de búsqueda requerido', false);
            }
            
            $sql = "SELECT * FROM empresas_convenio 
                    WHERE activo = 1 AND (
                        nombre LIKE ? OR 
                        descripcion LIKE ? OR 
                        sector LIKE ? OR 
                        beneficios LIKE ?
                    )
                    ORDER BY nombre ASC";
            
            $searchTerm = "%$termino%";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse([
                'empresas' => $empresas,
                'total' => count($empresas),
                'termino_busqueda' => $termino
            ]);
            break;
            
        case 'crear':
            if ($method !== 'POST') {
                sendJsonResponse('Método no permitido', false);
            }
            
            $nombre = $_POST['nombre'] ?? '';
            
            // Log para debug
            error_log("API crear - Nombre: $nombre");
            error_log("API crear - POST data: " . print_r($_POST, true));
            
            if (empty($nombre)) {
                sendJsonResponse('El nombre es requerido para crear empresa', false);
            }
            
            $campos = [
                'descripcion', 'logo_url', 'sitio_web', 'email', 'telefono',
                'direccion', 'sector', 'estado', 'fecha_convenio', 'contacto_nombre',
                'contacto_cargo', 'contacto_telefono', 'contacto_email',
                'beneficios', 'descuento_porcentaje', 'condiciones'
            ];
            
            $columnas = ['nombre_empresa', 'activo', 'created_at'];
            $valores = [$nombre, 1, date('Y-m-d H:i:s')];
            $placeholders = ['?', '?', '?'];
            
            // Agregar campos opcionales - solo si tienen valor
            foreach ($campos as $campo) {
                if (isset($_POST[$campo]) && $_POST[$campo] !== '') {
                    $columnas[] = $campo;
                    $valores[] = $_POST[$campo];
                    $placeholders[] = '?';
                    error_log("API crear - Campo $campo: " . $_POST[$campo]);
                }
            }

            // Mapear contacto_persona a contacto_nombre si existe
            if (isset($_POST['contacto_persona']) && $_POST['contacto_persona'] !== '') {
                $columnas[] = 'contacto_nombre';
                $valores[] = $_POST['contacto_persona'];
                $placeholders[] = '?';
                error_log("API crear - Mapeando contacto_persona a contacto_nombre: " . $_POST['contacto_persona']);
            }
            
            $sql = "INSERT INTO empresas_convenio (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            error_log("API crear - SQL: $sql");
            error_log("API crear - Valores: " . print_r($valores, true));
            
            $stmt = $pdo->prepare($sql);
            $executeResult = $stmt->execute($valores);
            
            error_log("API crear - Execute result: " . ($executeResult ? 'true' : 'false'));
            
            if ($executeResult) {
                $nuevoId = $pdo->lastInsertId();
                error_log("API crear - Nuevo ID: $nuevoId");
                
                // Verificar que realmente se insertó
                $checkStmt = $pdo->prepare("SELECT * FROM empresas_convenio WHERE id = ?");
                $checkStmt->execute([$nuevoId]);
                $empresaCreada = $checkStmt->fetch();
                
                if ($empresaCreada) {
                    error_log("API crear - Empresa verificada en BD: " . $empresaCreada['nombre']);
                    sendJsonResponse([
                        'mensaje' => 'Empresa creada exitosamente',
                        'id' => $nuevoId,
                        'empresa' => $empresaCreada
                    ]);
                } else {
                    error_log("API crear - ERROR: Empresa no encontrada después de insertar");
                    sendJsonResponse('Error: Empresa no se encontró después de crear', false);
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("API crear - Error info: " . print_r($errorInfo, true));
                sendJsonResponse('Error ejecutando creación: ' . $errorInfo[2], false);
            }
            break;
            
        case 'actualizar':
            if ($method !== 'POST') {
                sendJsonResponse('Método no permitido', false);
            }
            
            $id = $_POST['id'] ?? '';
            $nombre = $_POST['nombre'] ?? '';
            
            // Log para debug
            error_log("API actualizar - ID: $id, Nombre: $nombre");
            error_log("API actualizar - POST data: " . print_r($_POST, true));
            
            if (empty($id)) {
                sendJsonResponse('ID es requerido para actualización', false);
            }
            
            if (empty($nombre)) {
                sendJsonResponse('Nombre es requerido para actualización', false);
            }
            
            // Verificar que la empresa existe
            $checkStmt = $pdo->prepare("SELECT id FROM empresas_convenio WHERE id = ? AND activo = 1");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                sendJsonResponse("Empresa con ID $id no encontrada o inactiva", false);
            }
            
            $campos = [
                'descripcion', 'logo_url', 'sitio_web', 'email', 'telefono',
                'direccion', 'sector', 'estado', 'fecha_convenio', 'contacto_nombre',
                'contacto_cargo', 'contacto_telefono', 'contacto_email',
                'beneficios', 'descuento_porcentaje', 'condiciones'
            ];
            
            $updates = ['nombre = ?'];
            $valores = [$nombre];
            
            // Agregar campos a actualizar - solo si tienen valor
            foreach ($campos as $campo) {
                if (isset($_POST[$campo]) && $_POST[$campo] !== '') {
                    $updates[] = "$campo = ?";
                    $valores[] = $_POST[$campo];
                    error_log("API actualizar - Campo $campo: " . $_POST[$campo]);
                } elseif (isset($_POST[$campo])) {
                    // Si está presente pero vacío, actualizarlo a NULL
                    $updates[] = "$campo = NULL";
                    error_log("API actualizar - Campo $campo: NULL (vacío)");
                }
            }

            // Mapear contacto_persona a contacto_nombre si existe
            if (isset($_POST['contacto_persona'])) {
                if ($_POST['contacto_persona'] !== '') {
                    $updates[] = "contacto_nombre = ?";
                    $valores[] = $_POST['contacto_persona'];
                    error_log("API actualizar - Mapeando contacto_persona a contacto_nombre: " . $_POST['contacto_persona']);
                } else {
                    $updates[] = "contacto_nombre = NULL";
                    error_log("API actualizar - Campo contacto_nombre: NULL (vacío)");
                }
            }
            
            $valores[] = $id;
            $sql = "UPDATE empresas_convenio SET " . implode(', ', $updates) . " WHERE id = ? AND activo = 1";
            
            error_log("API actualizar - SQL: $sql");
            error_log("API actualizar - Valores: " . print_r($valores, true));
            
            $stmt = $pdo->prepare($sql);
            $executeResult = $stmt->execute($valores);
            $rowsAffected = $stmt->rowCount();
            
            error_log("API actualizar - Execute result: " . ($executeResult ? 'true' : 'false'));
            error_log("API actualizar - Rows affected: $rowsAffected");
            
            if ($executeResult && $rowsAffected > 0) {
                sendJsonResponse([
                    'mensaje' => 'Empresa actualizada exitosamente',
                    'id' => $id,
                    'rows_affected' => $rowsAffected
                ]);
            } elseif ($executeResult && $rowsAffected === 0) {
                sendJsonResponse('No se realizaron cambios - empresa no encontrada o datos idénticos', false);
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("API actualizar - Error info: " . print_r($errorInfo, true));
                sendJsonResponse('Error ejecutando actualización: ' . $errorInfo[2], false);
            }
            break;
            
        case 'eliminar':
            if ($method !== 'POST') {
                sendJsonResponse('Método no permitido', false);
            }
            
            $id = $_POST['id'] ?? '';
            
            // Log para debug
            error_log("API eliminar - ID: $id");
            error_log("API eliminar - POST data: " . print_r($_POST, true));
            
            if (empty($id)) {
                sendJsonResponse('ID es requerido para eliminación', false);
            }
            
            // Verificar que la empresa existe y está activa
            $checkStmt = $pdo->prepare("SELECT id, nombre FROM empresas_convenio WHERE id = ? AND activo = 1");
            $checkStmt->execute([$id]);
            $empresa = $checkStmt->fetch();
            
            if (!$empresa) {
                error_log("API eliminar - Empresa con ID $id no encontrada o ya inactiva");
                sendJsonResponse("Empresa con ID $id no encontrada o ya eliminada", false);
            }
            
            error_log("API eliminar - Eliminando empresa: " . $empresa['nombre']);
            
            // Soft delete - cambiar activo a 0
            $stmt = $pdo->prepare("UPDATE empresas_convenio SET activo = 0 WHERE id = ? AND activo = 1");
            $executeResult = $stmt->execute([$id]);
            $rowsAffected = $stmt->rowCount();
            
            error_log("API eliminar - Execute result: " . ($executeResult ? 'true' : 'false'));
            error_log("API eliminar - Rows affected: $rowsAffected");
            
            if ($executeResult && $rowsAffected > 0) {
                sendJsonResponse([
                    'mensaje' => 'Empresa eliminada exitosamente',
                    'id' => $id,
                    'rows_affected' => $rowsAffected
                ]);
            } elseif ($executeResult && $rowsAffected === 0) {
                sendJsonResponse('Empresa ya estaba eliminada o no encontrada', false);
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("API eliminar - Error info: " . print_r($errorInfo, true));
                sendJsonResponse('Error ejecutando eliminación: ' . $errorInfo[2], false);
            }
            break;
            
        default:
            sendJsonResponse('Acción no válida', false);
    }
    
} catch (Exception $e) {
    error_log("Error en API empresas-simple: " . $e->getMessage());
    sendJsonResponse('Error del servidor: ' . $e->getMessage(), false);
}
?>