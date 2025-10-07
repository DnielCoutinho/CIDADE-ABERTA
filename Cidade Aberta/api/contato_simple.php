<?php
/**
 * API Simples para Contatos
 * Cidade Aberta Santarém
 */

require_once '../database/Connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = Database::getInstance()->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Receber dados do formulário
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Se não vier JSON, tentar POST normal
        if (!$input) {
            $input = $_POST;
        }
        
        // Log para debug
        error_log("Dados de contato recebidos: " . print_r($input, true));
        
        // Validar dados obrigatórios
        $requiredFields = ['nome', 'email', 'assunto', 'mensagem'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }
        
        // Validar email
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email inválido");
        }
        
        // Criar tabela se não existir
        $createTable = "
            CREATE TABLE IF NOT EXISTS contatos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                telefone VARCHAR(20),
                assunto VARCHAR(200) NOT NULL,
                mensagem TEXT NOT NULL,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('novo', 'lido', 'respondido') DEFAULT 'novo'
            )
        ";
        $db->exec($createTable);
        
        // Inserir no banco
        $sql = "INSERT INTO contatos (nome, email, telefone, assunto, mensagem, data_criacao) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $input['nome'],
            $input['email'],
            $input['telefone'] ?? null,
            $input['assunto'],
            $input['mensagem']
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso! Retornaremos em breve.'
            ]);
        } else {
            throw new Exception('Erro ao salvar mensagem');
        }
        
    } else {
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>