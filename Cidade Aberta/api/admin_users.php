<?php
/**
 * API de Gerenciamento de Usuários Administrativos
 * Cidade Aberta Santarém
 */

require_once __DIR__ . '/../database/Connection.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar se usuário está logado e é admin
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        throw new Exception('Acesso negado. Login administrativo necessário.', 401);
    }
    
    // Verificar se é super admin para operações sensíveis
    $adminLevel = $_SESSION['gestor_nivel'] ?? 'admin';
    $adminId = $_SESSION['gestor_id'];
    
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($method) {
        case 'GET':
            handleGetRequest($db, $adminId);
            break;
            
        case 'POST':
            handlePostRequest($db, $input, $adminId, $adminLevel);
            break;
            
        case 'PUT':
            handlePutRequest($db, $input, $adminId, $adminLevel);
            break;
            
        case 'DELETE':
            handleDeleteRequest($db, $input, $adminId, $adminLevel);
            break;
            
        default:
            throw new Exception('Método não permitido', 405);
    }
    
} catch (Exception $e) {
    $errorCode = is_numeric($e->getCode()) ? $e->getCode() : 400;
    http_response_code($errorCode ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Listar usuários e logs
 */
function handleGetRequest($db, $adminId) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Listar todos os gestores
            $sql = "SELECT 
                        g.id, g.nome, g.email, g.cargo, g.departamento, 
                        g.ativo, g.nivel_acesso, g.data_criacao,
                        g.ultimo_login, g.telefone, g.foto_perfil
                    FROM gestores g
                    ORDER BY g.nivel_acesso DESC, g.nome ASC";
            
            $stmt = $db->query($sql);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $users
            ]);
            break;
            
        case 'logs':
            // Listar logs recentes
            $limit = $_GET['limit'] ?? 50;
            $sql = "SELECT 
                        l.*, g.nome as admin_nome
                    FROM admin_logs l
                    JOIN gestores g ON l.admin_id = g.id
                    ORDER BY l.data_criacao DESC
                    LIMIT ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$limit]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $logs
            ]);
            break;
            
        case 'stats':
            // Estatísticas do painel
            $stats = [];
            
            // Total de admins ativos
            $stmt = $db->query("SELECT COUNT(*) as total FROM gestores WHERE ativo = 1");
            $stats['admins_ativos'] = $stmt->fetch()['total'];
            
            // Admins inativos (em vez de bloqueados, já que a coluna bloqueado não existe)
            $stmt = $db->query("SELECT COUNT(*) as total FROM gestores WHERE ativo = 0");
            $stats['admins_bloqueados'] = $stmt->fetch()['total'];
            
            // Logins hoje
            $stmt = $db->query("SELECT COUNT(*) as total FROM admin_logs WHERE acao LIKE '%login%' AND DATE(data_criacao) = CURDATE()");
            $stats['logins_hoje'] = $stmt->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
}

/**
 * Criar novo usuário
 */
function handlePostRequest($db, $input, $adminId, $adminLevel) {
    // Apenas super admins podem criar novos usuários
    if ($adminLevel !== 'super_admin') {
        throw new Exception('Apenas super administradores podem criar novos usuários', 403);
    }
    
    $nome = trim($input['nome'] ?? '');
    $email = trim($input['email'] ?? '');
    $cargo = trim($input['cargo'] ?? '');
    $departamento = trim($input['departamento'] ?? '');
    $nivelAcesso = $input['nivel_acesso'] ?? 'admin';
    
    // Validações
    if (empty($nome) || empty($email) || empty($cargo) || empty($departamento)) {
        throw new Exception('Todos os campos são obrigatórios');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    if (!in_array($nivelAcesso, ['admin', 'super_admin'])) {
        throw new Exception('Nível de acesso inválido');
    }
    
    // Verificar se email já existe
    $stmt = $db->prepare("SELECT id FROM gestores WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Este email já está cadastrado');
    }
    
    // Gerar senha temporária
    $senhaTemporaria = generateTempPassword();
    $senhaHash = password_hash($senhaTemporaria, PASSWORD_DEFAULT);
    
    // Gerar token de convite
    $tokenConvite = bin2hex(random_bytes(32));
    $tokenExpira = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Inserir novo usuário
    $sql = "INSERT INTO gestores 
            (nome, email, senha, cargo, departamento, ativo, criado_por, nivel_acesso, senha_temporaria, token_convite, token_expira) 
            VALUES (?, ?, ?, ?, ?, 1, ?, ?, 1, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([
        $nome, $email, $senhaHash, $cargo, $departamento, 
        $adminId, $nivelAcesso, $tokenConvite, $tokenExpira
    ]);
    
    if ($success) {
        $newUserId = $db->lastInsertId();
        
        // Log da ação
        logAdminAction($db, $adminId, 'usuario_criado', [
            'novo_usuario_id' => $newUserId,
            'nome' => $nome,
            'email' => $email,
            'nivel_acesso' => $nivelAcesso
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Usuário criado com sucesso!',
            'data' => [
                'id' => $newUserId,
                'senha_temporaria' => $senhaTemporaria,
                'token_convite' => $tokenConvite
            ]
        ]);
    } else {
        throw new Exception('Erro ao criar usuário');
    }
}

/**
 * Atualizar usuário
 */
function handlePutRequest($db, $input, $adminId, $adminLevel) {
    $userId = $input['id'] ?? null;
    $action = $input['action'] ?? 'update';
    
    if (!$userId) {
        throw new Exception('ID do usuário é obrigatório');
    }
    
    switch ($action) {
        case 'update':
            // Atualizar dados básicos
            $nome = trim($input['nome'] ?? '');
            $cargo = trim($input['cargo'] ?? '');
            $departamento = trim($input['departamento'] ?? '');
            
            if (empty($nome) || empty($cargo) || empty($departamento)) {
                throw new Exception('Nome, cargo e departamento são obrigatórios');
            }
            
            $sql = "UPDATE gestores SET nome = ?, cargo = ?, departamento = ? WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$nome, $cargo, $departamento, $userId]);
            
            logAdminAction($db, $adminId, 'usuario_atualizado', [
                'usuario_id' => $userId,
                'mudancas' => ['nome' => $nome, 'cargo' => $cargo, 'departamento' => $departamento]
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
            break;
            
        case 'toggle_status':
            // Ativar/desativar usuário (apenas super admin)
            if ($adminLevel !== 'super_admin') {
                throw new Exception('Apenas super administradores podem alterar status de usuários', 403);
            }
            
            if ($userId == $adminId) {
                throw new Exception('Você não pode desativar sua própria conta');
            }
            
            $sql = "UPDATE gestores SET ativo = NOT ativo WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId]);
            
            logAdminAction($db, $adminId, 'status_alterado', ['usuario_id' => $userId]);
            
            echo json_encode(['success' => true, 'message' => 'Status alterado com sucesso!']);
            break;
            
        case 'reset_password':
            // Resetar senha
            $senhaTemporaria = generateTempPassword();
            $senhaHash = password_hash($senhaTemporaria, PASSWORD_DEFAULT);
            
            $sql = "UPDATE gestores SET senha = ?, senha_temporaria = 1, tentativas_login = 0, bloqueado = 0 WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$senhaHash, $userId]);
            
            logAdminAction($db, $adminId, 'senha_resetada', ['usuario_id' => $userId]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Senha resetada com sucesso!',
                'nova_senha' => $senhaTemporaria
            ]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
}

/**
 * Deletar usuário
 */
function handleDeleteRequest($db, $input, $adminId, $adminLevel) {
    // Apenas super admins podem deletar usuários
    if ($adminLevel !== 'super_admin') {
        throw new Exception('Apenas super administradores podem deletar usuários', 403);
    }
    
    $userId = $input['id'] ?? null;
    
    if (!$userId) {
        throw new Exception('ID do usuário é obrigatório');
    }
    
    if ($userId == $adminId) {
        throw new Exception('Você não pode deletar sua própria conta');
    }
    
    // Buscar dados do usuário antes de deletar
    $stmt = $db->prepare("SELECT nome, email FROM gestores WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Deletar usuário
    $stmt = $db->prepare("DELETE FROM gestores WHERE id = ?");
    $success = $stmt->execute([$userId]);
    
    if ($success) {
        logAdminAction($db, $adminId, 'usuario_deletado', [
            'usuario_deletado_id' => $userId,
            'nome' => $user['nome'],
            'email' => $user['email']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Usuário deletado com sucesso!']);
    } else {
        throw new Exception('Erro ao deletar usuário');
    }
}

/**
 * Gerar senha temporária
 */
function generateTempPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Registrar ação administrativa
 */
function logAdminAction($db, $adminId, $acao, $detalhes = []) {
    $sql = "INSERT INTO admin_logs (admin_id, acao, detalhes, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $adminId,
        $acao,
        json_encode($detalhes),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}
?>