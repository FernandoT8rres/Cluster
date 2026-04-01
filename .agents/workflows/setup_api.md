---
description: Creacion de un nuevo endpoint API
---

Para crear un nuevo endpoint API para Claut Intranet, sigue esta estructura estandarizada para mantener consistencia y seguridad:

1. **Ubicación:** Crea el archivo en `/build/api/[nombre].php`.
2. **Encabezados:** Define configuraciones JSON y CORS:
   ```php
   header('Content-Type: application/json; charset=utf-8');
   header('Access-Control-Allow-Origin: *'); // Ajustar a dominio en producción
   header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
   header('Access-Control-Allow-Headers: Content-Type, Authorization');

   if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
       exit(0);
   }
   ```
3. **Conexión a BD:** Usa la configuración central global:
   ```php
   require_once __DIR__ . '/../config/database.php';
   
   try {
       $pdo = Database::getInstance()->getConnection();
       // Lógica del endpoint
   } catch (Exception $e) {
       echo json_encode(['success' => false, 'message' => $e->getMessage()]);
   }
   ```
4. **Respuesta Estándar:** Devuelve siempre un objeto JSON uniforme (success true o false).
