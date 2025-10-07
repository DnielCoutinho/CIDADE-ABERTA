<?php
/**
 * API para estatísticas das ocorrências - Cidade Aberta Santarém
 * Versão limpa e otimizada
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Conexão direta com o banco de dados
    $pdo = new PDO("mysql:host=localhost;dbname=cidade_aberta;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar total de ocorrências
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ocorrencias");
    $total = $stmt->fetch()['total'];
    
    // Buscar ocorrências por status
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = 'concluida' THEN 1 END) as concluidas,
            COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
            COUNT(CASE WHEN status = 'em_andamento' THEN 1 END) as em_andamento
        FROM ocorrencias
    ");
    $counts = $stmt->fetch();
    
    // Calcular satisfação (% de ocorrências resolvidas)
    $resolvidas = $counts['concluidas'];
    $satisfacao = $total > 0 ? round(($resolvidas / $total) * 100) : 0;
    
    // Calcular tempo médio de resolução em horas
    $stmt = $pdo->query("
        SELECT AVG(TIMESTAMPDIFF(HOUR, data_criacao, data_atualizacao)) as media_horas
        FROM ocorrencias 
        WHERE status = 'concluida' 
        AND data_atualizacao IS NOT NULL
        AND data_atualizacao > data_criacao
    ");
    $tempo = $stmt->fetch();
    $horas_medias = $tempo['media_horas'] ? round($tempo['media_horas']) : 24;
    
    // Estatísticas por tipo
    $stmt = $pdo->query("
        SELECT 
            tipo,
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'concluida' THEN 1 END) as resolvidas
        FROM ocorrencias 
        GROUP BY tipo
        ORDER BY total DESC
    ");
    
    $por_tipo = [];
    while ($row = $stmt->fetch()) {
        $por_tipo[] = [
            'tipo' => $row['tipo'],
            'total' => (int)$row['total'],
            'resolvidas' => (int)$row['resolvidas'],
            'percentual' => $row['total'] > 0 ? round(($row['resolvidas'] / $row['total']) * 100) : 0
        ];
    }
    
    // Últimas ocorrências
    $stmt = $pdo->query("
        SELECT codigo, tipo, endereco, status, data_criacao
        FROM ocorrencias 
        ORDER BY data_criacao DESC 
        LIMIT 5
    ");
    
    $ultimas = [];
    while ($row = $stmt->fetch()) {
        $ultimas[] = [
            'codigo' => $row['codigo'],
            'tipo' => $row['tipo'],
            'endereco' => $row['endereco'],
            'status' => $row['status'],
            'data' => date('d/m/Y H:i', strtotime($row['data_criacao']))
        ];
    }
    
    // Resposta final
    echo json_encode([
        'success' => true,
        'data' => [
            'ocorrencias_resolvidas' => $resolvidas,
            'satisfacao' => $satisfacao,
            'horas_medias' => $horas_medias,
            'total_ocorrencias' => $total,
            'pendentes' => $counts['pendentes'],
            'em_andamento' => $counts['em_andamento'],
            'concluidas' => $counts['concluidas'],
            'por_tipo' => $por_tipo,
            'ultimas_ocorrencias' => $ultimas
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao obter estatísticas: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>