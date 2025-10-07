<?php
require_once '../config/database.php';

/**
 * Classe base para operações CRUD - Cidade Aberta
 * Fornece métodos seguros para interação com banco de dados
 */
abstract class BaseModel {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = DatabaseConfig::getConnection();
    }
    
    /**
     * Validar e sanitizar dados de entrada
     * 
     * @param array $data
     * @param array $rules
     * @return array
     * @throws Exception
     */
    protected function validateData($data, $rules) {
        $validated = [];
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? trim($data[$field]) : null;
            
            // Campo obrigatório
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[] = "Campo '{$field}' é obrigatório";
                continue;
            }
            
            // Validação de tipo
            if (!empty($value) && isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "Campo '{$field}' deve ser um email válido";
                            continue 2;
                        }
                        break;
                        
                    case 'float':
                        if (!is_numeric($value)) {
                            $errors[] = "Campo '{$field}' deve ser um número válido";
                            continue 2;
                        }
                        $value = (float) $value;
                        break;
                        
                    case 'int':
                        if (!is_numeric($value) || !is_int($value + 0)) {
                            $errors[] = "Campo '{$field}' deve ser um número inteiro";
                            continue 2;
                        }
                        $value = (int) $value;
                        break;
                        
                    case 'string':
                        $value = filter_var($value, FILTER_SANITIZE_STRING);
                        break;
                }
            }
            
            // Validação de tamanho mínimo
            if (!empty($value) && isset($rule['min_length'])) {
                if (strlen($value) < $rule['min_length']) {
                    $errors[] = "Campo '{$field}' deve ter pelo menos {$rule['min_length']} caracteres";
                    continue;
                }
            }
            
            // Validação de tamanho máximo
            if (!empty($value) && isset($rule['max_length'])) {
                if (strlen($value) > $rule['max_length']) {
                    $errors[] = "Campo '{$field}' deve ter no máximo {$rule['max_length']} caracteres";
                    continue;
                }
            }
            
            // Validação de valores permitidos
            if (!empty($value) && isset($rule['allowed'])) {
                if (!in_array($value, $rule['allowed'])) {
                    $errors[] = "Campo '{$field}' contém valor inválido";
                    continue;
                }
            }
            
            $validated[$field] = $value;
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors), 400);
        }
        
        return $validated;
    }
    
    /**
     * Gerar código único para ocorrência
     * 
     * @return string
     */
    protected function generateUniqueCode() {
        do {
            $code = 'STM' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $exists = $this->findByCode($code);
        } while ($exists);
        
        return $code;
    }
    
    /**
     * Buscar registro por código
     * 
     * @param string $code
     * @return array|null
     */
    protected function findByCode($code) {
        $stmt = $this->db->prepare("SELECT id FROM {$this->table} WHERE codigo = ?");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
    
    /**
     * Resposta JSON padronizada
     * 
     * @param mixed $data
     * @param string $message
     * @param int $status
     */
    protected function jsonResponse($data = null, $message = 'Sucesso', $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => $status < 400,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Resposta de erro
     * 
     * @param string $message
     * @param int $status
     */
    protected function errorResponse($message, $status = 400) {
        $this->jsonResponse(null, $message, $status);
    }
    
    /**
     * Verificar se usuário está autenticado
     * 
     * @return array
     * @throws Exception
     */
    protected function requireAuth() {
        if (!isset($_SESSION['gestor_id']) || !isset($_SESSION['gestor_nivel'])) {
            throw new Exception('Acesso negado. Faça login primeiro.', 401);
        }
        
        // Verificar timeout da sessão
        if (isset($_SESSION['last_activity'])) {
            $timeout = DatabaseConfig::getSessionTimeout();
            if (time() - $_SESSION['last_activity'] > $timeout) {
                session_destroy();
                throw new Exception('Sessão expirada. Faça login novamente.', 401);
            }
        }
        
        $_SESSION['last_activity'] = time();
        
        return [
            'id' => $_SESSION['gestor_id'],
            'nivel' => $_SESSION['gestor_nivel'],
            'nome' => $_SESSION['gestor_nome'] ?? 'Gestor'
        ];
    }
    
    /**
     * Verificar método HTTP
     * 
     * @param string|array $allowed
     * @throws Exception
     */
    protected function checkMethod($allowed) {
        $method = $_SERVER['REQUEST_METHOD'];
        $allowed = is_array($allowed) ? $allowed : [$allowed];
        
        if (!in_array($method, $allowed)) {
            throw new Exception('Método não permitido', 405);
        }
    }
    
    /**
     * Obter dados da requisição
     * 
     * @return array
     */
    protected function getRequestData() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                return $_GET;
                
            case 'POST':
                return $_POST;
                
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                return $data ?: [];
                
            default:
                return [];
        }
    }
    
    /**
     * Log de atividades do sistema
     * 
     * @param string $action
     * @param array $details
     */
    protected function logActivity($action, $details = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        // Log em arquivo (pode ser expandido para banco de dados)
        $logFile = '../logs/' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($logData) . PHP_EOL;
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Utilitários de segurança
 */
class SecurityUtils {
    /**
     * Gerar hash seguro para senha
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    /**
     * Verificar senha
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Gerar token CSRF
     * 
     * @return string
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verificar token CSRF
     * 
     * @param string $token
     * @return bool
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitizar entrada HTML
     * 
     * @param string $input
     * @return string
     */
    public static function sanitizeHtml($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Validar coordenadas geográficas
     * 
     * @param float $latitude
     * @param float $longitude
     * @return bool
     */
    public static function validateCoordinates($latitude, $longitude) {
        return (
            is_numeric($latitude) && 
            is_numeric($longitude) && 
            $latitude >= -90 && 
            $latitude <= 90 && 
            $longitude >= -180 && 
            $longitude <= 180
        );
    }
}
?>