---
description: Skill reutilizable — Estabilización y Auditoría de Componentes UI con Glassmorphism en Claut Intranet
---

# UI Glassmorphism Standardization & Repair (Skill)

**Objetivo:** Este skill debe ser cargado y ejecutado por el agente cuando se reporten discrepancias visuales de "fondos opacos", "imágenes tapadas por cuadros blancos" o similares en interfaces donde se espera un diseño transparente o inmersivo (.porsche-layout).

## Casos de Uso
1. **Carruseles Evervault o Complejos:** Elementos donde confluyen librerías externas (Three.js, animaciones espaciales) y el diseño interior asfixia la vista con un `bg-white` duro.
2. **Paneles de Control (Dashboards):** Tarjetas estáticas donde el background se rompe.
3. **Modales o Overlays.**

## Protocolo de Auditoría y Refactor
Cuando se te asigne "arreglar un problema de diseño blanco" o "ajustar el glassmorphism":

1. **Ubicar Contenedores Absolutos:**
   - Buscar `<div class="bg-white">` o `background: #ffffff;` en el contenedor padre.
   - **Acción:** Reemplazar por:
     \`\`\`css
     background: rgba(255, 255, 255, 0.03);  /* Transparencia oscura o clara dependiendo de contexto, usualmente 0.03 - 0.05 */
     backdrop-filter: blur(10px);
     border: 1px solid rgba(255, 255, 255, 0.05);
     \`\`\`
     *O si usa Tailwind puro:*
     \`\`\`html
     class="glass-card border-0 backdrop-blur-md"
     \`\`\`

2. **Liberación de Elementos Multimedia (`<img>`, `<video>`):**
   - Buscar imágenes con `background: #ffffff` y `padding`.
   - **Acción:** Las imágenes no suelen necesitar backgrounds si van dentro de un Glassmorphism. Eliminar color de fondo a `transparent`, reducir el `padding` para maximizar pantalla, añadir `filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));` para resaltar logos sin bordes, y asegurar `z-index` superior a 0 pero inferior al `overlay` de texto.

3. **Restructuración Tipográfica:**
   - Si se convierte un contenedor de `bg-white` a Glass (transparente oscuro), las letras pre-existentes probablemente sean `text-gray-900` (negras). 
   - **Acción:** Migrar las cabeceras a `text-white` y el cuerpo a `text-zinc-300` o `text-gray-200` para contraste completo con fondos espaciales/oscuros.

4. **Preservar el Layout (Grid):**
   - No alterar anchos ni altos absolutos si son usados como "canvas" por controladores `.js` (ej. GSAP o Evervault dependen del Width y Height estipulados para el bounding box).
