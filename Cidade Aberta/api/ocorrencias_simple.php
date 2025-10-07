<?php
/**
 * API Simples para Ocorrências
 * Cidade Aberta Santarém
 */

require_once '../database/Connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
        
        // Log para debug
        error_log("Dados recebidos: " . print_r($input, true));
        
        // Validar dados obrigatórios
        $requiredFields = ['tipo', 'endereco', 'descricao'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                throw new Exception("Campo obrigatório: {$field}");
            }
        }
        
        // Gerar código único
        $stmt = $db->query("SELECT COUNT(*) as total FROM ocorrencias");
        $count = $stmt->fetch()['total'];
        $codigo = 'STM' . str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        
        // Inserir no banco
        $sql = "INSERT INTO ocorrencias (codigo, tipo, endereco, descricao, status, data_criacao, latitude, longitude) 
                VALUES (?, ?, ?, ?, 'pendente', NOW(), ?, ?)";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $codigo,
            $input['tipo'],
            $input['endereco'],
            $input['descricao'],
            $input['latitude'] ?? null,
            $input['longitude'] ?? null
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Ocorrência registrada com sucesso!',
                'data' => [
                    'codigo' => $codigo,
                    'status' => 'pendente'
                ]
            ]);
        } else {
            throw new Exception('Erro ao salvar no banco de dados');
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Listar ocorrências
        $sql = "SELECT codigo, tipo, endereco, descricao, status, data_criacao, latitude, longitude 
                FROM ocorrencias 
                ORDER BY data_criacao DESC 
                LIMIT 50";
        
        $stmt = $db->query($sql);
        $ocorrencias = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $ocorrencias
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>