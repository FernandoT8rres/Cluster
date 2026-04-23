# 📄 Módulo de Documentos — Documentación Técnica

> **Última actualización:** 2026-04-23  
> **Archivos involucrados:**  
> - `build/api/documentos.php`  
> - `build/demo_documentos.html`

---

## 🏗️ Arquitectura del Módulo

```
demo_documentos.html  ──POST──▶  api/documentos.php  ──▶  DB: documentos
                       ──POST (con _method=PUT)──▶         (goto handle_put)
                       ──DELETE─▶
                       ──GET────▶
```

---

## 🗄️ Estructura de la Tabla `documentos`

| Columna         | Tipo                                          | Notas                              |
|-----------------|-----------------------------------------------|------------------------------------|
| `id`            | INT AUTO_INCREMENT PK                         |                                    |
| `titulo`        | VARCHAR(255) NOT NULL                         |                                    |
| `descripcion`   | TEXT NULL                                     |                                    |
| `archivo_nombre`| VARCHAR(255) NOT NULL                         | Nombre original del archivo        |
| `archivo_ruta`  | VARCHAR(500) NOT NULL                         | Ruta relativa desde `/api/`        |
| `tipo_archivo`  | VARCHAR(10) NOT NULL                          | Extensión en mayúsculas (PDF, DOC) |
| `tamaño_archivo`| BIGINT DEFAULT 0                              | En bytes                           |
| `categoria`     | VARCHAR(100) DEFAULT 'general'                |                                    |
| `subido_por`    | INT NULL                                      | FK a `usuarios.id`                 |
| `fecha_subida`  | DATETIME DEFAULT NOW()                        |                                    |
| `visibilidad`   | ENUM('publico','privado','restringido')        | Default: `publico`                 |
| `descargas`     | INT DEFAULT 0                                 |                                    |

> **Nota:** `archivo_ruta` se almacena con la ruta relativa desde el directorio `api/`, ejemplo: `../uploads/documentos/abc123_1768587384.pdf`.

---

## 🔌 API Reference — `documentos.php`

### GET — Listar / obtener documento
```
GET /api/documentos.php
GET /api/documentos.php?id={id}
GET /api/documentos.php?categoria=politicas&visibilidad=all&limit=20&search=texto
GET /api/documentos.php?action=file&path={nombre_archivo}
GET /api/documentos.php?action=download&path={nombre_archivo}
```

### POST — Crear documento
```http
POST /api/documentos.php
Content-Type: multipart/form-data

titulo      = "Título"          (requerido, mínimo 1 carácter)
descripcion = "Descripción"     (opcional)
categoria   = "politicas"       (opcional, default: general)
visibilidad = "publico"         (publico | privado | restringido)
subido_por  = 1                 (opcional, ID del usuario)
archivo     = [FILE]            (requerido en creación)
```

### POST con `_method=PUT` — Actualizar documento
```http
POST /api/documentos.php
Content-Type: multipart/form-data

_method     = "PUT"             (activa el goto handle_put)
id          = 4                 (requerido)
titulo      = "Nuevo título"
descripcion = "..."
categoria   = "manuales"
visibilidad = "privado"
archivo     = [FILE]            (opcional — reemplaza el archivo existente)
```

### DELETE — Eliminar documento
```
DELETE /api/documentos.php?id=4
```
Elimina el registro de la BD y el archivo físico del servidor.

---

## 🐛 Bug Crítico Corregido — 2026-04-23

### Descripción
Al intentar **crear** un documento, la respuesta del servidor era HTTP 400 y el frontend mostraba `"Error al crear el documento"` sin ningún detalle útil.

### Causa raíz 1 — `ApiValidator` rechazaba el título
El `case 'POST'` llamaba a `ApiValidator::validateAndSanitize()` con la regla:
```php
// ❌ Código incorrecto
'titulo' => 'required|string|min:3|max:255'
```
Cualquier título de **menos de 3 caracteres** retornaba HTTP 400.

### Causa raíz 2 — Formato de error incompatible
`ApiValidator::errorResponse()` devuelve `{ error: "...", validation_errors: {...} }`,  
pero el frontend espera `{ message: "..." }`:
```javascript
// Frontend buscaba result.message, pero el validador enviaba result.error
showError(result.message || 'Error al crear el documento'); // Siempre fallback
```
Resultado: el usuario nunca veía el error real, siempre el genérico.

### Causa raíz 3 — `handleSaveClick` con falso error en creación
```javascript
// ❌ Código incorrecto
if (!editingId) {
    console.error('EditingId está perdido!'); // Se disparaba en CREACIÓN también
}
```
`editingId = null` es el estado **correcto** al crear un documento nuevo. El error en consola era un falso positivo que confundía el diagnóstico.

### Solución aplicada

| Componente | Cambio |
|---|---|
| `documentos.php` `case 'POST'` | Eliminado `ApiValidator`. Validación directa con `empty($titulo)` y whitelist de visibilidades. Errores ahora usan el campo `message`. |
| `documentos.php` `case 'POST'` | Errores de `$_FILES` ahora muestran mensajes legibles según el código PHP de error de upload. |
| `demo_documentos.html` `handleSaveClick` | Solo intenta recuperar `editingId` del campo hidden **si el modal está en modo "Editar"**. No lanza `console.error` durante creación. |

### Flujo corregido (Creación)
```
showCreateModal() → editingId = null ✓
  → usuario llena formulario
  → handleSaveClick()
      → isEditMode = false (título = "Crear Documento")
      → NO intenta recuperar editingId
  → saveDocument()
      → editingId = null → rama CREAR
      → POST /api/documentos.php con FormData
  → PHP: clean($_POST), valida, processFileUpload()
      → INSERT INTO documentos ...
      → { success: true, id: N, message: "Documento subido exitosamente" }
```

---

## 📁 Gestión de Archivos

- **Directorio de upload:** `build/uploads/documentos/`
- **Nomenclatura:** `{uniqid()}_{timestamp}.{ext}`
- **Ruta en BD:** relativa desde `api/` → `../uploads/documentos/{filename}`
- **Tipos MIME permitidos:** PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, TXT, JPG, PNG, GIF
- **Tamaño máximo:** 50 MB

---

## ⚠️ Reglas para futuras modificaciones

- **Nunca usar `ApiValidator` para validaciones con archivo**: el validador no maneja `$_FILES` y puede retornar HTTP 400 con formato de respuesta incompatible con el frontend.
- **El campo de error en respuestas debe ser `message`**, no `error`, para compatibilidad con todos los módulos.
- **`editingId = null` es correcto en creación**: no tratar como error de estado.

---

## 🔗 Referencias

- Tabla: `u695712029_claut_intranet.documentos`
- Frontend: `build/demo_documentos.html`
- API: `build/api/documentos.php`
- Middleware (no usado en POST): `build/middleware/api-validator.php`
- Directorio uploads: `build/uploads/documentos/`
