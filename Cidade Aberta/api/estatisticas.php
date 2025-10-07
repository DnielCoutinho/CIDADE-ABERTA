<?php
/**
 * API para obter estatísticas das ocorrências
 * Retorna dados para exibição na página inicial
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $db = DatabaseConfig::getConnection();
    
    // Debug: verificar se a conexão funciona
    if (!$db) {
        throw new Exception('Falha na conexão com o banco de dados');
    }
    
    // Estatísticas gerais
    $stats = [
        'ocorrencias_resolvidas' => 0,
        'satisfacao' => 0,
        'horas_medias' => 0,
        'total_ocorrencias' => 0,
        'pendentes' => 0,
        'em_andamento' => 0,
        'concluidas' => 0,
        'por_tipo' => [],
        'ultimas_ocorrencias' => [],
        'debug' => [] // Para debug
    ];
    
    // Debug: verificar se existem tabelas
    $stmt = $db->query("SHOW TABLES LIKE 'ocorrencias'");
    $tableExists = $stmt->fetch();
    $stats['debug']['table_exists'] = $tableExists ? true : false;
    
    if (!$tableExists) {
        throw new Exception('Tabela ocorrencias não encontrada');
    }
    
    // Total de ocorrências
    $stmt = $db->query("SELECT COUNT(*) as total FROM ocorrencias");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_ocorrencias'] = (int)$result['total'];
    $stats['debug']['total_query'] = $result;
    
    // Debug: verificar dados brutos
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM ocorrencias GROUP BY status");
    $rawStats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rawStats[$row['status']] = (int)$row['count'];
    }
    $stats['debug']['raw_status'] = $rawStats;
    
    // Ocorrências por status - versão simplificada
    $stmt = $db->query("
        SELECT 
            status, 
            COUNT(*) as total 
        FROM ocorrencias 
        GROUP BY status
    ");
    
    $statusCounts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $statusCounts[$row['status']] = (int)$row['total'];
    }
    
    // Atribuir valores baseado nos status encontrados
    $stats['pendentes'] = isset($statusCounts['pendente']) ? $statusCounts['pendente'] : 0;
    $stats['em_andamento'] = isset($statusCounts['em_andamento']) ? $statusCounts['em_andamento'] : 0;
    $stats['concluidas'] = isset($statusCounts['concluida']) ? $statusCounts['concluida'] : 0;
    
    // Se não encontrar 'concluida', tentar 'concluido' (compatibilidade)
    if ($stats['concluidas'] == 0 && isset($statusCounts['concluido'])) {
        $stats['concluidas'] = $statusCounts['concluido'];
    }
    
    // Se não encontrar 'em_andamento', tentar 'andamento' (compatibilidade)
    if ($stats['em_andamento'] == 0 && isset($statusCounts['andamento'])) {
        $stats['em_andamento'] = $statusCounts['andamento'];
    }
    
    $stats['ocorrencias_resolvidas'] = $stats['concluidas'];
    $stats['debug']['status_counts'] = $statusCounts;
    
    // Calcular satisfação (baseado na proporção de resolvidas)
    if ($stats['total_ocorrencias'] > 0) {
        $stats['satisfacao'] = round(($stats['concluidas'] / $stats['total_ocorrencias']) * 100);
    }
    
    // Tempo médio de resolução (em horas) - versão mais robusta
    $stmt = $db->query("
        SELECT AVG(TIMESTAMPDIFF(HOUR, data_criacao, data_atualizacao)) as media_horas
        FROM ocorrencias 
        WHERE (status = 'concluida' OR status = 'concluido') 
        AND data_atualizacao IS NOT NULL
        AND data_atualizacao > data_criacao
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['horas_medias'] = $result['media_horas'] ? round($result['media_horas']) : 24; // Default 24h se não houver dados
    
    // Estatísticas por tipo - versão corrigida
    $stmt = $db->query("
        SELECT 
            tipo,
            COUNT(*) as total,
            COUNT(CASE WHEN status IN ('concluida', 'concluido') THEN 1 END) as resolvidas
        FROM ocorrencias 
        GROUP BY tipo
        ORDER BY total DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total = (int)$row['total'];
        $resolvidas = (int)$row['resolvidas'];
        $stats['por_tipo'][] = [
            'tipo' => $row['tipo'],
            'total' => $total,
            'resolvidas' => $resolvidas,
            'percentual' => $total > 0 ? round(($resolvidas / $total) * 100) : 0
        ];
    }
    
    // Últimas ocorrências
    $stmt = $db->query("
        SELECT codigo, tipo, endereco, status, data_criacao
        FROM ocorrencias 
        ORDER BY data_criacao DESC 
        LIMIT 5
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['ultimas_ocorrencias'][] = [
            'codigo' => $row['codigo'],
            'tipo' => $row['tipo'],
            'endereco' => $row['endereco'],
            'status' => $row['status'],
            'data' => date('d/m/Y H:i', strtotime($row['data_criacao']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao obter estatísticas: ' . $e->getMessage()
    ]);
}
?>