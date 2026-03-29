---
description: Skill reutilizable — Estabilización de FullCalendar v6 dentro de entornos Tailwind CSS
---

# Skill: Integración de FullCalendar con Tailwind CSS (Prevención de Desfases)

Este skill es obligatorio cuando se requiere implementar o debuggear la librería **FullCalendar v6** en un proyecto que utilice **Tailwind CSS**.

## El Problema Fundamental
Tailwind CSS incluye un reseteo de navegador llamado **Preflight**. Comúnmente se cree que la falta de bordes (`border-width: 0`) rompe FullCalendar, pero **FullCalendar v6 maneja Preflight correctamente a través de sus propias inyecciones de CSS**. 

El VERDADERO error ocurre cuando los programadores intentan sobrescribir el CSS del calendario forzando estilos (`padding`, `border`, `border-collapse`) usando `!important` sobre selectores de tabla específicos como `.fc-col-header-cell` o `th`. 
Esto causa una **desincronización matemática** entre el `<colgroup>` de las cabeceras (LUN, MAR) y las celdas del cuerpo del calendario. Como resultado, **la cuadrícula interna hace flex-wrap, se descuadra hacia la derecha, o deja grandes espacios vacíos en la tabla.**

## La Regla de Oro Quirúrgica
**NUNCA** uses selectores nucleares como `#calendar th { border: none !important; padding: 0 !important; }`.
La arquitectura de FullCalendar depende simétricamente de que los anchos concuerden entre head y body.

### 1. Variables CSS Locales (El Estándar Correcto)
Para modificar colores, márgenes o fondos, limítate EXCLUSIVAMENTE al contenedor padre y sus variables nativas provistas por la librería:

```css
/* CORRECTO: Customización vía Variables de FullCalendar y Prevención Layout */
#dashboard-preview-calendar {
    --fc-border-color: rgba(255, 255, 255, 0.15); /* Aumentar para hacer visible el grid */
    --fc-page-bg-color: transparent;
    --fc-neutral-bg-color: rgba(255, 255, 255, 0.05); /* Cabeceras */
    width: 100%;
    display: block;
}

/* FIX CRÍTICO: Anular contracción Flex/AutoLayout de Tailwind sobre tablas hijas */
#dashboard-preview-calendar .fc-scrollgrid,
#dashboard-preview-calendar table {
    width: 100% !important;
    table-layout: fixed !important;
    margin: 0 !important;
}
```

### 2. Espaciados Internos Seguros
Si necesitas padding o tipografía custom en las cabeceras (ej. Días LUN, MAR), aplica estilo SIEMPRE al `.fc-col-header-cell-cushion` (el span interno que envuelve el texto), **nunca** a la celda `<th class=".fc-col-header-cell">` que maneja el esqueleto de la tabla:

```css
/* CORRECTO: Estilizar el Cushion interno en vez de la celda */
#dashboard-preview-calendar .fc-col-header-cell-cushion {
    color: #94a3b8 !important;
    padding-top: 10px;
    padding-bottom: 10px;
    display: inline-block;
}
```

### 3. Evitando la confusión "Días Huérfanos" (Del 1 al 31)
En la `dayGridMonth` clásica, FullCalendar renderiza días finales del mes pasado y días iniciales del próximo para llenar el cuadro. Si el cliente o diseñador se queja de un "orden que no va del 1 al 30", oculta estos días grises en JS:

```javascript
const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    firstDay: 1, // Lunes
    fixedWeekCount: false, // Permite que el mes tenga 4, 5 o 6 semanas flexibles
    showNonCurrentDates: false, // ESTA ES LA CLAVE: esconde 23, 24 del mes pasado
    // ...
});
```
Con esto, la cuadrícula mostrará estrictamente bloques numéricos iniciando desde el número 1 del mes actual hacia adelante.
