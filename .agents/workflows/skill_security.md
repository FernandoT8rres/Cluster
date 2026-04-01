---
description: Skill reutilizable — Checklist de seguridad para nuevas implementaciones en Claut Intranet
---

# Skill: Seguridad — Checklist para Nuevas Implementaciones

Aplicar esta guía cada vez que se cree o modifique un endpoint API, formulario o subida de archivo.

---

## Al Crear un Nuevo Endpoint PHP

```php
<?php
// 1. Headers de seguridad (SIEMPRE primero)
require_once __DIR__ . '/../middleware/security-headers.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Restringir a dominio en producción

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// 2. Conexión BD — singleton, nunca PDO directo
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

// 3. Validar inputs — nunca confiar en $_POST/$_GET
require_once __DIR__ . '/../utils/input-validator.php';
try {
    $id    = InputValidator::validateInt($_GET['id'] ?? 0, 1, 999999);
    $email = InputValidator::validateEmail($_POST['email'] ?? '');
    $nombre = InputValidator::validateString($_POST['nombre'] ?? '', 255);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// 4. Respuesta estándar
function sendResponse(bool $success, string $message, $data = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data],
        JSON_UNESCAPED_UNICODE);
    exit;
}
```

---

## Reglas de Credenciales

| ❌ Nunca | ✅ Siempre |
|---------|-----------|
| Hardcodear DB user/pass en `.php` | Usar `$_ENV['DB_USER']` via `EnvLoader` |
| Hardcodear JWT secret | Usar `$_ENV['JWT_SECRET']` |
| Subir `.env` a Git | `.env` en `.gitignore` — solo `.env.example` |
| Credenciales en `.md` o documentación | Usar placeholders: `your_db_password` |

---

## Al Aceptar Subida de Archivos

```php
require_once __DIR__ . '/../utils/file-upload-validator.php';
require_once __DIR__ . '/../utils/security-logger.php';

$validator = new FileUploadValidator();
try {
    $fileInfo = $validator->validateImage($_FILES['image'], 5 * 1024 * 1024); // 5MB
} catch (Exception $e) {
    SecurityLogger::logSuspiciousFileUpload($_FILES['image']['name'], $e->getMessage());
    sendResponse(false, $e->getMessage());
}

$uploadDir = __DIR__ . '/../uploads/modulo/';
FileUploadValidator::createSecureUploadDirectory($uploadDir);
$fileName = FileUploadValidator::generateSafeFilename($fileInfo['extension'], 'prefijo');
FileUploadValidator::moveUploadedFileSafely($fileInfo['tmp_name'], $uploadDir . $fileName);
```

**Las 9 capas de validación de `FileUploadValidator`:**
1. Errores de upload PHP
2. Tamaño máximo
3. Extensión permitida (whitelist)
4. MIME type real via `finfo`
5. Coincidencia MIME/extensión
6. Contenido de imagen válido (`getimagesize`)
7. Patrones maliciosos (12 patrones PHP/JS/etc)
8. Null bytes en nombre
9. Nombre de archivo seguro (sin path traversal)

---

## Al Registrar Eventos de Seguridad

```php
require_once __DIR__ . '/../utils/security-logger.php';

// Eventos predefinidos:
SecurityLogger::logFailedLogin($email, $_SERVER['REMOTE_ADDR']);
SecurityLogger::logUnauthorizedAccess($userId, 'recurso');
SecurityLogger::logCSRFViolation($token);
SecurityLogger::logSuspiciousFileUpload($filename, $reason);

// Evento personalizado:
SecurityLogger::log('nombre_evento', 'INFO|WARNING|ERROR|CRITICAL', [
    'clave' => 'valor'
]);
```

---

## Checklist de Revisión de Seguridad

- [ ] ¿El endpoint requiere autenticación? (sesión o JWT)
- [ ] ¿Se validan TODOS los inputs antes de usarlos?
- [ ] ¿Ninguna credencial hardcodeada en el código?
- [ ] ¿Los campos `DATE` envían `null` cuando están vacíos (no `""`)?
- [ ] ¿Los errores retornan JSON sin stack trace ni datos internos?
- [ ] ¿Los archivos subidos pasan por `FileUploadValidator`?
- [ ] ¿Los eventos críticos se registran con `SecurityLogger`?
- [ ] ¿Soft delete en lugar de DELETE físico?
- [ ] ¿CORS restringido al dominio correcto en producción?
- [ ] ¿Sin `console.log` con datos sensibles en JS de producción?

---

## Vulnerabilidades Conocidas Pendientes (No Introducir Nuevas)

| Archivo | Problema | Prioridad |
|---------|---------|-----------|
| `api/auth/login-compatible.php` | Expone `$_SESSION` completo en respuesta JSON | ALTA |
| `middleware/security-headers.php` | CSP con `unsafe-inline`/`unsafe-eval` | ALTA |
| `demo_*.html` | Sin autenticación en producción | ALTA |
| `.htaccess` | CORS abierto a `*` | MEDIA |
| `config/database.php` | Usuarios de prueba hardcodeados en `insertSampleData()` | MEDIA |
