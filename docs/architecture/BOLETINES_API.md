# 📰 Módulo de Boletines — Documentación Técnica

> **Última actualización:** 2026-04-23  
> **Archivos involucrados:**  
> - `build/api/boletines_simple.php`  
> - `build/demo_boletines.html`

---

## 🏗️ Arquitectura del Módulo

```
demo_boletines.html  ──POST──▶  api/boletines_simple.php  ──▶  DB: boletines
                     ──DELETE─▶
                     ──GET────▶
```

El módulo expone un único endpoint REST (`boletines_simple.php`) que maneja **GET / POST / DELETE**.  
El método **PUT fue eliminado** (ver bug crítico abajo).

---

## 🗄️ Estructura de la Tabla `boletines`

| Columna            | Tipo                                     | Notas                          |
|--------------------|------------------------------------------|--------------------------------|
| `id`               | INT AUTO_INCREMENT PK                    |                                |
| `titulo`           | VARCHAR(255) NOT NULL                    |                                |
| `contenido`        | TEXT NOT NULL                            |                                |
| `estado`           | ENUM('borrador','publicado','archivado') | Default: `borrador`            |
| `fecha_creacion`   | DATETIME                                 | Default: NOW()                 |
| `fecha_publicacion`| DATETIME NULL                            | Se llena auto si estado=publicado |
| `archivo_adjunto`  | VARCHAR(255) NULL                        | Nombre de archivo en `/uploads/boletines/` |
| `visualizaciones`  | INT DEFAULT 0                            |                                |
| `autor_id`         | INT NULL                                 |                                |

---

## 🔌 API Reference — `boletines_simple.php`

### GET — Listar boletines
```
GET /api/boletines_simple.php
GET /api/boletines_simple.php?id={id}
GET /api/boletines_simple.php?estado=publicado&limit=10&orderBy=fecha_creacion&order=DESC
```
- Sin parámetros: devuelve todos (máx 50)
- Con `?id=N`: devuelve el boletín N e incrementa `visualizaciones`

### POST — Crear boletín (id ausente) / Actualizar (id presente)

```http
POST /api/boletines_simple.php
Content-Type: multipart/form-data

titulo    = "Título del boletín"
contenido = "Contenido completo"
estado    = "publicado" | "borrador" | "archivado"
id        = (opcional) — si está presente, actualiza en lugar de crear
archivo   = (opcional) — file upload
```

> ⚠️ **Importante:** Create y Update comparten el mismo método HTTP (`POST`).  
> La presencia del campo `id > 0` determina la operación.

### DELETE — Eliminar boletín

```http
DELETE /api/boletines_simple.php
Content-Type: application/json

{ "id": 4 }
```
O vía query string: `DELETE /api/boletines_simple.php?id=4`

El handler elimina también el archivo físico en `/uploads/boletines/` si existe.

---

## 🐛 Bug Crítico Corregido — 2026-04-23

### Descripción del problema
Al intentar **editar** un boletín, el modal mostraba:
```
❌ Error al actualizar boletín
ID inválido
```

### Causa raíz

El frontend enviaba `FormData` con `method: 'PUT'`, pero el backend usaba:
```php
// ❌ Código incorrecto (case 'PUT')
parse_str(file_get_contents("php://input"), $_PUT);
$id = intval($_PUT['id'] ?? 0); // Siempre 0 — parse_str no entiende multipart
```

`parse_str()` solo parsea datos `application/x-www-form-urlencoded`.  
`FormData` envía `multipart/form-data` → los campos **nunca llegaban** → `id = 0` → "ID inválido".

**Problema secundario:** `$_FILES` solo se popula en requests `POST`, no en `PUT` → los archivos tampoco se guardaban.

**Problema terciario:** La función `updateStatistics()` referenciaba `document.getElementById('stats-info')` que no existe en el DOM → `TypeError: null is not an object`.

### Solución aplicada

| Componente | Cambio |
|---|---|
| `boletines_simple.php` | `case 'POST'` ahora maneja **create y update**. Detecta la operación por `$_POST['id'] > 0`. Procesa `$_FILES['archivo']` correctamente. |
| `boletines_simple.php` `case 'DELETE'` | Acepta ID tanto por query string como por JSON body. También elimina el archivo físico adjunto. |
| `demo_boletines.html` | `method: 'PUT'` → `method: 'POST'`. El `id` ya se incluía en `FormData` con `formData.append('id', bulletinId)`. |
| `demo_boletines.html` | `document.getElementById('stats-info')` envuelto con null-check `if (statsInfo)`. |

### Flujo corregido (Edición)
```
Usuario abre modal editar
  → showEditModal(bulletin) — llena campos, guarda bulletin.id en input#bulletinId
  → handleBulletinSubmit()
      → FormData con: titulo, contenido, estado, id=5, [archivo opcional]
      → fetch POST /api/boletines_simple.php
  → PHP detecta $_POST['id'] = 5 → rama UPDATE
      → Conserva archivo existente si no llega uno nuevo
      → UPDATE boletines SET ... WHERE id = 5
      → Retorna { success: true, id: 5, data: {...} }
```

---

## 📁 Gestión de Archivos Adjuntos

- **Directorio:** `/uploads/boletines/` (relativo a la raíz del proyecto)
- **Nomenclatura:** `boletin_{timestamp}_{4bytesHex}.{ext}`
- **Extensiones permitidas:** pdf, doc, docx, xls, xlsx, ppt, pptx, txt, jpg, jpeg, png, gif, mp4, mp3, csv
- **Al editar sin subir nuevo archivo:** se conserva el archivo adjunto existente
- **Al eliminar boletín:** se elimina el archivo físico con `unlink()`

---

## ⚠️ Consideraciones de Seguridad

- Los archivos se validan por extensión (whitelist)
- El nombre de archivo se regenera con `time() + random_bytes(4)` para evitar colisiones y path traversal
- El directorio de uploads se crea automáticamente con permisos `0775`
- Rate limiting aplicado vía `middleware/rate-limiter.php`
- Headers CORS restringen origen a `https://intranet.clautmetropolitano.mx`

---

## 🔗 Referencias

- Tabla: `u695712029_claut_intranet.boletines`
- Frontend: `build/demo_boletines.html`
- API: `build/api/boletines_simple.php`
- Config DB: `build/config/database.php`
