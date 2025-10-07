<?php
require_once 'BaseModel.php';

/**
 * Modelo para Ocorrências - Cidade Aberta Santarém
 * Gerencia todas as operações CRUD relacionadas às ocorrências urbanas
 */
class OcorrenciaModel extends BaseModel {
    protected $table = 'ocorrencias';
    
    // Regras de validação para ocorrências
    private $validationRules = [
        'tipo' => [
            'required' => true,
            'type' => 'string',
            'allowed' => ['buraco', 'lixo', 'iluminacao', 'agua', 'outros']
        ],
        'descricao' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 10,
            'max_length' => 1000
        ],
        'endereco' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 5,
            'max_length' => 255
        ],
        'latitude' => [
            'required' => true,
            'type' => 'float'
        ],
        'longitude' => [
            'required' => true,
            'type' => 'float'
        ],
        'nome_cidadao' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 2,
            'max_length' => 100
        ],
        'email_cidadao' => [
            'required' => false,
            'type' => 'email',
            'max_length' => 100
        ]
    ];
    
    /**
     * Criar nova ocorrência
     * 
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function create($data) {
        try {
            // Validar dados
            $validatedData = $this->validateData($data, $this->validationRules);
            
            // Validar coordenadas
            if (!SecurityUtils::validateCoordinates($validatedData['latitude'], $validatedData['longitude'])) {
                throw new Exception('Coordenadas geográficas inválidas', 400);
            }
            
            // Gerar código único
            $codigo = $this->generateUniqueCode();
            
            // Processar upload de foto se existir
            $fotoPath = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $fotoPath = $this->handlePhotoUpload($_FILES['foto']);
            }
            
            // Preparar dados para inserção
            $insertData = [
                'codigo' => $codigo,
                'tipo' => $validatedData['tipo'],
                'descricao' => SecurityUtils::sanitizeHtml($validatedData['descricao']),
                'endereco' => SecurityUtils::sanitizeHtml($validatedData['endereco']),
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'nome_cidadao' => SecurityUtils::sanitizeHtml($validatedData['nome_cidadao']),
                'email_cidadao' => $validatedData['email_cidadao'],
                'foto' => $fotoPath,
                'status' => AppConfig::STATUS_PENDENTE,
                'data_criacao' => date('Y-m-d H:i:s')
            ];
            
            // Inserir no banco
            $sql = "INSERT INTO {$this->table} 
                    (codigo, tipo, descricao, endereco, latitude, longitude, nome_cidadao, email_cidadao, foto, status, data_criacao) 
                    VALUES (:codigo, :tipo, :descricao, :endereco, :latitude, :longitude, :nome_cidadao, :email_cidadao, :foto, :status, :data_criacao)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($insertData);
            
            // Obter ID da ocorrência criada
            $ocorrenciaId = $this->db->lastInsertId();
            
            // Log da atividade
            $this->logActivity('ocorrencia_criada', [
                'ocorrencia_id' => $ocorrenciaId,
                'codigo' => $codigo,
                'tipo' => $validatedData['tipo']
            ]);
            
            // Retornar dados da ocorrência criada
            return [
                'id' => $ocorrenciaId,
                'codigo' => $codigo,
                'tipo' => $validatedData['tipo'],
                'status' => AppConfig::STATUS_PENDENTE,
                'data_criacao' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Buscar ocorrência por código
     * 
     * @param string $codigo
     * @return array|null
     */
    public function findByCodigo($codigo) {
        $sql = "SELECT * FROM {$this->table} WHERE codigo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codigo]);
        
        $ocorrencia = $stmt->fetch();
        
        if ($ocorrencia) {
            // Adicionar URL da foto se existir
            if ($ocorrencia['foto']) {
                $ocorrencia['foto_url'] = AppConfig::BASE_URL . AppConfig::UPLOAD_PATH . $ocorrencia['foto'];
            }
            
            // Formatar datas
            $ocorrencia['data_criacao_formatada'] = date('d/m/Y H:i', strtotime($ocorrencia['data_criacao']));
            if ($ocorrencia['data_atualizacao']) {
                $ocorrencia['data_atualizacao_formatada'] = date('d/m/Y H:i', strtotime($ocorrencia['data_atualizacao']));
            }
        }
        
        return $ocorrencia;
    }
    
    /**
     * Listar ocorrências com filtros
     * 
     * @param array $filters
     * @return array
     */
    public function list($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        // Filtro por status
        if (isset($filters['status']) && in_array($filters['status'], AppConfig::getValidStatus())) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        // Filtro por tipo
        if (isset($filters['tipo']) && !empty($filters['tipo'])) {
            $sql .= " AND tipo = ?";
            $params[] = $filters['tipo'];
        }
        
        // Filtro por data
        if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
            $sql .= " AND DATE(data_criacao) >= ?";
            $params[] = $filters['data_inicio'];
        }
        
        if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
            $sql .= " AND DATE(data_criacao) <= ?";
            $params[] = $filters['data_fim'];
        }
        
        // Busca por texto
        if (isset($filters['busca']) && !empty($filters['busca'])) {
            $sql .= " AND (descricao LIKE ? OR endereco LIKE ? OR codigo LIKE ?)";
            $searchTerm = '%' . $filters['busca'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Ordenação
        $sql .= " ORDER BY data_criacao DESC";
        
        // Paginação
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 50;
        $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $ocorrencias = $stmt->fetchAll();
        
        // Processar cada ocorrência
        foreach ($ocorrencias as &$ocorrencia) {
            if ($ocorrencia['foto']) {
                $ocorrencia['foto_url'] = AppConfig::BASE_URL . AppConfig::UPLOAD_PATH . $ocorrencia['foto'];
            }
            $ocorrencia['data_criacao_formatada'] = date('d/m/Y H:i', strtotime($ocorrencia['data_criacao']));
        }
        
        return $ocorrencias;
    }
    
    /**
     * Atualizar status da ocorrência (apenas gestores)
     * 
     * @param int $id
     * @param string $status
     * @param string $observacoes
     * @return bool
     * @throws Exception
     */
    public function updateStatus($id, $status, $observacoes = '') {
        // Verificar autenticação
        $gestor = $this->requireAuth();
        
        // Validar status
        if (!in_array($status, AppConfig::getValidStatus())) {
            throw new Exception('Status inválido', 400);
        }
        
        // Verificar se ocorrência existe
        $stmt = $this->db->prepare("SELECT id, codigo, status FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $ocorrencia = $stmt->fetch();
        
        if (!$ocorrencia) {
            throw new Exception('Ocorrência não encontrada', 404);
        }
        
        // Atualizar status
        $sql = "UPDATE {$this->table} 
                SET status = ?, observacoes_gestor = ?, data_atualizacao = NOW(), gestor_id = ? 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$status, $observacoes, $gestor['id'], $id]);
        
        if ($result) {
            // Log da atividade
            $this->logActivity('ocorrencia_atualizada', [
                'ocorrencia_id' => $id,
                'codigo' => $ocorrencia['codigo'],
                'status_anterior' => $ocorrencia['status'],
                'status_novo' => $status,
                'gestor_id' => $gestor['id']
            ]);
        }
        
        return $result;
    }
    
    /**
     * Obter estatísticas das ocorrências
     * 
     * @return array
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                    SUM(CASE WHEN status = 'andamento' THEN 1 ELSE 0 END) as em_andamento,
                    SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as concluidas,
                    COUNT(CASE WHEN DATE(data_criacao) = CURDATE() THEN 1 END) as hoje,
                    COUNT(CASE WHEN WEEK(data_criacao) = WEEK(NOW()) THEN 1 END) as esta_semana,
                    COUNT(CASE WHEN MONTH(data_criacao) = MONTH(NOW()) THEN 1 END) as este_mes
                FROM {$this->table}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Fazer upload de foto da ocorrência
     * 
     * @param array $file
     * @return string
     * @throws Exception
     */
    private function handlePhotoUpload($file) {
        // Validar arquivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload da foto', 400);
        }
        
        // Validar tamanho
        if ($file['size'] > DatabaseConfig::getUploadMaxSize()) {
            throw new Exception('Arquivo muito grande. Máximo 5MB', 400);
        }
        
        // Validar tipo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, DatabaseConfig::getAllowedImageTypes())) {
            throw new Exception('Tipo de arquivo não permitido. Use apenas imagens', 400);
        }
        
        // Gerar nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'ocorrencia_' . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
        
        // Caminho de destino
        $uploadPath = '../' . AppConfig::UPLOAD_PATH;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $destination = $uploadPath . $filename;
        
        // Mover arquivo
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Erro ao salvar foto', 500);
        }
        
        return $filename;
    }
    
    /**
     * Atualizar uma ocorrência
     */
    public function update($id, $data) {
        try {
            // Validar dados básicos (menos restritivo que criação)
            $allowedFields = [
                'tipo', 'endereco', 'descricao', 'status', 'prioridade', 
                'observacoes', 'latitude', 'longitude', 'nome_cidadao', 
                'email_cidadao', 'telefone_cidadao'
            ];
            
            $updateData = [];
            $placeholders = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                    $placeholders[] = "{$field} = :{$field}";
                }
            }
            
            if (empty($updateData)) {
                throw new Exception('Nenhum dado válido para atualização');
            }
            
            // Adicionar timestamp de atualização
            $updateData['data_atualizacao'] = date('Y-m-d H:i:s');
            $placeholders[] = "data_atualizacao = :data_atualizacao";
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $placeholders) . " WHERE id = :id";
            $updateData['id'] = $id;
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($updateData);
            
            if (!$result) {
                throw new Exception('Erro ao atualizar ocorrência no banco de dados');
            }
            
            // Verificar se alguma linha foi afetada
            if ($stmt->rowCount() === 0) {
                throw new Exception('Ocorrência não encontrada ou nenhuma alteração feita');
            }
            
            return true;
            
        } catch (PDOException $e) {
            throw new Exception('Erro no banco de dados: ' . $e->getMessage());
        }
    }
    
    /**
     * Excluir uma ocorrência
     */
    public function delete($id) {
        try {
            // Primeiro, verificar se a ocorrência existe
            $checkSql = "SELECT id, foto FROM {$this->table} WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute(['id' => $id]);
            $ocorrencia = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ocorrencia) {
                throw new Exception('Ocorrência não encontrada');
            }
            
            // Excluir arquivo de foto se existir
            if (!empty($ocorrencia['foto'])) {
                $fotoPath = '../' . AppConfig::UPLOAD_PATH . $ocorrencia['foto'];
                if (file_exists($fotoPath)) {
                    unlink($fotoPath);
                }
            }
            
            // Excluir do banco de dados
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['id' => $id]);
            
            if (!$result) {
                throw new Exception('Erro ao excluir ocorrência do banco de dados');
            }
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            throw new Exception('Erro no banco de dados: ' . $e->getMessage());
        }
    }
}
?>