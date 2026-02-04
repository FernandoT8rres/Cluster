<?php
/**
 * Endpoint para subir imágenes de banners
 * Maneja la subida de archivos locales para los banners
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function responderJSON($success, $data = null, $message = '') {
    http_response_code($success ? 200 : 400);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit();
}

function crearDirectorioSiNoExiste($directorio) {
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJSON(false, null, 'Método no permitido');
}

if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    responderJSON(false, null, 'No se recibió ningún archivo o hubo un error en la subida');
}

$archivo = $_FILES['imagen'];

// Validar tipo de archivo
$extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if (!in_array($extension, $extensionesPermitidas)) {
    responderJSON(false, null, 'Formato de imagen no permitido. Use: ' . implode(', ', $extensionesPermitidas));
}

// Validar tamaño (máximo 5MB)
$tamañoMaximo = 5 * 1024 * 1024; // 5MB
if ($archivo['size'] > $tamañoMaximo) {
    responderJSON(false, null, 'El archivo es demasiado grande. Tamaño máximo: 5MB');
}

// Validar que es una imagen real
$infoImagen = getimagesize($archivo['tmp_name']);
if ($infoImagen === false) {
    responderJSON(false, null, 'El archivo no es una imagen válida');
}

// Crear directorio de subida
$directorioSubida = '../uploads/banners/';
crearDirectorioSiNoExiste($directorioSubida);

// Generar nombre único
$nombreArchivo = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
$rutaCompleta = $directorioSubida . $nombreArchivo;

// Mover archivo
if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
    // Crear URL relativa
    $urlImagen = 'uploads/banners/' . $nombreArchivo;
    
    // Crear URL absoluta para preview
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = dirname(dirname($_SERVER['REQUEST_URI']));
    $urlAbsoluta = $protocol . '://' . $host . $basePath . '/' . $urlImagen;
    
    responderJSON(true, [
        'url_relativa' => $urlImagen,
        'url_absoluta' => $urlAbsoluta,
        'nombre_archivo' => $nombreArchivo,
        'tamaño' => $archivo['size'],
        'tipo' => $infoImagen['mime'],
        'dimensiones' => [
            'ancho' => $infoImagen[0],
            'alto' => $infoImagen[1]
        ]
    ], 'Imagen subida correctamente');
} else {
    responderJSON(false, null, 'Error al guardar la imagen en el servidor');
}
?>