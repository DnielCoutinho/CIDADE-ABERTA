<?php
/**
 * API Simples para Contatos
 * Cidade Aberta Santarém
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendResponse($data, $message = '', $success = true) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = DatabaseConfig::getConnection();
    
    // Apenas POST é permitido para enviar contatos
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Apenas método POST é permitido');
    }
    
    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validação dos campos obrigatórios
    $nome = trim($input['nome'] ?? '');
    $email = trim($input['email'] ?? '');
    $assunto = trim($input['assunto'] ?? '');
    $mensagem = trim($input['mensagem'] ?? '');
    $telefone = trim($input['telefone'] ?? '');
    
    if (empty($nome)) {
        throw new Exception('Nome é obrigatório');
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email válido é obrigatório');
    }
    
    if (empty($assunto)) {
        throw new Exception('Assunto é obrigatório');
    }
    
    if (empty($mensagem)) {
        throw new Exception('Mensagem é obrigatória');
    }
    
    // Inserir contato no banco
    $sql = "INSERT INTO contatos (nome, email, telefone, assunto, mensagem, data_criacao, status) 
            VALUES (?, ?, ?, ?, ?, NOW(), 'novo')";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$nome, $email, $telefone ?: null, $assunto, $mensagem]);
    
    $contactId = $db->lastInsertId();
    
    sendResponse([
        'id' => $contactId,
        'protocolo' => 'CNT' . str_pad($contactId, 6, '0', STR_PAD_LEFT)
    ], 'Contato enviado com sucesso! Entraremos em contato em breve.');
    
} catch (Exception $e) {
    http_response_code(400);
    sendResponse(null, $e->getMessage(), false);
}
?>