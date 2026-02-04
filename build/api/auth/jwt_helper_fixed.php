<?php
// jwt_helper.php - Funciones mejoradas para manejo de JWT con validación de patrones

function generateJWT($payload, $secretKey = null, $expirationTime = 3600) {
    try {
        if ($secretKey === null) {
            $secretKey = getJWTSecret();
        }
        
        // Validar payload
        if (!is_array($payload)) {
            throw new Exception('Payload debe ser un array');
        }
        
        // Limpiar payload de caracteres problemáticos
        $cleanPayload = [];
        foreach ($payload as $key => $value) {
            $cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            if (is_string($value)) {
                // Para strings, limpiar caracteres especiales pero mantener básicos
                $cleanPayload[$cleanKey] = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            } else {
                $cleanPayload[$cleanKey] = $value;
            }
        }
        
        // Header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        if ($header === false) {
            throw new Exception('Error encoding header');
        }
        
        // Payload con tiempo de expiración
        $cleanPayload['iat'] = time();
        $cleanPayload['exp'] = time() + $expirationTime;
        $payloadJson = json_encode($cleanPayload);
        
        if ($payloadJson === false) {
            throw new Exception('Error encoding payload');
        }
        
        // Encode de manera segura
        $base64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64Payload = rtrim(strtr(base64_encode($payloadJson), '+/', '-_'), '=');
        
        // Signature
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secretKey, true);
        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        
        $token = $base64Header . "." . $base64Payload . "." . $base64Signature;
        
        // Validar el token generado
        if (!preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $token)) {
            throw new Exception('Token generated with invalid pattern');
        }
        
        return $token;
        
    } catch (Exception $e) {
        error_log("JWT Generation Error: " . $e->getMessage());
        return false;
    }
}

function verifyJWT($token, $secretKey = null) {
    try {
        if ($secretKey === null) {
            $secretKey = getJWTSecret();
        }
        
        // Validar formato básico del token
        if (!is_string($token) || empty($token)) {
            return false;
        }
        
        // Verificar patrón del token antes de procesarlo
        if (!preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $token)) {
            error_log("JWT Verify Error: Invalid token pattern");
            return false;
        }
        
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            return false;
        }
        
        // Decode de manera segura
        $header = base64_decode(strtr($tokenParts[0], '-_', '+/'));
        $payload = base64_decode(strtr($tokenParts[1], '-_', '+/'));
        $signatureProvided = $tokenParts[2];
        
        if ($header === false || $payload === false) {
            return false;
        }
        
        // Verificar que el JSON es válido
        $headerArray = json_decode($header, true);
        $payloadArray = json_decode($payload, true);
        
        if ($headerArray === null || $payloadArray === null) {
            return false;
        }
        
        // Verificar signature
        $base64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64Payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secretKey, true);
        $base64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        
        if (!hash_equals($base64Signature, $signatureProvided)) {
            return false;
        }
        
        // Verificar expiración
        if (isset($payloadArray['exp']) && $payloadArray['exp'] < time()) {
            return false;
        }
        
        return $payloadArray;
        
    } catch (Exception $e) {
        error_log("JWT Verify Error: " . $e->getMessage());
        return false;
    }
}

function getJWTSecret() {
    // Clave más segura para producción
    $secret = 'claut_jwt_secret_key_2024_muy_segura_cambiar_en_produccion';
    
    // Validar que la clave no tenga caracteres problemáticos
    return preg_replace('/[^\w\-@!#$%^&*()]/', '', $secret);
}

function requireAuth() {
    try {
        $headers = getallheaders();
        $token = null;
        
        // Buscar token de múltiples fuentes con validación de patrón
        if ($headers && isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            // Usar patrón más flexible para extraer el token
            if (preg_match('/^Bearer\s+([a-zA-Z0-9_.-]+)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        // Fallback: buscar en cookies
        if (!$token && isset($_COOKIE['clúster_token'])) {
            $cookieToken = $_COOKIE['clúster_token'];
            if (preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $cookieToken)) {
                $token = $cookieToken;
            }
        }
        
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Token requerido',
                'error_code' => 'NO_TOKEN'
            ]);
            exit;
        }
        
        $decoded = verifyJWT($token);
        if (!$decoded) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Token inválido o expirado',
                'error_code' => 'INVALID_TOKEN'
            ]);
            exit;
        }
        
        return $decoded;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error interno de autenticación',
            'error_code' => 'AUTH_ERROR'
        ]);
        exit;
    }
}

// Función para validar token sin lanzar excepciones
function validateJWT($token) {
    try {
        if (!preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+\.[a-zA-Z0-9_-]+$/', $token)) {
            return ['valid' => false, 'reason' => 'invalid_pattern'];
        }
        
        $decoded = verifyJWT($token);
        if (!$decoded) {
            return ['valid' => false, 'reason' => 'verification_failed'];
        }
        
        return ['valid' => true, 'payload' => $decoded];
        
    } catch (Exception $e) {
        return ['valid' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
    }
}

// Función para limpiar datos antes de crear JWT
function sanitizeJWTData($data) {
    $clean = [];
    
    foreach ($data as $key => $value) {
        // Limpiar clave
        $cleanKey = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
        
        if (is_string($value)) {
            // Para strings, mantener solo caracteres seguros
            $clean[$cleanKey] = preg_replace('/[^\p{L}\p{N}\s@._-]/u', '', $value);
        } elseif (is_numeric($value)) {
            $clean[$cleanKey] = $value;
        } elseif (is_bool($value)) {
            $clean[$cleanKey] = $value;
        }
        // Ignorar otros tipos de datos
    }
    
    return $clean;
}

?>