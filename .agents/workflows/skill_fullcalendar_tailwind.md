---
description: Skill reutilizable — Estabilización de FullCalendar v6 dentro de entornos Tailwind CSS
---

# Skill: Integración de FullCalendar con Tailwind CSS (Preflight Fix)

Este skill es obligatorio cuando se requiere implementar o debuggear la librería **FullCalendar v6** en un proyecto que utilice **Tailwind CSS**.

## El Problema Fundamental
Tailwind CSS incluye un reseteo de navegador llamado **Preflight** que aplica por defecto las siguientes reglas globales:
```css
*, ::before, ::after { border-width: 0; border-style: solid; border-color: theme('borderColor.DEFAULT', currentColor); }
table { border-collapse: collapse; }
```

**FullCalendar v6** utiliza una arquitectura interna compleja (flexbox + grid + HTML tables) para calcular el tamaño de los días, ubicar eventos y renderizar la cuadrícula (`.fc-scrollgrid`).
Al recibir `border-width: 0`, las celdas de FullCalendar colapsan, los anchos se calculan mal (`NaN` o `0px`) y **la cuadrícula mensual se desglosa hacia la derecha o desaparece**.

## La Solución Quirúrgica
NUNCA uses selectores nucleares como `.fc * { border-width: 1px }`. Eso destruye barras de scroll invisibles y wrappers internos.
La solución exacta es **restaurar el borde SOLO en las etiquetas estructurales nativas** usadas por FullCalendar, preferiblemente escudadas dentro de un ID específico (ej. `#calendar-container`):

### 1. CSS a inyectar en el `<head>` o hoja global:
```css
/* Escudo protector contra Tailwind Preflight */
#ID_DEL_CONTENEDOR_CALENDARIO table,
#ID_DEL_CONTENEDOR_CALENDARIO th, 
#ID_DEL_CONTENEDOR_CALENDARIO td,
#ID_DEL_CONTENEDOR_CALENDARIO .fc-scrollgrid {
    border-width: 1px !important;
    border-style: solid !important;
    border-collapse: collapse !important;
    /* Define tu propio color para recuperar las líneas divisorias */
    border-color: rgba(255, 255, 255, 0.05) !important;
}
```

### 2. Altura de Renderizado
FullCalendar v6 necesita espacio. Si la cuadrícula se recorta, usa:
```css
#ID_DEL_CONTENEDOR_CALENDARIO {
    min-height: 850px;
}
```
O en la configuración JS: `{ height: 'auto', contentHeight: 'auto' }`.

## ¿Cómo verificar si funcionó?
1. Inspecciona en Chrome y busca elementos `.fc-daygrid-day-frame`.
2. Revisa que tengan dimensiones correctas (ej. `122.5px x 140px`).
3. Comprueba que las columnas (LUN, MAR, MIÉ...) no excedan el contenedor padre.
