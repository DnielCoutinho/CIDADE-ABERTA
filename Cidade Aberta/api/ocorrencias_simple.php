<?php
/**
 * API Simples para Ocorrências Públicas
 * Cidade Aberta Santarém
 * Retorna informações (GET) e registra novas ocorrências (POST)
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // <<< CORREÇÃO: ADICIONADO 'POST'
header('Access-Control-Allow-Headers: Content-Type');

// Tratar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendResponse($data, $message = '', $success = true) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = DatabaseConfig::getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // ==========================================================
    // SEPARAÇÃO DA LÓGICA GET e POST
    // ==========================================================

    if ($method === 'GET') {
        
        // --- LÓGICA EXISTENTE PARA LISTAR (GET) ---
        
        // Parâmetros de filtro
        $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;
        $limite = min($limite, 50); // Máximo 50 ocorrências
        
        $tipo = $_GET['tipo'] ?? null;
        $status = $_GET['status'] ?? null;
        
        // Query base - apenas informações públicas
        $sql = "SELECT 
                    id,
                    codigo,
                    tipo,
                    endereco,
                    descricao,
                    status,
                    prioridade,
                    latitude,
                    longitude,
                    data_criacao,
                    data_atualizacao,
                    DATE_FORMAT(data_criacao, '%d/%m/%Y %H:%i') as data_criacao_formatada,
                    DATE_FORMAT(data_atualizacao, '%d/%m/%Y %H:%i') as data_atualizacao_formatada,
                    nome_cidadao,
                    email_cidadao
                FROM ocorrencias 
                WHERE 1=1";
        
        $params = [];
        
        // Filtros opcionais
        if ($tipo) {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
        }
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY data_criacao DESC LIMIT ?";
        $params[] = $limite;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $ocorrencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estatísticas básicas
        $statsSQL = "SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
                        COUNT(CASE WHEN status = 'em_andamento' THEN 1 END) as em_andamento,
                        COUNT(CASE WHEN status = 'concluida' THEN 1 END) as concluidas,
                        COUNT(CASE WHEN status = 'cancelada' THEN 1 END) as canceladas
                    FROM ocorrencias";
        
        $statsStmt = $db->prepare($statsSQL);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        sendResponse([
            'ocorrencias' => $ocorrencias,
            'estatisticas' => $stats,
            'parametros' => [
                'limite' => $limite,
                'tipo' => $tipo,
                'status' => $status
            ]
        ], 'Ocorrências carregadas com sucesso');

    } elseif ($method === 'POST') {
        
        // --- NOVA LÓGICA PARA CRIAR (POST) ---
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validar dados (mesmos campos do app.js)
        $tipo = $input['tipo'] ?? null;
        $descricao = $input['descricao'] ?? null;
        $endereco = $input['endereco'] ?? null;
        $latitude = $input['latitude'] ?? null;
        $longitude = $input['longitude'] ?? null;
        $nome_cidadao = $input['nome_cidadao'] ?? null;
        $email_cidadao = $input['email_cidadao'] ?? null;

        if (empty($tipo) || empty($descricao) || empty($endereco) || empty($latitude) || empty($longitude) || empty($nome_cidadao)) {
            throw new Exception("Dados insuficientes para criar ocorrência. Verifique tipo, descrição, endereço, localização e nome.");
        }

        // Gerar código único (baseado no script setup_completo.sql e OcorrenciaModel.php)
        $codigo = 'STM' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $db->prepare("SELECT id FROM ocorrencias WHERE codigo = ?");
        $stmt->execute([$codigo]);
        while($stmt->fetch()) {
            $codigo = 'STM' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $stmt->execute([$codigo]);
        }
        
        $sql = "INSERT INTO ocorrencias 
                (codigo, tipo, endereco, descricao, status, latitude, longitude, data_criacao, nome_cidadao, email_cidadao) 
                VALUES 
                (:codigo, :tipo, :endereco, :descricao, 'pendente', :latitude, :longitude, NOW(), :nome_cidadao, :email_cidadao)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':codigo' => $codigo,
            ':tipo' => $tipo,
            ':endereco' => $endereco,
            ':descricao' => $descricao,
            ':latitude' => $latitude,
            ':longitude' => $longitude,
            ':nome_cidadao' => $nome_cidadao,
            ':email_cidadao' => $email_cidadao ?: null
        ]);
        
        $newId = $db->lastInsertId();
        
        // Resposta de sucesso para o app.js
        sendResponse([
            'id' => $newId,
            'codigo' => $codigo,
            'data_criacao' => date('Y-m-d H:i:s')
        ], 'Ocorrência registrada com sucesso');

    } else {
        // Se não for GET nem POST
        throw new Exception('Método não permitido');
    }
    
} catch (Exception $e) {
    http_response_code(500); // Erro 500 (Internal Server Error) é mais apropriado
    sendResponse(null, $e->getMessage(), false);
}
?>