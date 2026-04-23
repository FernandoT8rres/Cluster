# 🧠 Memoria del Proyecto: Clúster Intranet (v2.3)

## 🎯 Visión General
Plataforma integral para la gestión de socios y empresas del Clúster Metropolitano. El objetivo es proporcionar una interfaz premium (Glassmorphism/Apple-inspired) que permita a los administradores gestionar el directorio y a los socios actualizar su propia información mediante un sistema de solicitudes de revisión.

## 🛠️ Comandos y Flujo de Trabajo
- **Despliegue**: Los cambios se realizan en la carpeta `/build/` para pruebas antes de ser sincronizados al servidor de producción vía FTP/SFTP por el usuario.
- **Entorno Local**: `/Users/fernandotorres/Desktop/Claut_BD/`
- **URLs Críticas**:
  - `demo_empresas.html`: Panel administrativo de empresas.
  - `profile.html`: Perfil del socio y gestión de "Mi Empresa".
  - `api/empresas-simple.php`: Backend principal de empresas.
  - `api/perfil_empresa.php`: Gestión de datos desde el perfil.

## 🎨 Guía de Estilo (Aesthetics)
- **Core**: Vanilla JS + Tailwind CSS.
- **Diseño**: Glassmorphism (transparencias, desenfoques en fondo).
- **Colores**:
  - Rojo Clúster: `#C7252B` (Primario)
  - Fondo: Oscuro (`#1D1D1F`) con gradientes.
  - Acentos: Oro/Amarillo para estados destacados.
- **Tipografía**: Inter / Open Sans.

## 🔒 Seguridad y Acceso
- **Roles**: `admin`, `Administrador`, `root` tienen acceso al panel `demo_empresas.html`.
- **Sesiones**: Gestionadas mediante `session-config.php` y validadas en cada API.
- **Asignación**: Las empresas se vinculan a los usuarios mediante `admin_usuario_id`.

## 📝 Bitácora de Errores (Error Log)

### BUG-006: Fallo de Carga en Perfil de Empresa
- **Síntoma**: El perfil mostraba "No se pudo cargar la información" a pesar de que la API devolvía éxito.
- **Causa**: Error de referencia en `profile.html`. Se intentaba acceder a `result` en lugar de `resultData` dentro del callback de la promesa del fetch.
- **Solución**: Corregida la referencia a la variable de respuesta de la API.

### BUG-007: Menú Lateral (Sidebar) Persistent
- **Síntoma**: El sidebar no se cerraba correctamente en dispositivos móviles o tras interactuar con él.
- **Causa**: Falta de lógica de *toggle* para las clases CSS de Tailwind.
- **Solución**: Refactorización del script de navegación para manejar estados `hidden` y `expanded`.

### BUG-008: Ruptura de Layout Flexbox (El formulario "salta" de la tarjeta)
- **Síntoma**: Partes del formulario en `demo_empresas.html` (como el botón Guardar o la Información de Registro) se salían del área con scroll e interrumpían el header modal.
- **Causa**: Etiquetas `</div>` extras/huérfanos procedentes de ediciones mal cerradas rompían la contención vertical del div maestro flex `flex-1 overflow-y-auto`.
- **Solución**: Depurar el árbol DOM del HTML usando la indentación para identificar cierres prematuros y borrarlos.

### BUG-009: Pestañas Empalmadas en Profile (Superposición)
- **Síntoma**: Al acceder a Mis Datos / Mi Empresa la información se apilaba verticalmente en `profile.html` por debajo del header personal.
- **Causa**: TailwindCSS y Argon no tenían definida la clase oculta/activa nativamente. Todos los `.tab-pane` se renderizaban como bloque por defecto.
- **Solución**: Insertar una regla de transición en línea `<style>` forzando `.tab-pane { display: none; opacity: 0; }` y habilitando `.active`.

### BUG-010: Scripts Argon Legacy que no existen en producción (404)
- **Síntoma**: Consola muestra múltiples errores 404 al cargar `profile.html`: `popper.min.js`, `bootstrap.min.js`, `dragula.min.js`, `jkanban.js`, `smooth-scrollbar.min.js`, `argon-dashboard-tailwind.js`.
- **Causa**: `profile.html` heredó los tags `<script>` del template Argon original, pero esos archivos no están en el servidor de producción de Clúster Intranet (solo existe `claut-core.min.js`).
- **Solución**: Eliminar **todos** los `<script src="./assets/js/core/...">` y `<script src="./assets/js/plugins/...">` del archivo. Solo deben quedar: `auth-session.js`, `session-security.js`, `header-navbar.js`, `loading-screen.js` y `claut-core.min.js`.
- **Regla**: NUNCA incluir scripts de Argon Dashboard en páginas nuevas o rediseñadas. El sistema de producción usa `dist/claut-core.min.js`.

### BUG-011: URL del Dominio con tilde (clúster vs claut)
- **Síntoma**: La solicitud `PUT` a `perfil_empresa.php` fallaba con `Load failed` (sin conectar al servidor).
- **Causa**: La función `determineApiUrl()` en `profile.html` usaba el hostname `'intranet.clústermetropolitano.mx'` (con ú - carácter UTF-8) en lugar del hostname real `'intranet.clautmetropolitano.mx'`. JavaScript nunca activaba la URL absoluta de producción, y la relativa `./api/perfil_empresa.php` resolvía a la raíz del servidor que devolvía error de red.
- **Solución**: Reescribir la condición usando el dominio correcto: `hostname.includes('clautmetropolitano')` para detección más robusta.
- **Regla**: En cualquier script de detección de entorno, usar `clautmetropolitano.mx` (sin tilde, sin acento). Jamás el hostname con unicode `clúster`.

### BUG-012: Panel de Solicitudes no aparecía en demo_empresas.html
- **Síntoma**: El panel "Solicitudes de Cambio" permanecía oculto (`class="hidden"`) aunque había solicitudes pendientes en la BD.
- **Causa**: El `fetch()` en `admin-empresas.js → cargarSolicitudes()` no incluía `credentials: 'include'`. La API `solicitudes_empresa.php` requiere sesión válida y devolvía HTTP 401 sin la cookie, haciendo que `data.success` nunca fuera `true`.
- **Solución**: Agregar `credentials: 'include'` y `headers: {'Content-Type':'application/json'}` al fetch de solicitudes.
- **Regla**: TODOS los fetches que llamen a APIs PHP autenticadas deben incluir `credentials: 'include'`. Sin esto, la cookie de sesión no se envía y el servidor rechaza la petición.

### BUG-013: 404 en Notificaciones de Perfil (gestionar_usuarios.php)
- **Síntoma**: El panel de administración de usuarios intentaba cargar solicitudes de cambio pero fallaba con Error 404 al buscar `api_notificaciones_perfil.php`.
- **Causa**: El archivo API invocado por `loadProfileNotifications()` no existía en el servidor ni en el repositorio local.
- **Solución**: Creación del endpoint `/build/api_notificaciones_perfil.php` con soporte para acciones `listar`, `aprobar` (mapeo dinámico de campos a `usuarios_perfil`) y `rechazar`.
- **Regla**: Al implementar notificaciones de cambio en el frontend, asegurar que el endpoint backend correspondiente esté deployado en el mismo nivel de directorio o ruta relativa correcta.

### BUG-014: "ID inválido" al editar boletines + archivo no se guardaba
- **Síntoma**: Al hacer clic en "Actualizar Boletín" dentro del modal de edición, el servidor respondía `{ success: false, message: "ID inválido" }`. El archivo adjunto tampoco se guardaba al crear ni al editar.
- **Causa raíz (API)**: El `case 'PUT'` en `boletines_simple.php` usaba `parse_str(file_get_contents("php://input"), $_PUT)` para leer el body. `parse_str` **solo parsea `application/x-www-form-urlencoded`**, pero el frontend enviaba `FormData` (`multipart/form-data`). Por eso `$_PUT['id']` siempre llegaba vacío → `intval('') = 0` → "ID inválido". Además, `$_FILES` **solo se popula en requests POST**, nunca en PUT → los archivos tampoco se procesaban.
- **Causa raíz (Frontend)**: `updateStatistics()` referenciaba `document.getElementById('stats-info')` que no existe en el DOM → `TypeError: null is not an object`.
- **Solución**:
  1. Se eliminó el `case 'PUT'` del backend.
  2. El `case 'POST'` ahora maneja **create y update** según `$_POST['id'] > 0`. Esto permite usar `$_FILES` correctamente y conservar el archivo existente si no se sube uno nuevo.
  3. El `case 'DELETE'` se corrigió para aceptar el ID tanto por query string como por JSON body, y ahora también elimina el archivo físico con `unlink()`.
  4. En `demo_boletines.html`, `method: 'PUT'` → `method: 'POST'`. El campo `id` ya se incluía en FormData.
  5. Se agregó null-check: `const el = document.getElementById('stats-info'); if (el) el.textContent = ...`.
- **Archivos modificados**: `build/api/boletines_simple.php`, `build/demo_boletines.html`
- **Documentación completa**: `docs/architecture/BOLETINES_API.md`
- **Regla**: Para operaciones con `FormData` (multipart) en PHP, **siempre usar POST**. Los métodos PUT/PATCH no populan `$_POST` ni `$_FILES`; solo funcionan con `application/x-www-form-urlencoded` via `parse_str`.

### BUG-015: HTTP 400 "Error al crear el documento" en demo_documentos.html
- **Síntoma**: Al hacer clic en "Guardar" en el modal de creación, el servidor respondía HTTP 400 y el frontend mostraba siempre "Error al crear el documento" sin detalle.
- **Causa raíz 1 (API)**: El `case 'POST'` usaba `ApiValidator::validateAndSanitize()` con la regla `'titulo' => 'required|string|min:3|max:255'`. Títulos de menos de 3 caracteres retornaban HTTP 400.
- **Causa raíz 2 (formato de respuesta)**: `ApiValidator::errorResponse()` devuelve `{ error: "..." }` pero el frontend busca `result.message`. Siempre mostraba el fallback genérico.
- **Causa raíz 3 (Frontend)**: `handleSaveClick` lanzaba `console.error('EditingId está perdido!')` incluso durante creación, cuando `editingId = null` es el estado correcto. Falso positivo que confundía el diagnóstico.
- **Solución**:
  1. Se eliminó `ApiValidator` del `case 'POST'`. Validación directa con `empty($titulo)` y whitelist de visibilidades. Errores usan el campo `message`.
  2. Errores de upload de `$_FILES` muestran mensajes legibles según el código PHP de error.
  3. `handleSaveClick` solo intenta recuperar `editingId` si el modal está en modo "Editar" (detectado por el título). Sin `console.error` en creación.
- **Archivos modificados**: `build/api/documentos.php`, `build/demo_documentos.html`
- **Documentación completa**: `docs/architecture/DOCUMENTOS_API.md`
- **Regla**: NUNCA usar `ApiValidator` en endpoints que manejan archivos (`$_FILES`). Siempre usar validación directa PHP y asegurar que los errores usen el campo `message` para compatibilidad con el frontend.

### BUG-016: Selector de empresas vacío + registros duplicados en demo_descuentos.html
- **Bug A — Selector vacío**: El `<select>` mostraba el total (`11 disponibles`) pero sin opciones. Causa: el map en `loadEmpresas()` guardaba solo `nombre_empresa`, pero la BD usa el campo `nombre`. El `forEach` buscaba `empresa.nombre` (undefined post-map) → condición `if (id && nombre)` fallaba para todas las empresas → 0 opciones. Fix: preservar ambos campos en el map (`nombre` y `nombre_empresa`) para que el `forEach` siempre encuentre el valor.
- **Bug B — Doble guardado**: Cada clic generaba 2 registros. Causa: doble disparo de `saveDescuento()` — el botón era `type="submit"` y además `setupEventListeners()` añadía un listener `submit` al form. Al hacer clic, el form disparaba el submit event que lo llamaba dos veces. Fix: cambiar botón a `type="button" onclick="saveDescuento(event)"` y eliminar el `addEventListener('submit', saveDescuento)`.
- **Archivos modificados**: `build/demo_descuentos.html`
- **Documentación completa**: `docs/architecture/DESCUENTOS_API.md`
- **Regla**: Nunca registrar `submit` event en un form que ya tiene botón con `onclick`. Un único punto de entrada por acción.

### BUG-017: Botón "Crear Comité" faltante en demo_comite.html
- **Síntoma**: No había forma de abrir el formulario en modo creación. El acordeón `#acordeonFormulario` existía pero no había ningún botón que lo abriera ni que llamara a `limpiarFormulario()`.
- **Causa raíz**: La barra de controles solo tenía el botón "Actualizar". `editComite(id)` sí abría el acordeón, pero no había punto de entrada equivalente para creación.
- **Solución**: Se agregó botón "Crear Comité" (rojo corporativo) en la barra de controles, y la función global `window.nuevoComite()` que limpia el form, resetea `comiteEditando = null`, actualiza el título del acordeón, lo abre y hace scroll suave al formulario.
- **Archivos modificados**: `build/demo_comite.html`
- **Documentación completa**: `docs/architecture/COMITES_API.md`
- **Regla**: Todo módulo CRUD debe tener un botón de creación visible en la barra de controles. No asumir que el usuario encontrará el formulario en un acordeón colapsado.

## 🚀 Próximos Pasos (En curso)
- [x] Sidebar → Header horizontal homologado en `profile.html`.
- [x] Eliminar scripts Argon legacy que causan 404.
- [x] Corregir URL del dominio en `determineApiUrl()`.
- [x] Panel de solicitudes en `demo_empresas.html` — agregar `credentials:include` al fetch.
- [x] **New**: Resolución 404 API Notificaciones de Perfil en `gestionar_usuarios.php`.
- [x] **New**: Rediseño Premium (Glassmorphism + Dark Mode) del módulo `gestionar_usuarios.php`.
- [x] **New**: Fix BUG-014 — Edición de boletines (PUT→POST, archivos adjuntos, null-check stats-info).
- [x] **New**: Fix BUG-015 — Creación de documentos (ApiValidator HTTP 400, formato error, falso editingId).
- [x] **New**: Fix BUG-016 — Descuentos: selector de empresas vacío (campo `nombre` vs `nombre_empresa`) + registros duplicados (doble submit).
- [x] **New**: Fix BUG-017 — Comités: botón "Crear Comité" faltante; se agregó botón + función `window.nuevoComite()`.
- [x] **New**: Fix BUG-018 — Eventos: URL de registro opcional, modal de detalle de notificaciones habilitado, y vista de detalles de registro en lista reactivada.
- [ ] **Subir via FTP** (pendiente — 5 archivos):
  - `build/api/documentos.php` → `public_html/api/documentos.php`
  - `build/demo_documentos.html` → `public_html/demo_documentos.html`
  - `build/demo_descuentos.html` → `public_html/demo_descuentos.html`
  - `build/demo_comite.html` → `public_html/demo_comite.html`
  - `build/demo_evento.html` → `public_html/demo_evento.html`
  - `build/js/demo-eventos.js` → `public_html/js/demo-eventos.js`
- [ ] Validar flujo completo: Empresa edita → solicitud llega → Admin aprueba en `demo_empresas.html`.
- [ ] Finalizar ajustes de responsividad extrema en tablas de `gestionar_usuarios.php`.




### Responsividad de Dashboard (`dashboard.html`)
* **Problema:** En pantallas grandes (mayores a 1440px), el contenido de la intranet se mostraba agrupado a la izquierda o con márgenes excesivos, dejando gran parte del ancho de la pantalla desaprovechado.
* **Solución:**
  * Se eliminaron los anchos máximos estáticos (`max-width: 1400px`) y márgenes fijos (`margin-left: 140px`) en pantallas `>1441px` para permitir un diseño verdaderamente fluido.
  * Se actualizó la clase `.max-w-7xl` para que en resoluciones ultra-anchas ocupe el `95%` del ancho total de la pantalla en lugar de quedar estancado en `80rem` (1280px).
  * Se corrigió el uso de `100vw` en `html, body` reemplazándolo por `width: 100%` para evitar conflictos de scrollbar horizontal.
  * Se deshabilitó un script inyectado al final del documento que sobrescribía de forma agresiva los márgenes (`margin: 0`), rompiendo el comportamiento `mx-auto` de centrado en contenedores internos.


### BUG-019: Retraso Crítico en Creación de Eventos
- **Síntoma**: Al crear un evento, la notificación de éxito tardaba varios segundos en aparecer, provocando que el usuario hiciera clic múltiples veces y creara duplicados.
- **Causa**: El API enviaba un correo electrónico síncrono vía SMTP antes de responder al navegador. Además, el frontend no tenía un bloqueo de peticiones concurrentes.
- **Solución**:
  1. Se implementó `fastcgi_finish_request()` y técnicas de flush en `api/eventos.php` para cerrar la conexión con el navegador **antes** de procesar el envío de correos.
  2. Se añadió una bandera `isSubmitting` en `demo-eventos.js` y `admin-calendar-master.js` para bloquear el botón de guardado.
- **Regla**: Todo proceso pesado (envío de correos, procesamiento de imágenes pesadas) debe ocurrir después de enviar la respuesta JSON al cliente.

### BUG-020: Sincronización Visual en Calendario (Cache y Estado)
- **Síntoma**: Al eliminar o crear registros en el calendario, los cambios no se reflejaban automáticamente; el usuario debía pulsar F5.
- **Causa**: El navegador cacheaba las respuestas de la API de eventos. Además, `refetchEvents()` no siempre forzaba un redibujado inmediato de los elementos eliminados.
- **Solución**: 
  1. Se agregó un timestamp dinámico (`&t=Date.now()`) a las URLs de consulta de eventos.
  2. Se implementó manipulación directa del DOM del calendario (`eventObj.remove()`, `addEvent`) para feedback instantáneo de milisegundos.
- **Archivos**: `build/js/admin-calendar-master.js`, `build/dashboard.html`.

### BUG-021: Navegación Bloqueada en Calendarios
- **Síntoma**: Los calendarios de la consola master y del dashboard estaban "congelados" en el mes actual sin botones para ver meses futuros.
- **Causa**: El diseño premium ocultaba el header nativo de FullCalendar y no se habían vinculado botones externos a los métodos `.prev()` y `.next()`.
- **Solución**: Creación de controles personalizados (Prev, Hoy, Next) y sincronización del título dinámico mediante el callback `datesSet`.
- **Archivos**: `build/calendario.html`, `build/dashboard.html`.
