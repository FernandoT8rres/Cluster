# Utilidades de Seguridad - Claut Intranet

Este directorio contiene las utilidades de seguridad implementadas en la Fase 1 de mejoras.

## 📁 Archivos Creados

### Middleware
- **`middleware/csrf-protection.php`** - Protección contra CSRF
- **`middleware/security-headers.php`** - Headers HTTP de seguridad

### Utilidades
- **`utils/input-validator.php`** - Validación de entrada de datos
- **`utils/file-upload-validator.php`** - Validación segura de archivos subidos
- **`utils/output-sanitizer.php`** - Sanitización de salida (prevención XSS)
- **`utils/security-logger.php`** - Registro de eventos de seguridad

---

## 🔒 Uso de CSRF Protection

### En APIs que modifican datos (POST/PUT/DELETE):

```php
<?php
require_once __DIR__ . '/../middleware/csrf-protection.php';

// Opción 1: Protección automática
CSRFProtection::protect(); // Valida automáticamente en POST/PUT/DELETE

// Opción 2: Validación manual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::requireValidToken(); // Lanza excepción si es inválido
    // Continuar con el procesamiento...
}
?>
```

### En formularios HTML:

```php
<form method="POST" action="/api/endpoint.php">
    <?php echo CSRFProtection::getHiddenField(); ?>
    <!-- Otros campos del formulario -->
    <button type="submit">Enviar</button>
</form>
```

### En AJAX (JavaScript):

```html
<!-- En el <head> del HTML -->
<?php echo CSRFProtection::getMetaTag(); ?>

<script>
// Obtener token del meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Enviar con fetch
fetch('/api/endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(data)
});

// O con FormData
const formData = new FormData();
formData.append('csrf_token', csrfToken);
formData.append('data', value);
</script>
```

---

## ✅ Uso de Input Validator

```php
<?php
require_once __DIR__ . '/../utils/input-validator.php';

try {
    // Validar entero
    $id = InputValidator::validateInt($_GET['id'] ?? null, 1, 1000);
    
    // Validar string
    $nombre = InputValidator::validateString($_POST['nombre'] ?? '', 100);
    
    // Validar email
    $email = InputValidator::validateEmail($_POST['email'] ?? '');
    
    // Validar enum
    $estado = InputValidator::validateEnum($_POST['estado'] ?? '', ['activo', 'inactivo']);
    
    // Validar fecha
    $fecha = InputValidator::validateDate($_POST['fecha'] ?? '', 'Y-m-d');
    
    // Validar array de IDs
    $ids = InputValidator::validateIntArray($_POST['ids'] ?? [], 1);
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
```

---

## 📤 Uso de File Upload Validator

```php
<?php
require_once __DIR__ . '/../utils/file-upload-validator.php';
require_once __DIR__ . '/../utils/security-logger.php';

try {
    $validator = new FileUploadValidator();
    
    // Validar imagen
    $fileInfo = $validator->validateImage($_FILES['image'], 5242880); // 5MB max
    
    // O validar documento
    // $fileInfo = $validator->validateDocument($_FILES['document'], 10485760); // 10MB max
    
    // Crear directorio seguro
    $uploadDir = __DIR__ . '/../uploads/images/';
    FileUploadValidator::createSecureUploadDirectory($uploadDir);
    
    // Generar nombre seguro
    $filename = FileUploadValidator::generateSafeFilename($fileInfo['extension'], 'prefix');
    $filepath = $uploadDir . $filename;
    
    // Mover archivo de forma segura
    FileUploadValidator::moveUploadedFileSafely($fileInfo['tmp_name'], $filepath);
    
    // Registrar éxito
    SecurityLogger::log('file_upload_success', 'INFO', [
        'filename' => $filename,
        'size' => $fileInfo['size']
    ]);
    
} catch (Exception $e) {
    SecurityLogger::logSuspiciousFileUpload($_FILES['image']['name'] ?? 'unknown', $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
```

---

## 🛡️ Uso de Output Sanitizer

```php
<?php
require_once __DIR__ . '/../utils/output-sanitizer.php';

// En templates HTML
echo "<h1>" . OutputSanitizer::html($userInput) . "</h1>";
echo "<a href='" . OutputSanitizer::url($userUrl) . "'>Link</a>";
echo "<div data-value='" . OutputSanitizer::attr($userAttr) . "'></div>";

// En JavaScript
echo "<script>var data = " . OutputSanitizer::js($userData) . ";</script>";

// HTML con tags seguros
echo OutputSanitizer::safeHtml($userHtml, ['p', 'br', 'strong', 'em']);
?>
```

---

## 📝 Uso de Security Logger

```php
<?php
require_once __DIR__ . '/../utils/security-logger.php';

// Métodos de conveniencia
SecurityLogger::logFailedLogin($email, 'Invalid password');
SecurityLogger::logSuccessfulLogin($userId, $email);
SecurityLogger::logUnauthorizedAccess('/admin/users');
SecurityLogger::logCSRFViolation();
SecurityLogger::logSuspiciousFileUpload($filename, $reason);
SecurityLogger::logRateLimitExceeded($ip);

// Método genérico
SecurityLogger::log('custom_event', 'WARNING', [
    'key' => 'value',
    'data' => $data
]);
?>
```

**Logs se guardan en:** `logs/security/security.log`

---

## 🔐 Uso de Security Headers

```php
<?php
// Incluir al inicio de cada archivo PHP público
require_once __DIR__ . '/../middleware/security-headers.php';

// Los headers se configuran automáticamente:
// - X-Frame-Options: DENY
// - X-Content-Type-Options: nosniff
// - X-XSS-Protection: 1; mode=block
// - Content-Security-Policy
// - HSTS (si HTTPS está activo)
?>
```

---

## 📋 Checklist de Implementación

### Para cada API que modifica datos:

- [ ] Agregar `require_once 'middleware/csrf-protection.php'`
- [ ] Llamar `CSRFProtection::protect()` al inicio
- [ ] Validar todos los parámetros con `InputValidator`
- [ ] Sanitizar salida con `OutputSanitizer`
- [ ] Registrar eventos importantes con `SecurityLogger`

### Para APIs de subida de archivos:

- [ ] Usar `FileUploadValidator` para validar archivos
- [ ] Crear directorios con `createSecureUploadDirectory()`
- [ ] Generar nombres con `generateSafeFilename()`
- [ ] Mover archivos con `moveUploadedFileSafely()`
- [ ] Registrar subidas con `SecurityLogger`

### Para todas las páginas públicas:

- [ ] Incluir `middleware/security-headers.php`
- [ ] Generar token CSRF con `CSRFProtection::generateToken()`
- [ ] Incluir meta tag o campo hidden según corresponda

---

## ⚠️ Notas Importantes

1. **CSRF Tokens**: Se regeneran automáticamente cada 5 minutos para mayor seguridad
2. **Logs**: Se rotan automáticamente cuando superan 10MB
3. **Alertas**: Los eventos CRITICAL envían email a admin@claut.com
4. **Permisos**: Los archivos subidos se guardan con permisos 0640
5. **Directorios**: Los directorios de upload se crean con permisos 0750

---

## 🔄 Próximos Pasos (Fase 2)

- Implementar Rate Limiting
- Configurar sesiones seguras
- Migrar credenciales a .env
- Implementar más validaciones en APIs existentes

---

**Fecha de creación:** 2026-01-29  
**Versión:** 1.0  
**Autor:** Sistema de Seguridad Claut
