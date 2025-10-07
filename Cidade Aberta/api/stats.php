<?php
/**
 * API de Estatísticas Públicas
 * Cidade Aberta Santarém
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    
    // Apenas GET é permitido
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Apenas método GET é permitido');
    }
    
    // Estatísticas gerais
    $statsSQL = "SELECT 
                    COUNT(*) as total_ocorrencias,
                    COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
                    COUNT(CASE WHEN status = 'em_andamento' THEN 1 END) as em_andamento,
                    COUNT(CASE WHEN status = 'concluida' THEN 1 END) as concluidas,
                    COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as canceladas,
                    COUNT(CASE WHEN data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as ultimos_30_dias,
                    COUNT(CASE WHEN data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as ultima_semana
                FROM ocorrencias";
    
    $stmt = $db->prepare($statsSQL);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estatísticas por tipo
    $tiposSQL = "SELECT 
                    tipo,
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'concluida' THEN 1 END) as resolvidas,
                    ROUND((COUNT(CASE WHEN status = 'concluida' THEN 1 END) * 100.0 / COUNT(*)), 1) as taxa_resolucao
                FROM ocorrencias 
                GROUP BY tipo 
                ORDER BY total DESC";
    
    $stmt = $db->prepare($tiposSQL);
    $stmt->execute();
    $porTipo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tempo médio de resolução
    $tempoSQL = "SELECT 
                    AVG(TIMESTAMPDIFF(HOUR, data_criacao, data_atualizacao)) as media_horas
                FROM ocorrencias 
                WHERE status = 'concluida' AND data_atualizacao IS NOT NULL";
    
    $stmt = $db->prepare($tempoSQL);
    $stmt->execute();
    $tempo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Estatísticas mensais (últimos 6 meses)
    $mensalSQL = "SELECT 
                    DATE_FORMAT(data_criacao, '%Y-%m') as mes,
                    DATE_FORMAT(data_criacao, '%m/%Y') as mes_formatado,
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'concluida' THEN 1 END) as resolvidas
                FROM ocorrencias 
                WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(data_criacao, '%Y-%m')
                ORDER BY mes DESC
                LIMIT 6";
    
    $stmt = $db->prepare($mensalSQL);
    $stmt->execute();
    $mensal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular taxa de satisfação (simulada baseada em resoluções)
    $taxa_satisfacao = $stats['total_ocorrencias'] > 0 
        ? round(($stats['concluidas'] * 100) / $stats['total_ocorrencias'], 1)
        : 0;
    
    $resultado = [
        'resumo' => [
            'total_ocorrencias' => (int)$stats['total_ocorrencias'],
            'pendentes' => (int)$stats['pendentes'],
            'em_andamento' => (int)$stats['em_andamento'],
            'concluidas' => (int)$stats['concluidas'],
            'canceladas' => (int)$stats['canceladas'],
            'ultimos_30_dias' => (int)$stats['ultimos_30_dias'],
            'ultima_semana' => (int)$stats['ultima_semana'],
            'taxa_satisfacao' => $taxa_satisfacao,
            'tempo_medio_resolucao' => $tempo['media_horas'] ? round($tempo['media_horas'], 1) : 0
        ],
        'por_tipo' => $porTipo,
        'historico_mensal' => array_reverse($mensal),
        'atualizado_em' => date('Y-m-d H:i:s')
    ];
    
    sendResponse($resultado, 'Estatísticas obtidas com sucesso');
    
} catch (Exception $e) {
    http_response_code(500);
    sendResponse(null, $e->getMessage(), false);
}
?>