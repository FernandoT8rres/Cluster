# 🧠 Memoria del Proyecto: Clúster Intranet

## 🏔️ Visión General
**Clúster Intranet** es una plataforma administrativa de élite diseñada bajo la estética **Porsche-inspired Glassmorphism**. El objetivo es proporcionar una experiencia de usuario premium, rápida e intuitiva para la gestión de comités, empresas y miembros del Clúster Metropolitano.

### Pilares del Diseño:
- **Estética Porsche**: Uso de Dark Mode profundo (`#09090b`), acentos en Rojo Neón (`#C7252B`) y tipografía Inter.
- **Glassmorphism**: Superposición de capas con desenfoque de fondo (`backdrop-filter: blur`) y bordes sutiles.
- **Interactividad Senior**: Uso de acordeones, micro-animaciones (GSAP) y feedback inmediato.

---

## 🎨 Guía de Estilos y Componentes

### Colores Principales:
- **Base**: `#09090b` (Deep Black)
- **Acento**: `#C7252B` (Porsche Red)
- **Superficie**: `rgba(255, 255, 255, 0.03)` (Glass effect)
- **Bordes**: `rgba(255, 255, 255, 0.08)`

### Componentes Estándar:
1. **Paneles Accordion**: Usar la estructura `<details class="glass-panel">` para agrupar contenido y ahorrar espacio vertical.
2. **Botones Premium**: Clase `.porsche-btn` o botones con gradientes rojos y sombras suaves.
3. **Tablas Modernas**: Filas con separación, sombras leves y efectos hover de elevación.

---

## 🏗️ Arquitectura y Comandos

- **Frontend**: HTML5, Vanilla JS, Tailwind CSS (CDI).
- **Backend**: PHP 7.4+ (Hostinger production).
- **API**: Endpoints en `/api/` (ej. `comites.php`, `auth/session.php`).
- **Seguridad**: Autenticación asíncrona basada en sesiones. Evitar `localStorage` para datos sensibles; usar `auth-session.js`.

### Comandos de Mantenimiento:
- **Limpieza de Caché**: `Ctrl + Shift + R` (obligatorio tras cambios en JS).
- **Verificación de Sesión**: `window.authSessionManager.checkAuthentication()`.

---

## 🛠️ Registro de Errores Históricos (Troubleshooting)

### 1. Error SQL 1055 (ONLY_FULL_GROUP_BY)
- **Problema**: `api/comites.php` fallaba en Hostinger por falta de columnas en el `GROUP BY`.
- **Solución**: Se añadió `u.nombre` a la cláusula `GROUP BY` en las consultas de listado.

### 2. SyntaxError en `admin-comites.js`
- **Problema**: Un objeto JSON "huérfano" bloqueaba la carga de la clase.
- **Solución**: Eliminación del bloque de código residual tras un console.log comentado.

### 3. Error 404 en Panel de Administración
- **Problema**: URLs absolutas hardcoded con el prefijo `/build/` fallaban en producción.
- **Solución**: Migración masiva a rutas relativas (`./`) en todos los archivos HTML del root.

### 4. Visibilidad de Botón Admin
- **Problema**: El rol `Administrador` (capitalizado) no activaba el botón debido a una comparación estricta.
- **Solución**: Normalización de roles a `toLowerCase()` y uso de `!important` en el estilo para vencer clases de Tailwind.

### 5. Privacidad de Directorio y BUG de Roles (BUG-007)
- **Problema**: Datos sensibles eran visibles Y las empresas desaparecieron para el administrador debido a una comparación de roles sensible a mayúsculas.
- **Solución**: 
  - Normalización de roles a `toLowerCase()` en `api/empresas-simple.php`.
  - Ajuste del filtro público para incluir registros legados (`autoriza_directorio IS NULL`).
  - Implementación de **borrado físico** (`DELETE`) para limpieza real de la BD a petición del usuario.

---

## 🔒 Reglas de Privacidad y Visibilidad (Directorio)

- **Autorización**: Empresas con `1` o `NULL` en `autoriza_directorio` aparecen en el listado público (legados por defecto visibles).
- **Campos Prohibidos**: Dirección física, datos de trazabilidad.
- **Borrado**: La eliminación desde el panel administrativo es **permanente (física)**, no lógica.
- **Roles Identificados**: `admin`, `administrador`, `empresa`, `empleado`.

---

## 📝 Notas de Versión Actual
- **Estado**: Visibilidad restaurada para Administradores. Borrado físico habilitado para limpieza de BD.
- **Pendiente**: Validar persistencia de filtros en Mensajería.
