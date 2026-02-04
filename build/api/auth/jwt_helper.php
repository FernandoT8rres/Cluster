<?php
// jwt_helper.php - Funciones para manejo de JWT

function generateJWT($payload, $secretKey = null, $expirationTime = 3600) {
    if ($secretKey === null) {
        $secretKey = getJWTSecret();
    }
    
    // Header
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    // Payload con tiempo de expiración
    $payload['iat'] = time();
    $payload['exp'] = time() + $expirationTime;
    $payload = json_encode($payload);
    
    // Encode
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    // Signature
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secretKey, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

function verifyJWT($token, $secretKey = null) {
    if ($secretKey === null) {
        $secretKey = getJWTSecret();
    }
    
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        return false;
    }
    
    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
    $signatureProvided = $tokenParts[2];
    
    // Verificar signature
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secretKey, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if (!hash_equals($base64Signature, $signatureProvided)) {
        return false;
    }
    
    // Verificar expiración
    $payloadArray = json_decode($payload, true);
    if (isset($payloadArray['exp']) && $payloadArray['exp'] < time()) {
        return false;
    }
    
    return $payloadArray;
}

function getJWTSecret() {
    // Cargar variables de entorno
    require_once dirname(dirname(__DIR__)) . '/config/env-loader.php';
    
    try {
        EnvLoader::load();
        return EnvLoader::required('JWT_SECRET');
    } catch (Exception $e) {
        // Fallback a valor por defecto si .env no está disponible
        error_log("⚠️ JWT_SECRET no encontrado en .env, usando valor por defecto");
        return 'claut_jwt_secret_key_2024_muy_segura_cambiar_en_produccion';
    }
}

function requireAuth() {
    $headers = apache_request_headers();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token requerido']);
        exit;
    }
    
    $decoded = verifyJWT($token);
    if (!$decoded) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit;
    }
    
    return $decoded;
}
?>