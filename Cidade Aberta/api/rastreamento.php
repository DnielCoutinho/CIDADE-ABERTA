<?php
/**
 * API de Rastreamento - Cidade Aberta Santarém
 * Endpoint para rastreamento público de ocorrências por código
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
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

require_once '../classes/OcorrenciaModel.php';

// Função para obter dados da requisição
function getRequestData() {
    $input = file_get_contents('php://input');
    $data = $input ? json_decode($input, true) : [];
    
    // Mesclar com parâmetros GET
    return array_merge($_GET, $data ?: []);
}

// Função para enviar resposta JSON
function sendJsonResponse($data, $message = '', $success = true, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $ocorrenciaModel = new OcorrenciaModel();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Apenas GET e POST são permitidos para rastreamento
    if (!in_array($method, ['GET', 'POST'])) {
        throw new Exception('Método não permitido', 405);
    }
    
    $requestData = getRequestData();
    
    // Obter código da ocorrência
    $codigo = null;
    
    if ($method === 'GET') {
        $codigo = $requestData['codigo'] ?? null;
    } else if ($method === 'POST') {
        $codigo = $requestData['id_ocorrencia'] ?? $requestData['codigo'] ?? null;
    }
    
    if (!$codigo) {
        throw new Exception('Código da ocorrência é obrigatório', 400);
    }
    
    // Sanitizar código
    $codigo = trim(strtoupper($codigo));
    
    // Validar formato do código (STM + 6 dígitos)
    if (!preg_match('/^STM\d{6}$/', $codigo)) {
        throw new Exception('Formato de código inválido. Use o formato STM000000', 400);
    }
    
    // Buscar ocorrência
    $ocorrencia = $ocorrenciaModel->findByCodigo($codigo);
    
    if (!$ocorrencia) {
        // Log de tentativa de rastreamento de código inexistente
        $ocorrenciaModel->logActivity('rastreamento_codigo_inexistente', [
            'codigo' => $codigo,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        throw new Exception('Ocorrência não encontrada', 404);
    }
    
    // Preparar dados para resposta (remover informações sensíveis)
    $dadosPublicos = [
        'codigo' => $ocorrencia['codigo'],
        'tipo' => $ocorrencia['tipo'],
        'descricao' => $ocorrencia['descricao'],
        'endereco' => $ocorrencia['endereco'],
        'status' => $ocorrencia['status'],
        'data_criacao' => $ocorrencia['data_criacao'],
        'data_criacao_formatada' => $ocorrencia['data_criacao_formatada'],
        'data_atualizacao' => $ocorrencia['data_atualizacao'],
        'coordenadas' => [
            'latitude' => (float) $ocorrencia['latitude'],
            'longitude' => (float) $ocorrencia['longitude']
        ]
    ];
    
    // Adicionar URL da foto se existir
    if ($ocorrencia['foto']) {
        $dadosPublicos['foto_url'] = $ocorrencia['foto_url'];
    }
    
    // Adicionar data de atualização formatada se existir
    if ($ocorrencia['data_atualizacao']) {
        $dadosPublicos['data_atualizacao_formatada'] = $ocorrencia['data_atualizacao_formatada'];
    }
    
    // Adicionar observações se existir e ocorrência não estiver pendente
    if (isset($ocorrencia['observacoes']) && $ocorrencia['observacoes'] && $ocorrencia['status'] !== 'pendente') {
        $dadosPublicos['observacoes'] = $ocorrencia['observacoes'];
    }
    
    // Gerar timeline de status
    $dadosPublicos['timeline'] = generateStatusTimeline($ocorrencia);
    
    // Calcular tempo de processamento
    $dadosPublicos['tempo_processamento'] = calculateProcessingTime($ocorrencia);
    
    // Retornar dados
    sendJsonResponse($dadosPublicos, 'Ocorrência encontrada');
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Gerar timeline de status da ocorrência
 * 
 * @param array $ocorrencia
 * @return array
 */
function generateStatusTimeline($ocorrencia) {
    $timeline = [];
    
    // Status: Registrada
    $timeline[] = [
        'status' => 'registrada',
        'label' => 'Ocorrência Registrada',
        'descricao' => 'Sua ocorrência foi registrada no sistema',
        'data' => $ocorrencia['data_criacao'],
        'data_formatada' => $ocorrencia['data_criacao_formatada'],
        'concluido' => true,
        'icon' => 'fas fa-plus-circle'
    ];
    
    // Status: Em Análise (sempre presente)
    $emAnalise = true;
    $timeline[] = [
        'status' => 'analise',
        'label' => 'Em Análise',
        'descricao' => 'Equipe técnica está analisando a ocorrência',
        'data' => $ocorrencia['data_criacao'],
        'data_formatada' => $ocorrencia['data_criacao_formatada'],
        'concluido' => $emAnalise,
        'icon' => 'fas fa-search'
    ];
    
    // Status: Em Andamento
    $emAndamento = in_array($ocorrencia['status'], ['andamento', 'concluido']);
    $dataAndamento = $emAndamento ? $ocorrencia['data_atualizacao'] : null;
    $timeline[] = [
        'status' => 'andamento',
        'label' => 'Em Andamento',
        'descricao' => 'Equipe está trabalhando na resolução',
        'data' => $dataAndamento,
        'data_formatada' => $dataAndamento ? date('d/m/Y H:i', strtotime($dataAndamento)) : null,
        'concluido' => $emAndamento,
        'ativo' => $ocorrencia['status'] === 'andamento',
        'icon' => 'fas fa-tools'
    ];
    
    // Status: Concluído
    $concluido = $ocorrencia['status'] === 'concluido';
    $dataConclusao = $concluido ? $ocorrencia['data_atualizacao'] : null;
    $timeline[] = [
        'status' => 'concluido',
        'label' => 'Concluído',
        'descricao' => 'Ocorrência resolvida com sucesso',
        'data' => $dataConclusao,
        'data_formatada' => $dataConclusao ? date('d/m/Y H:i', strtotime($dataConclusao)) : null,
        'concluido' => $concluido,
        'icon' => 'fas fa-check-circle'
    ];
    
    return $timeline;
}

/**
 * Calcular tempo de processamento
 * 
 * @param array $ocorrencia
 * @return array
 */
function calculateProcessingTime($ocorrencia) {
    $dataCriacao = new DateTime($ocorrencia['data_criacao']);
    $agora = new DateTime();
    
    $diff = $dataCriacao->diff($agora);
    
    $resultado = [
        'dias' => $diff->days,
        'horas' => $diff->h,
        'minutos' => $diff->i,
        'texto' => ''
    ];
    
    // Gerar texto descritivo
    if ($diff->days > 0) {
        $resultado['texto'] = $diff->days . ' dia' . ($diff->days > 1 ? 's' : '');
        if ($diff->h > 0) {
            $resultado['texto'] .= ' e ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        }
    } elseif ($diff->h > 0) {
        $resultado['texto'] = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
        if ($diff->i > 0) {
            $resultado['texto'] .= ' e ' . $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
        }
    } else {
        $resultado['texto'] = $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    }
    
    // Se concluído, calcular tempo total de resolução
    if ($ocorrencia['status'] === 'concluido' && $ocorrencia['data_atualizacao']) {
        $dataConclusao = new DateTime($ocorrencia['data_atualizacao']);
        $diffResolucao = $dataCriacao->diff($dataConclusao);
        
        $resultado['tempo_resolucao'] = [
            'dias' => $diffResolucao->days,
            'horas' => $diffResolucao->h,
            'texto' => ''
        ];
        
        if ($diffResolucao->days > 0) {
            $resultado['tempo_resolucao']['texto'] = $diffResolucao->days . ' dia' . ($diffResolucao->days > 1 ? 's' : '');
            if ($diffResolucao->h > 0) {
                $resultado['tempo_resolucao']['texto'] .= ' e ' . $diffResolucao->h . ' hora' . ($diffResolucao->h > 1 ? 's' : '');
            }
        } elseif ($diffResolucao->h > 0) {
            $resultado['tempo_resolucao']['texto'] = $diffResolucao->h . ' hora' . ($diffResolucao->h > 1 ? 's' : '');
        } else {
            $resultado['tempo_resolucao']['texto'] = 'Menos de 1 hora';
        }
    }
    
    return $resultado;
}
?>