# 🧠 Memoria del Proyecto: Clúster Intranet (claude.md)

Documento de memoria principal del proyecto. Leer **siempre antes de implementar** cualquier cambio.

---

## 🎯 Visión General del Proyecto

**Clúster Intranet** es una plataforma web (v2.0) de gestión interna para el Clúster Automotriz Metropolitano (Claut). Permite administrar empresas socias, usuarios, comités, eventos, boletines, descuentos y documentos.

**Audiencia:** Empleados del clúster, empresas socias, administradores.  
**Entorno de producción:** Hostinger — `https://intranet.clautmetropolitano.mx`  
**Repositorio:** `https://github.com/FernandoT8rres/Cluster`

### Stack Principal
| Capa | Tecnología |
|------|-----------|
| Frontend | HTML5, Vanilla JS ES6+, Tailwind CSS, GSAP, Chart.js, Three.js |
| Backend | PHP 8.0+, PDO |
| Base de datos | MySQL 8.0+ (Hostinger) |
| Seguridad | CSRF, JWT, Rate Limiting, FileUploadValidator (9 capas) |
| Entorno | `.env` via `EnvLoader` — NUNCA credenciales en código |

---

## 🏗️ Arquitectura y Contexto Importante

### Estructura de Directorios
```
Claut_BD/
└── build/                      ← Raíz del servidor (php -S localhost:8000 -t build/)
    ├── api/                    ← Endpoints REST (JSON puro)
    │   ├── auth/               ← Login, logout, session, profile
    │   ├── empresas-simple.php ← ⭐ API activa para admin-empresas.js
    │   ├── empresas.php        ← Legacy (Bearer token) — no usar en nuevas features
    │   ├── empresas_convenio.php ← Legacy — no usar en nuevas features
    │   ├── upload-image.php    ← Subida de logos con validación 9 capas
    │   ├── estadisticas_simple.php ← Stats del dashboard
    │   └── ...
    ├── middleware/             ← CSRF, JWT, Rate Limiter, Security Headers
    ├── utils/                  ← FileUploadValidator, InputValidator, SecurityLogger
    ├── config/
    │   ├── database.php        ← Singleton Database — ÚNICA fuente de conexión BD
    │   ├── env-loader.php      ← Carga .env
    │   └── session-config.php
    ├── js/
    │   ├── admin-empresas.js   ← ⭐ Clase AdminEmpresasManager — CRUD completo
    │   ├── auth-session.js     ← Validación de sesión
    │   └── ...
    ├── uploads/
    │   └── empresas/           ← Logos subidos (creado automáticamente por upload-image.php)
    ├── demo_empresas.html      ← Panel admin de empresas
    └── .env                    ← Credenciales reales — NUNCA versionar
```

### Patrones de Arquitectura
- **Singleton DB**: `$db = Database::getInstance()->getConnection()` — nunca instanciar PDO directamente
- **Soft Delete**: Nunca `DELETE` físico — usar `activo = 0` + `estado = 'inactiva'`
- **API Response**: Siempre `['success' => bool, 'data' => mixed, 'message' => string]`
- **COALESCE dual-column**: Columnas duplicadas por legacy (`nombre`/`nombre_empresa`, `sector`/`categoria`) — usar `COALESCE(e.nombre, e.nombre_empresa) AS nombre`

---

## 🔑 Comandos Clave

```bash
# Servidor de desarrollo
php -S localhost:8000 -t build/

# URLs principales
http://localhost:8000/demo_empresas.html     # Panel admin empresas
http://localhost:8000/dashboard.html         # Dashboard principal
http://localhost:8000/admin-panel.html       # Panel admin general

# Verificar logs de seguridad
tail -f build/logs/security/security_$(date +%Y%m%d).log

# Generar JWT secret seguro
openssl rand -hex 32
```

---

## 🎨 Guías de Estilo

### UI/UX — Tema "Premium Porsche" (Glassmorphism)
- **Color primario**: `#C7252B` (rojo Claut)
- **Fondo**: oscuro con `backdrop-filter: blur` y transparencias
- **Modales Premium Glassmorphism**: Usar fondos de cristal oscuro (`bg-slate-900/40`), grids organizados para datos complejos (ej. modales de empresa), y desenfoques acentuados (`backdrop-blur-xl`) en lugar de fondos blancos convencionales para garantizar legibilidad e integración dinámica.
- **Fuentes**: Inter / sistema
- **Animaciones**: GSAP — fluidas, no intrusivas
- **Loading**: `loading-screen.js` con anillos expansivos y color `#C7252B`
- **Toasts/notificaciones**: Siempre para operaciones AJAX (éxito y error)
- **Layout master**: `claut-header` + `porsche-navbar` + `claut-bottom-nav`
- **Centrado de menú desktop**: `position: absolute; left: 50%; transform: translateX(-50%)`

### PHP — Backend
- Clases: `PascalCase` | Métodos/variables: `camelCase` | Tablas/columnas: `snake_case`
- Conexión BD: siempre `Database::getInstance()->getConnection()`
- Nunca hardcodear credenciales — usar `$_ENV['KEY']` via `EnvLoader`
- Sanitizar con `trim()` + `htmlspecialchars()` (NO `FILTER_SANITIZE_STRING` — deprecado PHP 8.1)
- Campos `DATE` de MySQL: enviar `null` cuando estén vacíos (nunca `""`)
- Respuestas de error: nunca exponer stack trace en producción

### JavaScript — Frontend
- Clases: ES6 `class` con `PascalCase`
- Variables/métodos: `camelCase` | Constantes: `UPPER_SNAKE_CASE`
- Sin `console.log` en producción — solo `console.error` y `console.warn`
- FormData: SIEMPRE hacer `append` de todos los campos (incluso vacíos) para que los vaciados en edición se persistan
- Inputs sin `name=""` no son capturados por `new FormData(form)` — verificar siempre

### SQL
- Tablas: `snake_case` plural | Columnas: `snake_case`
- Migraciones: siempre idempotentes (usar stored procedures o `IF NOT EXISTS`)
- Índices: `activo`, `estado`, `destacado`, `admin_usuario_id` en tablas con filtros frecuentes

---

## 🗄️ Tabla `empresas_convenio` — Referencia Completa

| Columna | Tipo | Notas |
|---------|------|-------|
| `id` | INT PK AUTO | |
| `nombre` | VARCHAR(255) | Columna activa — preferida sobre `nombre_empresa` |
| `nombre_empresa` | VARCHAR(255) | Legacy — mantener por compatibilidad |
| `sector` | VARCHAR(100) | Columna activa — preferida sobre `categoria` |
| `categoria` | VARCHAR(100) | Legacy — mantener |
| `estado` | ENUM/VARCHAR | `'activa'`, `'inactiva'`, `'pendiente'` |
| `activo` | TINYINT(1) | Soft delete — `1` = visible, `0` = eliminado |
| `destacado` | TINYINT(1) | `1` = destacado en portal |
| `email` | VARCHAR(255) | Email general |
| `telefono` | VARCHAR(50) | Teléfono general |
| `sitio_web` | VARCHAR(255) | |
| `direccion` | TEXT | |
| `descripcion` | TEXT | |
| `logo_url` | VARCHAR(500) | Ruta relativa o URL externa |
| `descuento_porcentaje` | DECIMAL(5,2) | Porcentaje de descuento |
| `descuento` | DECIMAL(5,2) | Legacy — mantener |
| `fecha_convenio` | DATE | NULL cuando vacío — nunca `""` |
| `fecha_inicio_convenio` | DATE | |
| `fecha_fin_convenio` | DATE | |
| `beneficios` | TEXT | |
| `condiciones` | TEXT | Términos y condiciones |
| `contacto_nombre` | VARCHAR(255) | Recibe `$_POST['contacto_persona']` |
| `contacto_cargo` | VARCHAR(100) | |
| `contacto_telefono` | VARCHAR(50) | |
| `contacto_email` | VARCHAR(255) | |
| `admin_usuario_id` | INT FK | FK → `usuarios_perfil.id` |
| `fecha_registro` | TIMESTAMP | Auto en INSERT |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

> **Mapeo de nombre en formulario vs BD**: El campo HTML `id="contacto_persona"` se mapea al campo `contacto_nombre` en la BD. PHP en `empresas-simple.php`: `$contacto_nombre = trim($_POST['contacto_persona'] ?? '')`.

---

## 📡 APIs del Módulo de Empresas

| Archivo | Acción POST | Quién lo usa | Estado |
|---------|-------------|--------------|--------|
| `empresas-simple.php` | `listar`, `obtener`, `crear`, `actualizar`, `eliminar` | `admin-empresas.js` | ✅ Activo |
| `empresas.php` | Bearer token CRUD | Legacy externo | ⚠️ Legacy |
| `empresas_convenio.php` | CRUD antiguo | Legacy | ⚠️ Legacy |
| `upload-image.php` | POST `image` file | `admin-empresas.js` → `subirImagen()` | ✅ Activo |

---

## ⚠️ Errores Encontrados y Corregidos

### Sesión Marzo 2026 — Sistema de Registro de Empresas/Socios

18. **[CRÍTICO] `case 'eliminar'` faltante** en `empresas-simple.php`: El switch no tenía ese case — el botón eliminar nunca funcionó. Fix: añadido `case 'eliminar'` + función `eliminarEmpresa()` con soft delete.

19. **[CRÍTICO] 18 campos sin `name=""` en `demo_empresas.html`**: `new FormData(form)` no capturaba ningún campo. Fix: `name="campo"` idéntico al `id` en todos los inputs/selects/textareas del modal.

20. **[CRÍTICO] FormData ignoraba campos vacíos en edición**: `if (valor) { formData.append(...) }` — vaciados no se persistían. Fix: `formData.append(campo, valor)` sin condición siempre.

21. **[CRÍTICO] `listarEmpresas` no retornaba `estado`, `fecha_convenio`, `condiciones`**: Modal de edición siempre mostraba vacíos. Fix: añadidos al SELECT y al array de respuesta.

22. **`obtenerEmpresa` tampoco retornaba `estado`, `fecha_convenio`, `condiciones`**: Mismo problema que #21 pero en la función de obtención individual. Fix: añadidos al SELECT y response.

23. **`fecha_convenio` como `""` a MySQL `DATE`**: Causa error en INSERT/UPDATE. Fix: `$fecha_convenio = ($raw !== '' && strtotime($raw)) ? $raw : null`

24. **`descuento_porcentaje` guardado como string**: Fix: `floatval()` con comprobación `!== ''`.

25. **`admin_usuario_id = "0"` era falsy**: Fix: `!empty()` en lugar de ternario simple.

26. **60+ `console.log` en producción**: Exponían datos sensibles. Fix: limpieza completa, solo `console.error`/`console.warn`.

27. **Sin validación frontend de campo `nombre`**: Fix: validación explícita en `guardarEmpresa()` con `focus()` automático.

28. **Contadores `destacadasEmpresas` y `descuentosEmpresas` nunca actualizados**: `actualizarEstadisticas()` solo actualizaba `totalEmpresas` y `empresasActivas`. Fix: añadidos cómputos para `destacado === true` y `descuento_porcentaje > 0`.

29. **Credenciales reales en `SYSTEM_ARCHITECTURE.md`**: DB user/pass/name y JWT secret hardcodeados en sección de Variables de Entorno. Fix: reemplazados por placeholders genéricos.

### Sesión Marzo 2026 — Refactorización General UI/UX

2. **Duplicidad Masiva de CSS**: En `boletines.html` se eliminaron más de 3,800 líneas de CSS redundante. Ahora usa `header-navbar.css` compartido.
3. **Inconsistencia de Diseño**: Estandarizado tema "Premium Porsche" en todas las vistas principales.
4. **Funciones Deprecadas**: `FILTER_SANITIZE_STRING` → `htmlspecialchars()`.
5. **Doble Implementación de DB**: Eliminadas clases `Database` duplicadas, única instancia en `config/database.php`.
6. **Layout Desfasado**: Márgenes fijos `120px` → clases responsivas `.porsche-navbar-responsive`.
7. **Artefactos de Sidebar y Bottom Nav**: Eliminados iconos flotantes legacy.
8. **Pérdida de Estilos Críticos (Glitch buttons)**: Restaurados manualmente en Boletines.
9. **Limpieza de Archivos Redundantes**: Eliminadas 5+ carpetas backup y scripts obsoletos.
10-17. **Homologación de vistas** (`comites.html`, `contacto.html`, `eventos.html`, `nosotros.html`): header, navbar, bottom-nav unificados con `dashboard.html`.

### Sesión Marzo 2026 — Homologación `empresas-convenio.html`

30. **[CRÍTICO] Logo incorrecto en header**: Usaba `apple-icon.png` en lugar de `logo-claut-blanco.png`. Fix: reemplazado en header y loading screen.

31. **[CRÍTICO] Nav items con clases Tailwind inline redundantes**: Todos los `<i>` y `<span>` del header nav tenían `text-white` hardcodeado, overrideando los estilos del tema. Fix: eliminados.

32. **[CRÍTICO] Ícono fa-tags en lugar de fa-tag** (Descuentos): Ícono incorrecto en header nav y bottom nav. Fix: corregido a `fa-tag` en ambos.

33. **[MEDIO] "Comites" sin acento**: Label del nav decía "Comites" — debería ser "Comités". Fix: corregido en header nav y bottom nav.

34. **[CRÍTICO] Faltaba `adminNavItem` y `adminNavItemBottom`**: El ítem Admin (oculto para usuarios sin rol admin) no existía en header ni bottom nav. Fix: añadidos con `style="display:none;"` como en dashboard.

35. **[CRÍTICO] Actions area incompleta**: Solo tenía un `<a href="./profile.html">` con ícono user. Faltaban: bell button con `headerMessagesBtn`, notification badge, y dropdown completo `headerUserDropdown` con nombre/rol/logout. Fix: reemplazado por el bloque completo de dashboard.

36. **[CRÍTICO] Faltaba sección `porsche-navbar`** (breadcrumb bar): La barra de breadcrumb debajo del header no existía. Fix: añadida con breadcrumb "Clúster Intranet / Socios" y `userNameTopBar`.

37. **[CRÍTICO] Faltaba `loading-container` div** al inicio del body: La pantalla de carga no se mostraba al navegar a la página. Fix: añadido el div con anillos y logo.

38. **[CRÍTICO] Legacy navbar negro dentro de `<main>`**: Existía un `<nav class="bg-black ...">` con dropdown de usuario `userDropdown`/`toggleUserMenu()` dentro del `#mainContent` — completamente duplicado y en conflicto con el nuevo header. Fix: eliminado completamente.

39. **[CRÍTICO] Bottom nav: label "Convenios" en lugar de "Socios"**: Label del link `empresas-convenio.html` decía "Convenios" en bottom nav. Fix: corregido a "Socios" (consistente con header nav).

40. **[MEDIO] Funciones `toggleUserMenu()`/`handleLogout()` legacy**: Referenciaban `userDropdown` (eliminado) y no usaban `authSessionManager`. Fix: reemplazadas por implementación que maneja `headerUserDropdown` y delega a `window.authSessionManager.logout()`.

41. **[MEDIO] `setupHeaderButtons()` con `console.log` en producción**: La función de setup de botones del header logeaba eventos. Fix: removidos los console.log y conectado `headerMessagesBtn` al message center.

42. **[MEDIO] Faltaban links a CSS compartidos**: `header-navbar.css` y `estilos-empresas.css` no estaban linkeados en `<head>` — el archivo dependía solo de CSS inline masivo. Fix: añadidos los `<link>` en head, consistente con dashboard.html.

### Sesión Marzo 2026 — Bugs Carrusel Empresas y Fotos

43. **[CRÍTICO] `#carouselTrack` faltaba en el HTML de `empresas-convenio.html`**: La función `renderCarruselEmpresas()` usa `document.getElementById('carouselTrack')` que retornaba `null` y salía silenciosamente (`if (!carouselTrack) return`). La sección `.loop-images` no tenía el elemento contenedor. Fix: añadido `<div id="carouselTrack" class="carousel-track">` con mensaje de carga y botones de navegación prev/next dentro de la sección `.loop-images`.

44. **[CRÍTICO] `refrescarSilencioso()` solo re-renderizaba si cambiaba el conteo**: Comparaba `empresasAntes !== this.empresas.length`. Al actualizar el logo de una empresa el conteo no cambia → la tabla nunca se actualizaba en el auto-refresh. Fix: comparar con hash JSON de `{id, logo_url, nombre}` de cada empresa — si cualquier dato cambia, re-renderiza.

45. **[MEDIO] Sin cache-busting en imágenes de logos en `renderizarTablaAdmin`**: Al subir un nuevo logo, el navegador mostraba la versión cacheada. Fix: añadido `?t=${updated_at_timestamp}` a las URLs de uploads locales (`uploads/empresas/...`).

46. **[BAJO] `empresas-evervault.js` referenciado pero ausente localmente**: `empresas-convenio.html` carga `./js/empresas-evervault.js` que no existe en `build/js/`. Existe en servidor de producción. No causa error crítico pero la sección evervault depende de él. Pendiente: crear versión local de respaldo.

### Sesión Marzo 2026 — Homologación Socios (empresas-convenio.html)

47. **[CRÍTICO] Inconsistencia en Iconos de Cabecera**: Usaba `fa-bell` en lugar de `fa-envelope` para el buzón de mensajes. Fix: estandarizado a `fa-envelope` para consistencia con Dashboard.
48. **[CRÍTICO] Dropdown de Usuario Obsoleto**: Tenía una implementación inline con estilos hardcodeados que colisionaba con `header-navbar.js`. Fix: reemplazado por la estructura modular del dashboard.
49. **[MEDIO] Breadcrumb con Estilos Legacy**: Usaba clases `text-slate-700` que dificultaban la visibilidad en temas oscuros. Fix: actualizado a `text-white` con clases de opacidad Porsche.

### Sesión Marzo 2026 — Modernización Admin Panel (admin-panel.html)

55. **[ARQUITECTURA] Estándar "Premium Porsche" Homologado**: Se reemplazó el antiguo navbar negro personalizado por la arquitectura corporativa `claut-header` + `porsche-navbar`. Esto unifica la experiencia de usuario entre el Dashboard y el Panel Administrativo.

56. **[DISEÑO] Implementación de Glassmorphism v2**: Todas las tarjetas de estadísticas (`stat-card`) y módulos de gestión (`module-card`) fueron reconstruidas con `porsche-glass-card`. Se utilizaron gradientes metálicos y desenfoque de fondo para una estética de alta gama.

57. **[ANIMACIÓN] Integración GSAP 3**: Se añadieron secuencias de entrada cinematográficas (`reveal-anim`). Los elementos ahora aparecen con desplazamientos suaves y escalados escalonados (stagger), eliminando la carga estática y mejorando la percepción de fluidez.

58. **[UX] Rediseño de Módulos de Gestión**: Los antiguos botones con SVGs inline fueron sustituidos por tarjetas interactivas con iconografía FontAwesome 6 y efectos de hover dinámicos. Se añadieron descripciones claras para cada funcionalidad administrativa.

59. **[UI] Estandarización de Actividad Reciente**: La sección de logs de actividad fue rediseñada para seguir el lenguaje visual de Porsche, utilizando avatares circulares, badges de estado (`activo`, `admin`, `empresa`) y tipografía Inter optimizada.

60. **[SISTEMA] Cleanup de Fragmentación de Código**: Se eliminaron bloques duplicados de lógica de navegación y se centralizó el control de dropdowns de usuario mediante una implementación robusta compatible con `auth-session.js`.

### Sesión Marzo 2026 — Modernización Sign-In y Sign-Up

51. **[DISEÑO] Implementación de Glassmorphism**: Se aplicó la clase `porsche-glass-card` al formulario de login para mejorar la profundidad visual.
52. **[UX] Animaciones Cinematográficas**: Integración de **GSAP 3** para la entrada escalonada de elementos (`reveal-anim`), mejorando la percepción de calidad del sistema.
53. **[ESTILO] Limpieza de CSS Inline**: Se eliminaron más de 120 líneas de estilos manuales de sidebar que estaban erróneamente en `sign-in.html`, delegando ahora al sistema de diseño unificado.
54. **[MEJORA] Carrusel de Banners**: Se añadió un sistema de `carousel-overlay` para mejorar la legibilidad del texto dinámico sobre imágenes de banners con variada luminosidad.
60. **Modernización Sign-Up**: Se aplicó el diseño "Premium Porsche" a `sign-up.html` (Glassmorphism, GSAP 3, Micro-interacciones).
61. **Unificación Estética**: Se homologaron `sign-in.html` y `sign-up.html` bajo el mismo sistema de diseño visual (fondos dinámicos, tarjetas traslúcidas).
62. **Optimización JS**: Se consolidó la lógica de registro y carga de empresas en un bloque unificado, mejorando el rendimiento y la mantenibilidad.
63. **Corrección Estructural**: Se resolvieron fragmentaciones de código y duplicidades de etiquetas (body/html) para cumplir con estándares de linting.
64. **Seguridad UX Inducida**: Se implementó un medidor de fortaleza visual de contraseña integrado en el diseño Glassmorphism.
65. **[CRÍTICO] Bug de Imagen Gigante en Sign-In**: Se detectó que `apple-icon.png` (usado como logo) es una imagen de muy alta resolución que se desbordaba sin restricciones de tamaño. Fix: reemplazado por `logo-ct.png` y definido contenedores con `overflow: hidden`.
66. **[UX] Rediseño Tema Claro (Light Clarity)**: Siguiendo feedback del usuario ("muy oscura"), se transformó el login a un tema claro premium. Uso de `logo-ct-dark.png`, fondos `#f8fafc`, sombras suaves y tipografía en gris pizarra profundo para máxima claridad funcional.

### 65. Restauración de Funcionalidad y Módulos "Premium Porsche"
- **Admin Panel**: Reintegración de los módulos de Gestión de Descuentos, Comités, Gráficos y Estadísticas Dinámicas que se habían omitido durante la consolidación inicial. Se mantiene el diseño Porsche pero con el 100% de la funcionalidad original.
- **Sign-In**: Corrección de error estructural en el carrusel de banners (div de apertura faltante) y verificación de enlaces de recuperación/visitante.
- **Sign-Up**: Restauración de más de 10 campos originales (Ubicación, Contactos de Emergencia, Biografía) y el modal completo de registro de nuevas empresas.
- **GSAP**: Las animaciones se mantuvieron y optimizaron para no interferir con el acceso a los campos del formulario.
- **Diseño Senior**: El formulario de registro se organizó en secciones lógicas (Datos Personales, Ubicación, Adicionales, Seguridad) para una presentación más profesional y legible.
- **UI Dinámica Vanilla**: Usar `<template>` tags o constructores DOM para inyectar filas. Manipular `innerHTML` solo cuando sea aséptico (`textContent` es mejor).
- **SweetAlert2** para confirmaciones (ej. Borrar usuario, timeout de sesión).
- **Glassmorphism Premium**: Todo panel principal (Banners, Calendarios, Widgets) debe usar `.glass-card` con bordes transparentes (`border-0` o `border-white/10`) en lugar de `bg-white` o gradientes estáticos, asegurando integración total con el fondo oscuro `.porsche-layout`.
- **Carruseles Externos (Ejs. Evervault/Three.js)**: Al integrar librerías que generan contenedores DOM nativamente, nunca dejar sus tarjetas hijas (`.empresa-card-normal`, `.empresa-card-image`) con fondos `bg-white` y `padding` pesados nativos, pues asfixian el layout oscuro. Convertirlas a *glass cards* de transparencia dinámica y remover color de fondo de la imagen (`background: transparent; filter: drop-shadow`).

### Sesión Marzo 2026 — Depuración Crítica UI/UX (Actual)

67. **[CRÍTICO] Fuga de CSS por Etiqueta mal cerrada**: Al insertar el bloque `<style>` en `admin-panel.html`, se eliminó accidentalmente el cierre `/>` del `<link>` anterior. 
    - **Lección**: Siempre verificar el cierre de etiquetas pre-existentes al realizar inyecciones en el `<head>`.
68. **[CRÍTICO] Inyección Duplicada por Pattern Match**: Se inyectó el bloque de branding del sidebar dentro de la sección de eventos debido a una coincidencia parcial en la herramienta de reemplazo.
    - **Lección**: Usar bloques de código más específicos (incluyendo contenedores padres) para evitar coincidencias erróneas en archivos grandes (>2000 líneas).
69. **[UX] Pivot a "Light Clarity"**: La versión inicial del diseño Porsche era demasiado oscura para el flujo de trabajo diario según el usuario.
    - **Decisión**: Estándar para Login/Registro es ahora fondos claro `#f8fafc`, tarjetas blancas puras y acentos Porsche Red para máxima legibilidad.
70. **[DISEÑO] Pérdida de Campos en Refactorización**: El primer intento de rediseño de `sign-up.html` simplificó demasiado el formulario, eliminando campos de base de datos críticos.
    - **Corrección**: Restaurados todos los campos originales (Ubicación, Emergencia, Bio) bajo el nuevo sistema visual Porsche.
71. **[CRÍTICO] Tarjetas de socios no hacían nada al hacer clic**: Se detectó una función `setupModalFallback()` en `empresas-convenio.html` que sobrescribía accidentalmente y de manera destructiva el controlador maestro `window.modalHandler`, dejándolo sin el método `showModal`. Fix: `setupModalFallback()` eliminada por completo.
72. **[UX/PERFORMANCE] Duplicidad de carruseles**: Había dos carruseles renderizando al mismo tiempo (`#carouselTrack` legado y `Evervault`). Fix: El carrusel clásico de logos fue eliminado para dar prioridad de rendimiento y estética al *Carrusel de Empresas con Animación Evervault*.

### Sesión Marzo 2026 — Modernización Dashboard y Gestión de Eventos (Actual)

71. **[DISEÑO] Dashboard Futurista**: Eliminadas las secciones legadas de estadísticas (Chart.js) y empresas recientes. Implementado un **Calendario Corporativo Elite** basado en FullCalendar v6 con Glassmorphism y acentos Porsche Red (`#C7252B`).
72. **[SISTEMA] Gestión de Eventos Unificada**: Integrado el módulo de administración de eventos directamente en `admin-panel.html`. Ya no se requiere navegar a demos externas; todo el CRUD se maneja mediante modales premium en la misma interfaz.
73. **[JS] Arquitectura Modular**: Creados `dashboard-calendar.js` (visualización socio) y `admin-eventos.js` (gestión administrativa) para separar responsabilidades y optimizar la carga de la página.
74. **[BD] Extensión de Tablas**: La tabla `eventos` ahora soporta `modalidad` (Híbrido/Virtual/Presencial), links de acceso, mapas y marcas de beneficios Clúster. Se ha habilitado `evento_registros` para el control de asistencia.
75. **[UX] QuickView Eventos**: Implementado un sistema de previsualización rápida de eventos en el dashboard que permite el registro y visualización de detalles sin abandonar el flujo principal.
76. **[FIX] Inyección de Scripts**: Corregido error de sintaxis donde etiquetas HTML (`<!-- -->`) se inyectaban accidentalmente dentro de bloques `<script>`, rompiendo la ejecución de JS en el panel admin.
307. **[MODERNIZACIÓN] Modernización de Agenda (Marzo 2026)**: Se transformó el calendario del dashboard principal en un formato de **Agenda Corporativa (listMonth)**. Se consolidó la administración de eventos en `admin-panel.html` eliminando redundancias y activando el controlador especializado `admin-eventos.js`. Además, se eliminaron las secciones legadas de "Gestionar Empresas" y "Nuestros Boletines" del dashboard para una interfaz más centrada y profesional.
- **Evolución Master Calendar Console (Marzo 2026)**: Se implementó la **Consola Master Calendar (`calendario.html`)**, una interfaz de planificación estratégica avanzada estilo Notion. El Dashboard fue actualizado a un visor visual (`timeGridWeek`) con estética de 'Post-its' Porsche, permitiendo una gestión bidireccional de 360 grados de la agenda corporativa mediante gestos interactivos como arrastrar y soltar (Drag & Drop).
312. **[ARQUITECTURA] Master Calendar Console**: Creada la página `calendario.html` y su controlador `admin-calendar-master.js`. Esta consola permite una gestión visual estratégica estilo Notion/Google Calendar con soporte nativo para **Drag & Drop**, redimensionamiento de eventos y creación reactiva por clic.
313. **[DISEÑO] Post-it Aesthetics**: Implementado un sistema de renderizado de eventos en el Dashboard y en la Consola Master que utiliza 'Post-its' estéticos: bloques con sombras profundas, bordes de cristal (glassmorphism) y codificación de colores Porsche Claut.
314. **[SISTEMA] Sincronización 360°**: El Dashboard ahora utiliza `timeGridWeek` para proporcionar una visión visual de la semana, sincronizando todos los cambios de planificación realizados en la Consola Master de forma instantánea.
315. **[ADMIN] Dashboard Hub**: Modificado `admin-panel.html` para incluir el acceso directo a la Consola Master Calendar como módulo prioritario de gestión.
321. **[UI/UX] Modal Premium Split-Panel (Marzo 2026)**: Rediseño integral del modal de empresas en `empresas-convenio.html`. Implementada una arquitectura de doble columna (Izquierda: Identidad y Badges; Derecha: Detalles con Scroll). Se resolvió el error de colisión con el `claut-header` aplicando un `padding-top` estratégico y ajustando el `max-height`.
322. **[SISTEMA] Botones de Acción Directa**: Integrados botones de llamada (`tel:`), correo (`mailto:`) y sitio web en el perfil de socios, eliminando la navegación pasiva y fomentando el contacto comercial inmediato entre socios del Clúster.
323. **[FIX] Blindaje de modalHandler**: Eliminadas funciones legacy (`setupModalFallback`) que sobrescribían destructivamente el controlador maestro de modales, asegurando la estabilidad del sistema de visualización de empresas.
310. **[SISTEMA] Calendario Visual Administrativo**: Implementada una vista de cuadrícula mensual (1-30 días, Lunes-Domingo) en `admin-panel.html` utilizando FullCalendar v6. Esta vista permite a los administradores gestionar la agenda mediante interacción directa (clic en día para crear, clic en evento para editar), sincronizándose automáticamente con la API de eventos.
311. **[UI/UX] Switcher de Gestión**: Añadido un selector de vista (Tabla/Calendario) en el panel administrativo con transiciones GSAP, permitiendo una alternancia fluida entre la gestión de datos masivos y la planificación visual estratégica.
311. **[ARQUITECTURA] Master Calendar UI/UX (Notion Style)**: Refactorización integral del visor de agenda en el Dashboard. Se eliminó la dependencia de inyección de estilos vía JS, trasladando la arquitectura visual directamente al `<head>` para garantizar el renderizado inmediato de transparencias y eliminar el 'bloque blanco' discordante.
312. **[DISEÑO] Simetría Matemática**: Implementado un diseño de cabecera 'Master Grid' que asegura que los controles de navegación (Anterior/Hoy/Siguiente) y el título dinámico nunca se solapen, independientemente de la resolución de pantalla.
313. **[UX] Post-it Elite Experience**: Los eventos evolucionaron de simples bloques a tarjetas con efectos de cristal (*glassmorphism*), desenfoque de fondo (*backdrop-filter*) y sombras profundas, logrando una estética idéntica a Notion Calendar.
358. **[CALENDARIO] Inyección de Estilos Críticos**: Los estilos de FullCalendar (transparencias, Notion style) DEBEN estar definidos en un bloque `<style>` en el `<head>` del HTML. La inyección vía JS (`applyStyles`) es insuficiente para anular el parpadeo blanco inicial.
359. **[CALENDARIO] Nuclear Header Reset**: Para eliminar recuadros blancos en calendarios oscuros, usar siempre: `.fc-col-header, .fc-col-header-cell { background: transparent !important; }`.
360. **[CALENDARIO] Altura del Visor**: Asegurar siempre un `min-height: 850px !important` para que la cuadrícula mensual de 30 días sea plenamente visible sin recortes.
361. **[TAILWIND VS FULLCALENDAR] Prevención de Desfases en el Grid**: 
    - **NUNCA** utilices selectores CSS forzados como `#calendar th { border: none !important; }` o `#calendar td { padding: 4px !important; }` para estilar la cabecera geométrica de FullCalendar. Hacerlo causa un desbalance entre las dimensiones calculadas por JS para el `<colgroup>` del head y el body, desplazando las columnas hacia la derecha en colisión con el Tailwind Preflight.
    - **LA FORMA CORRECTA**: Utilizar exclusivamente las variables CSS nativas (`--fc-border-color`, `--fc-page-bg-color`) o añadir padding a los componentes internos usando sus wrappers (`.fc-col-header-cell-cushion`).
362. **[CALENDARIO] Vista Mensual Estricta**: Al configurar `dayGridMonth`, añade `showNonCurrentDates: false`, `weekNumbers: false` y `fixedWeekCount: false` en tu JS para evitar que la semana inicial inicie con días "huérfanos" (ej. 23, 24 del mes anterior), previniendo confusión al usuario sobre el "orden del 1 al 30".
363. **[CALENDARIO] Geometría Flex y Contención Visual**: Tailwind resetea las tablas perdiendo su `width 100%`. SIEMPRE aplica `table-layout: fixed !important; width: 100% !important; margin: 0 !important;` tanto a `.fc-scrollgrid` como a `table`. Además, en pantallas ultrawide (ej. max-w-7xl), el calendario puede parecer vacío con "LUN" flotando lejos. Aplícale un `max-width` (ej. 1000px) y centralo (`margin: 0 auto`) para acotar el widget.
364. **[CALENDARIO] Post-its vs Dots**: En vista Mensual (`dayGridMonth`), FullCalendar por defecto dibuja eventos Timed (con hora) como pequeños puntos o diminutos bloques ilegibles. Para forzar que TODO se vea como "Post-It" sólido configurado desde tu JS, usa la propiedad `eventDisplay: 'block'` global.
365. **[DOM DESTRUCTIVO] El Selector Comodín (\*) es Reactivo y Venenoso**: NUNCA apliques reglas como `.max-w-7xl * { padding-left: 2rem !important; }` intentando hacer "responsive fixing" a contenedores principales. Esto inyecta 2rem de padding RECURSIVO a cada elemento descendiente. Un widget con un DOM de 7 niveles (`<table class="fc"> <thead> <tr> <th> <div> <table_inner> <tr> <th>`) sumará +160px de padding interno, desplazando por completo los días de la semana hacia la derecha y rompiendo irreparablemente la cuadrícula.
366. **[CALENDARIO] Multi-Day Layout Intacto**: NUNCA sobreescribas el `display`, `margin` ni el `overflow` nativo del bloque `.fc-event` de FullCalendar en vista mensual para "imitar" notion. Esto estrella el cálculo matemático interno del JS que hace que un evento abarque 3 días unificadamente (Ej. "Miércoles a Viernes"), provocando que el texto se desconecte ("flote") y el cuadro se dibuje roto. Modifica SOLAMENTE la superficie óptica (`border-radius`, `box-shadow`) y gestiona el color desde JS en `eventDidMount`.

### 🐛 Errores Externos Reportados (Fuera de Workspace)

- **Fatal error: Foreign key violation en `calendario_editorial.php`**: Reportado error de integridad referencial al intentar insertar `creado_por = 28` en `df_eventos_editoriales`.
  - **Causa**: El ID de usuario 28 no existe en la tabla `administradores`.
  - **Recomendación**: Implementar validación de existencia de usuario antes de INSERT/UPDATE y verificar que la limpieza de la base de datos no deje sesiones huérfanas.

---

> Estas reglas previenen la re-introducción de bugs ya corregidos.

### Carrusel y Logos
- **SIEMPRE incluir `<div id="carouselTrack" class="carousel-track">` en `.loop-images`** — sin él, `renderCarruselEmpresas()` sale silenciosamente y el carrusel queda vacío.
- **NUNCA comparar solo `length` en `refrescarSilencioso`** — comparar hash de datos relevantes (`id`, `logo_url`, `nombre`) para detectar cambios de logo o nombre sin cambio de conteo.
- **SIEMPRE añadir cache-busting a URLs de uploads locales** — `?t=${updated_at}` en `img src` para uploads en `uploads/empresas/` y similares.

### Módulo Empresas
- NUNCA usar `if (valor)` para decidir si se agrega un campo al FormData — siempre `append` incondicionalmente.
- SIEMPRE incluir `estado`, `fecha_convenio`, `condiciones` en el SELECT de `listarEmpresas` y `obtenerEmpresa`.
- NUNCA añadir inputs al modal HTML sin `name=""` — sin él `FormData` no captura el campo.
- Los campos `DATE` de MySQL DEBEN recibir `null` (no `""`) cuando están vacíos.
- El campo HTML `contacto_persona` mapea a `contacto_nombre` en BD — mantener ese mapeo.
- **Estándar de Modal Dividido**: Usar arquitectura de doble columna (Izquierda: Identidad/Badges, Derecha: Detalles con Scroll). Aplicar `padding-top` para evitar colisión con `claut-header`.

### Seguridad
- NUNCA hardcodear credenciales en ningún archivo `.php` ni `.md` — solo en `.env`.
- NUNCA usar `FILTER_SANITIZE_STRING` — deprecado PHP 8.1, usar `htmlspecialchars()`.
- NUNCA exponer `$_SESSION` completo en respuestas JSON.
- SIEMPRE usar soft delete (`activo = 0`) — nunca `DELETE` físico en tablas de entidades.

### UI/UX
- Usar siempre el `claut-header` horizontal como header maestro.
- Centrado de menú desktop: `position: absolute; left: 50%; transform: translateX(-50%)`.
- Si encuentras CSS incrustado masivo, moverlo a archivo `.css` externo.
- Usar variables de espaciado (`--space-1` a `--space-12`).
- **NUNCA usar `apple-icon.png` como logo del header o inicio de sesión** — siempre `logo-ct.png` (para oscuro) o `logo-ct-dark.png` (para claro).
- **NUNCA dejar imágenes sin restricción de tamaño (`w-*`)** — el sistema debe manejar resoluciones Retina (>2000px) sin romper el layout.
- **SIEMPRE usar `overflow: hidden` en el carrusel de banners** para evitar fugas visuales entre los lados de la pantalla dividida.
- **NUNCA agregar `text-white` inline a `<i>` o `<span>` dentro de `.claut-nav-item`** — los estilos los controla `header-navbar.css`.
- **SIEMPRE incluir `adminNavItem` y `adminNavItemBottom`** con `style="display:none;"` en header nav y bottom nav — auth-session.js los muestra/oculta según rol.
- **SIEMPRE incluir el bloque completo de actions**: `headerMessagesBtn` + `notificationBadge` + `headerUserDropdown` con nombre/rol/logout — no simplificar a un solo link.
- **NUNCA dejar legacy navbars negros (`bg-black`) dentro de `<main>`** — esos son residuos del template Argon y deben eliminarse al homologar.
- **SIEMPRE linkear `header-navbar.css` y `estilos-empresas.css`** en el `<head>` al homologar una vista de empresas.
- **El label del link a `empresas-convenio.html` en bottom nav debe ser "Socios"** — no "Convenios".
- **SIEMPRE incluir el `loading-container` div** al inicio del body (después de `<body>`) con los tres `.loading-ring` y el logo.
- **SIEMPRE incluir la sección `porsche-navbar`** (breadcrumb bar) después de `</header>`.

---

## 🔒 Mapa de Seguridad del Sistema

| Componente | Archivo | Estado |
|-----------|---------|--------|
| CSRF Protection | `middleware/csrf-protection.php` | ✅ Implementado |
| Rate Limiter | `middleware/rate-limiter.php` | ✅ Implementado |
| JWT Validator | `middleware/jwt-validator.php` | ✅ Implementado (fuente de verdad) |
| Input Validator | `utils/input-validator.php` | ✅ Implementado |
| File Upload Validator | `utils/file-upload-validator.php` | ✅ 9 capas de validación |
| Security Logger | `utils/security-logger.php` | ✅ JSON logs + rotación |
| Output Sanitizer | `utils/output-sanitizer.php` | ✅ Implementado |
| Security Headers | `middleware/security-headers.php` | ✅ Implementado |
| `.env` credentials | `build/.env` | ✅ En `.gitignore` |

**JWT duplicado**: Hay 3 implementaciones JWT (`jwt_helper.php`, `jwt_helper_fixed.php`, `middleware/jwt-validator.php`). La fuente de verdad es `middleware/jwt-validator.php` — las otras son legacy.

---

## 📧 Sistema de Correo SMTP — Implementado 2026-04-01

### Cuenta institucional
- **Email**: `auxsistemas@clautmetropolitano.mx`
- **Servidor**: `smtp.hostinger.com:465` (SSL)
- **Password**: En `build/.env` como `MAIL_PASS` — obtener desde hPanel Hostinger → Emails → Accounts
- **Documentación completa**: `build/EMAIL_SETUP.md`

### Archivos Creados
| Archivo | Propósito |
|---------|----------|
| `build/services/EmailService.php` | Motor central — métodos por tipo de correo |
| `build/services/phpmailer/` | PHPMailer v6 standalone (sin Composer) |
| `build/api/auth/forgot-password.php` | POST: solicitar reset de contraseña |
| `build/api/auth/reset-password.php` | GET: validar token / POST: nueva contraseña |
| `build/api/auth/verify-account.php` | GET: confirmar email / POST: reenviar |
| `build/api/auth/notify-approval.php` | POST (admin): notificar aprobación/rechazo |
| `build/setup/migrations/20260401_email_tokens.sql` | Tabla `email_tokens` |
| `build/EMAIL_SETUP.md` | Guía SMTP para operaciones |

### Tabla `email_tokens`
- Tokens de 64 chars hex (`bin2hex(random_bytes(32))`)
- Tipos: `password_reset` (expira 1h) y `account_verify` (expira 24h)
- Rate limit: máximo 3/hora por email en reset, 2/hora en verify
- Columna `usado = 1` post-uso (tokens de un solo uso)
- `usuarios_perfil` tiene nueva columna `email_verificado TINYINT(1)`

### Flujo Modal en Sign-In
- El link "¿Olvidaste?" abre `#modalForgot` en `sign-in.html` (no navega a otra página)
- Si la URL tiene `?reset_token=xxx`, se auto-valida y abre `#modalReset`
- Si la URL tiene `?verify=success|used|expired|invalid`, banner contextual aparece
- Post-reset: token se limpia de URL con `history.replaceState()`

### Errores a NO repetir en Email
- **NUNCA** borrar el `try/catch` del hook de correo en `register.php` — si falla el email, el registro ya ocurrió y es exitoso
- **NUNCA** exponer si un email existe o no en forgot-password (respuesta siempre genérica)
- **NUNCA** usar `mail()` nativo de PHP en producción — siempre PHPMailer con SMTP auth para evitar spam
- **NUNCA** dejar `MAIL_PASS` en `.env.example` con valores reales — solo placeholders

---

## 🤖 Subagentes Disponibles

El proyecto tiene workflows en `.agents/` y `.agent/` para tareas específicas:

| Workflow | Archivo | Propósito |
|---------|---------|-----------|
| Refactor JS | `.agent/workflows/refactor_js.md` | Limpiar, modularizar y minificar JS con esbuild |
| Security Guidelines | `.agents/workflows/security_guidelines.md` | Checklist de seguridad para nuevas implementaciones |
| Setup API | `.agents/workflows/setup_api.md` | Plantilla estándar para crear nuevos endpoints |

**Cuándo usar subagentes**:
- Tareas de exploración masiva de código (usar `Explore` subagent)
- Refactorizaciones que tocan muchos archivos en paralelo (usar `General Purpose`)
- Planificación de features complejas con trade-offs (usar `Plan` subagent)
- Búsqueda de patrones en múltiples archivos (usar `Explore` con thoroughness "very thorough")

---

## 📋 Deuda Técnica Conocida (Priorizada)

### Alta Prioridad
- [x] Modernización del Modal de Empresas (`empresas-convenio.html`)
    - [x] Reestructurar el contenedor principal con el nuevo diseño de panel dividido (Side-by-Side).
    - [x] Ajustar dimensiones y posicionamiento para evitar el navbar del clúster.
    - [x] Implementar botones de acción (Llamada, Correo, Sitio Web) con diseño Porsche.
    - [x] Estilizar la columna derecha con scroll independiente y tipografía mejorada.
- [x] Documentación y Estándares
    - [x] Actualizar `claude.md` con el nuevo estándar de modales divididos.
    - [x] Registrar errores evitados (colisión de navbar y sobrescritura de modalHandler).
- [x] Verificación Final
    - [x] Validar responsividad (apilado vertical en móviles).
    - [x] Comprobar legibilidad con textos largos.
- [x] **Sistema de Correo SMTP** — 2026-04-01
    - [x] EmailService.php con PHPMailer standalone
    - [x] Endpoints: forgot-password, reset-password, verify-account, notify-approval
    - [x] Modal "¿Olvidaste?" en sign-in.html
    - [x] Tabla email_tokens con rate limiting
    - [x] Hook de correo de bienvenida en register.php
    - [ ] **PENDIENTE**: Configurar `MAIL_PASS` en `.env` (obtener desde hPanel Hostinger)
    - [ ] **PENDIENTE**: Ejecutar migración `20260401_email_tokens.sql` en producción
- [ ] Consolidar 3 APIs de empresas en una sola (`empresas-simple.php` es la activa)
- [ ] Consolidar 3 implementaciones JWT en `middleware/jwt-validator.php`
- [ ] Eliminar exposición de `$_SESSION` en `login-compatible.php` (L38)
- [ ] Añadir autenticación a páginas `demo_*.html` o eliminarlas de producción

### Media Prioridad
- [ ] Minificar archivos HTML grandes (`dashboard.html` 437KB, `admin-panel.html` 149KB)
- [ ] Unificar función `responderJSON` / `jsonResponse` (firmas distintas en diferentes archivos)
- [ ] Eliminar fallback SQLite de `config/database.php`

### Baja Prioridad
- [ ] Implementar MVC + autoloading PSR-4 (Fase 4)
- [ ] Tests unitarios con PHPUnit (Fase 5)
- [ ] CI/CD con GitHub Actions (Fase 5)
