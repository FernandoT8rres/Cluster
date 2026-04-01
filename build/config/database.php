<?php
/**
 * Configuración de base de datos - Producción Hostinger Only
 * 
 * Este archivo ha sido simplificado para eliminar soporte local/SQLite.
 * La conexión se realiza exclusivamente a MySQL mediante variables de entorno.
 */

class Database {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    private function loadConfig() {
        // Cargar variables de entorno
        require_once __DIR__ . '/env-loader.php';
        try {
            EnvLoader::load();
        } catch (Exception $e) {
            error_log("⚠️ Fallo al cargar .env: " . $e->getMessage());
        }
        
        $this->config = [
            'host'     => EnvLoader::get('DB_HOST', '127.0.0.1'),
            'port'     => EnvLoader::get('DB_PORT', 3306),
            'username' => EnvLoader::get('DB_USER', 'u695712029_claut_fer'),
            'password' => EnvLoader::get('DB_PASS', 'CLAUT@admin_fernando!7'),
            'database' => EnvLoader::get('DB_NAME', 'u695712029_claut_intranet')
        ];
    }
    
    private function connect() {
        $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset=utf8mb4";
        
        try {
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 30,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            error_log("❌ Error fatal de conexión MySQL: " . $e->getMessage());
            throw new Exception("Error de conexión al servidor de base de datos.");
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
    
    // Métodos para retrocompatibilidad (siempre retornan estado real de producción)
    public function isUsingRemoteDB() { return true; }
    public function isUsingSQLite() { return false; }
    
    public function getEnvironmentInfo() {
        return [
            'using_remote' => true,
            'using_sqlite' => false,
            'server_name'  => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'http_host'    => $_SERVER['HTTP_HOST'] ?? 'unknown'
        ];
    }
}
?>