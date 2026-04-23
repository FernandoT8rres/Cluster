# Plan de Implementación: Calendario Visual Administrativo — Claut Intranet

Este documento detalla la estrategia para integrar una vista de calendario interactiva en el panel administrativo, permitiendo a los administradores visualizar y gestionar eventos directamente sobre una cuadrícula mensual (1-30/31 días), sincronizada con el Dashboard de socios.

---

## 🎯 Objetivo
Proporcionar una interfaz visual "Premium Porsche" dentro de `admin-panel.html` que permita el control directo de la agenda corporativa, complementando la vista de tabla actual con una cuadrícula de calendario interactiva.

---

## 🏗️ Fases de Implementación

### Fase 1: Inyección de Dependencias
- **FullCalendar v6**: Añadir los scripts y estilos necesarios (CDN) en `admin-panel.html` para habilitar el motor de calendario.
- **GSAP**: Asegurar que las animaciones de transición entre "Tabla" y "Calendario" sean cinemáticas.

### Fase 2: Rediseño de la Sección de Eventos (`admin-panel.html`)
- **Switcher de Vista**: Crear un control (toggle) para alternar entre "Vista de Tabla" y "Vista de Calendario".
- **Contenedor Visual**: Implementar un bloque Glassmorphism para el calendario (#adminCalendar).
- **Consistencia de Diseño**: Mantener los colores Claut Red (`#C7252B`) y acentos Bluetooth Blue (`#3B82F6`).

### Fase 3: Lógica de Control Administrativa (`js/admin-eventos.js`)
- **Inicialización**: Crear la instancia de `adminCalendar` en modo `dayGridMonth` (1-30 días).
- **Interacción**:
    - **Click en Día**: Abrir automáticamente el modal de "Nuevo Evento" pre-cargando la fecha seleccionada.
    - **Click en Evento**: Abrir el modal de edición del evento existente.
    - **Sincronización**: Al guardar/editar/eliminar un evento, refrescar tanto la tabla como el calendario administrativo sin recargar la página.

### Fase 4: Sincronización con el Dashboard
- Verificar que las APIs de `eventos.php` retornen los campos necesarios para que el Dashboard (que ahora usa vista de Agenda/Lista) muestre la misma información de manera coherente.

---

## 🔧 Archivos a Modificar
1.  `build/admin-panel.html`: UI (Scripts + Switcher + Calendar Container).
2.  `build/js/admin-eventos.js`: Lógica de integración y renderizado del calendario.
3.  `claude.md`: Documentar esta nueva capacidad de control visual.
4.  `build/ANALISIS_SISTEMA.md`: Registrar hito de arquitectura.

---

## 🛡️ Consideraciones de Seguridad
- **Validación de Sesión**: La carga del calendario y sus eventos solo se disparará si el usuario es `ADMIN`.
- **Sanitización**: Todas las entradas del calendario visual pasarán por el validador central antes de guardarse en la BD.

---

## ⏳ Cronograma Estimado (Sesión Actual)
- **Min 0-5**: Inyección de FullCalendar.
- **Min 5-15**: Implementación del Switcher y UI.
- **Min 15-25**: Integración JS y sincronización CRUD.
- **Min 25-30**: Documentación y pruebas.
