<?php
/**
 * API de Contato - Cidade Aberta Santarém
 * Endpoint para processamento de formulário de contato
 */

// Headers de segurança
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS para desenvolvimento
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: POST, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

require_once '../classes/BaseModel.php';

/**
 * Modelo para Contatos
 */
class ContatoModel extends BaseModel {
    protected $table = 'contatos';
    
    private $validationRules = [
        'nome' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 2,
            'max_length' => 100
        ],
        'email' => [
            'required' => true,
            'type' => 'email',
            'max_length' => 100
        ],
        'assunto' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 5,
            'max_length' => 150
        ],
        'mensagem' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 10,
            'max_length' => 2000
        ]
    ];
    
    /**
     * Processar formulário de contato
     * 
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function processContact($data) {
        try {
            // Validar dados
            $validatedData = $this->validateData($data, $this->validationRules);
            
            // Validação adicional - verificar se não é spam
            if ($this->isSpam($validatedData)) {
                throw new Exception('Mensagem identificada como spam', 400);
            }
            
            // Sanitizar dados
            $sanitizedData = [
                'nome' => SecurityUtils::sanitizeHtml($validatedData['nome']),
                'email' => filter_var($validatedData['email'], FILTER_SANITIZE_EMAIL),
                'assunto' => SecurityUtils::sanitizeHtml($validatedData['assunto']),
                'mensagem' => SecurityUtils::sanitizeHtml($validatedData['mensagem']),
                'ip_origem' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'data_envio' => date('Y-m-d H:i:s'),
                'status' => 'novo'
            ];
            
            // Salvar no banco de dados
            $contatoId = $this->saveContact($sanitizedData);
            
            // Enviar email de notificação (opcional)
            $this->sendNotificationEmail($sanitizedData);
            
            // Log da atividade
            $this->logActivity('contato_recebido', [
                'contato_id' => $contatoId,
                'nome' => $sanitizedData['nome'],
                'assunto' => $sanitizedData['assunto']
            ]);
            
            return [
                'id' => $contatoId,
                'protocolo' => 'CONT' . str_pad($contatoId, 6, '0', STR_PAD_LEFT),
                'data_envio' => $sanitizedData['data_envio']
            ];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Salvar contato no banco de dados
     * 
     * @param array $data
     * @return int
     */
    private function saveContact($data) {
        try {
            // Criar tabela se não existir
            $this->createContactTableIfNotExists();
            
            $sql = "INSERT INTO {$this->table} 
                    (nome, email, assunto, mensagem, ip_origem, user_agent, data_envio, status) 
                    VALUES (:nome, :email, :assunto, :mensagem, :ip_origem, :user_agent, :data_envio, :status)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Erro ao salvar contato: " . $e->getMessage());
            throw new Exception("Erro interno do servidor", 500);
        }
    }
    
    /**
     * Criar tabela de contatos se não existir
     */
    private function createContactTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            assunto VARCHAR(150) NOT NULL,
            mensagem TEXT NOT NULL,
            ip_origem VARCHAR(45),
            user_agent TEXT,
            data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('novo', 'lido', 'respondido') DEFAULT 'novo',
            resposta TEXT,
            data_resposta TIMESTAMP NULL,
            gestor_id INT,
            INDEX idx_data_envio (data_envio),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    /**
     * Verificar se a mensagem pode ser spam
     * 
     * @param array $data
     * @return bool
     */
    private function isSpam($data) {
        // Lista de palavras suspeitas
        $spamWords = [
            'viagra', 'casino', 'lottery', 'winner', 'congratulations',
            'nigerian prince', 'inheritance', 'millions', 'urgent',
            'click here', 'free money', 'no obligation'
        ];
        
        $content = strtolower($data['mensagem'] . ' ' . $data['assunto']);
        
        foreach ($spamWords as $word) {
            if (strpos($content, strtolower($word)) !== false) {
                return true;
            }
        }
        
        // Verificar excesso de links
        $linkCount = preg_match_all('/(http|https|www\.)/i', $content);
        if ($linkCount > 3) {
            return true;
        }
        
        // Verificar se tem muitos caracteres especiais
        $specialCharCount = preg_match_all('/[!@#$%^&*()_+=\[\]{}|;:",.<>?]/', $content);
        if ($specialCharCount > (strlen($content) * 0.2)) {
            return true;
        }
        
        // Verificar rate limiting por IP
        if ($this->checkRateLimit()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar rate limiting (máximo 5 mensagens por hora por IP)
     * 
     * @return bool
     */
    private function checkRateLimit() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $hourAgo = date('Y-m-d H:i:s', time() - 3600);
        
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE ip_origem = ? AND data_envio > ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$ip, $hourAgo]);
            $result = $stmt->fetch();
            
            return $result['count'] >= 5;
            
        } catch (PDOException $e) {
            // Se houver erro na consulta, permitir o envio
            return false;
        }
    }
    
    /**
     * Enviar email de notificação (implementação futura)
     * 
     * @param array $data
     */
    private function sendNotificationEmail($data) {
        // Implementação futura com PHPMailer ou similar
        // Por enquanto, apenas log
        error_log("Novo contato recebido: {$data['nome']} - {$data['assunto']}");
    }
}

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Apenas método POST é permitido', 405);
    }
    
    $contatoModel = new ContatoModel();
    $requestData = $contatoModel->getRequestData();
    
    // Processar contato
    $resultado = $contatoModel->processContact($requestData);
    
    $contatoModel->jsonResponse($resultado, 'Mensagem enviada com sucesso! Retornaremos em breve.', 201);
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
}
?>