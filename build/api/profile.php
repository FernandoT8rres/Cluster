<?php
// Definir constante de acceso
define('CLAUT_ACCESS', true);

// Configuración segura de sesiones
require_once dirname(__DIR__) . '/config/session-config.php';
SessionConfig::init();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

class RealDatabaseProfileAPI {
    private $pdo;
    private $currentUserId = null;

    public function __construct() {
        // Conexión directa
        try {
            $host = '127.0.0.1';
            $username = 'u695712029_claut_fer';
            $password = 'CLAUT@admin_fernando!7';
            $database = 'u695712029_claut_intranet';

            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$database;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 10,
                ]
            );

            error_log("Profile API - Conexión exitosa");

        } catch (Exception $e) {
            error_log("Profile API - Error conexión: " . $e->getMessage());
            throw $e;
        }

        // Obtener usuario actual (requiere autenticación real)
        $this->getCurrentUser();
    }
    
    private function getCurrentUser() {
        // Obtener usuario real de la sesión PHP
        if (isset($_SESSION['user_email'])) {
            $userEmail = $_SESSION['user_email'];

            try {
                // Buscar el usuario por email en la base de datos
                $stmt = $this->pdo->prepare("SELECT id FROM usuarios_perfil WHERE email = ?");
                $stmt->execute([$userEmail]);
                $user = $stmt->fetch();

                if ($user) {
                    $this->currentUserId = $user['id'];
                    error_log("Profile API - Usuario autenticado encontrado: ID " . $this->currentUserId . " (email: " . $userEmail . ")");
                } else {
                    error_log("Profile API - Usuario con email '$userEmail' no encontrado en usuarios_perfil");
                }
            } catch (Exception $e) {
                error_log("Profile API - Error obteniendo usuario de sesión: " . $e->getMessage());
            }
        } else {
            error_log("Profile API - No hay sesión de usuario activa");
        }
    }
    
    public function handleRequest() {
        if (!$this->currentUserId) {
            return [
                'success' => false,
                'message' => 'Usuario no autenticado. Debe iniciar sesión para acceder al perfil.',
                'error_code' => 401,
                'requires_login' => true
            ];
        }
        
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            switch ($method) {
                case 'GET':
                    $action = $_GET['action'] ?? 'get_profile';
                    switch ($action) {
                        case 'get_profile':
                            return $this->getProfile();
                        case 'get_activity':
                            return $this->getRealActivity();
                        case 'get_stats':
                            return $this->getRealStats();
                        default:
                            return $this->getProfile();
                    }
                    
                case 'POST':
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input) {
                        $input = $_POST;
                    }
                    
                    $action = $input['action'] ?? 'update';
                    
                    switch ($action) {
                        case 'update':
                            return $this->updateProfile($input);
                        case 'upload_avatar':
                            return $this->uploadAvatar();
                        default:
                            return $this->updateProfile($input);
                    }
                    
                default:
                    throw new Exception('Método no permitido');
            }
        } catch (Exception $e) {
            error_log("Real DB Profile API Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }
    
    private function getProfile() {
        try {
            error_log("Real DB Profile API - Getting profile for user ID: " . $this->currentUserId);
            
            // Buscar datos del usuario en las tablas existentes
            $userData = $this->findUserInDatabase();
            
            if (!$userData) {
                throw new Exception('No se encontró información del usuario en la base de datos');
            }
            
            // Obtener estadísticas reales
            $stats = $this->calculateRealStats();
            
            // Construir respuesta con datos reales de BD
            $profileData = [
                'id' => $this->currentUserId,
                'firstName' => $userData['firstName'] ?? '',
                'lastName' => $userData['lastName'] ?? '',
                'email' => $userData['email'] ?? '',
                'phone' => $userData['phone'] ?? '',
                'birthDate' => $userData['birthDate'] ?? '',
                'department' => $userData['department'] ?? '',
                'position' => $userData['position'] ?? '',
                'bio' => $userData['bio'] ?? '',
                'address' => $userData['address'] ?? '',
                'city' => $userData['city'] ?? '',
                'state' => $userData['state'] ?? '',
                'zipCode' => $userData['zipCode'] ?? '',
                'country' => $userData['country'] ?? 'México',
                'emergencyPhone' => $userData['emergencyPhone'] ?? '',
                'emergencyContact' => $userData['emergencyContact'] ?? '',
                'avatar' => $this->getAvatarFromDatabase($userData),
                'joinDate' => $userData['joinDate'] ?? '',
                'lastActivity' => $userData['lastActivity'] ?? '',
                'stats' => $stats
            ];
            
            error_log("Real DB Profile API - Profile retrieved successfully from database");
            
            return [
                'success' => true,
                'data' => $profileData,
                'message' => 'Perfil obtenido desde base de datos real',
                'source' => 'database'
            ];
            
        } catch (Exception $e) {
            error_log("Real DB Profile API - Error getting profile: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function findUserInDatabase() {
        // Usar directamente la tabla usuarios_perfil con estructura real
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, nombre, apellidos, email, telefono, fecha_nacimiento,
                       departamento, rol, biografia, direccion, ciudad, estado,
                       codigo_postal, pais, telefono_emergencia, contacto_emergencia,
                       avatar, fecha_ingreso, ultima_actividad
                FROM usuarios_perfil
                WHERE id = ?
            ");
            $stmt->execute([$this->currentUserId]);
            $user = $stmt->fetch();

            if ($user) {
                // Mapear campos reales a estructura esperada
                return [
                    'firstName' => $user['nombre'] ?? '',
                    'lastName' => $user['apellidos'] ?? '',
                    'email' => $user['email'] ?? '',
                    'phone' => $user['telefono'] ?? '',
                    'birthDate' => $user['fecha_nacimiento'] ?? '',
                    'department' => $user['departamento'] ?? '',
                    'position' => $user['rol'] ?? '',
                    'bio' => $user['biografia'] ?? '',
                    'address' => $user['direccion'] ?? '',
                    'city' => $user['ciudad'] ?? '',
                    'state' => $user['estado'] ?? '',
                    'zipCode' => $user['codigo_postal'] ?? '',
                    'country' => $user['pais'] ?? '',
                    'emergencyPhone' => $user['telefono_emergencia'] ?? '',
                    'emergencyContact' => $user['contacto_emergencia'] ?? '',
                    'avatar' => $user['avatar'] ?? './assets/img/team-2.jpg',
                    'joinDate' => $user['fecha_ingreso'] ?? '',
                    'lastActivity' => $user['ultima_actividad'] ?? ''
                ];
            }

            return null;
        } catch (Exception $e) {
            error_log("Profile API - Error getting user: " . $e->getMessage());
            return null;
        }
        
        return null;
    }
    
    private function getUserFromTable($table) {
        try {
            // Obtener estructura de la tabla
            $stmt = $this->pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $columnNames = array_column($columns, 'Field');
            
            // Verificar si la tabla tiene campos típicos de usuario
            $userFields = ['email', 'correo', 'nombre', 'name', 'username'];
            $hasUserFields = false;
            foreach ($userFields as $field) {
                if (in_array($field, $columnNames)) {
                    $hasUserFields = true;
                    break;
                }
            }
            
            if (!$hasUserFields) {
                return null;
            }
            
            // Intentar diferentes estrategias para encontrar al usuario
            $queries = [
                "SELECT * FROM $table WHERE id = ? LIMIT 1",
                "SELECT * FROM $table WHERE user_id = ? LIMIT 1",
                "SELECT * FROM $table WHERE usuario_id = ? LIMIT 1"
            ];
            
            foreach ($queries as $query) {
                try {
                    $stmt = $this->pdo->prepare($query);
                    $stmt->execute([$this->currentUserId]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($row) {
                        return $this->mapRowToUserData($row, $columnNames);
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
            
            // Si no encontramos por ID específico, obtener primer usuario como demo
            try {
                $stmt = $this->pdo->query("SELECT * FROM $table LIMIT 1");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    error_log("Real DB Profile API - Using first user from table $table as demo");
                    return $this->mapRowToUserData($row, $columnNames);
                }
            } catch (Exception $e) {
                return null;
            }
            
        } catch (Exception $e) {
            error_log("Real DB Profile API - Error with table $table: " . $e->getMessage());
            return null;
        }
        
        return null;
    }
    
    private function mapRowToUserData($row, $columnNames) {
        // Mapeo inteligente de campos de BD a campos de perfil
        $fieldMappings = [
            'firstName' => ['nombre', 'first_name', 'name', 'nombres'],
            'lastName' => ['apellidos', 'last_name', 'apellido', 'surname'],
            'email' => ['email', 'correo', 'mail'],
            'phone' => ['telefono', 'phone', 'tel', 'celular'],
            'birthDate' => ['fecha_nacimiento', 'birth_date', 'nacimiento'],
            'department' => ['departamento', 'department', 'area'],
            'position' => ['puesto', 'position', 'cargo', 'job_title'],
            'bio' => ['biografia', 'bio', 'descripcion', 'about'],
            'address' => ['direccion', 'address', 'domicilio'],
            'city' => ['ciudad', 'city', 'localidad'],
            'state' => ['estado', 'state', 'provincia'],
            'zipCode' => ['codigo_postal', 'zip_code', 'cp'],
            'country' => ['pais', 'country', 'nacionalidad'],
            'emergencyPhone' => ['telefono_emergencia', 'emergency_phone'],
            'emergencyContact' => ['contacto_emergencia', 'emergency_contact'],
            'avatar' => ['avatar', 'photo', 'imagen', 'foto', 'picture'],
            'joinDate' => ['fecha_ingreso', 'join_date', 'created_at', 'fecha_registro'],
            'lastActivity' => ['ultima_actividad', 'last_activity', 'updated_at', 'last_login']
        ];
        
        $userData = [];
        
        foreach ($fieldMappings as $profileField => $possibleColumns) {
            foreach ($possibleColumns as $column) {
                if (in_array($column, $columnNames) && isset($row[$column])) {
                    $userData[$profileField] = $row[$column];
                    break;
                }
            }
        }
        
        return $userData;
    }
    
    private function getAvatarFromDatabase($userData) {
        // Si hay avatar en los datos del usuario, usarlo
        if (!empty($userData['avatar'])) {
            $avatar = $userData['avatar'];
            
            // Si es una ruta completa, usarla tal como está
            if (filter_var($avatar, FILTER_VALIDATE_URL) || strpos($avatar, '/') === 0) {
                return $avatar;
            }
            
            // Si es solo un nombre de archivo, construir la ruta
            if (file_exists("./uploads/avatars/$avatar")) {
                return "./uploads/avatars/$avatar";
            }
            
            // Si es base64, usarlo directamente
            if (strpos($avatar, 'data:image') === 0) {
                return $avatar;
            }
        }
        
        // Avatar por defecto si no hay imagen en BD
        return './assets/img/team-2.jpg';
    }
    
    private function calculateRealStats() {
        $stats = [
            'events' => 0,
            'documents' => 0,
            'committees' => 0,
            'points' => 0
        ];
        
        try {
            // Contar datos reales de las tablas existentes
            $tableCounts = [
                'empresas_convenio' => 'documents',
                'eventos' => 'events',
                'comites' => 'committees',
                'descuentos' => 'documents'
            ];
            
            foreach ($tableCounts as $table => $statKey) {
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM $table");
                    $count = $stmt->fetch()['count'];
                    $stats[$statKey] += (int)$count;
                    error_log("Real DB Profile API - Table $table: $count records");
                } catch (Exception $e) {
                    error_log("Real DB Profile API - Table $table not found");
                }
            }
            
            // Calcular puntos basados en actividad real
            $stats['points'] = ($stats['documents'] * 10) + ($stats['events'] * 5) + ($stats['committees'] * 20);
            
        } catch (Exception $e) {
            error_log("Real DB Profile API - Error calculating stats: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    private function getRealActivity() {
        try {
            $activities = [];
            
            // Obtener actividad real de diferentes tablas
            $activitySources = [
                [
                    'table' => 'empresas_convenio',
                    'query' => "SELECT nombre as title, fecha_registro as date FROM empresas_convenio ORDER BY fecha_registro DESC LIMIT 5",
                    'type' => 'company',
                    'icon' => 'fa-building',
                    'text_template' => 'Empresa registrada: {title}'
                ],
                [
                    'table' => 'eventos',
                    'query' => "SELECT titulo as title, fecha_creacion as date FROM eventos ORDER BY fecha_creacion DESC LIMIT 3",
                    'type' => 'event',
                    'icon' => 'fa-calendar',
                    'text_template' => 'Evento creado: {title}'
                ],
                [
                    'table' => 'comites',
                    'query' => "SELECT nombre as title, fecha_creacion as date FROM comites ORDER BY fecha_creacion DESC LIMIT 3",
                    'type' => 'committee',
                    'icon' => 'fa-users',
                    'text_template' => 'Comité formado: {title}'
                ],
                [
                    'table' => 'descuentos',
                    'query' => "SELECT titulo as title, fecha_creacion as date FROM descuentos ORDER BY fecha_creacion DESC LIMIT 3",
                    'type' => 'discount',
                    'icon' => 'fa-tag',
                    'text_template' => 'Descuento agregado: {title}'
                ]
            ];
            
            foreach ($activitySources as $source) {
                try {
                    $stmt = $this->pdo->query($source['query']);
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($results as $result) {
                        $activities[] = [
                            'type' => $source['type'],
                            'text' => str_replace('{title}', $result['title'] ?? 'Sin título', $source['text_template']),
                            'icon' => $source['icon'],
                            'time' => $this->getRelativeTime($result['date'] ?? null)
                        ];
                    }
                } catch (Exception $e) {
                    error_log("Real DB Profile API - Error getting activity from {$source['table']}: " . $e->getMessage());
                }
            }
            
            // Ordenar por fecha real si es posible, sino mantener orden de inserción
            if (empty($activities)) {
                $activities[] = [
                    'type' => 'info',
                    'text' => 'No hay actividad registrada en el sistema',
                    'icon' => 'fa-info-circle',
                    'time' => 'N/A'
                ];
            }
            
            // Limitar a 10 actividades más recientes
            $activities = array_slice($activities, 0, 10);
            
            return [
                'success' => true,
                'data' => $activities,
                'message' => 'Actividad obtenida desde base de datos real',
                'source' => 'database'
            ];
            
        } catch (Exception $e) {
            error_log("Real DB Profile API - Error getting real activity: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function getRealStats() {
        return [
            'success' => true,
            'data' => $this->calculateRealStats(),
            'message' => 'Estadísticas calculadas desde base de datos real',
            'source' => 'database'
        ];
    }
    
    private function updateProfile($data) {
        try {
            // OPCIONAL: Validación adicional de seguridad (no altera funcionamiento existente)
            // Si el validador no está disponible, la función continúa normalmente
            if (file_exists(__DIR__ . '/../../middleware/api-validator.php')) {
                require_once __DIR__ . '/../../middleware/api-validator.php';
                
                // Definir reglas opcionales (ningún campo es requerido en actualización)
                $validationRules = [
                    'phone' => 'string|min:10|max:15|regex:/^[0-9+\-\s()]+$/',
                    'birthDate' => 'regex:/^\d{4}-\d{2}-\d{2}$/',
                    'bio' => 'string|max:500',
                    'zipCode' => 'string|max:10'
                ];
                
                // Validar solo los campos que se están enviando
                $fieldsToValidate = array_intersect_key($validationRules, $data);
                
                if (!empty($fieldsToValidate)) {
                    $validation = ApiValidator::validateAndSanitize($data, $fieldsToValidate);
                    
                    if (!$validation['valid']) {
                        return [
                            'success' => false,
                            'message' => 'Errores de validación',
                            'validation_errors' => $validation['errors']
                        ];
                    }
                    
                    // Usar datos sanitizados si la validación pasó
                    $data = $validation['data'];
                }
            }
            
            // LÓGICA ORIGINAL (sin cambios)
            // Solo actualizar campos editables (excluyendo firstName, lastName, email)
            $editableFields = [
                'phone' => 'telefono',
                'birthDate' => 'fecha_nacimiento',
                'department' => 'departamento',
                'position' => 'rol',
                'bio' => 'biografia',
                'address' => 'direccion',
                'city' => 'ciudad',
                'state' => 'estado',
                'zipCode' => 'codigo_postal',
                'country' => 'pais',
                'emergencyPhone' => 'telefono_emergencia',
                'emergencyContact' => 'contacto_emergencia'
            ];

            // Obtener valores actuales del usuario
            $currentUser = $this->findUserInDatabase();
            if (!$currentUser) {
                throw new Exception('Usuario no encontrado');
            }

            // Crear notificaciones para cada cambio solicitado
            $changesCreated = 0;
            foreach ($editableFields as $fieldKey => $dbField) {
                if (isset($data[$fieldKey])) {
                    $newValue = $data[$fieldKey];
                    $oldValue = $currentUser[$fieldKey] ?? '';

                    // Solo crear notificación si el valor cambió
                    if ($newValue !== $oldValue) {
                        $this->createChangeNotification($fieldKey, $oldValue, $newValue);
                        $changesCreated++;
                    }
                }
            }

            if ($changesCreated === 0) {
                return [
                    'success' => true,
                    'message' => 'No hay cambios para notificar',
                    'data' => ['changes_created' => 0]
                ];
            }

            return [
                'success' => true,
                'message' => "Se han enviado $changesCreated solicitudes de cambio para revisión",
                'data' => [
                    'user_id' => $this->currentUserId,
                    'changes_created' => $changesCreated
                ]
            ];

        } catch (Exception $e) {
            error_log("Real DB Profile API - Error updating profile: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function createChangeNotification($field, $oldValue, $newValue) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notificaciones_cambios_perfil
                (usuario_id, campo_modificado, valor_anterior, valor_nuevo, estado)
                VALUES (?, ?, ?, ?, 'pendiente')
            ");

            $stmt->execute([
                $this->currentUserId,
                $field,
                $oldValue,
                $newValue
            ]);

            error_log("Profile API - Change notification created for field: $field");
            return true;

        } catch (Exception $e) {
            error_log("Profile API - Error creating notification: " . $e->getMessage());
            return false;
        }
    }
    
    private function uploadAvatar() {
        // Implementar subida real de avatar a BD
        error_log("Real DB Profile API - Avatar upload functionality requires implementation");
        return [
            'success' => false,
            'message' => 'Funcionalidad de subida de avatar requiere implementación específica',
            'error_code' => 501
        ];
    }
    
    private function getRelativeTime($datetime) {
        if (!$datetime) return 'Fecha desconocida';
        
        $time = strtotime($datetime);
        if (!$time) return 'Fecha inválida';
        
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'Hace unos segundos';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return "Hace $mins " . ($mins == 1 ? 'minuto' : 'minutos');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "Hace $hours " . ($hours == 1 ? 'hora' : 'horas');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "Hace $days " . ($days == 1 ? 'día' : 'días');
        } else {
            $weeks = floor($diff / 604800);
            return "Hace $weeks " . ($weeks == 1 ? 'semana' : 'semanas');
        }
    }
}

// Main execution
try {
    $profileAPI = new RealDatabaseProfileAPI();
    $result = $profileAPI->handleRequest();
    
    http_response_code($result['success'] ? 200 : ($result['error_code'] ?? 400));
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Real DB Profile API Fatal Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>