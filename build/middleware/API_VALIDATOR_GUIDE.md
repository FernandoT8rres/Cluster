# API Validator - Guía de Uso

## 📋 Descripción

Middleware centralizado para validación de APIs que **NO altera el funcionamiento existente**.

### ✅ Características

- **Opcional:** Solo valida si se invoca explícitamente
- **No invasivo:** Las APIs funcionan normalmente sin el validador
- **Retrocompatible:** No rompe código existente
- **Sin dependencias:** Compatible con Hostinger
- **Flexible:** Múltiples reglas de validación

---

## 🚀 Uso Básico

### 1. Validación Simple

```php
<?php
require_once __DIR__ . '/../middleware/api-validator.php';

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);

// Validar campos requeridos
$validation = ApiValidator::validateRequired($data, ['email', 'password']);

if (!$validation['valid']) {
    ApiValidator::errorResponse($validation['errors']);
}

// Continuar con la lógica normal...
```

### 2. Validación con Reglas

```php
<?php
require_once __DIR__ . '/../middleware/api-validator.php';

$data = json_decode(file_get_contents('php://input'), true);

// Definir reglas
$rules = [
    'email' => 'required|email|max:255',
    'password' => 'required|string|min:6',
    'nombre' => 'required|string|min:2|max:100'
];

// Validar
$validation = ApiValidator::validateTypes($data, $rules);

if (!$validation['valid']) {
    ApiValidator::errorResponse($validation['errors']);
}

// Datos validados disponibles en $validation['data']
```

### 3. Validación con Sanitización

```php
<?php
require_once __DIR__ . '/../middleware/api-validator.php';

$data = json_decode(file_get_contents('php://input'), true);

$rules = [
    'nombre' => 'required|string|min:2|max:100',
    'email' => 'required|email'
];

// Validar Y sanitizar
$validation = ApiValidator::validateAndSanitize($data, $rules);

if (!$validation['valid']) {
    ApiValidator::errorResponse($validation['errors']);
}

// Usar datos sanitizados
$nombre = $validation['data']['nombre']; // Ya sanitizado
$email = $validation['data']['email'];   // Ya sanitizado
```

---

## 📝 Reglas de Validación

### Reglas Disponibles

| Regla | Descripción | Ejemplo |
|-------|-------------|---------|
| `required` | Campo obligatorio | `'required'` |
| `email` | Email válido | `'email'` |
| `int` | Número entero | `'int'` |
| `string` | Cadena de texto | `'string'` |
| `min:X` | Longitud/valor mínimo | `'min:6'` |
| `max:X` | Longitud/valor máximo | `'max:255'` |
| `in:a,b,c` | Valor en lista | `'in:admin,empleado'` |
| `regex:pattern` | Expresión regular | `'regex:/^[A-Z]+$/'` |

### Combinar Reglas

Usa `|` para combinar múltiples reglas:

```php
$rules = [
    'email' => 'required|email|max:255',
    'password' => 'required|string|min:6|max:100',
    'rol' => 'required|in:admin,empleado,empresa',
    'telefono' => 'string|min:10|max:15|regex:/^[0-9+\-\s()]+$/'
];
```

---

## 🎯 Reglas Predefinidas

Usa la clase `ValidationRules` para reglas comunes:

```php
use ValidationRules;

$rules = [
    'email' => ValidationRules::EMAIL,           // required|email|max:255
    'password' => ValidationRules::PASSWORD,     // required|string|min:6|max:255
    'nombre' => ValidationRules::NOMBRE,         // required|string|min:2|max:100
    'apellidos' => ValidationRules::APELLIDOS,   // required|string|min:2|max:100
    'rol' => ValidationRules::ROL,               // required|in:admin,empleado,empresa
    'id' => ValidationRules::ID                  // required|int|min:1
];
```

---

## 📁 Validación de Archivos

```php
<?php
require_once __DIR__ . '/../middleware/api-validator.php';

// Validar archivo subido
$fileValidation = ApiValidator::validateFile($_FILES['avatar'], [
    'required' => true,
    'maxSize' => 5 * 1024 * 1024, // 5MB
    'allowedTypes' => ['jpg', 'jpeg', 'png', 'gif'],
    'allowedMimes' => ['image/jpeg', 'image/png', 'image/gif']
]);

if (!$fileValidation['valid']) {
    ApiValidator::errorResponse($fileValidation['error']);
}

// Archivo válido, procesar...
$file = $fileValidation['file'];
```

---

## 🔧 Métodos Disponibles

### `validateRequired($data, $required)`
Valida que los campos requeridos existan y no estén vacíos.

**Parámetros:**
- `$data` (array): Datos a validar
- `$required` (array): Lista de campos requeridos

**Retorna:**
```php
[
    'valid' => bool,
    'errors' => array,
    'data' => array
]
```

### `validateTypes($data, $rules)`
Valida tipos de datos según reglas.

**Parámetros:**
- `$data` (array): Datos a validar
- `$rules` (array): Reglas de validación

**Retorna:**
```php
[
    'valid' => bool,
    'errors' => array,
    'data' => array
]
```

### `validateAndSanitize($data, $rules, $sanitize = true)`
Valida y sanitiza datos.

**Parámetros:**
- `$data` (array): Datos a validar
- `$rules` (array): Reglas de validación
- `$sanitize` (bool): Si debe sanitizar (default: true)

**Retorna:**
```php
[
    'valid' => bool,
    'errors' => array,
    'data' => array (sanitizado si $sanitize = true)
]
```

### `errorResponse($errors, $httpCode = 400)`
Envía respuesta de error estandarizada y termina ejecución.

**Parámetros:**
- `$errors` (string|array): Errores de validación
- `$httpCode` (int): Código HTTP (default: 400)

**Respuesta JSON:**
```json
{
    "success": false,
    "error": "Errores de validación",
    "validation_errors": {
        "email": "El campo 'email' debe ser un email válido",
        "password": "El campo 'password' debe tener al menos 6 caracteres"
    },
    "timestamp": "2026-01-30 14:00:00"
}
```

### `validateField($value, $rules, $fieldName)`
Validación rápida de un solo campo.

**Ejemplo:**
```php
$emailValidation = ApiValidator::validateField(
    $email, 
    'required|email', 
    'email'
);

if (!$emailValidation['valid']) {
    echo $emailValidation['error'];
}
```

### `sanitize($data)`
Sanitiza datos sin validación.

**Ejemplo:**
```php
$cleanData = ApiValidator::sanitize($_POST);
```

### `validateFile($file, $options)`
Valida archivo subido.

**Opciones:**
- `required` (bool): Si el archivo es requerido
- `maxSize` (int): Tamaño máximo en bytes
- `allowedTypes` (array): Extensiones permitidas
- `allowedMimes` (array): Tipos MIME permitidos

---

## 💡 Ejemplos Completos

### Ejemplo 1: API de Login

```php
<?php
// api/auth/login.php
require_once __DIR__ . '/../../middleware/api-validator.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validar entrada
$validation = ApiValidator::validateAndSanitize($data, [
    'email' => ValidationRules::EMAIL,
    'password' => ValidationRules::PASSWORD
]);

if (!$validation['valid']) {
    ApiValidator::errorResponse($validation['errors']);
}

// Continuar con lógica de login...
$email = $validation['data']['email'];
$password = $validation['data']['password'];
```

### Ejemplo 2: API de Registro

```php
<?php
// api/auth/register.php
require_once __DIR__ . '/../../middleware/api-validator.php';

$data = json_decode(file_get_contents('php://input'), true);

$rules = [
    'nombre' => ValidationRules::NOMBRE,
    'apellidos' => ValidationRules::APELLIDOS,
    'email' => ValidationRules::EMAIL,
    'password' => ValidationRules::PASSWORD,
    'telefono' => ValidationRules::TELEFONO,
    'rol' => ValidationRules::ROL
];

$validation = ApiValidator::validateAndSanitize($data, $rules);

if (!$validation['valid']) {
    ApiValidator::errorResponse($validation['errors']);
}

// Datos validados y sanitizados
$userData = $validation['data'];
```

### Ejemplo 3: API de Actualización de Perfil

```php
<?php
// api/profile.php
require_once __DIR__ . '/../middleware/api-validator.php';

$data = json_decode(file_get_contents('php://input'), true);

// Campos opcionales (sin 'required')
$rules = [
    'nombre' => 'string|min:2|max:100',
    'apellidos' => 'string|min:2|max:100',
    'telefono' => ValidationRules::TELEFONO,
    'biografia' => 'string|max:500'
];

$validation = ApiValidator::validateAndSanitize($data, $rules);

if (!$validation['valid']) {
    ApiValidator::errorResponse($validation['errors']);
}

// Solo actualizar campos proporcionados
$updateData = array_filter($validation['data']);
```

---

## ⚠️ Importante

### NO Invasivo

Este validador es **completamente opcional**:

```php
// ❌ ANTES (sin validador) - SIGUE FUNCIONANDO
$email = $_POST['email'];
$password = $_POST['password'];

// ✅ DESPUÉS (con validador) - OPCIONAL
$validation = ApiValidator::validateAndSanitize($_POST, [
    'email' => 'required|email',
    'password' => 'required|string|min:6'
]);

if (!$validation['valid']) {
    ApiValidator::errorResponse($validation['errors']);
}

$email = $validation['data']['email'];
$password = $validation['data']['password'];
```

### Retrocompatibilidad

- Si no incluyes el validador, todo funciona igual
- Si lo incluyes pero no lo usas, no afecta nada
- Solo valida cuando lo invocas explícitamente

---

## 🧪 Testing

### Probar Validación

```bash
# Email inválido
curl -X POST http://localhost/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"invalid","password":"test123"}'

# Respuesta esperada:
{
  "success": false,
  "error": "Errores de validación",
  "validation_errors": {
    "email": "El campo 'email' debe ser un email válido"
  },
  "timestamp": "2026-01-30 14:00:00"
}
```

```bash
# Password muy corto
curl -X POST http://localhost/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"123"}'

# Respuesta esperada:
{
  "success": false,
  "error": "Errores de validación",
  "validation_errors": {
    "password": "El campo 'password' debe tener al menos 6 caracteres"
  },
  "timestamp": "2026-01-30 14:00:00"
}
```

---

## 📊 Ventajas

✅ **Seguridad:** Previene inyección de código malicioso  
✅ **Consistencia:** Respuestas de error estandarizadas  
✅ **Mantenibilidad:** Reglas centralizadas  
✅ **Flexibilidad:** Fácil de extender  
✅ **Performance:** Sin overhead significativo  
✅ **Compatible:** Funciona en Hostinger sin dependencias

---

**Versión:** 1.0.0  
**Fecha:** 30 de enero de 2026  
**Autor:** Claut Intranet Security Team
