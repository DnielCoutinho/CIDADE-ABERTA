<?php
/**
 * Configurações do Banco de Dados - Cidade Aberta Santarém
 * Configuração segura para conexão com MySQL
 */

class DatabaseConfig {
    // Configurações do Banco de Dados
    private const DB_HOST = 'localhost';
    private const DB_NAME = 'cidade_aberta';
    private const DB_USER = 'root';
    private const DB_PASS = '';
    private const DB_CHARSET = 'utf8mb4';
    
    // Configurações de Segurança
    private const UPLOAD_MAX_SIZE = 5242880; // 5MB
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Configurações de Sessão
    private const SESSION_TIMEOUT = 3600; // 1 hora
    
    private static $connection = null;
    
    /**
     * Obter conexão PDO com o banco de dados
     * 
     * @return PDO
     * @throws Exception
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::DB_CHARSET
                ];
                
                self::$connection = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
                
            } catch (PDOException $e) {
                error_log("Erro de conexão com banco: " . $e->getMessage());
                throw new Exception("Erro interno do servidor", 500);
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Configurações de upload
     */
    public static function getUploadMaxSize() {
        return self::UPLOAD_MAX_SIZE;
    }
    
    public static function getAllowedImageTypes() {
        return self::ALLOWED_IMAGE_TYPES;
    }
    
    public static function getSessionTimeout() {
        return self::SESSION_TIMEOUT;
    }
    
    /**
     * Fechar conexão
     */
    public static function closeConnection() {
        self::$connection = null;
    }
}

/**
 * Configurações Gerais da Aplicação
 */
class AppConfig {
    public const APP_NAME = 'Cidade Aberta Santarém';
    public const APP_VERSION = '1.0.0';
    public const TIMEZONE = 'America/Belem';
    
    // URLs e Caminhos
    public const BASE_URL = '/Cidade Aberta/';
    public const UPLOAD_PATH = 'uploads/';
    public const ASSETS_PATH = 'assets/';
    
    // Configurações de Email (para futuras implementações)
    public const SMTP_HOST = 'localhost';
    public const SMTP_PORT = 587;
    public const SMTP_USER = '';
    public const SMTP_PASS = '';
    public const FROM_EMAIL = 'noreply@santarem.pa.gov.br';
    public const FROM_NAME = 'Cidade Aberta Santarém';
    
    // Status das Ocorrências
    public const STATUS_PENDENTE = 'pendente';
    public const STATUS_ANDAMENTO = 'andamento';
    public const STATUS_CONCLUIDO = 'concluido';
    
    /**
     * Inicializar configurações da aplicação
     */
    public static function init() {
        // Configurar timezone
        date_default_timezone_set(self::TIMEZONE);
        
        // Configurar charset
        mb_internal_encoding('UTF-8');
        
        // Configurar sessão segura
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            session_start();
        }
        
        // Configurar headers de segurança
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    /**
     * Obter status válidos
     */
    public static function getValidStatus() {
        return [
            self::STATUS_PENDENTE,
            self::STATUS_ANDAMENTO,
            self::STATUS_CONCLUIDO
        ];
    }
}

// Inicializar configurações
AppConfig::init();
?>