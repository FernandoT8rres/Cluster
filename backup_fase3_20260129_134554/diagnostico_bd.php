<?php
/**
 * Script de Diagnóstico de Base de Datos
 * Verifica y repara problemas comunes de conexión
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

function responderJSON($success, $data = null, $message = '', $detalles = []) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'detalles' => $detalles,
        'timestamp' => date('c')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

function verificarArchivosConfig() {
    $archivos = [
        'config/database.php' => '../config/database.php',
        'sql/empresas_convenio.sql' => '../sql/empresas_convenio.sql'
    ];
    
    $resultados = [];
    
    foreach ($archivos as $nombre => $ruta) {
        $resultados[$nombre] = [
            'existe' => file_exists($ruta),
            'ruta_completa' => realpath($ruta) ?: $ruta,
            'legible' => file_exists($ruta) && is_readable($ruta)
        ];
    }
    
    return $resultados;
}

function verificarConexionBD() {
    try {
        require_once __DIR__ . '/../config/database.php';
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        if ($conn) {
            return [
                'conectado' => true,
                'mensaje' => 'Conexión exitosa',
                'version' => $conn->query('SELECT VERSION()')->fetchColumn()
            ];
        } else {
            return [
                'conectado' => false,
                'mensaje' => 'No se pudo obtener la conexión'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'conectado' => false,
            'mensaje' => $e->getMessage(),
            'tipo_error' => get_class($e)
        ];
    }
}

function verificarTablaEmpresas($conn) {
    try {
        // Verificar si la tabla existe
        $stmt = $conn->query("SHOW TABLES LIKE 'empresas_convenio'");
        $tablaExiste = $stmt->rowCount() > 0;
        
        if (!$tablaExiste) {
            return [
                'existe' => false,
                'mensaje' => 'La tabla empresas_convenio no existe'
            ];
        }
        
        // Verificar estructura de la tabla
        $stmt = $conn->query("DESCRIBE empresas_convenio");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contar registros
        $stmt = $conn->query("SELECT COUNT(*) as total FROM empresas_convenio");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'existe' => true,
            'columnas' => count($columnas),
            'registros' => $total,
            'estructura' => $columnas
        ];
        
    } catch (Exception $e) {
        return [
            'existe' => false,
            'error' => $e->getMessage()
        ];
    }
}

function crearTablaEmpresas($conn) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `empresas_convenio` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nombre_empresa` varchar(255) NOT NULL,
            `descripcion` text,
            `logo_url` varchar(500) DEFAULT NULL,
            `sitio_web` varchar(255) DEFAULT NULL,
            `telefono` varchar(50) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `direccion` text,
            `categoria` varchar(100) DEFAULT NULL,
            `descuento` decimal(5,2) DEFAULT NULL,
            `beneficios` text,
            `fecha_inicio_convenio` date DEFAULT NULL,
            `fecha_fin_convenio` date DEFAULT NULL,
            `activo` tinyint(1) DEFAULT '1',
            `destacado` tinyint(1) DEFAULT '0',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_activo` (`activo`),
            KEY `idx_destacado` (`destacado`),
            KEY `idx_categoria` (`categoria`),
            KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        
        return [
            'creada' => true,
            'mensaje' => 'Tabla creada exitosamente'
        ];
        
    } catch (Exception $e) {
        return [
            'creada' => false,
            'error' => $e->getMessage()
        ];
    }
}

function insertarDatosEjemplo($conn) {
    try {
        // Verificar si ya hay datos
        $stmt = $conn->query("SELECT COUNT(*) as total FROM empresas_convenio");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($total > 0) {
            return [
                'insertados' => false,
                'mensaje' => 'Ya existen datos en la tabla',
                'total_existente' => $total
            ];
        }
        
        $sql = "INSERT INTO `empresas_convenio` 
                (`nombre_empresa`, `descripcion`, `categoria`, `descuento`, `beneficios`, `activo`, `destacado`, `sitio_web`, `email`, `telefono`) 
                VALUES
                ('AutoParts México', 'Proveedor líder de autopartes en México', 'Automotriz', 15.00, 'Descuentos especiales en autopartes, envío gratis en compras mayores a \$1000', 1, 1, 'https://autoparts.mx', 'contacto@autoparts.mx', '+52 55 1234 5678'),
                ('TechSolutions', 'Soluciones tecnológicas para la industria automotriz', 'Tecnología', 10.00, 'Consultoría gratuita, soporte técnico prioritario', 1, 0, 'https://techsolutions.com', 'info@techsolutions.com', '+52 55 2345 6789'),
                ('Logística Express', 'Servicios de logística y transporte', 'Logística', 20.00, 'Tarifas preferenciales, rastreo en tiempo real', 1, 1, 'https://logisticaexpress.mx', 'ventas@logisticaexpress.mx', '+52 55 3456 7890'),
                ('Manufactura Avanzada', 'Fabricación de componentes automotrices', 'Manufactura', 12.50, 'Descuentos por volumen, tiempos de entrega prioritarios', 1, 0, 'https://manufactura-avanzada.com', 'contacto@manufactura.com', '+52 55 4567 8901'),
                ('Servicios Integrales', 'Consultoría y servicios para empresas', 'Servicios', 8.00, 'Primera consulta gratis, planes personalizados', 1, 0, NULL, 'info@servicios.mx', '+52 55 5678 9012')";
        
        $conn->exec($sql);
        
        // Contar registros insertados
        $stmt = $conn->query("SELECT COUNT(*) as total FROM empresas_convenio");
        $totalInsertado = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'insertados' => true,
            'mensaje' => 'Datos de ejemplo insertados correctamente',
            'total_registros' => $totalInsertado
        ];
        
    } catch (Exception $e) {
        return [
            'insertados' => false,
            'error' => $e->getMessage()
        ];
    }
}

function reparacionCompleta() {
    $resultados = [];
    $conn = null;
    
    // 1. Verificar archivos de configuración
    $resultados['archivos'] = verificarArchivosConfig();
    
    // 2. Intentar conexión a BD
    $resultados['conexion'] = verificarConexionBD();
    
    if ($resultados['conexion']['conectado']) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // 3. Verificar tabla
            $resultados['tabla'] = verificarTablaEmpresas($conn);
            
            // 4. Crear tabla si no existe
            if (!$resultados['tabla']['existe']) {
                $resultados['crear_tabla'] = crearTablaEmpresas($conn);
                
                // Volver a verificar después de crear
                if ($resultados['crear_tabla']['creada']) {
                    $resultados['tabla'] = verificarTablaEmpresas($conn);
                }
            }
            
            // 5. Insertar datos de ejemplo si no existen
            if ($resultados['tabla']['existe'] && $resultados['tabla']['registros'] == 0) {
                $resultados['datos_ejemplo'] = insertarDatosEjemplo($conn);
            }
            
        } catch (Exception $e) {
            $resultados['error_reparacion'] = $e->getMessage();
        }
    }
    
    return $resultados;
}

// Procesar acción solicitada
$accion = $_GET['action'] ?? 'diagnostico';

switch ($accion) {
    case 'diagnostico':
        $diagnostico = reparacionCompleta();
        
        $todoOK = $diagnostico['conexion']['conectado'] && 
                  ($diagnostico['tabla']['existe'] ?? false) && 
                  ($diagnostico['tabla']['registros'] ?? 0) > 0;
        
        responderJSON(
            $todoOK,
            $diagnostico,
            $todoOK ? 'Sistema funcionando correctamente' : 'Se encontraron problemas que requieren atención'
        );
        break;
        
    case 'reparar':
        $reparacion = reparacionCompleta();
        
        $reparado = $reparacion['conexion']['conectado'] && 
                   ($reparacion['tabla']['existe'] ?? false);
        
        responderJSON(
            $reparado,
            $reparacion,
            $reparado ? 'Reparación completada exitosamente' : 'No se pudo completar la reparación'
        );
        break;
        
    case 'test':
        // Test simple de conexión
        $conexion = verificarConexionBD();
        responderJSON(
            $conexion['conectado'],
            $conexion,
            $conexion['conectado'] ? 'Conexión exitosa' : 'Error de conexión'
        );
        break;
        
    default:
        responderJSON(false, null, 'Acción no válida. Acciones disponibles: diagnostico, reparar, test');
}
?>