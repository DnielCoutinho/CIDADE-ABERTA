<?php
/**
 * API para verificar sessão do usuário - Cidade Aberta Santarém
 */

require_once '../database/Connection.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Verificar se há sessão ativa
        if (isset($_SESSION['user_type'])) {
            $userType = $_SESSION['user_type'];
            
            if ($userType === 'admin' && isset($_SESSION['gestor_id'])) {
                // Usuário admin/gestor logado
                echo json_encode([
                    'success' => true,
                    'authenticated' => true,
                    'user' => [
                        'id' => $_SESSION['gestor_id'],
                        'nome' => $_SESSION['gestor_nome'],
                        'email' => $_SESSION['gestor_email'],
                        'cargo' => $_SESSION['gestor_cargo'] ?? '',
                        'departamento' => $_SESSION['gestor_departamento'] ?? '',
                        'tipo' => 'admin'
                    ]
                ]);
            } elseif ($userType === 'cidadao' && isset($_SESSION['cidadao_email'])) {
                // Usuário cidadão logado
                echo json_encode([
                    'success' => true,
                    'authenticated' => true,
                    'user' => [
                        'email' => $_SESSION['cidadao_email'],
                        'nome' => $_SESSION['cidadao_nome'],
                        'tipo' => 'cidadao',
                        'token' => $_SESSION['cidadao_token'] ?? null
                    ]
                ]);
            } else {
                // Tipo de usuário inválido
                echo json_encode([
                    'success' => true,
                    'authenticated' => false,
                    'user' => null
                ]);
            }
        } else {
            // Nenhum usuário logado
            echo json_encode([
                'success' => true,
                'authenticated' => false,
                'user' => null
            ]);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Logout
        session_destroy();
        echo json_encode([
            'success' => true,
            'message' => 'Logout realizado com sucesso'
        ]);
        
    } else {
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>