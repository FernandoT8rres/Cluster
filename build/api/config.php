<?php
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        // CONFIGURACIÓN DE BASE DE DATOS - CREDENCIALES CORRECTAS
        $host = '127.0.0.1';
        $dbname = 'u695712029_claut_intranet';
        $username = 'u695712029_claut_fer';
        $password = 'CLAUT@admin_fernando!7'; // Contraseña correcta proporcionada
        
        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            error_log("Conexión exitosa a la base de datos '$dbname'");
        } catch (PDOException $e) {
            // Si no existe la base de datos, intentar crearla
            if ($e->getCode() == 1049) { // Error: Unknown database
                try {
                    $this->connection = new PDO(
                        "mysql:host=$host;charset=utf8mb4",
                        $username,
                        $password,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]
                    );
                    
                    // Crear base de datos
                    $this->connection->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $this->connection->exec("USE $dbname");
                    
                    error_log("Base de datos '$dbname' creada exitosamente");
                    
                } catch (PDOException $e2) {
                    error_log("Error al crear/conectar base de datos: " . $e2->getMessage());
                    throw new Exception("No se pudo conectar a la base de datos: " . $e2->getMessage());
                }
            } else {
                // Otro tipo de error
                error_log("Error de conexión a MySQL: " . $e->getMessage());
                throw new Exception("Error de conexión a MySQL: " . $e->getMessage());
            }
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
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>