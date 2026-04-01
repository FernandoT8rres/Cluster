---
description: Skill reutilizable — Plantilla y patrón estándar para crear nuevos endpoints API REST en Claut Intranet
---

# Skill: Nuevo Endpoint API (Patrón Estándar)

Usar esta plantilla cada vez que se crea un endpoint nuevo para garantizar consistencia, seguridad y compatibilidad con el frontend.

---

## Plantilla Completa de Endpoint

```php
<?php
/**
 * API: [Nombre del módulo]
 * Descripción: [Qué hace este endpoint]
 */

// 1. Headers de seguridad — SIEMPRE antes de cualquier output
require_once __DIR__ . '/../middleware/security-headers.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Cambiar a dominio específico en producción
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// 2. Conexión BD — Singleton, nunca PDO directo
require_once __DIR__ . '/../config/database.php';

// 3. Función de respuesta estándar
function sendResponse(bool $success, string $message, $data = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode(
        ['success' => $success, 'message' => $message, 'data' => $data],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

// 4. Obtener acción del request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 5. Router principal
try {
    $db = Database::getInstance();

    switch ($action) {
        case 'listar':
            listar($db);
            break;
        case 'obtener':
            obtener($db);
            break;
        case 'crear':
            crear($db);
            break;
        case 'actualizar':
            actualizar($db);
            break;
        case 'eliminar':
            eliminar($db);
            break;
        default:
            sendResponse(false, 'Acción no válida', null, 400);
    }
} catch (Exception $e) {
    error_log('[API Error] ' . $e->getMessage());
    sendResponse(false, 'Error interno del servidor', null, 500);
}

// ─── Funciones ────────────────────────────────────────────────

function listar($db): void {
    $sql = "SELECT * FROM tabla WHERE activo = 1 ORDER BY id DESC";
    $rows = $db->selectAll($sql, []);
    sendResponse(true, 'Listado obtenido', ['items' => $rows]);
}

function obtener($db): void {
    $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$id) sendResponse(false, 'ID requerido', null, 400);

    $row = $db->selectOne("SELECT * FROM tabla WHERE id = ? AND activo = 1", [$id]);
    if (!$row) sendResponse(false, 'No encontrado', null, 404);

    sendResponse(true, 'Registro encontrado', $row);
}

function crear($db): void {
    $campo = trim($_POST['campo'] ?? '');
    if (empty($campo)) sendResponse(false, 'El campo es requerido', null, 400);

    $sql = "INSERT INTO tabla (campo, activo, created_at) VALUES (?, 1, NOW())";
    $id = $db->insert($sql, [$campo]);

    if ($id) {
        listar($db); // Retornar lista actualizada
    } else {
        sendResponse(false, 'Error al crear', null, 500);
    }
}

function actualizar($db): void {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) sendResponse(false, 'ID requerido', null, 400);

    $campo = trim($_POST['campo'] ?? '');

    $sql = "UPDATE tabla SET campo = ?, updated_at = NOW() WHERE id = ?";
    $db->update($sql, [$campo, $id]);

    listar($db); // Retornar lista actualizada
}

function eliminar($db): void {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) sendResponse(false, 'ID requerido', null, 400);

    // SIEMPRE soft delete — nunca DELETE físico
    $sql = "UPDATE tabla SET activo = 0 WHERE id = ?";
    $db->update($sql, [$id]);

    listar($db);
}
```

---

## Convenciones del Patrón

| Elemento | Convención |
|---------|-----------|
| Nombre de archivo | `build/api/[modulo]-simple.php` o `build/api/[modulo].php` |
| Action routing | `$_POST['action']` para POST, `$_GET['action']` para GET |
| Respuesta de éxito en crear/actualizar | Retornar lista completa actualizada (no solo el item) |
| Respuesta de error | `sendResponse(false, 'Mensaje claro', null, httpCode)` |
| Soft delete | `UPDATE tabla SET activo = 0 WHERE id = ?` |
| Fechas vacías a MySQL | `$fecha = ($raw !== '' && strtotime($raw)) ? $raw : null` |
| Floats | `floatval()` con comprobación `!== ''` |
| IDs opcionales | `!empty($_POST['id']) ? intval($_POST['id']) : null` |

---

## Integración con el Frontend (JavaScript)

```javascript
// Patrón estándar de llamada al API desde JS
async function llamarApi(action, datos = {}) {
    const formData = new FormData();
    formData.append('action', action);
    Object.entries(datos).forEach(([key, val]) => formData.append(key, val));

    const response = await fetch('./api/modulo.php', {
        method: 'POST',
        body: formData
    });

    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Error del servidor');

    return data.data;
}

// Uso:
const resultado = await llamarApi('crear', { nombre: 'Valor', sector: 'Tech' });
```

---

## Checklist Antes de Hacer Deploy

- [ ] ¿El endpoint usa `require_once middleware/security-headers.php`?
- [ ] ¿La conexión BD es via `Database::getInstance()`?
- [ ] ¿Todos los inputs son validados/sanitizados antes de usar?
- [ ] ¿El soft delete usa `activo = 0` y no `DELETE`?
- [ ] ¿Los campos `DATE` vacíos envían `null`?
- [ ] ¿La respuesta sigue el formato `{success, message, data}`?
- [ ] ¿Sin credenciales hardcodeadas?
- [ ] ¿Sin `var_dump`, `print_r` ni stack traces en la respuesta?
- [ ] ¿CORS ajustado al dominio correcto para producción?
