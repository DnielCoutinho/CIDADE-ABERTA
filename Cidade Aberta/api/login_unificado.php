<?php
/**
 * API de Login Unificado - Cidade Aberta Santarém
 * Detecta automaticamente se é admin ou cidadão
 */

require_once __DIR__ . '/../database/Connection.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Tratar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    $db = Database::getInstance()->getConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Login
        $input = json_decode(file_get_contents('php://input'), true);
        
        $email = trim($input['email'] ?? '');
        $senha = $input['senha'] ?? '';
        $lembrar = $input['lembrar'] ?? false;
        
        if (empty($email) || empty($senha)) {
            throw new Exception('Email/usuário e senha são obrigatórios');
        }
        
        // Tentativa 1: Verificar se é administrador
        $sqlAdmin = "SELECT 
                        id, nome, email, senha, cargo, departamento, ativo, nivel_acesso
                     FROM gestores 
                     WHERE (email = :email OR nome = :nome) AND ativo = 1";
        
        $stmtAdmin = $db->prepare($sqlAdmin);
        $stmtAdmin->execute(['email' => $email, 'nome' => $email]);
        $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($senha, $admin['senha'])) {
            // É um administrador
            $_SESSION['user_type'] = 'admin';
            $_SESSION['gestor_id'] = $admin['id'];
            $_SESSION['gestor_nome'] = $admin['nome'];
            $_SESSION['gestor_email'] = $admin['email'];
            $_SESSION['gestor_cargo'] = $admin['cargo'];
            $_SESSION['gestor_departamento'] = $admin['departamento'];
            $_SESSION['gestor_nivel'] = $admin['nivel_acesso'];
            $_SESSION['login_timestamp'] = time();
            
            // Configurar lembrar login
            if ($lembrar) {
                setcookie('remember_admin', base64_encode($admin['id']), time() + (30 * 24 * 60 * 60), '/');
            }
            
            // Log de acesso (comentado temporariamente)
            /*
            $logSql = "INSERT INTO logs (tipo, mensagem, ip, user_agent, data_criacao) VALUES (?, ?, ?, ?, NOW())";
            $logStmt = $db->prepare($logSql);
            $logStmt->execute([
                'admin_login',
                "Login administrativo: {$admin['nome']} ({$admin['email']})",
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            */
            
            echo json_encode([
                'success' => true,
                'user_type' => 'admin',
                'user' => [
                    'id' => $admin['id'],
                    'nome' => $admin['nome'],
                    'email' => $admin['email'],
                    'cargo' => $admin['cargo'],
                    'departamento' => $admin['departamento']
                ],
                'message' => 'Login administrativo realizado com sucesso!'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Tentativa 2: Verificar se é cidadão
        // Buscar cidadão pelo email nas ocorrências
        $sqlCidadao = "SELECT DISTINCT 
                          nome_cidadao as nome, 
                          email_cidadao as email,
                          MIN(id) as first_occurrence_id
                       FROM ocorrencias 
                       WHERE email_cidadao = :email 
                       GROUP BY email_cidadao, nome_cidadao";
        
        $stmtCidadao = $db->prepare($sqlCidadao);
        $stmtCidadao->execute(['email' => $email]);
        $cidadao = $stmtCidadao->fetch(PDO::FETCH_ASSOC);
        
        if ($cidadao) {
            // Para cidadão, vamos usar uma verificação simples baseada no primeiro registro
            // Em um sistema real, você teria uma tabela de usuários separada
            $senhaEsperada = 'cidadao123'; // Senha padrão para cidadãos no demo
            
            if ($senha === $senhaEsperada) {
                // É um cidadão válido
                $_SESSION['user_type'] = 'cidadao';
                $_SESSION['cidadao_email'] = $cidadao['email'];
                $_SESSION['cidadao_nome'] = $cidadao['nome'];
                $_SESSION['login_timestamp'] = time();
                
                // Configurar lembrar login
                if ($lembrar) {
                    setcookie('remember_cidadao', base64_encode($cidadao['email']), time() + (30 * 24 * 60 * 60), '/');
                }
                
                // Log de acesso (comentado temporariamente)
                /*
                $logSql = "INSERT INTO logs (tipo, mensagem, ip, user_agent, data_criacao) VALUES (?, ?, ?, ?, NOW())";
                $logStmt = $db->prepare($logSql);
                $logStmt->execute([
                    'cidadao_login',
                    "Login do cidadão: {$cidadao['nome']} ({$cidadao['email']})",
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                */
                
                echo json_encode([
                    'success' => true,
                    'user_type' => 'cidadao',
                    'user' => [
                        'nome' => $cidadao['nome'],
                        'email' => $cidadao['email']
                    ],
                    'message' => 'Login realizado com sucesso!'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        
        // Se chegou até aqui, credenciais inválidas
        throw new Exception('Email/usuário ou senha incorretos');
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Verificar sessão atual
        if (isset($_SESSION['user_type'])) {
            $userType = $_SESSION['user_type'];
            
            if ($userType === 'admin' && isset($_SESSION['gestor_id'])) {
                echo json_encode([
                    'success' => true,
                    'user_type' => 'admin',
                    'user' => [
                        'id' => $_SESSION['gestor_id'],
                        'nome' => $_SESSION['gestor_nome'],
                        'email' => $_SESSION['gestor_email'],
                        'cargo' => $_SESSION['gestor_cargo'],
                        'departamento' => $_SESSION['gestor_departamento']
                    ]
                ]);
            } elseif ($userType === 'cidadao' && isset($_SESSION['cidadao_email'])) {
                echo json_encode([
                    'success' => true,
                    'user_type' => 'cidadao',
                    'user' => [
                        'nome' => $_SESSION['cidadao_nome'],
                        'email' => $_SESSION['cidadao_email']
                    ]
                ]);
            } else {
                throw new Exception('Sessão inválida');
            }
        } else {
            throw new Exception('Nenhuma sessão ativa');
        }
    }
    
} catch (Exception $e) {
    $errorCode = is_numeric($e->getCode()) ? $e->getCode() : 400;
    http_response_code($errorCode ?: 400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>