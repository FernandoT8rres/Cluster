---
description: despliega un Subagente de Refactorización JS (Analiza, Elimina y Minifica)
---

# 🤖 Subagente de Refactorización JS

Este workflow inicializa tu asistente como un **Subagente Especializado en Refactorización**. 
Su labor consiste en limpiar, modularizar (ES6) y minificar el código JavaScript (`build/js/` y `build/assets/js/`).

## Paso 1: Limpieza de Archivos Legacy
// turbo-all
Ejecuta la limpieza inicial eliminando todos los archivos de desarrollo redundantes o experimentales mencionados en `ANALISIS_SISTEMA.md`.

```bash
# Limpiar JS Root
rm -f build/dashboard-slider-fix.js build/announcements-fix.js build/frontend-integration.js build/anuncios-mejorados.js

# Limpiar JS Carpeta
rm -f build/js/dashboard-simple.js build/js/empresas-simple-viewer.js build/js/empresas-evervault.js build/js/empresas-analisis.js build/js/gestor-datos-reales.js build/js/grafico-seccion-correcta.js build/js/bulletin-database.js
```

## Paso 2: Análisis de Redundancias
Busca funciones globales que se repiten en muchos archivos (ej. inicializaciones de Swiper, validaciones de auth, modales).
- Agrupa todas las llamadas API en `build/js/modules/api.js`.
- Envuelve las lógicas de UI en `build/js/modules/ui.js`.
- Extrae la validación de sesión a `build/js/modules/auth.js`.

## Paso 3: Inicialización de ESBuild (Configuración de Bundler Subagente)
Configura el empaquetado del sistema instalando `esbuild`.

```bash
cd build
npm init -y
npm install esbuild --save-dev
```

## Paso 4: Creación de Script Empaquetador
Crea un archivo de configuración `build.js` que el subagente ejecutará para minificar los archivos exportados.

```javascript
// file: build/builder.js
const esbuild = require('esbuild');

esbuild.build({
  entryPoints: ['js/index.js'], // Punto de entrada refactorizado modular
  bundle: true,
  minify: true,
  sourcemap: false,
  format: 'esm',
  outfile: 'dist/claut-core.min.js',
}).catch(() => process.exit(1));
```

## Paso 5: Ejecución y Reemplazo
- Ejecuta `node builder.js`.
- Cambia progresivamente las etiquetas `<script>` en las páginas HTML para importar un solo archivo empaquetado, borrando miles de líneas desperdiciadas y dependencias sueltas.
