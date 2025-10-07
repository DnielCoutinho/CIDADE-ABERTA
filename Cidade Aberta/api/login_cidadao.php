<?php
/**
 * API de Login para Cidadãos - Cidade Aberta Santarém
 * Permite que cidadãos façam login com email para ver suas ocorrências
 */

require_once '../database/Connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = Database::getInstance()->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = trim($input['email'] ?? '');
        $nome = trim($input['nome'] ?? '');
        
        if (!$email) {
            throw new Exception('Email é obrigatório');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        // Buscar cidadão pelo email
        $sql = "SELECT DISTINCT email_cidadao, nome_cidadao 
                FROM ocorrencias 
                WHERE email_cidadao = ? 
                AND email_cidadao IS NOT NULL";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        $cidadao = $stmt->fetch();
        
        if (!$cidadao) {
            // Se forneceu nome, verificar se existe ocorrência com esse nome
            if ($nome) {
                $sql = "SELECT DISTINCT email_cidadao, nome_cidadao 
                        FROM ocorrencias 
                        WHERE nome_cidadao = ? 
                        AND (email_cidadao = ? OR email_cidadao IS NULL)";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([$nome, $email]);
                $cidadao = $stmt->fetch();
                
                if (!$cidadao) {
                    throw new Exception('Nenhuma ocorrência encontrada para este nome/email');
                }
            } else {
                throw new Exception('Nenhuma ocorrência encontrada para este email');
            }
        }
        
        // Buscar total de ocorrências do cidadão
        $sql = "SELECT COUNT(*) as total 
                FROM ocorrencias 
                WHERE email_cidadao = ? OR nome_cidadao = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$email, $cidadao['nome_cidadao']]);
        $total = $stmt->fetch()['total'];
        
        // Gerar token simples (em produção usar JWT)
        $token = base64_encode($email . ':' . time());
        
        // Salvar sessão simples
        session_start();
        $_SESSION['cidadao_email'] = $email;
        $_SESSION['cidadao_nome'] = $cidadao['nome_cidadao'];
        $_SESSION['cidadao_token'] = $token;
        
        echo json_encode([
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'data' => [
                'email' => $email,
                'nome' => $cidadao['nome_cidadao'],
                'token' => $token,
                'total_ocorrencias' => $total,
                'tipo_usuario' => 'cidadao'
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>