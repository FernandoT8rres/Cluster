# 📚 Guía de Uso: Rate Limiter

## 🎯 Uso Básico

### 1. Proteger Endpoint de Login

```php
<?php
// En api/auth/login.php

require_once '../../middleware/rate-limiter.php';

// Crear instancia del rate limiter
$rateLimiter = new RateLimiter();

// Obtener identificador del cliente (IP)
$identifier = getRateLimitIdentifier();

// Proteger endpoint (5 intentos / 5 minutos)
$rateLimiter->protect(
    $identifier,
    RateLimitConfig::LOGIN['max'],
    RateLimitConfig::LOGIN['window'],
    RateLimitConfig::LOGIN['action']
);

// Si llega aquí, el rate limit no se ha excedido
// Continuar con la lógica de login...
?>
```

### 2. Proteger Registro de Usuarios

```php
<?php
// En api/auth/register.php

require_once '../../middleware/rate-limiter.php';

$rateLimiter = new RateLimiter();
$identifier = getRateLimitIdentifier();

// 3 registros por hora por IP
$rateLimiter->protect(
    $identifier,
    RateLimitConfig::REGISTER['max'],
    RateLimitConfig::REGISTER['window'],
    RateLimitConfig::REGISTER['action']
);

// Continuar con registro...
?>
```

### 3. Proteger APIs Públicas

```php
<?php
// En cualquier API pública

require_once '../middleware/rate-limiter.php';

$rateLimiter = new RateLimiter();
$identifier = getRateLimitIdentifier();

// 100 requests por minuto
$rateLimiter->protect(
    $identifier,
    RateLimitConfig::API_PUBLIC['max'],
    RateLimitConfig::API_PUBLIC['window'],
    RateLimitConfig::API_PUBLIC['action']
);

// Continuar con la API...
?>
```

### 4. Verificar Estado Sin Bloquear

```php
<?php
// Verificar si el usuario puede hacer una acción sin registrar intento

$rateLimiter = new RateLimiter();
$identifier = getRateLimitIdentifier();

$status = $rateLimiter->getStatus(
    $identifier,
    5,
    300,
    'login'
);

if (!$status['allowed']) {
    // Mostrar mensaje al usuario
    echo "Debes esperar {$status['retry_after']} segundos antes de intentar de nuevo.";
} else {
    echo "Tienes {$status['remaining']} intentos restantes.";
}
?>
```

### 5. Resetear Intentos (Después de Login Exitoso)

```php
<?php
// Después de un login exitoso, resetear el contador

$rateLimiter = new RateLimiter();
$identifier = getRateLimitIdentifier();

// Resetear intentos de login
$rateLimiter->reset($identifier, 'login');

// El usuario puede intentar de nuevo sin restricciones
?>
```

## 🔧 Configuraciones Predefinidas

```php
RateLimitConfig::LOGIN           // 5 intentos / 5 minutos
RateLimitConfig::REGISTER        // 3 registros / hora
RateLimitConfig::PASSWORD_RESET  // 3 intentos / hora
RateLimitConfig::API_PUBLIC      // 100 requests / minuto
RateLimitConfig::API_PRIVATE     // 300 requests / minuto
RateLimitConfig::FILE_UPLOAD     // 10 uploads / hora
RateLimitConfig::CONTACT_FORM    // 5 envíos / hora
```

## 📊 Estadísticas y Mantenimiento

```php
<?php
// Obtener estadísticas del sistema

$rateLimiter = new RateLimiter();
$stats = $rateLimiter->getStats();

echo "Archivos totales: {$stats['total_files']}\n";
echo "Tamaño total: {$stats['total_size_mb']} MB\n";

// Limpiar archivos antiguos manualmente
$deleted = $rateLimiter->cleanup();
echo "Archivos eliminados: $deleted\n";
?>
```

## ⚠️ Respuesta de Rate Limit Excedido

Cuando se excede el límite, el servidor responde con:

**HTTP Status:** 429 Too Many Requests

**Headers:**
```
Retry-After: 180
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1738187400
```

**Body:**
```json
{
  "success": false,
  "error": "Too many requests",
  "message": "Has excedido el límite de intentos. Por favor, intenta de nuevo en 180 segundos.",
  "retry_after": 180,
  "reset_at": 1738187400
}
```

## 🎨 Manejo en Frontend

```javascript
// Ejemplo de manejo en JavaScript

async function login(email, password) {
    try {
        const response = await fetch('/api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });
        
        if (response.status === 429) {
            const data = await response.json();
            const minutes = Math.ceil(data.retry_after / 60);
            alert(`Demasiados intentos. Intenta de nuevo en ${minutes} minutos.`);
            return;
        }
        
        // Continuar con login normal...
        const data = await response.json();
        // ...
        
    } catch (error) {
        console.error('Error:', error);
    }
}
```

## 📁 Estructura de Almacenamiento

```
storage/rate-limit/
├── .htaccess                    # Protección del directorio
├── .last_cleanup                # Timestamp de última limpieza
├── ab/                          # Subdirectorio (primeros 2 chars del hash)
│   ├── ab123...def.json        # Archivo de intentos
│   └── ab456...ghi.json
├── cd/
│   └── cd789...jkl.json
└── ...
```

## 🔒 Seguridad

1. **Directorio protegido:** El directorio `storage/rate-limit/` tiene un `.htaccess` que deniega acceso web
2. **Hashes seguros:** Los identificadores se hashean con SHA-256
3. **Limpieza automática:** Los archivos antiguos se eliminan automáticamente cada hora
4. **Subdirectorios:** Los archivos se distribuyen en subdirectorios para mejor rendimiento

## 🚀 Deployment en Hostinger

1. **Subir archivo:**
   - Subir `middleware/rate-limiter.php` vía FileZilla

2. **Crear directorio de almacenamiento:**
   ```bash
   mkdir -p storage/rate-limit
   chmod 755 storage/rate-limit
   ```

3. **Verificar permisos:**
   - El servidor debe poder escribir en `storage/rate-limit/`

4. **Probar:**
   - Intentar login 6 veces con contraseña incorrecta
   - Debería bloquear después del 5to intento

## 📝 Notas Importantes

- ✅ **Compatible con Hostinger:** No requiere Redis ni extensiones especiales
- ✅ **Sin alterar funcionalidad:** Solo agrega protección, no cambia lógica existente
- ✅ **Limpieza automática:** Los archivos antiguos se eliminan automáticamente
- ✅ **Bajo overhead:** Operaciones de archivo muy rápidas
- ⚠️ **Permisos:** Asegúrate de que el directorio `storage/` tenga permisos de escritura
