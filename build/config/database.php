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
            'username' => EnvLoader::get('DB_USER', ''),
            'password' => EnvLoader::get('DB_PASS', ''),
            'database' => EnvLoader::get('DB_NAME', '')
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
    
    /**
     * Métodos de utilidad unificados (Senior Helpers)
     * Estos métodos permiten al sistema actuar sobre cualquier API con una sintaxis limpia.
     */
    public function select($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("❌ Error en SELECT: " . $e->getMessage());
            throw new Exception("Error al realizar la consulta.");
        }
    }
    
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("❌ Error en SELECT_ONE: " . $e->getMessage());
            throw new Exception("Error al obtener el registro.");
        }
    }
    
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            if ($stmt->execute($params)) {
                return $this->connection->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("❌ Error en INSERT: " . $e->getMessage());
            throw new Exception("Error al insertar el registro.");
        }
    }
    
    public function update($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("❌ Error en UPDATE: " . $e->getMessage());
            throw new Exception("Error al actualizar el registro.");
        }
    }
    
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("❌ Error en DELETE: " . $e->getMessage());
            throw new Exception("Error al eliminar el registro.");
        }
    }

    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("❌ Error en EXECUTE: " . $e->getMessage());
            throw new Exception("Error al ejecutar la operación.");
        }
    }
}
?>