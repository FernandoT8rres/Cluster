# 👥 Módulo de Comités — Documentación Técnica

> **Última actualización:** 2026-04-23  
> **Archivos involucrados:**  
> - `build/api/comites.php`  
> - `build/demo_comite.html`  
> - `build/js/admin-comites.js`

---

## 🏗️ Arquitectura del Módulo

```
demo_comite.html
  ├── admin-comites.js  (clase AdminComitesManager → window.adminComites)
  │     ├── cargarComites()              → GET /api/comites.php?action=listar
  │     ├── guardarComite(e)             → POST /api/comites.php?action=crear|editar
  │     ├── editComite(id)               → Llena form + abre acordeón
  │     ├── deleteComite(id)             → POST /api/comites.php?action=eliminar
  │     ├── limpiarFormulario()          → Resetea form + comiteEditando = null
  │     └── cargarSolicitudesPendientes()→ GET /api/comites.php?action=listar_registros_pendientes
  │
  └── Funciones globales (inline en HTML)
        ├── toggleLinkInput()             → Muestra/oculta campo URL según radio
        └── nuevoComite()                 → Resetea form + abre acordeón (BUG-017 Fix)
```

---

## 🗄️ Tablas Involucradas

### `comites`
| Columna           | Tipo                              | Notas                                     |
|-------------------|-----------------------------------|-------------------------------------------|
| `id`              | INT AUTO_INCREMENT PK             |                                           |
| `nombre`          | VARCHAR(255) NOT NULL             |                                           |
| `descripcion`     | TEXT NULL                         |                                           |
| `objetivo`        | TEXT NULL                         |                                           |
| `imagen`          | VARCHAR(500) NULL                 | Ruta `uploads/comites/...` o URL externa  |
| `periodicidad`    | VARCHAR(50)                       | Semanal, Mensual, Trimestral, etc.        |
| `miembros_activos`| INT DEFAULT 0                     |                                           |
| `organizacion`    | VARCHAR(255) NULL                 |                                           |
| `fecha_creacion`  | DATETIME DEFAULT NOW()            |                                           |
| `estado`          | ENUM(`activo`,`inactivo`,`suspendido`) |                                      |
| `coordinador_id`  | INT NULL                          | FK → `usuarios.id` (nullable)             |
| `tipo_registro`   | ENUM(`formulario`,`link`)         | Flujo de registro público                 |
| `link_registro`   | VARCHAR(500) NULL                 | URL externa (solo si `tipo_registro=link`)|

### `comite_registros`
| Columna                    | Tipo                              | Notas                              |
|----------------------------|-----------------------------------|------------------------------------|
| `id`                       | INT AUTO_INCREMENT PK             |                                    |
| `comite_id`                | INT NOT NULL                      | FK → `comites.id`                  |
| `empresa_id`               | INT NULL                          | FK → `empresas_convenio.id`        |
| `usuario_id`               | INT NULL                          | FK → `usuarios.id`                 |
| `nombre_empresa`           | VARCHAR(255) NULL                 | Texto libre si no hay empresa_id   |
| `nombre_usuario`           | VARCHAR(255) NULL                 |                                    |
| `email_contacto`           | VARCHAR(255)                      |                                    |
| `telefono_contacto`        | VARCHAR(50) NULL                  |                                    |
| `cargo`                    | VARCHAR(100) NULL                 |                                    |
| `departamento`             | VARCHAR(100) NULL                 |                                    |
| `comentarios`              | TEXT NULL                         |                                    |
| `usuario_loggeado_id`      | INT NULL                          | Usuario admin que registró         |
| `usuario_loggeado_nombre`  | VARCHAR(255) NULL                 |                                    |
| `usuario_loggeado_email`   | VARCHAR(255) NULL                 |                                    |
| `usuario_loggeado_empresa` | VARCHAR(255) NULL                 |                                    |
| `session_info`             | JSON NULL                         | Metadata de sesión                 |
| `ip_address`               | VARCHAR(45) NULL                  | IPv4/IPv6                          |
| `fecha_registro`           | DATETIME DEFAULT NOW()            |                                    |
| `estado_registro`          | ENUM(`pendiente`,`aprobado`,`rechazado`) | Default: `pendiente`         |
| `fecha_aprobacion`         | DATETIME NULL                     |                                    |
| `aprobado_por`             | INT NULL                          | FK → `usuarios.id`                 |

---

## 🔌 API Reference — `comites.php`

```
GET  /api/comites.php?action=listar
GET  /api/comites.php?action=imagen&id={id}
POST /api/comites.php?action=crear       (multipart/form-data)
POST /api/comites.php?action=editar      (multipart/form-data, requiere id)
POST /api/comites.php?action=eliminar    (requiere id)
POST /api/comites.php?action=subir_imagen (requiere imagen file)
GET  /api/comites.php?action=listar_registros_pendientes
POST /api/comites.php?action=aprobar_registro   (requiere id)
POST /api/comites.php?action=rechazar_registro  (requiere id)
```

---

## 🐛 BUG-017 — Botón "Crear Comité" faltante en demo_comite.html

### Síntoma
Al acceder a `demo_comite.html` no existía ningún botón, enlace, ni método para abrir el formulario de creación de comités. El usuario solo podía editar comités existentes (desde los botones de cada card), pero no crear nuevos.

### Causa raíz
La barra de controles de la página solo tenía el botón "Actualizar". El Acordeón 2 (`#acordeonFormulario`) contenía el formulario de creación/edición, pero **no había ningún punto de entrada para creación**:

- `editComite(id)` → Sí abría el acordeón con datos de edición.
- Para crear: el acordeón iniciaba cerrado y no había botón que lo abriera ni que llamara a `limpiarFormulario()` para resetear a modo creación.
- La función `limpiarFormulario()` existía en la clase `AdminComitesManager` pero no era accesible desde ningún elemento de la UI.

### Solución
1. **Botón "Crear Comité"** en la barra de controles (junto a "Actualizar"), con estilo rojo corporativo consistente con el resto del sistema.
2. **Función global `window.nuevoComite()`** definida en el bloque `<script>` inline del HTML:
   - Llama a `window.adminComites.limpiarFormulario()` → resetea form y pone `comiteEditando = null`
   - Resetea el radio a `tipo_registro = 'formulario'`
   - Llama a `toggleLinkInput()` para ocultar el campo URL
   - Actualiza el título del acordeón a "Nuevo Comité"
   - Abre el acordeón (`acordeonFormulario.open = true`)
   - Hace scroll suave al formulario

### Por qué no se modificó `admin-comites.js`
La función se añadió como `window.nuevoComite` en el HTML para mantener la separación de responsabilidades:
- `admin-comites.js` gestiona la lógica de negocio y API
- El HTML gestiona los puntos de entrada del usuario (botones)
- El patrón es consistente con otros módulos (`toggleLinkInput` también es global)

### Archivos modificados
- `build/demo_comite.html`

---

## ⚠️ Reglas para futuras modificaciones

- **Cada módulo CRUD necesita un botón de creación visible** en la barra de controles. No asumir que el usuario encontrará el acordeón por su cuenta.
- **`admin-comites.js` depende de `window.adminComites`** — el script crea la instancia al final con `window.adminComites = new AdminComitesManager()`. No renombrar.
- **`coordinador_id` puede ser NULL** — no hacer `intval()` sin verificar si está vacío; la FK falla si se envía `0`.
- **Las imágenes se sirven via endpoint**: `?action=imagen&id={id}`. La ruta física en BD es `uploads/comites/...` relativa al directorio `api/`.

---

## 🔗 Referencias

- Tabla principal: `u695712029_claut_intranet.comites`
- Tabla registros: `u695712029_claut_intranet.comite_registros`
- Frontend: `build/demo_comite.html`
- JS Admin: `build/js/admin-comites.js`
- API: `build/api/comites.php`
- Frontend público: `build/comites.html`
