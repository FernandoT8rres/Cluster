# 🏷️ Módulo de Descuentos — Documentación Técnica

> **Última actualización:** 2026-04-23  
> **Archivos involucrados:**  
> - `build/api/descuentos.php`  
> - `build/demo_descuentos.html`

---

## 🏗️ Arquitectura del Módulo

```
demo_descuentos.html
  ├── loadEmpresas()       → GET /api/empresas.php         (selector modal)
  ├── loadDescuentos()     → GET /api/descuentos.php?estado=todos
  ├── saveDescuento()      → POST /api/descuentos.php      (crear/editar)
  ├── editDescuento(id)    → GET /api/descuentos.php?id=X
  └── confirmDelete()      → DELETE /api/descuentos.php?id=X
```

---

## 🗄️ Tabla `descuentos`

| Columna                | Tipo                                                    | Notas                              |
|------------------------|---------------------------------------------------------|------------------------------------|
| `id`                   | INT AUTO_INCREMENT PK                                   |                                    |
| `titulo`               | VARCHAR(255) NOT NULL                                   |                                    |
| `descripcion`          | TEXT NULL                                               |                                    |
| `empresa_oferente_id`  | INT NULL                                                | FK → `empresas_convenio.id` o NULL si empresa externa |
| `empresa_nombre`       | VARCHAR(255)                                            | Nombre guardado (para externas)    |
| `tipo_empresa`         | `convenio` / `externa`                                  |                                    |
| `codigo_descuento`     | VARCHAR(50) NULL                                        |                                    |
| `porcentaje_descuento` | DECIMAL(5,2) NULL                                       | Exclusivo con `monto_descuento`    |
| `monto_descuento`      | DECIMAL(10,2) NULL                                      | Exclusivo con `porcentaje_descuento` |
| `fecha_inicio`         | DATE NOT NULL                                           |                                    |
| `fecha_fin`            | DATE NOT NULL                                           |                                    |
| `usos_maximos`         | INT NULL                                                | NULL = ilimitado                   |
| `usos_actuales`        | INT DEFAULT 0                                           |                                    |
| `estado`               | ENUM(`activo`,`inactivo`,`expirado`) DEFAULT `activo`  |                                    |
| `accion_tipo`          | ENUM(`link`,`telefono`,`email`,`whatsapp`,`mapa`,`ninguno`) |                              |
| `accion_valor`         | VARCHAR(500) NULL                                       |                                    |
| `accion_etiqueta`      | VARCHAR(100) NULL                                       |                                    |
| `fecha_creacion`       | DATETIME DEFAULT NOW()                                  |                                    |

---

## 🔌 API Reference — `descuentos.php`

### GET — Listar / obtener descuento
```
GET /api/descuentos.php
GET /api/descuentos.php?id={id}
GET /api/descuentos.php?estado=todos|activo|vigente|inactivo
GET /api/descuentos.php?empresa_id={id}&limit=20&orderBy=fecha_fin&order=ASC
```

### POST — Crear descuento
```http
POST /api/descuentos.php
Content-Type: multipart/form-data

titulo               (requerido)
empresa_oferente_id  (requerido si tipo_empresa=convenio)
empresa_nombre       (requerido si tipo_empresa=externa)
tipo_empresa         convenio | externa
codigo_descuento     (opcional)
porcentaje_descuento (exclusivo con monto)
monto_descuento      (exclusivo con porcentaje)
fecha_inicio         YYYY-MM-DD (requerido)
fecha_fin            YYYY-MM-DD (requerido)
usos_maximos         (opcional, vacío = ilimitado)
estado               activo | inactivo
accion_tipo          ninguno | link | telefono | email | whatsapp | mapa
accion_valor         (requerido si accion_tipo ≠ ninguno)
accion_etiqueta      (opcional)
```

### POST con `_method=PUT` — Actualizar descuento
```http
POST /api/descuentos.php
Content-Type: multipart/form-data

_method = PUT
id      = {id}   (requerido)
... mismos campos que crear ...
```

### DELETE — Eliminar descuento
```
DELETE /api/descuentos.php?id={id}
```

---

## 🐛 BUG-016 — Bugs críticos corregidos el 2026-04-23

### Bug A — Selector de empresas muestra solo el total, sin opciones

**Síntoma:** El `<select id="empresaOferente">` mostraba `"Seleccionar empresa (11 disponibles)..."` pero sin ninguna opción real debajo.

**Causa raíz:**  
La tabla `empresas_convenio` usa la columna `nombre` (no `nombre_empresa`). La respuesta de `/api/empresas.php` devuelve ambas columnas, pero el map en `loadEmpresas()` hacía:

```javascript
// ❌ Código incorrecto
nombre_empresa: empresa.nombre_empresa || empresa.nombre,
```

Cuando `empresa.nombre_empresa` era `''` (cadena vacía de BD), `||` lo trataba como falsy → usaba `empresa.nombre`. Hasta aquí correcto.  
Pero luego `populateEmpresaSelectors()` buscaba:

```javascript
// ❌ En el forEach
const nombre = empresa.nombre || empresa.nombre_empresa || empresa.company_name;
```

Después del map, el objeto solo tenía `nombre_empresa` (no `nombre`), así que `empresa.nombre` era `undefined`. Como `empresa.nombre_empresa` podía ser `''` (vacío de BD), la condición `if (id && nombre)` fallaba → **0 opciones agregadas al select**.

**Solución:**
```javascript
// ✅ Código correcto — preservar AMBOS campos en el map
nombre:          empresa.nombre          || empresa.nombre_empresa || '',
nombre_empresa:  empresa.nombre_empresa  || empresa.nombre         || '',
```
Ahora el `forEach` encuentra el valor correcto en `empresa.nombre` aunque la BD use solo `nombre`.

---

### Bug B — Registros duplicados al guardar

**Síntoma:** Cada clic en "Guardar" creaba dos registros idénticos en la tabla `descuentos`.

**Causa raíz:**  
Doble disparo de `saveDescuento()` por dos mecanismos simultáneos:

1. **`setupEventListeners()`** añadía `form.addEventListener('submit', saveDescuento)` — se dispara cuando el form hace submit.
2. **El botón** tenía `type="submit" form="descuentoForm"` — al hacer clic, disparaba el submit del form (**mismo evento** → `saveDescuento` llamado por listener). Pero el form también ejecuta su submit nativo que volvía a llamar al listener.

En la práctica:
- Clic en botón → `submit` event del form → listener → `saveDescuento()` #1 (POST) 
- Clic en botón → `submit` event del form → `saveDescuento()` #2 (POST) (race condition / segundo dispatch)

**Solución:**
```html
<!-- ❌ Antes -->
<button type="submit" form="descuentoForm">Guardar</button>

<!-- ✅ Ahora — un único punto de entrada -->
<button type="button" onclick="saveDescuento(event)">Guardar</button>
```
Y en `setupEventListeners()` se eliminó la línea:
```javascript
// ❌ Eliminada — causaba el doble envío
document.getElementById('descuentoForm').addEventListener('submit', saveDescuento);
```

---

## ⚠️ Reglas para futuras modificaciones

- **No registrar `submit` event en formularios que también tienen botón con `onclick`**. Elegir un solo mecanismo.
- **El map de empresas debe preservar `nombre` Y `nombre_empresa`** porque la columna activa en BD es `nombre`, no `nombre_empresa`.
- **`tipo_empresa`** debe enviarse siempre desde el frontend para que el backend seleccione la tabla correcta de JOIN.
- **`empresa_oferente_id` puede ser `null`** para empresas externas — no hacer `intval()` antes de verificar si está vacío.

---

## 🔗 Referencias

- Tabla principal: `u695712029_claut_intranet.descuentos`
- Tabla empresas: `u695712029_claut_intranet.empresas_convenio`
- Frontend: `build/demo_descuentos.html`
- API: `build/api/descuentos.php`
- API empresas: `build/api/empresas.php`
