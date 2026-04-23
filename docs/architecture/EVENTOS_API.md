# 📅 Módulo de Eventos — Documentación Técnica

> **Última actualización:** 2026-04-23  
> **Archivos involucrados:**  
> - `build/api/eventos.php`  
> - `build/demo_evento.html`  
> - `build/js/demo-eventos.js`  
> - `build/js/admin-eventos.js`

---

## 🏗️ Arquitectura del Módulo

```
demo_evento.html
  └── js/demo-eventos.js  (lógica principal)
        ├── loadEventos()                  → GET /api/eventos.php?action=listar
        ├── renderEventos()                → Renderiza tabla premium
        ├── showCreateModal()              → Abre modal #eventModal en modo creación
        ├── editEvento(id)                 → Abre modal con datos prellenados
        ├── deleteEvento(id, titulo, reg)  → DELETE /api/eventos.php?action=eliminar&id=X
        ├── [form submit]                  → POST /api/eventos.php?action=crear|editar
        │
        ├── loadNotificaciones()           → GET /api/notificaciones_eventos.php
        ├── renderNotificaciones(tab)      → Muestra pendientes/confirmados/rechazados
        ├── cambiarEstadoNotif(id, estado) → POST /api/notificaciones_eventos.php
        ├── viewDetalleNotif(id)           → Abre modal #detalleRegistroModal (BUG-018B fix)
        │
        ├── viewEventoRegistros(eventoId)  → GET /api/eventos.php?action=registros&evento_id=X
        ├── viewRegistros(...)             → Renderiza tabla en modal #registrosModal
        ├── viewDetalleRegistro(id)        → Abre modal #detalleRegistroModal (BUG-018C fix)
        ├── cambiarEstadoRegistro(id, est) → POST /api/eventos.php?action=cambiar_estado_registro
        └── eliminarRegistro(id)           → DELETE /api/eventos.php?action=eliminar_registro&registro_id=X
```

---

## 🗄️ Tablas Involucradas

### `eventos`
| Columna              | Tipo                                                     | Notas                                      |
|----------------------|----------------------------------------------------------|--------------------------------------------|
| `id`                 | INT AUTO_INCREMENT PK                                    |                                            |
| `titulo`             | VARCHAR(255) NOT NULL                                    |                                            |
| `descripcion`        | TEXT NULL                                                |                                            |
| `fecha_inicio`       | DATETIME NOT NULL                                        |                                            |
| `fecha_fin`          | DATETIME NULL                                            |                                            |
| `ubicacion`          | VARCHAR(255) NULL                                        |                                            |
| `capacidad_maxima`   | INT DEFAULT 100                                          |                                            |
| `capacidad_actual`   | INT DEFAULT 0                                            | Contador de registros confirmados          |
| `tipo`               | VARCHAR(50)                                              | reunion, capacitacion, social, importante  |
| `modalidad`          | VARCHAR(50)                                              | presencial, virtual, hibrido               |
| `estado`             | VARCHAR(50) NULL                                         | activo, programado, finalizado, cancelado  |
| `organizador_id`     | INT NULL                                                 | FK → `usuarios.id`                         |
| `comite_id`          | INT NULL                                                 | FK → `comites.id`                          |
| `imagen`             | VARCHAR(255) NULL                                        | Nombre de archivo en `uploads/eventos/`    |
| `precio`             | DECIMAL(10,2) DEFAULT 0                                  |                                            |
| `fecha_creacion`     | DATETIME DEFAULT NOW()                                   |                                            |
| `fecha_actualizacion`| DATETIME                                                 |                                            |
| `link_evento`        | VARCHAR(500) NULL                                        | URL externa opcional (BUG-018A fix)        |
| `link_mapa`          | VARCHAR(500) NULL                                        |                                            |
| `tiene_beneficio`    | TINYINT(1) DEFAULT 0                                     | 1 = Cuestionario previo activo             |

### `evento_registros`
| Columna               | Tipo                                      | Notas                             |
|-----------------------|-------------------------------------------|-----------------------------------|
| `id`                  | INT AUTO_INCREMENT PK                     |                                   |
| `evento_id`           | INT NOT NULL                              | FK → `eventos.id`                 |
| `empresa_id`          | INT NULL                                  | FK → `empresas_convenio.id`       |
| `usuario_id`          | INT NULL                                  | FK → `usuarios.id`                |
| `nombre_empresa`      | VARCHAR(255) NULL                         |                                   |
| `nombre_usuario`      | VARCHAR(255) NULL                         |                                   |
| `email_contacto`      | VARCHAR(255)                              |                                   |
| `telefono_contacto`   | VARCHAR(50) NULL                          |                                   |
| `comentarios`         | TEXT NULL                                 |                                   |
| `fecha_registro`      | DATETIME DEFAULT NOW()                    |                                   |
| `estado_registro`     | ENUM(`pendiente`,`confirmado`,`rechazado`)| Default: `pendiente`              |
| `motivo_rechazo`      | TEXT NULL                                 |                                   |

---

## 🔌 API Reference — `eventos.php`

```
GET  /api/eventos.php?action=listar
GET  /api/eventos.php?action=registros&evento_id={id}
GET  /api/eventos.php?action=registros_all
GET  /api/eventos.php?action=imagen&id={id}
POST /api/eventos.php?action=crear         (multipart/form-data)
POST /api/eventos.php?action=editar        (requiere id)
POST /api/eventos.php?action=cambiar_estado_registro  (JSON: {registro_id, estado})
DELETE /api/eventos.php?action=eliminar&id={id}
DELETE /api/eventos.php?action=eliminar_registro&registro_id={id}
```

### API Notificaciones
```
GET  /api/notificaciones_eventos.php                    → {data: {por_estado: {pendientes, confirmados, rechazados}}}
POST /api/notificaciones_eventos.php                    → JSON {action:'actualizar_estado', id, estado}
```

---

## 🐛 BUG-018 — Tres bugs corregidos el 2026-04-23

### Bug A — Campo "URL de Registro" bloqueaba la creación

**Síntoma:** Al intentar crear un evento sin poner una URL de registro, el formulario no enviaba aunque el usuario quisiera usar el formulario interno.

**Causa raíz:** El `<input type="url" ... required>` en línea 1507 de `demo_evento.html` hacía que el navegador bloqueara el submit si el campo estaba vacío, independientemente de si el usuario quería URL externa o formulario interno.

**Solución:** Se eliminó `required` y se actualizó el label con aclaración "(Opcional — déjalo vacío para usar el formulario interno)".

**Archivo modificado:** `build/demo_evento.html`

---

### Bug B — "Visualizar" en Solicitudes de Registro mostraba "Funcionalidad en desarrollo"

**Síntoma:** Al pulsar el ojo 👁️ en cualquier registro con estado confirmado o rechazado (en el panel "Solicitudes de Registro"), aparecía la notificación "Funcionalidad de detalles en desarrollo" y no se mostraba ningún modal.

**Causa raíz:** `viewDetalleNotif(id)` en `demo-eventos.js` solo tenía:
```javascript
async function viewDetalleNotif(id) {
    showNotification('Funcionalidad de detalles en desarrollo', 'info');
}
```

**Solución:** Se implementó completamente la función. Busca el registro en `notificationsData` (ya cargado en memoria), construye el HTML de la ficha con todos los campos del asistente (nombre, empresa, email, teléfono, evento, fecha, estado, comentarios) y lo inyecta en `#detalleRegistroContent`. Luego abre el modal `#detalleRegistroModal`.

**Archivo modificado:** `build/js/demo-eventos.js`

---

### Bug C — Ojo en "Listado de Asistentes" no desplegaba información

**Síntoma:** Al abrir el modal de asistentes de un evento y pulsar el ojo 👁️ en cualquier fila con estado confirmado/rechazado, no pasaba nada (no se abría la ficha del asistente).

**Causa raíz:** `viewDetalleRegistro(id)` tenía un `return;` al inicio del cuerpo de la función (comentado como "FUNCIÓN DESHABILITADA"), lo que hacía que el modal nunca se abriera aunque el HTML del modal `#detalleRegistroModal` sí existía.

**Solución:** Se reimplementó la función completa. Busca el registro en el array global `registros` (llenado por `viewRegistros()`), construye la ficha del asistente con el mismo diseño Porsche del sistema, y abre el modal `#detalleRegistroModal`. Se declaró `let registros = []` en el bloque global para evitar referencias rotas.

**Archivo modificado:** `build/js/demo-eventos.js`

---

## ⚠️ Reglas para futuras modificaciones

- **`link_evento` es OPCIONAL** — La lógica de negocio soporta formulario interno (sin URL) o redirección a URL externa. Nunca marcar como `required`.
- **No deshabilitar funciones con `return;`** sin documentar y sin crear un issue rastreable. Usar `// TODO:` o flags de feature.
- **`notificationsData`** contiene los registros cargados en memoria por `loadNotificaciones()`. Ambas funciones de detalle (`viewDetalleNotif` y `viewDetalleRegistro`) reutilizan esos datos sin llamadas adicionales a la API.
- **Modal `#detalleRegistroModal`** es compartido por ambos flujos (notificaciones y lista de asistentes). El botón "Eliminar de la Lista" en ese modal llama `eliminarRegistro()` que usa `currentRegistroId`.

---

## 🔗 Referencias

- Tabla eventos: `u695712029_claut_intranet.eventos`
- Tabla registros: `u695712029_claut_intranet.evento_registros`
- Frontend admin: `build/demo_evento.html` + `build/js/demo-eventos.js`
- API principal: `build/api/eventos.php`
- API notificaciones: `build/api/notificaciones_eventos.php`
- Frontend público: `build/eventos.html`
