# JWT Validator - Guía de Uso

## 📋 Descripción

Sistema mejorado de validación JWT con soporte para:
- Tokens de acceso (access tokens) de corta duración
- Tokens de actualización (refresh tokens) de larga duración
- Blacklist de tokens revocados
- Validación de permisos

---

## 🚀 Uso Básico

### 1. Validar Token JWT

```php
<?php
require_once __DIR__ . '/../middleware/jwt-validator.php';

// Obtener token del header Authorization
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

// Validar token
$secret = 'tu_secreto_jwt'; // Usar desde .env
$validation = JwtValidator::validate($token, $secret);

if (!$validation['valid']) {
    JwtValidator::errorResponse($validation['error']);
}

// Token válido, usar payload
$userId = $validation['payload']['user_id'];
$userRole = $validation['payload']['rol'];
```

### 2. Generar Access Token (15 minutos)

```php
<?php
require_once __DIR__ . '/../middleware/jwt-validator.php';

$payload = [
    'user_id' => 123,
    'email' => 'usuario@ejemplo.com',
    'rol' => 'empleado'
];

$secret = 'tu_secreto_jwt';

// Generar access token (expira en 15 minutos)
$accessToken = JwtValidator::generate($payload, $secret, JwtConfig::ACCESS_TOKEN_EXPIRY);

echo json_encode([
    'access_token' => $accessToken,
    'token_type' => 'Bearer',
    'expires_in' => 900 // 15 minutos
]);
```

### 3. Generar Refresh Token (7 días)

```php
<?php
require_once __DIR__ . '/../middleware/jwt-validator.php';

$payload = [
    'user_id' => 123,
    'email' => 'usuario@ejemplo.com'
];

$secret = 'tu_secreto_jwt';

// Generar refresh token (expira en 7 días)
$refreshToken = JwtValidator::generateRefreshToken($payload, $secret);

echo json_encode([
    'refresh_token' => $refreshToken,
    'expires_in' => 604800 // 7 días
]);
```

---

## 🔒 Blacklist de Tokens

### Agregar Token a Blacklist (Logout)

```php
<?php
require_once __DIR__ . '/../middleware/jwt-validator.php';
require_once __DIR__ . '/../utils/token-blacklist.php';

// Obtener token
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

// Obtener expiry del token
$payload = JwtValidator::getPayload($token);
$expiry = $payload['exp'] ?? null;

// Agregar a blacklist
TokenBlacklist::add($token, $expiry);

echo json_encode([
    'success' => true,
    'message' => 'Sesión cerrada correctamente'
]);
```

### Verificar si Token está en Blacklist

```php
<?php
require_once __DIR__ . '/../utils/token-blacklist.php';

if (TokenBlacklist::isBlacklisted($token)) {
    echo json_encode([
        'success' => false,
        'error' => 'Token revocado'
    ]);
    exit;
}
```

---

## 🔄 Refresh Tokens

### Endpoint de Refresh

```php
<?php
// api/auth/refresh-token.php
require_once __DIR__ . '/../../middleware/jwt-validator.php';
require_once __DIR__ . '/../../utils/token-blacklist.php';

$data = json_decode(file_get_contents('php://input'), true);
$refreshToken = $data['refresh_token'] ?? '';

$secret = getenv('JWT_SECRET');

// Validar refresh token
$validation = JwtValidator::validate($refreshToken, $secret);

if (!$validation['valid']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Refresh token inválido'
    ]);
    exit;
}

// Verificar que sea un refresh token
if (!JwtValidator::isRefreshToken($refreshToken)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Token no es de tipo refresh'
    ]);
    exit;
}

// Generar nuevo access token
$payload = [
    'user_id' => $validation['payload']['user_id'],
    'email' => $validation['payload']['email'],
    'rol' => $validation['payload']['rol']
];

$newAccessToken = JwtValidator::generate($payload, $secret, JwtConfig::ACCESS_TOKEN_EXPIRY);

echo json_encode([
    'success' => true,
    'access_token' => $newAccessToken,
    'token_type' => 'Bearer',
    'expires_in' => 900
]);
```

---

## 🛡️ Validación de Permisos

### Verificar Permiso Específico

```php
<?php
require_once __DIR__ . '/../middleware/jwt-validator.php';

$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

// Verificar si tiene permiso de admin
if (!JwtValidator::hasPermission($token, 'admin')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Acceso denegado'
    ]);
    exit;
}
```

---

## 📊 Estadísticas de Blacklist

### Obtener Estadísticas

```php
<?php
require_once __DIR__ . '/../utils/token-blacklist.php';

$stats = TokenBlacklist::getStats();

echo json_encode([
    'success' => true,
    'stats' => $stats
]);

// Respuesta:
// {
//     "total_tokens": 150,
//     "expired_tokens": 30,
//     "active_tokens": 120,
//     "subdirectories": 256,
//     "last_cleanup": "2026-01-30 14:00:00"
// }
```

### Limpiar Tokens Expirados

```php
<?php
require_once __DIR__ . '/../utils/token-blacklist.php';

$stats = TokenBlacklist::cleanup();

echo json_encode([
    'success' => true,
    'cleanup_stats' => $stats
]);

// Respuesta:
// {
//     "total_checked": 150,
//     "expired_removed": 30,
//     "errors": 0
// }
```

---

## 💡 Ejemplo Completo: Login con Access + Refresh Tokens

```php
<?php
// api/auth/login.php
require_once __DIR__ . '/../../middleware/jwt-validator.php';
require_once __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Validar credenciales (tu lógica existente)
$db = Database::getInstance();
$user = $db->selectOne("SELECT * FROM usuarios_perfil WHERE email = ?", [$email]);

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Credenciales inválidas'
    ]);
    exit;
}

// Generar tokens
$secret = getenv('JWT_SECRET');

$payload = [
    'user_id' => $user['id'],
    'email' => $user['email'],
    'rol' => $user['rol']
];

$accessToken = JwtValidator::generate($payload, $secret, JwtConfig::ACCESS_TOKEN_EXPIRY);
$refreshToken = JwtValidator::generateRefreshToken($payload, $secret);

// Respuesta
echo json_encode([
    'success' => true,
    'access_token' => $accessToken,
    'refresh_token' => $refreshToken,
    'token_type' => 'Bearer',
    'expires_in' => 900,
    'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'rol' => $user['rol']
    ]
]);
```

---

## 💡 Ejemplo Completo: Logout con Blacklist

```php
<?php
// api/auth/logout.php
require_once __DIR__ . '/../../middleware/jwt-validator.php';
require_once __DIR__ . '/../../utils/token-blacklist.php';

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

if (empty($token)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Token requerido'
    ]);
    exit;
}

// Obtener expiry del token
$payload = JwtValidator::getPayload($token);
$expiry = $payload['exp'] ?? null;

// Agregar a blacklist
TokenBlacklist::add($token, $expiry);

// Limpiar sesión
session_start();
session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Sesión cerrada correctamente'
]);
```

---

## 🔧 Configuración

### Tiempos de Expiración

```php
// En middleware/jwt-validator.php
class JwtConfig {
    const ACCESS_TOKEN_EXPIRY = 900;      // 15 minutos
    const REFRESH_TOKEN_EXPIRY = 604800;  // 7 días
    const RENEWAL_GRACE_PERIOD = 300;     // 5 minutos
}
```

### Cambiar Tiempos

```php
// Access token de 30 minutos
$accessToken = JwtValidator::generate($payload, $secret, 1800);

// Refresh token de 30 días
$refreshToken = JwtValidator::generateRefreshToken($payload, $secret, 2592000);
```

---

## ⚠️ Importante

### NO Invasivo

Este sistema es **completamente opcional**:

```php
// ❌ ANTES (sin JWT validator) - SIGUE FUNCIONANDO
$token = $_GET['token'];
// ... tu lógica existente ...

// ✅ DESPUÉS (con JWT validator) - OPCIONAL
if (file_exists(__DIR__ . '/../middleware/jwt-validator.php')) {
    require_once __DIR__ . '/../middleware/jwt-validator.php';
    
    $validation = JwtValidator::validate($token, $secret);
    
    if (!$validation['valid']) {
        JwtValidator::errorResponse($validation['error']);
    }
}

// ... tu lógica existente ...
```

### Retrocompatibilidad

- Si no incluyes el validador, todo funciona igual
- Si lo incluyes pero no lo usas, no afecta nada
- Solo valida cuando lo invocas explícitamente

---

## 🧪 Testing

### Probar Generación de Tokens

```bash
curl -X POST http://localhost/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"test123"}'

# Respuesta esperada:
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 900
}
```

### Probar Validación

```bash
curl -X GET http://localhost/api/profile.php \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."

# Token válido: HTTP 200
# Token inválido: HTTP 401
```

### Probar Refresh

```bash
curl -X POST http://localhost/api/auth/refresh-token.php \
  -H "Content-Type: application/json" \
  -d '{"refresh_token":"eyJ0eXAiOiJKV1QiLCJhbGc..."}'

# Respuesta esperada:
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 900
}
```

### Probar Logout

```bash
curl -X POST http://localhost/api/auth/logout.php \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."

# Respuesta esperada:
{
  "success": true,
  "message": "Sesión cerrada correctamente"
}

# Intentar usar el mismo token:
curl -X GET http://localhost/api/profile.php \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."

# Respuesta esperada: HTTP 401 - Token revocado
```

---

## 📊 Ventajas

✅ **Seguridad mejorada:** Tokens de corta duración  
✅ **Revocación:** Blacklist de tokens  
✅ **Renovación:** Refresh tokens sin re-login  
✅ **Compatible:** Funciona en Hostinger sin Redis  
✅ **Performance:** Subdirectorios para mejor velocidad  
✅ **Limpieza automática:** Tokens expirados se eliminan

---

**Versión:** 1.0.0  
**Fecha:** 30 de enero de 2026  
**Autor:** Claut Intranet Security Team
