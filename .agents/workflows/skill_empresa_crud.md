---
description: Skill reutilizable — CRUD completo del módulo de Empresas/Socios
---

# Skill: Empresa CRUD (Módulo de Empresas)

Guía de referencia rápida para trabajar con el módulo de empresas sin introducir regresiones.

---

## Archivos Involucrados

| Archivo | Rol |
|---------|-----|
| `build/api/empresas-simple.php` | API activa — todas las operaciones CRUD |
| `build/js/admin-empresas.js` | Clase `AdminEmpresasManager` — frontend del panel admin |
| `build/demo_empresas.html` | UI del panel admin de empresas |
| `build/api/upload-image.php` | Subida de logos (9 capas de seguridad) |

---

## Flujo de Datos

```
[demo_empresas.html]
  → FormData (con todos los campos del modal #formEmpresa)
    → POST build/api/empresas-simple.php?action=crear|actualizar|eliminar|listar|obtener
      → empresas_convenio (tabla MySQL)
```

---

## Reglas Críticas — No Romper

1. **Todos los `<input>`, `<select>`, `<textarea>` del modal DEBEN tener `name=""`** idéntico al `id`. Sin `name`, `new FormData(form)` ignora el campo.

2. **FormData siempre hace `append` sin condición**:
   ```javascript
   campos.forEach(campo => {
       const el = document.getElementById(campo);
       if (el) formData.append(campo, el.value.trim()); // sin if(valor)
   });
   ```

3. **Campos `DATE` a MySQL → siempre `null` cuando vacíos**:
   ```php
   $fecha = ($raw !== '' && strtotime($raw)) ? $raw : null;
   ```

4. **`admin_usuario_id` puede ser `"0"` (string)** — usar `!empty()`, no ternario:
   ```php
   $admin_id = !empty($_POST['admin_usuario_id']) ? intval($_POST['admin_usuario_id']) : null;
   ```

5. **Soft delete siempre** — nunca `DELETE` físico:
   ```php
   UPDATE empresas_convenio SET activo = 0, estado = 'inactiva' WHERE id = ?
   ```

6. **SELECT debe incluir siempre**: `e.estado`, `e.fecha_convenio`, `e.condiciones` — sin ellos el modal de edición muestra vacíos.

7. **Mapeo `contacto_persona` → `contacto_nombre`**: El campo HTML tiene `id="contacto_persona"` pero la columna BD es `contacto_nombre`. PHP: `$contacto_nombre = trim($_POST['contacto_persona'] ?? '')`.

---

## Checklist al Agregar un Campo Nuevo al Formulario

- [ ] Añadir `<input id="campo" name="campo">` en `demo_empresas.html`
- [ ] Agregar `'campo'` al array `campos` en `guardarEmpresa()` de `admin-empresas.js`
- [ ] Agregar `document.getElementById('campo').value = empresa.campo || ''` en `llenarFormulario()`
- [ ] Agregar `$campo = trim($_POST['campo'] ?? '')` en `crearEmpresa()` y `actualizarEmpresa()`
- [ ] Incluir `campo = ?` en el `INSERT` y `UPDATE` SQL de `empresas-simple.php`
- [ ] Incluir `e.campo` en el `SELECT` de `listarEmpresas()` y `obtenerEmpresa()`
- [ ] Incluir `'campo' => $empresa['campo']` en el array de respuesta formateada
- [ ] Si es columna nueva en BD: añadir al script de migración SQL (idempotente)

---

## Estructura de Respuesta de la API

```json
{
  "success": true,
  "message": "Empresa creada exitosamente",
  "data": {
    "empresas": [
      {
        "id": 1,
        "nombre": "Empresa XYZ",
        "sector": "Tecnología",
        "estado": "activa",
        "activo": true,
        "destacado": false,
        "email": "",
        "telefono": "",
        "sitio_web": "",
        "direccion": "",
        "descripcion": "",
        "logo_url": "",
        "descuento_porcentaje": 0,
        "fecha_convenio": "",
        "beneficios": "",
        "condiciones": "",
        "contacto_nombre": "",
        "contacto_cargo": "",
        "contacto_telefono": "",
        "contacto_email": "",
        "admin_usuario_id": null,
        "admin_nombre": null
      }
    ]
  }
}
```

---

## Subida de Logos

El JS usa `./api/upload-image.php` enviando el campo `image` (no `logo_file`):

```javascript
async subirImagen(file) {
    const formData = new FormData();
    formData.append('image', file); // ← clave: 'image'
    const response = await fetch('./api/upload-image.php', { method: 'POST', body: formData });
    const data = await response.json();
    return data.data.url; // URL relativa: './uploads/empresas/empresa_xxxx.jpg'
}
```

El endpoint guarda en `build/uploads/empresas/` y retorna URL relativa lista para guardar en `logo_url`.

---

## Visualización de Logos — Reglas de URLs

| Contexto | Transformación aplicada | Razón |
|----------|------------------------|-------|
| `renderizarTablaAdmin()` (`demo_empresas.html`) | `.replace(/^\.\//, '')` → `uploads/empresas/file.jpg` | Relative desde raíz del servidor |
| Carrusel `renderCarruselEmpresas()` (`empresas-convenio.html`) | URL directa sin transformar | `./uploads/...` resuelve correctamente |
| Cache-busting en tabla admin | `?t=${updated_at_timestamp}` solo en `uploads/` locales | Evita caché de browser tras actualizar logo |

**Regla crítica**: El elemento `<div id="carouselTrack" class="carousel-track">` DEBE existir en el HTML de `empresas-convenio.html` dentro de `.loop-images`. Sin él, `renderCarruselEmpresas()` retorna silenciosamente sin error visible.

---

## Auto-Refresh en `demo_empresas.html`

`refrescarSilencioso()` corre cada 30 segundos. **Comparar hash de datos, no solo length**:

```javascript
// ✅ Correcto — detecta cambios de logo, nombre, etc.
const hashAntes = JSON.stringify(this.empresas.map(e => ({ id: e.id, logo_url: e.logo_url, nombre: e.nombre })));
// ...actualizar this.empresas...
if (hashAntes !== hashDespues) { this.renderizarTablaAdmin(); }

// ❌ Incorrecto — solo detecta cambios de conteo
if (empresasAntes !== this.empresas.length) { ... }
```
