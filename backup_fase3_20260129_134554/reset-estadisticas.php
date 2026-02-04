<?php
/**
 * Script para resetear/limpiar la tabla de estadísticas dinámicas
 * USAR SOLO EN DESARROLLO O CUANDO SEA NECESARIO
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

require_once '../assets/conexion/config.php';

// Función para respuesta JSON
function sendJsonResponse($data, $success = true) {
    $response = [
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($success) {
        $response = array_merge($response, $data);
    } else {
        $response['message'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    // Conectar a la base de datos
    $db = new DatabaseWrapper();
    $pdo = $db->getConnection();
    
    $action = $_GET['action'] ?? 'info';
    
    switch ($action) {
        case 'info':
            // Mostrar información actual
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM estadisticas_config")->fetchColumn();
                $configs = $pdo->query("SELECT nombre, titulo, activo FROM estadisticas_config ORDER BY posicion")->fetchAll();
                
                sendJsonResponse([
                    'message' => 'Información de la tabla estadisticas_config',
                    'total_registros' => $count,
                    'configuraciones' => $configs,
                    'acciones_disponibles' => [
                        'reset-estadisticas.php?action=clear' => 'Eliminar todos los registros',
                        'reset-estadisticas.php?action=reset' => 'Resetear con datos por defecto',
                        'reset-estadisticas.php?action=drop' => 'Eliminar tabla completamente'
                    ]
                ]);
            } catch (Exception $e) {
                sendJsonResponse([
                    'message' => 'La tabla estadisticas_config no existe aún',
                    'error' => $e->getMessage(),
                    'solucion' => 'Visita /api/estadisticas-config.php para crearla automáticamente'
                ]);
            }
            break;
            
        case 'clear':
            // Limpiar todos los registros
            $deleted = $pdo->exec("DELETE FROM estadisticas_config");
            sendJsonResponse([
                'message' => "Se eliminaron $deleted registros de estadisticas_config",
                'registros_eliminados' => $deleted
            ]);
            break;
            
        case 'reset':
            // Resetear con datos por defecto
            $pdo->exec("DELETE FROM estadisticas_config");
            
            $defaultStats = [
                [
                    'nombre' => 'usuarios_total',
                    'titulo' => 'Total Usuarios',
                    'icono' => 'fas fa-users',
                    'color' => 'blue',
                    'query_sql' => 'SELECT COUNT(*) as valor FROM usuarios',
                    'formato' => 'number',
                    'posicion' => 1,
                    'descripcion' => 'Total de usuarios registrados'
                ],
                [
                    'nombre' => 'usuarios_activos',
                    'titulo' => 'Usuarios Activos',
                    'icono' => 'fas fa-user-check',
                    'color' => 'green',
                    'query_sql' => 'SELECT COUNT(*) as valor FROM usuarios WHERE estado = "activo"',
                    'formato' => 'number',
                    'posicion' => 2,
                    'descripcion' => 'Usuarios con estado activo'
                ],
                [
                    'nombre' => 'empresas_total',
                    'titulo' => 'Total Empresas',
                    'icono' => 'fas fa-building',
                    'color' => 'green',
                    'query_sql' => 'SELECT COUNT(*) as valor FROM empresas_convenio',
                    'formato' => 'number',
                    'posicion' => 3,
                    'descripcion' => 'Total de empresas registradas'
                ],
                [
                    'nombre' => 'eventos_total',
                    'titulo' => 'Total Eventos',
                    'icono' => 'fas fa-calendar-alt',
                    'color' => 'purple',
                    'query_sql' => 'SELECT COUNT(*) as valor FROM eventos',
                    'formato' => 'number',
                    'posicion' => 4,
                    'descripcion' => 'Total de eventos registrados'
                ],
                [
                    'nombre' => 'banners_total',
                    'titulo' => 'Total Banners',
                    'icono' => 'fas fa-images',
                    'color' => 'orange',
                    'query_sql' => 'SELECT COUNT(*) as valor FROM banner_carrusel',
                    'formato' => 'number',
                    'posicion' => 5,
                    'descripcion' => 'Total de banners en carrusel'
                ]
            ];
            
            $insertSQL = "INSERT INTO estadisticas_config (nombre, titulo, icono, color, query_sql, formato, posicion, descripcion, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($insertSQL);
            
            $inserted = 0;
            foreach ($defaultStats as $stat) {
                if ($stmt->execute([
                    $stat['nombre'], $stat['titulo'], $stat['icono'], $stat['color'],
                    $stat['query_sql'], $stat['formato'], $stat['posicion'], 
                    $stat['descripcion']
                ])) {
                    $inserted++;
                }
            }
            
            sendJsonResponse([
                'message' => "Tabla reseteada exitosamente con datos por defecto",
                'registros_insertados' => $inserted
            ]);
            break;
            
        case 'drop':
            // Eliminar tabla completamente
            $pdo->exec("DROP TABLE IF EXISTS estadisticas_config");
            sendJsonResponse([
                'message' => "Tabla estadisticas_config eliminada completamente",
                'nota' => 'La tabla se recreará automáticamente la próxima vez que visites /api/estadisticas-config.php'
            ]);
            break;
            
        default:
            sendJsonResponse('Acción no válida. Usa: info, clear, reset, o drop', false);
    }
    
} catch (PDOException $e) {
    error_log("Error en reset-estadisticas (PDO): " . $e->getMessage());
    sendJsonResponse('Error de base de datos: ' . $e->getMessage(), false);
    
} catch (Exception $e) {
    error_log("Error general en reset-estadisticas: " . $e->getMessage());
    sendJsonResponse('Error del servidor: ' . $e->getMessage(), false);
}
?>