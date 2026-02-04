<?php
/**
 * Configuración de base de datos con detección automática de entorno
 */

class Database {
    private static $instance = null;
    private $connection;
    private $isRemoteDB = false;
    private $useSQLite = false;
    private $config;
    
    private function __construct() {
        $this->detectEnvironment();
        
        // Intentar conexión remota primero si estamos en servidor
        if ($this->config['try_remote']) {
            $connected = $this->tryRemoteConnection();
            
            if ($connected) {
                return; // Conexión exitosa, terminar
            }
        }
        
        // Si falla conexión remota, usar SQLite como fallback para autenticación
        error_log("Conexión remota falló, usando SQLite local como fallback...");
        $this->initializeSQLite();
    }
    
    private function detectEnvironment() {
        // Cargar variables de entorno
        require_once __DIR__ . '/env-loader.php';
        try {
            EnvLoader::load();
        } catch (Exception $e) {
            error_log("⚠️ No se pudo cargar .env: " . $e->getMessage());
        }
        
        // Detectar si estamos en el servidor remoto o desarrollo local
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        
        // Detectar desarrollo local más específicamente
        $isLocalDev = (
            in_array($serverName, ['localhost', '127.0.0.1']) || 
            strpos($httpHost, 'localhost') !== false ||
            strpos($httpHost, '127.0.0.1') !== false ||
            strpos($httpHost, ':8000') !== false || // PHP server local
            strpos($httpHost, ':3000') !== false    // Otros servers locales
        );
        
        // Si contiene clautmetropolitano.mx es definitivamente servidor remoto
        $isRemoteServer = (
            strpos($httpHost, 'clautmetropolitano.mx') !== false ||
            strpos($serverName, 'clautmetropolitano.mx') !== false
        );
        
        // Siempre forzar uso de MySQL remoto
        $tryRemote = true;
        
        // Configuración desde variables de entorno
        $dbHost = EnvLoader::get('DB_HOST', '127.0.0.1');
        $dbPort = EnvLoader::get('DB_PORT', 3306);
        $dbUser = EnvLoader::get('DB_USER', 'u695712029_claut_fer');
        $dbPass = EnvLoader::get('DB_PASS', 'CLAUT@admin_fernando!7');
        $dbName = EnvLoader::get('DB_NAME', 'u695712029_claut_intranet');
        
        // Soportar múltiples hosts separados por coma
        $hosts = strpos($dbHost, ',') !== false 
            ? explode(',', $dbHost) 
            : [$dbHost, 'localhost', 'clautmetropolitano.mx'];
        
        $this->config = [
            'try_remote' => $tryRemote,
            'mysql' => [
                'hosts' => $hosts,
                'port' => $dbPort,
                'username' => $dbUser,
                'password' => $dbPass,
                'database' => $dbName
            ]
        ];
        
        $environment = $isRemoteServer ? 'Servidor Remoto (forzado)' : 
                      ($isLocalDev ? 'Desarrollo Local' : 'Servidor Remoto (detectado)');
        
        error_log("Entorno detectado: $environment (servidor: $serverName, host: $httpHost)");
        error_log("DB Config: host={$dbHost}, user={$dbUser}, db={$dbName}");
    }
    
    private function tryRemoteConnection() {
        $mysql = $this->config['mysql'];
        
        foreach ($mysql['hosts'] as $host) {
            try {
                error_log("Intentando conexión MySQL en host: $host");
                
                $this->connection = new PDO(
                    "mysql:host={$host};port=3306;dbname={$mysql['database']};charset=utf8mb4",
                    $mysql['username'],
                    $mysql['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_TIMEOUT => 30,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    ]
                );
                
                $this->isRemoteDB = true;
                error_log("✅ Conexión MySQL exitosa en host: $host");
                return true;
                
            } catch (PDOException $e) {
                error_log("❌ Fallo conexión MySQL en $host: " . $e->getMessage());
                continue; // Intentar siguiente host
            }
        }
        
        error_log("No se pudo conectar a ningún host MySQL");
        return false;
    }
    
    private function initializeSQLite() {
        try {
            $sqliteFile = __DIR__ . '/../data/local.db';
            $sqliteDir = dirname($sqliteFile);
            
            if (!is_dir($sqliteDir)) {
                mkdir($sqliteDir, 0777, true);
            }
            
            $this->connection = new PDO(
                "sqlite:$sqliteFile",
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            
            $this->useSQLite = true;
            error_log("✅ Usando base de datos SQLite local para desarrollo");
            $this->createSQLiteTables();
            $this->insertSampleData(); // Insertar datos de prueba para desarrollo
            
        } catch (PDOException $sqliteError) {
            error_log("❌ Error SQLite: " . $sqliteError->getMessage());
            throw new Exception("Error de conexión a base de datos");
        }
    }
    
    private function createSQLiteTables() {
        // Verificación de seguridad: NO ejecutar en conexiones MySQL remotas
        if ($this->isRemoteDB) {
            error_log("⚠️ ADVERTENCIA: Intento de llamar createSQLiteTables() en conexión MySQL remota - BLOQUEADO");
            return;
        }

        // SOLO para SQLite: Eliminar tabla eventos vieja si existe para recrearla con nueva estructura
        // DISABLED: No longer dropping table to preserve existing data
        // if ($this->useSQLite && !$this->isRemoteDB) {
        //     $this->connection->exec("DROP TABLE IF EXISTS eventos");
        // }

        // Crear tablas compatibles con la estructura esperada
        $tables = [
            "CREATE TABLE IF NOT EXISTS usuarios_perfil (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(100) NOT NULL,
                apellidos VARCHAR(100) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                telefono VARCHAR(20),
                empresa_id INTEGER,
                rol VARCHAR(50) DEFAULT 'empleado',
                estado_usuario VARCHAR(50) DEFAULT 'activo',
                avatar BLOB,
                fecha_nacimiento DATE,
                nombre_empresa VARCHAR(255),
                biografia TEXT,
                direccion TEXT,
                ciudad VARCHAR(100),
                estado_geografico VARCHAR(100),
                codigo_postal VARCHAR(20),
                pais VARCHAR(100) DEFAULT 'México',
                telefono_emergencia VARCHAR(20),
                contacto_emergencia VARCHAR(255),
                activo INTEGER DEFAULT 1,
                cargo VARCHAR(100),
                departamento VARCHAR(100),
                avatar_mime_type VARCHAR(50),
                avatar_filename VARCHAR(255),
                fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (empresa_id) REFERENCES empresas_convenio(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS empresas_convenio (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre_empresa VARCHAR(255) NOT NULL,
                descripcion TEXT,
                logo_url VARCHAR(500),
                sitio_web VARCHAR(500),
                email VARCHAR(255),
                telefono VARCHAR(50),
                direccion TEXT,
                categoria VARCHAR(100),
                descuento DECIMAL(5,2),
                beneficios TEXT,
                fecha_inicio_convenio DATE,
                fecha_fin_convenio DATE,
                activo INTEGER DEFAULT 1,
                destacado INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS comites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(255) NOT NULL,
                descripcion TEXT,
                objetivo TEXT,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                estado VARCHAR(50) DEFAULT 'activo',
                coordinador_id INTEGER,
                activo INTEGER DEFAULT 1,
                FOREIGN KEY (coordinador_id) REFERENCES usuarios_perfil(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS descuentos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo VARCHAR(255) NOT NULL,
                activo INTEGER DEFAULT 1,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_vencimiento DATETIME NOT NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS eventos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo VARCHAR(255) NOT NULL,
                descripcion TEXT,
                fecha_inicio DATETIME NOT NULL,
                fecha_fin DATETIME,
                ubicacion VARCHAR(255),
                capacidad_maxima INTEGER DEFAULT 100,
                capacidad_actual INTEGER DEFAULT 0,
                tipo VARCHAR(100),
                modalidad VARCHAR(100),
                estado VARCHAR(50) DEFAULT 'activo',
                organizador_id INTEGER,
                comite_id INTEGER,
                imagen VARCHAR(500),
                precio DECIMAL(10,2) DEFAULT 0.00,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS evento_registros (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                evento_id INTEGER NOT NULL,
                empresa_id INTEGER,
                usuario_id INTEGER,
                nombre_empresa VARCHAR(255),
                nombre_usuario VARCHAR(255),
                email_contacto VARCHAR(255),
                telefono_contacto VARCHAR(20),
                comentarios TEXT,
                fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                estado_registro VARCHAR(50) DEFAULT 'confirmado',
                FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
                FOREIGN KEY (empresa_id) REFERENCES empresas_convenio(id) ON DELETE SET NULL,
                FOREIGN KEY (usuario_id) REFERENCES usuarios_perfil(id) ON DELETE SET NULL
            )",

            "CREATE TABLE IF NOT EXISTS comite_registros (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                comite_id INTEGER NOT NULL,
                empresa_id INTEGER,
                usuario_id INTEGER,
                nombre_empresa VARCHAR(255),
                nombre_usuario VARCHAR(255),
                email_contacto VARCHAR(255),
                telefono_contacto VARCHAR(20),
                cargo VARCHAR(255),
                departamento VARCHAR(255),
                comentarios TEXT,
                fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
                estado_registro VARCHAR(50) DEFAULT 'pendiente',
                fecha_aprobacion DATETIME,
                aprobado_por INTEGER,
                FOREIGN KEY (comite_id) REFERENCES comites(id) ON DELETE CASCADE,
                FOREIGN KEY (empresa_id) REFERENCES empresas_convenio(id) ON DELETE SET NULL,
                FOREIGN KEY (usuario_id) REFERENCES usuarios_perfil(id) ON DELETE SET NULL,
                FOREIGN KEY (aprobado_por) REFERENCES usuarios_perfil(id) ON DELETE SET NULL
            )",

            "CREATE TABLE IF NOT EXISTS notificaciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo VARCHAR(255) NOT NULL,
                contenido TEXT NOT NULL,
                tipo VARCHAR(50) NOT NULL,
                origen_id INTEGER,
                origen_tabla VARCHAR(50),
                destinatario_email VARCHAR(255),
                destinatario_rol VARCHAR(50),
                dirigido_a VARCHAR(20) DEFAULT 'todos',
                importante BOOLEAN DEFAULT FALSE,
                leido BOOLEAN DEFAULT FALSE,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_leido DATETIME,
                activo BOOLEAN DEFAULT TRUE,
                metadata TEXT
            )",

            "CREATE TABLE IF NOT EXISTS usuario_restricciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                pagina VARCHAR(50) NOT NULL,
                restringido BOOLEAN DEFAULT TRUE,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(usuario_id, pagina),
                FOREIGN KEY (usuario_id) REFERENCES usuarios_perfil(id) ON DELETE CASCADE
            )",

            "CREATE TABLE IF NOT EXISTS banner_carrusel (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo VARCHAR(255) NOT NULL,
                descripcion TEXT,
                imagen_url VARCHAR(500),
                link_url VARCHAR(500),
                orden INTEGER DEFAULT 0,
                activo BOOLEAN DEFAULT TRUE,
                fecha_inicio DATETIME,
                fecha_fin DATETIME,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP
            )"
        ];
        
        foreach ($tables as $sql) {
            $this->connection->exec($sql);
        }

        // Crear índices después de las tablas (SQLite compatible)
        $indices = [
            "CREATE INDEX IF NOT EXISTS idx_notif_destinatario ON notificaciones(destinatario_email)",
            "CREATE INDEX IF NOT EXISTS idx_notif_tipo ON notificaciones(tipo)",
            "CREATE INDEX IF NOT EXISTS idx_notif_leido ON notificaciones(leido)",
            "CREATE INDEX IF NOT EXISTS idx_notif_fecha ON notificaciones(fecha_creacion)",
            "CREATE INDEX IF NOT EXISTS idx_eventos_fecha ON eventos(fecha_inicio)",
            "CREATE INDEX IF NOT EXISTS idx_eventos_estado ON eventos(estado)",
            "CREATE INDEX IF NOT EXISTS idx_registros_evento ON evento_registros(evento_id)",
            "CREATE INDEX IF NOT EXISTS idx_registros_email ON evento_registros(email_contacto)"
        ];

        foreach ($indices as $sql) {
            $this->connection->exec($sql);
        }
    }
    
    private function insertSampleData() {
        // Insertar usuarios de prueba para desarrollo
        $userCount = $this->connection->query("SELECT COUNT(*) FROM usuarios_perfil")->fetchColumn();
        
        if ($userCount == 0) {
            $sampleUsers = [
                [
                    'nombre' => 'Fernando',
                    'apellidos' => 'Torres',
                    'email' => 'fernando@claut.mx',
                    'password' => password_hash('admin123', PASSWORD_BCRYPT),
                    'rol' => 'admin',
                    'nombre_empresa' => 'CLAUT Metropolitano'
                ],
                [
                    'nombre' => 'María',
                    'apellidos' => 'González',
                    'email' => 'empresa@techsolutions.mx',
                    'password' => password_hash('empresa123', PASSWORD_BCRYPT),
                    'rol' => 'empresa',
                    'nombre_empresa' => 'TechSolutions México'
                ],
                [
                    'nombre' => 'Carlos',
                    'apellidos' => 'Rodríguez',
                    'email' => 'empleado@claut.mx',
                    'password' => password_hash('empleado123', PASSWORD_BCRYPT),
                    'rol' => 'empleado',
                    'nombre_empresa' => 'CLAUT Metropolitano'
                ]
            ];
            
            $stmt = $this->connection->prepare("
                INSERT INTO usuarios_perfil (nombre, apellidos, email, password, nombre_empresa, rol)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($sampleUsers as $user) {
                $stmt->execute([
                    $user['nombre'],
                    $user['apellidos'],
                    $user['email'],
                    $user['password'],
                    $user['nombre_empresa'] ?? null,
                    $user['rol']
                ]);
            }
            
            error_log("✅ Usuarios de prueba insertados en SQLite");
        }
        
        // Verificar si ya hay datos de empresas
        $count = $this->connection->query("SELECT COUNT(*) FROM empresas_convenio")->fetchColumn();
        
        if ($count == 0) {
            $sampleEmpresas = [
                [
                    'nombre_empresa' => 'TechSolutions México',
                    'descripcion' => 'Empresa líder en soluciones tecnológicas empresariales.',
                    'categoria' => 'Tecnología',
                    'email' => 'info@techsolutions.mx',
                    'telefono' => '+52 55 1234 5678',
                    'sitio_web' => 'https://techsolutions.mx',
                    'descuento' => 15.0,
                    'beneficios' => 'Descuento del 15% en servicios, consultoría gratuita.',
                    'fecha_inicio_convenio' => date('Y-m-d', strtotime('-6 months')),
                    'destacado' => 1
                ],
                [
                    'nombre_empresa' => 'Salud Integral Plus',
                    'descripcion' => 'Centro médico especializado en medicina preventiva.',
                    'categoria' => 'Salud',
                    'email' => 'contacto@saludintegralplus.com',
                    'telefono' => '+52 55 9876 5432',
                    'sitio_web' => 'https://saludintegralplus.com',
                    'descuento' => 20.0,
                    'beneficios' => '20% descuento en consultas, estudios de laboratorio.',
                    'fecha_inicio_convenio' => date('Y-m-d', strtotime('-4 months')),
                    'destacado' => 1
                ],
                [
                    'nombre_empresa' => 'Fitness & Wellness Center',
                    'descripcion' => 'Gimnasio y centro de bienestar completo.',
                    'categoria' => 'Deportes y Bienestar',
                    'email' => 'info@fitnesswellness.mx',
                    'telefono' => '+52 55 5555 1234',
                    'descuento' => 25.0,
                    'beneficios' => 'Membresía con 25% descuento, clases incluidas.',
                    'fecha_inicio_convenio' => date('Y-m-d', strtotime('-2 months')),
                    'destacado' => 1
                ],
                [
                    'nombre_empresa' => 'Clínica Dental Sonrisa',
                    'descripcion' => 'Clínica dental con tecnología de vanguardia.',
                    'categoria' => 'Salud',
                    'email' => 'contacto@clinicasonrisa.mx',
                    'telefono' => '+52 55 4444 7777',
                    'sitio_web' => 'https://clinicasonrisa.mx',
                    'descuento' => 30.0,
                    'beneficios' => '30% descuento en tratamientos, consulta gratuita.',
                    'fecha_inicio_convenio' => date('Y-m-d', strtotime('-3 months')),
                    'destacado' => 1
                ]
            ];
            
            $stmt = $this->connection->prepare("
                INSERT INTO empresas_convenio 
                (nombre_empresa, descripcion, categoria, email, telefono, sitio_web, descuento, beneficios, fecha_inicio_convenio, destacado, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            foreach ($sampleEmpresas as $empresa) {
                $stmt->execute([
                    $empresa['nombre_empresa'],
                    $empresa['descripcion'],
                    $empresa['categoria'],
                    $empresa['email'],
                    $empresa['telefono'],
                    $empresa['sitio_web'] ?? null,
                    $empresa['descuento'],
                    $empresa['beneficios'],
                    $empresa['fecha_inicio_convenio'],
                    $empresa['destacado']
                ]);
            }
            
            error_log("✅ Datos de ejemplo insertados en SQLite");
        }
        
        // Insertar eventos de ejemplo
        $eventCount = $this->connection->query("SELECT COUNT(*) FROM eventos")->fetchColumn();

        if ($eventCount == 0) {
            $sampleEventos = [
                [
                    'titulo' => 'Conferencia Automotriz 2024',
                    'descripcion' => 'Evento anual sobre innovaciones en el sector automotriz mexicano y nuevas tecnologías.',
                    'ubicacion' => 'Centro de Convenciones México',
                    'tipo' => 'Conferencia',
                    'modalidad' => 'Presencial',
                    'capacidad_maxima' => 500,
                    'capacidad_actual' => 156,
                    'precio' => 0.00,
                    'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+15 days')),
                    'fecha_fin' => date('Y-m-d H:i:s', strtotime('+15 days +6 hours'))
                ],
                [
                    'titulo' => 'Taller de Mantenimiento Vehicular',
                    'descripcion' => 'Taller práctico sobre técnicas modernas de mantenimiento preventivo y correctivo.',
                    'ubicacion' => 'Instituto Técnico Automotriz',
                    'tipo' => 'Taller',
                    'modalidad' => 'Presencial',
                    'capacidad_maxima' => 50,
                    'capacidad_actual' => 23,
                    'precio' => 150.00,
                    'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+20 days')),
                    'fecha_fin' => date('Y-m-d H:i:s', strtotime('+20 days +4 hours'))
                ],
                [
                    'titulo' => 'Exposición de Vehículos Eléctricos',
                    'descripcion' => 'Muestra de los últimos modelos de vehículos eléctricos y sostenibles.',
                    'ubicacion' => 'Centro Banamex',
                    'tipo' => 'Exposición',
                    'modalidad' => 'Presencial',
                    'capacidad_maxima' => 1000,
                    'capacidad_actual' => 45,
                    'precio' => 0.00,
                    'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'fecha_fin' => date('Y-m-d H:i:s', strtotime('+32 days'))
                ],
                [
                    'titulo' => 'Seminario de Seguridad Vial',
                    'descripcion' => 'Charla sobre normativas de seguridad vial y prevención de accidentes.',
                    'ubicacion' => 'Auditorio CLAUT',
                    'tipo' => 'Seminario',
                    'modalidad' => 'Híbrida',
                    'capacidad_maxima' => 200,
                    'capacidad_actual' => 78,
                    'precio' => 75.00,
                    'fecha_inicio' => date('Y-m-d H:i:s', strtotime('+7 days')),
                    'fecha_fin' => date('Y-m-d H:i:s', strtotime('+7 days +3 hours'))
                ]
            ];
            
            $stmt = $this->connection->prepare("
                INSERT INTO eventos (titulo, descripcion, ubicacion, tipo, modalidad, capacidad_maxima, capacidad_actual, precio, fecha_inicio, fecha_fin, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo')
            ");
            
            foreach ($sampleEventos as $evento) {
                $stmt->execute([
                    $evento['titulo'],
                    $evento['descripcion'],
                    $evento['ubicacion'],
                    $evento['tipo'],
                    $evento['modalidad'],
                    $evento['capacidad_maxima'],
                    $evento['capacidad_actual'],
                    $evento['precio'],
                    $evento['fecha_inicio'],
                    $evento['fecha_fin']
                ]);
            }
            
            error_log("✅ Eventos de ejemplo insertados en SQLite");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function isUsingRemoteDB() {
        return $this->isRemoteDB;
    }
    
    public function isUsingSQLite() {
        return $this->useSQLite;
    }
    
    public function getEnvironmentInfo() {
        return [
            'using_remote' => $this->isRemoteDB,
            'using_sqlite' => $this->useSQLite,
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown'
        ];
    }
}
?>