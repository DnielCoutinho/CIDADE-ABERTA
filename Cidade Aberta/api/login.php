<?php
/**
 * API de Autenticação - Cidade Aberta Santarém
 * Endpoint para login, logout e gerenciamento de sessões
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
        header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

require_once '../classes/GestorModel.php';

try {
    $gestorModel = new GestorModel();
    $method = $_SERVER['REQUEST_METHOD'];
    $requestData = $gestorModel->getRequestData();
    
    switch ($method) {
        case 'GET':
            handleGetRequest($gestorModel);
            break;
            
        case 'POST':
            handlePostRequest($gestorModel, $requestData);
            break;
            
        case 'DELETE':
            handleDeleteRequest($gestorModel);
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
 * Manipular requisições GET (verificar sessão)
 */
function handleGetRequest($model) {
    $currentGestor = $model->getCurrentGestor();
    
    if ($currentGestor) {
        $model->jsonResponse([
            'gestor' => $currentGestor,
            'csrf_token' => SecurityUtils::generateCSRFToken()
        ], 'Sessão ativa');
    } else {
        throw new Exception('Sessão não encontrada ou expirada', 401);
    }
}

/**
 * Manipular requisições POST (login ou outras ações)
 */
function handlePostRequest($model, $data) {
    // Verificar se é uma ação específica
    $action = $data['action'] ?? 'login';
    
    switch ($action) {
        case 'login':
            handleLogin($model, $data);
            break;
            
        case 'create_gestor':
            handleCreateGestor($model, $data);
            break;
            
        case 'change_password':
            handleChangePassword($model, $data);
            break;
            
        case 'list_gestores':
            handleListGestores($model);
            break;
            
        case 'toggle_gestor_status':
            handleToggleGestorStatus($model, $data);
            break;
            
        default:
            throw new Exception('Ação não reconhecida', 400);
    }
}

/**
 * Manipular requisições DELETE (logout)
 */
function handleDeleteRequest($model) {
    $model->logout();
    $model->jsonResponse(null, 'Logout realizado com sucesso');
}

/**
 * Processar login
 */
function handleLogin($model, $data) {
    // Validar campos obrigatórios
    if (!isset($data['usuario']) || !isset($data['senha'])) {
        throw new Exception('Usuário e senha são obrigatórios', 400);
    }
    
    // Verificar CSRF token se fornecido
    if (isset($data['csrf_token'])) {
        if (!SecurityUtils::verifyCSRFToken($data['csrf_token'])) {
            throw new Exception('Token de segurança inválido', 403);
        }
    }
    
    $credentials = [
        'usuario' => $data['usuario'],
        'senha' => $data['senha']
    ];
    
    try {
        $gestor = $model->authenticate($credentials);
        
        $model->jsonResponse([
            'gestor' => $gestor,
            'csrf_token' => SecurityUtils::generateCSRFToken()
        ], 'Login realizado com sucesso');
        
    } catch (Exception $e) {
        // Adicionar delay para prevenir ataques de força bruta
        sleep(1);
        throw $e;
    }
}

/**
 * Criar novo gestor
 */
function handleCreateGestor($model, $data) {
    // Verificar CSRF token
    if (!isset($data['csrf_token']) || !SecurityUtils::verifyCSRFToken($data['csrf_token'])) {
        throw new Exception('Token de segurança inválido', 403);
    }
    
    $gestorData = [
        'usuario' => $data['usuario'] ?? '',
        'senha' => $data['senha'] ?? '',
        'nome' => $data['nome'] ?? '',
        'email' => $data['email'] ?? '',
        'nivel' => $data['nivel'] ?? 'operador'
    ];
    
    try {
        $novoGestor = $model->create($gestorData);
        $model->jsonResponse($novoGestor, 'Gestor criado com sucesso', 201);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Alterar senha
 */
function handleChangePassword($model, $data) {
    // Verificar CSRF token
    if (!isset($data['csrf_token']) || !SecurityUtils::verifyCSRFToken($data['csrf_token'])) {
        throw new Exception('Token de segurança inválido', 403);
    }
    
    $passwordData = [
        'senha_atual' => $data['senha_atual'] ?? '',
        'senha_nova' => $data['senha_nova'] ?? '',
        'confirmar_senha' => $data['confirmar_senha'] ?? ''
    ];
    
    try {
        $result = $model->changePassword($passwordData);
        
        if ($result) {
            $model->jsonResponse(null, 'Senha alterada com sucesso');
        } else {
            throw new Exception('Erro ao alterar senha', 500);
        }
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Listar gestores
 */
function handleListGestores($model) {
    try {
        $gestores = $model->list();
        $model->jsonResponse($gestores, 'Gestores listados com sucesso');
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Ativar/desativar gestor
 */
function handleToggleGestorStatus($model, $data) {
    // Verificar CSRF token
    if (!isset($data['csrf_token']) || !SecurityUtils::verifyCSRFToken($data['csrf_token'])) {
        throw new Exception('Token de segurança inválido', 403);
    }
    
    if (!isset($data['gestor_id']) || !isset($data['ativo'])) {
        throw new Exception('ID do gestor e status são obrigatórios', 400);
    }
    
    $gestorId = (int) $data['gestor_id'];
    $ativo = (bool) $data['ativo'];
    
    try {
        $result = $model->changeStatus($gestorId, $ativo);
        
        if ($result) {
            $status = $ativo ? 'ativado' : 'desativado';
            $model->jsonResponse(null, "Gestor {$status} com sucesso");
        } else {
            throw new Exception('Erro ao alterar status do gestor', 500);
        }
        
    } catch (Exception $e) {
        throw $e;
    }
}
?>