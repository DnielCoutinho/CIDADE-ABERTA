<?php
/**
 * Classe de Conexão Simples com Banco de Dados
 * Cidade Aberta Santarém
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Configurações do banco
    private $host = 'localhost';
    private $database = 'cidade_aberta';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            throw new Exception("Erro de conexão com banco de dados: " . $e->getMessage());
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
    
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Prevenir clonagem
    private function __clone() {}
    
    // Prevenir unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>