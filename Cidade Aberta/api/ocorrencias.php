<?php
/**
 * API de Ocorrências - Cidade Aberta Santarém
 * Endpoint para gerenciamento de ocorrências urbanas
 */

// Headers de segurança e CORS
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS para desenvolvimento (remover em produção)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

require_once '../classes/OcorrenciaModel.php';

try {
    $ocorrenciaModel = new OcorrenciaModel();
    $method = $_SERVER['REQUEST_METHOD'];
    $requestData = $ocorrenciaModel->getRequestData();
    
    switch ($method) {
        case 'GET':
            handleGetRequest($ocorrenciaModel, $requestData);
            break;
            
        case 'POST':
            handlePostRequest($ocorrenciaModel, $requestData);
            break;
            
        case 'PUT':
            handlePutRequest($ocorrenciaModel, $requestData);
            break;
            
        default:
            throw new Exception('Método não permitido', 405);
    }
    
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Manipular requisições GET
 */
function handleGetRequest($model, $data) {
    // Se foi fornecido um código, buscar ocorrência específica
    if (isset($data['codigo']) && !empty($data['codigo'])) {
        $ocorrencia = $model->findByCodigo($data['codigo']);
        
        if (!$ocorrencia) {
            throw new Exception('Ocorrência não encontrada', 404);
        }
        
        $model->jsonResponse($ocorrencia, 'Ocorrência encontrada');
        return;
    }
    
    // Se foi solicitado estatísticas
    if (isset($data['action']) && $data['action'] === 'stats') {
        $stats = $model->getStatistics();
        $model->jsonResponse($stats, 'Estatísticas obtidas com sucesso');
        return;
    }
    
    // Listar ocorrências com filtros
    $filters = [
        'status' => $data['status'] ?? null,
        'tipo' => $data['tipo'] ?? null,
        'data_inicio' => $data['data_inicio'] ?? null,
        'data_fim' => $data['data_fim'] ?? null,
        'busca' => $data['busca'] ?? null,
        'limit' => isset($data['limit']) ? (int)$data['limit'] : 50,
        'offset' => isset($data['offset']) ? (int)$data['offset'] : 0
    ];
    
    $ocorrencias = $model->list($filters);
    
    $model->jsonResponse([
        'ocorrencias' => $ocorrencias,
        'total' => count($ocorrencias),
        'filtros_aplicados' => array_filter($filters)
    ], 'Ocorrências listadas com sucesso');
}

/**
 * Manipular requisições POST (criar nova ocorrência)
 */
function handlePostRequest($model, $data) {
    // Validar dados obrigatórios
    $requiredFields = ['tipo', 'descricao', 'endereco', 'latitude', 'longitude', 'nome_cidadao'];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("Campo '{$field}' é obrigatório", 400);
        }
    }
    
    // Processar coordenadas vindas do JavaScript
    if (isset($data['coordenadas']) && is_array($data['coordenadas'])) {
        $data['latitude'] = $data['coordenadas'][0];
        $data['longitude'] = $data['coordenadas'][1];
    }
    
    // Converter coordenadas de string para float se necessário
    if (is_string($data['latitude'])) {
        $data['latitude'] = (float) $data['latitude'];
    }
    if (is_string($data['longitude'])) {
        $data['longitude'] = (float) $data['longitude'];
    }
    
    try {
        $ocorrencia = $model->create($data);
        
        $model->jsonResponse($ocorrencia, 'Ocorrência registrada com sucesso', 201);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manipular requisições PUT (atualizar status - apenas gestores)
 */
function handlePutRequest($model, $data) {
    // Verificar se é uma atualização de status
    if (!isset($data['action']) || $data['action'] !== 'update_status') {
        throw new Exception('Ação não especificada', 400);
    }
    
    // Validar dados obrigatórios
    if (!isset($data['id']) || !isset($data['status'])) {
        throw new Exception('ID da ocorrência e novo status são obrigatórios', 400);
    }
    
    $id = (int) $data['id'];
    $status = $data['status'];
    $observacoes = $data['observacoes'] ?? '';
    
    try {
        $result = $model->updateStatus($id, $status, $observacoes);
        
        if ($result) {
            $model->jsonResponse(null, 'Status da ocorrência atualizado com sucesso');
        } else {
            throw new Exception('Erro ao atualizar status da ocorrência', 500);
        }
        
    } catch (Exception $e) {
        throw $e;
    }
}
?>