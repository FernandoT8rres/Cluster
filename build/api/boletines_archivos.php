<?php
/**
 * API para manejar archivos adjuntos de boletines
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Configuración de base de datos
$config = [
    'host' => '127.0.0.1',
    'username' => 'u695712029_claut_fer',
    'password' => 'CLAUT@admin_fernando!7',
    'database' => 'u695712029_claut_intranet',
    'charset' => 'utf8mb4'
];

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
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $boletin_id = isset($_GET['boletin_id']) ? intval($_GET['boletin_id']) : null;
    
    if (!$boletin_id) {
        sendJsonResponse('ID del boletín es requerido', false);
    }
    
    // Obtener información del archivo adjunto del boletín
    $stmt = $pdo->prepare("SELECT id, titulo, archivo_adjunto FROM boletines WHERE id = ?");
    $stmt->execute([$boletin_id]);
    $boletin = $stmt->fetch();
    
    if (!$boletin) {
        sendJsonResponse('Boletín no encontrado', false);
    }
    
    $archivos = [];
    
    // Si hay archivo adjunto, agregar a la lista
    if ($boletin['archivo_adjunto']) {
        $filePath = '../uploads/' . $boletin['archivo_adjunto'];
        $fileExists = file_exists($filePath);
        
        // Obtener información del archivo
        $fileInfo = pathinfo($boletin['archivo_adjunto']);
        $extension = strtolower($fileInfo['extension'] ?? '');
        
        // Determinar el tipo MIME
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg'
        ];
        
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        // Determinar el ícono
        $iconos = [
            'pdf' => '📄',
            'doc' => '📝', 'docx' => '📝',
            'xls' => '📊', 'xlsx' => '📊',
            'ppt' => '📽️', 'pptx' => '📽️',
            'txt' => '📄', 'csv' => '📊',
            'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️',
            'mp4' => '🎥',
            'mp3' => '🎵'
        ];
        
        $icono = $iconos[$extension] ?? '📎';
        
        // Obtener tamaño del archivo si existe
        $fileSize = 0;
        $sizeFormatted = 'N/A';
        if ($fileExists) {
            $fileSize = filesize($filePath);
            
            // Formatear tamaño
            if ($fileSize < 1024) {
                $sizeFormatted = $fileSize . ' B';
            } elseif ($fileSize < 1048576) {
                $sizeFormatted = round($fileSize / 1024, 2) . ' KB';
            } else {
                $sizeFormatted = round($fileSize / 1048576, 2) . ' MB';
            }
        }
        
        // Determinar si es visualizable
        $isViewable = in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'csv']);
        
        $archivos[] = [
            'id' => $boletin['id'],
            'nombre_original' => $boletin['archivo_adjunto'],
            'nombre_archivo' => $boletin['archivo_adjunto'],
            'tipo_mime' => $mimeType,
            'extension' => $extension,
            'tamaño' => $fileSize,
            'tamaño_formateado' => $sizeFormatted,
            'icono' => $icono,
            'es_pdf' => $extension === 'pdf',
            'es_imagen' => in_array($extension, ['jpg', 'jpeg', 'png', 'gif']),
            'es_visualizable' => $isViewable,
            'existe' => $fileExists,
            'url_vista' => './uploads/' . $boletin['archivo_adjunto'],
            'url_descarga' => './uploads/' . $boletin['archivo_adjunto']
        ];
    }
    
    sendJsonResponse([
        'data' => $archivos,
        'total' => count($archivos)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en archivos API: " . $e->getMessage());
    sendJsonResponse('Error de base de datos: ' . $e->getMessage(), false);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    sendJsonResponse('Error del servidor: ' . $e->getMessage(), false);
}
?>
