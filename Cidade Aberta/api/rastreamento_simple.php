<?php
/**
 * API Simples para Rastreamento de Ocorrências
 * Cidade Aberta Santarém
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $db = DatabaseConfig::getConnection();
    
    // Obter código da ocorrência
    $codigo = null;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $codigo = $_GET['codigo'] ?? null;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $codigo = $input['codigo'] ?? $input['id_ocorrencia'] ?? null;
    }
    
    if (!$codigo) {
        throw new Exception('Código da ocorrência é obrigatório');
    }
    
    // Sanitizar código
    $codigo = trim(strtoupper($codigo));
    
    // Validar formato do código (STM + números)
    if (!preg_match('/^STM\d+$/', $codigo)) {
        throw new Exception('Formato de código inválido. Use o formato STM000000');
    }
    
    // Para rastreamento público, verificar se é admin sem iniciar sessão desnecessária
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isAdmin = isset($_SESSION['gestor_id']);
    
    // Buscar ocorrência - público pode ver informações básicas
    if ($isAdmin) {
        // Admin pode ver todas as informações
        $sql = "SELECT 
                    codigo, 
                    tipo, 
                    endereco, 
                    descricao, 
                    status, 
                    data_criacao, 
                    data_atualizacao,
                    latitude,
                    longitude,
                    nome_cidadao,
                    email_cidadao,
                    observacoes
                FROM ocorrencias 
                WHERE codigo = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$codigo]);
        
    } else {
        // Usuários públicos (incluindo visitantes) - informações básicas para transparência
        $sql = "SELECT 
                    codigo, 
                    tipo, 
                    endereco, 
                    descricao, 
                    status, 
                    data_criacao, 
                    data_atualizacao,
                    latitude,
                    longitude
                FROM ocorrencias 
                WHERE codigo = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$codigo]);
    }
    
    $ocorrencia = $stmt->fetch();
    
    if (!$ocorrencia) {
        throw new Exception('Ocorrência não encontrada ou não está disponível para consulta pública');
    }
    
    // Formatar datas
    $ocorrencia['data_criacao_formatada'] = date('d/m/Y H:i', strtotime($ocorrencia['data_criacao']));
    if ($ocorrencia['data_atualizacao']) {
        $ocorrencia['data_atualizacao_formatada'] = date('d/m/Y H:i', strtotime($ocorrencia['data_atualizacao']));
    }
    
    // Gerar timeline de status
    $timeline = generateTimeline($ocorrencia);
    
    // Calcular tempo de processamento
    $tempoProcessamento = calculateProcessingTime($ocorrencia);
    
    // Preparar resposta
    $response = [
        'codigo' => $ocorrencia['codigo'],
        'tipo' => $ocorrencia['tipo'],
        'endereco' => $ocorrencia['endereco'],
        'descricao' => $ocorrencia['descricao'],
        'status' => $ocorrencia['status'],
        'data_criacao' => $ocorrencia['data_criacao'],
        'data_criacao_formatada' => $ocorrencia['data_criacao_formatada'],
        'data_atualizacao' => $ocorrencia['data_atualizacao'],
        'data_atualizacao_formatada' => $ocorrencia['data_atualizacao_formatada'] ?? null,
        'latitude' => $ocorrencia['latitude'],
        'longitude' => $ocorrencia['longitude'],
        'timeline' => $timeline,
        'tempo_processamento' => $tempoProcessamento
    ];
    
    // Adicionar campos administrativos apenas se for admin
    if ($isAdmin) {
        $response['nome_cidadao'] = $ocorrencia['nome_cidadao'] ?? null;
        $response['email_cidadao'] = $ocorrencia['email_cidadao'] ?? null;
        $response['observacoes'] = $ocorrencia['observacoes'] ?? null;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $response,
        'message' => 'Ocorrência encontrada com sucesso'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Gerar timeline de status da ocorrência
 */
function generateTimeline($ocorrencia) {
    $timeline = [];
    
    // 1. Registrada (sempre presente)
    $timeline[] = [
        'status' => 'registrada',
        'titulo' => 'Ocorrência Registrada',
        'descricao' => 'Sua ocorrência foi registrada no sistema',
        'data' => $ocorrencia['data_criacao'],
        'data_formatada' => $ocorrencia['data_criacao_formatada'],
        'concluido' => true,
        'ativo' => false,
        'icon' => 'fas fa-plus-circle',
        'cor' => 'blue'
    ];
    
    // 2. Em análise (sempre presente após registro)
    $timeline[] = [
        'status' => 'analise',
        'titulo' => 'Em Análise',
        'descricao' => 'Equipe técnica está analisando a ocorrência',
        'data' => $ocorrencia['data_criacao'],
        'data_formatada' => $ocorrencia['data_criacao_formatada'],
        'concluido' => true,
        'ativo' => $ocorrencia['status'] === 'pendente',
        'icon' => 'fas fa-search',
        'cor' => 'orange'
    ];
    
    // 3. Em andamento
    $emAndamento = in_array($ocorrencia['status'], ['em_andamento', 'concluida']);
    $dataAndamento = $emAndamento ? $ocorrencia['data_atualizacao'] : null;
    
    $timeline[] = [
        'status' => 'andamento',
        'titulo' => 'Em Andamento',
        'descricao' => 'Equipe está trabalhando na resolução do problema',
        'data' => $dataAndamento,
        'data_formatada' => $dataAndamento ? date('d/m/Y H:i', strtotime($dataAndamento)) : null,
        'concluido' => $emAndamento,
        'ativo' => $ocorrencia['status'] === 'em_andamento',
        'icon' => 'fas fa-tools',
        'cor' => 'blue'
    ];
    
    // 4. Concluída
    $concluida = $ocorrencia['status'] === 'concluida';
    $dataConclusao = $concluida ? $ocorrencia['data_atualizacao'] : null;
    
    $timeline[] = [
        'status' => 'concluida',
        'titulo' => 'Concluída',
        'descricao' => 'Problema resolvido com sucesso!',
        'data' => $dataConclusao,
        'data_formatada' => $dataConclusao ? date('d/m/Y H:i', strtotime($dataConclusao)) : null,
        'concluido' => $concluida,
        'ativo' => $concluida,
        'icon' => 'fas fa-check-circle',
        'cor' => 'green'
    ];
    
    return $timeline;
}

/**
 * Calcular tempo de processamento
 */
function calculateProcessingTime($ocorrencia) {
    $dataCriacao = new DateTime($ocorrencia['data_criacao']);
    $agora = new DateTime();
    
    $diff = $dataCriacao->diff($agora);
    
    // Tempo decorrido desde o registro
    $tempoDecorrido = '';
    if ($diff->days > 0) {
        $tempoDecorrido = $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
    } elseif ($diff->h > 0) {
        $tempoDecorrido = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    } else {
        $tempoDecorrido = $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    }
    
    $resultado = [
        'tempo_decorrido' => $tempoDecorrido,
        'dias' => $diff->days,
        'horas' => $diff->h,
        'minutos' => $diff->i
    ];
    
    // Se concluída, calcular tempo de resolução
    if ($ocorrencia['status'] === 'concluida' && $ocorrencia['data_atualizacao']) {
        $dataConclusao = new DateTime($ocorrencia['data_atualizacao']);
        $diffResolucao = $dataCriacao->diff($dataConclusao);
        
        $tempoResolucao = '';
        if ($diffResolucao->days > 0) {
            $tempoResolucao = $diffResolucao->days . ' dia' . ($diffResolucao->days > 1 ? 's' : '');
        } elseif ($diffResolucao->h > 0) {
            $tempoResolucao = $diffResolucao->h . ' hora' . ($diffResolucao->h > 1 ? 's' : '');
        } else {
            $tempoResolucao = 'Menos de 1 hora';
        }
        
        $resultado['tempo_resolucao'] = $tempoResolucao;
        $resultado['resolvida_em'] = [
            'dias' => $diffResolucao->days,
            'horas' => $diffResolucao->h,
            'minutos' => $diffResolucao->i
        ];
    }
    
    return $resultado;
}
?>