---
description: Implementación de Seguridad y Variables de Entorno
---

Guía rápida para aplicar las mejores prácticas de seguridad en el proyecto Claut Intranet:

1. **No hay hardcoding:** Cualquier credencial (JWT, base de datos) va en `/build/.env`.
2. **Llamadas a la DB:** 
   - No usar contraseñas por defecto en código abierto (incluso si está comentado).
   - Enlazar siempre mediante `EnvLoader` que se llama dentro de `Database::getInstance()`.
3. **Implementar CSRF en Formularios (HTML/PHP):**
   ```php
   require_once 'middleware/csrf-protection.php';
   CSRFProtection::protect();
   ```
4. **Logs Críticos:** Cuando agregues nuevas operaciones sensibles (logins borrados, intentos fallidos), usa:
   ```php
   require_once 'utils/security-logger.php';
   SecurityLogger::logSuspiciousFileUpload(...);
   ```
