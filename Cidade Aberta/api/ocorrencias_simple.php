<?php
/**
 * API Simples para Ocorrências Públicas
 * Cidade Aberta Santarém
 * Retorna apenas informações básicas das ocorrências para exibição pública
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
    
    // Apenas GET é permitido para dados públicos
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Apenas método GET é permitido');
    }
    
    // Parâmetros de filtro
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;
    $limite = min($limite, 50); // Máximo 50 ocorrências
    
    $tipo = $_GET['tipo'] ?? null;
    $status = $_GET['status'] ?? null;
    
    // Query base - apenas informações públicas
    $sql = "SELECT 
                id,
                codigo,
                tipo,
                endereco,
                descricao,
                status,
                prioridade,
                latitude,
                longitude,
                data_criacao,
                data_atualizacao,
                DATE_FORMAT(data_criacao, '%d/%m/%Y %H:%i') as data_criacao_formatada,
                DATE_FORMAT(data_atualizacao, '%d/%m/%Y %H:%i') as data_atualizacao_formatada
            FROM ocorrencias 
            WHERE 1=1";
    
    $params = [];
    
    // Filtros opcionais
    if ($tipo) {
        $sql .= " AND tipo = ?";
        $params[] = $tipo;
    }
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY data_criacao DESC LIMIT ?";
    $params[] = $limite;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $ocorrencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas básicas
    $statsSQL = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
                    COUNT(CASE WHEN status = 'em_andamento' THEN 1 END) as em_andamento,
                    COUNT(CASE WHEN status = 'concluida' THEN 1 END) as concluidas,
                    COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as canceladas
                FROM ocorrencias";
    
    $statsStmt = $db->prepare($statsSQL);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    sendResponse([
        'ocorrencias' => $ocorrencias,
        'estatisticas' => $stats,
        'parametros' => [
            'limite' => $limite,
            'tipo' => $tipo,
            'status' => $status
        ]
    ], 'Ocorrências carregadas com sucesso');
    
} catch (Exception $e) {
    http_response_code(500);
    sendResponse(null, $e->getMessage(), false);
}
?>