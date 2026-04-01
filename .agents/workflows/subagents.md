---
description: Estrategia de Subagentes para Claut Intranet — cuándo y cómo usar agentes especializados
---

# Subagentes en Claut Intranet

Este proyecto puede aprovechar subagentes para paralelizar trabajo, explorar código masivo y planificar refactorizaciones complejas sin perder contexto.

---

## Subagentes Disponibles

| Tipo | Cuándo usarlo | Ejemplo de tarea |
|------|--------------|-----------------|
| **Explore** | Explorar código para entender patrones, buscar dónde se usa algo | "¿Dónde se valida la sesión en todos los archivos?" |
| **General Purpose** | Tareas autónomas de múltiples pasos con escritura de código | Refactorizar un módulo completo, crear feature end-to-end |
| **Plan** | Planificar features complejas con trade-offs y decisiones arquitectónicas | Migrar a MVC, implementar sistema de cache |

---

## Casos de Uso Concretos para Este Proyecto

### 1. Auditoría de Seguridad
```
Agente: Explore (thoroughness: "very thorough")
Tarea: "Busca todos los archivos PHP en build/api/ que no usen 
        middleware/security-headers.php ni validen sesión. 
        Lista los archivos vulnerables con su línea de inicio."
```

### 2. Refactorización JS Masiva
```
Agente: General Purpose
Workflow: .agent/workflows/refactor_js.md
Tarea: Consolidar los archivos JS duplicados indicados en ANALISIS_SISTEMA.md,
       extraer módulos reutilizables a build/js/modules/
```

### 3. Consolidación de APIs Duplicadas
```
Agente: Plan
Tarea: "Planificar la consolidación de empresas.php, empresas_convenio.php 
        y empresas-simple.php en un solo endpoint sin romper funcionalidad 
        existente. Identificar todos los consumidores de cada API."
```

### 4. Exploración de Consumidores de una API
```
Agente: Explore (thoroughness: "medium")
Tarea: "¿Qué archivos HTML y JS consumen api/empresas.php, 
        api/empresas_convenio.php y api/empresas-simple.php?
        Listar con nombre de archivo y línea."
```

### 5. Revisión de Deuda Técnica
```
Agente: Explore (thoroughness: "very thorough")
Tarea: "Busca todos los console.log en build/js/ que no sean 
        console.error o console.warn. Lista archivo, línea y contenido."
```

---

## Workflows Existentes

Los workflows actúan como prompts predefinidos para subagentes. Están en:

```
.agent/workflows/
└── refactor_js.md          ← Limpieza y minificación de JS con esbuild

.agents/workflows/
├── setup_api.md            ← Crear nuevo endpoint estándar
├── security_guidelines.md  ← Checklist de seguridad
├── skill_api.md            ← Skill: patrón completo de endpoint API
├── skill_empresa_crud.md   ← Skill: reglas del módulo de empresas
└── skill_security.md       ← Skill: validaciones y seguridad
```

### 6. Debug de Imágenes / Carrusel No Renderiza
```
Agente: Explore (thoroughness: "medium")
Tarea: "En empresas-convenio.html, busca:
        1. El elemento HTML con id='carouselTrack' — ¿existe en el DOM?
        2. Qué JS llama a getElementById('carouselTrack')
        3. Qué sección HTML contiene class='carousel-track'
        Retorna líneas exactas y si hay mismatch entre JS y HTML."
```

### 7. Auditoría de Cache-Busting en Imágenes
```
Agente: Explore (thoroughness: "quick")
Tarea: "Busca todos los <img src> y template literals con logo_url en 
        build/js/*.js y build/*.html. Lista cuáles NO tienen ?t= o cache-busting
        en URLs de uploads/. Archivo y línea."
```



Para mejores resultados al usar un subagente:

1. **Sé específico sobre qué archivos explorar**: mencionar directorio y extensiones.
2. **Define el output esperado**: "retorna una lista de...", "retorna el código de...".
3. **Indica si debe escribir código o solo explorar**: subagentes de exploración no modifican archivos.
4. **Especifica el nivel de thoroughness para Explore**: `quick`, `medium`, `very thorough`.

### Ejemplo de prompt efectivo para subagente:
```
"Usando thoroughness 'very thorough', explora todos los archivos en 
build/api/ y build/js/ y encuentra:
1. Todos los fetch() o XMLHttpRequest que apunten a 'empresas_convenio.php'
2. Todos los require_once que incluyan 'empresas_convenio.php'
Retorna lista con: archivo, línea, fragmento de código relevante."
```

---

## Limitaciones Actuales

- Los subagentes son **stateless**: no recuerdan conversaciones anteriores.
- Para contexto del proyecto, deben leer `claude.md` al inicio.
- No pueden acceder a la base de datos directamente — solo a archivos.
- Cambios de subagentes requieren revisión manual antes de hacer deploy.
