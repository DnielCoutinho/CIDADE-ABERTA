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

// Função para obter dados da requisição
function getRequestData() {
    $input = file_get_contents('php://input');
    return $input ? json_decode($input, true) : [];
}

try {
    $ocorrenciaModel = new OcorrenciaModel();
    $method = $_SERVER['REQUEST_METHOD'];
    $requestData = getRequestData();
    
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
            
        case 'DELETE':
            handleDeleteRequest($ocorrenciaModel, $requestData);
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
        
        sendJsonResponse($ocorrencia, 'Ocorrência encontrada');
        return;
    }
    
    // Se foi solicitado estatísticas
    if (isset($data['action']) && $data['action'] === 'stats') {
        $stats = $model->getStatistics();
        sendJsonResponse($stats, 'Estatísticas obtidas com sucesso');
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
    
    sendJsonResponse([
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
        
        sendJsonResponse($ocorrencia, 'Ocorrência registrada com sucesso', true, 201);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manipular requisições PUT (atualizar ocorrência)
 */
function handlePutRequest($model, $data) {
    // Validar dados obrigatórios
    if (!isset($data['id'])) {
        throw new Exception('ID da ocorrência é obrigatório', 400);
    }
    
    $id = (int) $data['id'];
    
    // Verificar se é apenas atualização de status (compatibilidade)
    if (isset($data['action']) && $data['action'] === 'update_status') {
        if (!isset($data['status'])) {
            throw new Exception('Novo status é obrigatório', 400);
        }
        
        $status = $data['status'];
        $observacoes = $data['observacoes'] ?? '';
        
        try {
            $result = $model->updateStatus($id, $status, $observacoes);
            
            if ($result) {
                sendJsonResponse(null, 'Status da ocorrência atualizado com sucesso');
            } else {
                throw new Exception('Erro ao atualizar status da ocorrência', 500);
            }
        } catch (Exception $e) {
            throw $e;
        }
        return;
    }
    
    // Atualização completa da ocorrência
    try {
        $dadosPermitidos = [
            'tipo', 'endereco', 'descricao', 'status', 'prioridade', 
            'observacoes', 'latitude', 'longitude'
        ];
        
        $dadosAtualizacao = [];
        foreach ($dadosPermitidos as $campo) {
            if (isset($data[$campo])) {
                $dadosAtualizacao[$campo] = $data[$campo];
            }
        }
        
        if (empty($dadosAtualizacao)) {
            throw new Exception('Nenhum dado válido para atualização foi fornecido', 400);
        }
        
        $result = $model->update($id, $dadosAtualizacao);
        
        if ($result) {
            sendJsonResponse(null, 'Ocorrência atualizada com sucesso');
        } else {
            throw new Exception('Erro ao atualizar ocorrência', 500);
        }
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Manipular requisições DELETE (excluir ocorrência)
 */
function handleDeleteRequest($model, $data) {
    // Validar dados obrigatórios
    if (!isset($data['id'])) {
        throw new Exception('ID da ocorrência é obrigatório', 400);
    }
    
    $id = (int) $data['id'];
    
    try {
        $result = $model->delete($id);
        
        if ($result) {
            sendJsonResponse(null, 'Ocorrência excluída com sucesso');
        } else {
            throw new Exception('Erro ao excluir ocorrência ou ocorrência não encontrada', 404);
        }
        
    } catch (Exception $e) {
        throw $e;
    }
}
?>