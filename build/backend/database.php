<?php
class Database {
    private static $instance = null;
    private $connection;
    
    // Configuración de base de datos remota únicamente
    private $host = '127.0.0.1';
    private $username = 'u695712029_claut_fer';
    private $password = 'CLAUT@admin_fernando!7';
    private $database = 'u695712029_claut_intranet';
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
            error_log("Conexión exitosa a la base de datos '{$this->database}'");
            
        } catch (PDOException $e) {
            error_log("Error de conexión a MySQL: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
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
}
?>
