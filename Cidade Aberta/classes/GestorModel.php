<?php
require_once 'BaseModel.php';

/**
 * Modelo para Gestores - Cidade Aberta Santarém
 * Gerencia autenticação e operações relacionadas aos gestores
 */
class GestorModel extends BaseModel {
    protected $table = 'gestores';
    
    // Regras de validação para login
    private $loginRules = [
        'usuario' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 3,
            'max_length' => 50
        ],
        'senha' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 6
        ]
    ];
    
    // Regras de validação para criação de gestor
    private $createRules = [
        'usuario' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 3,
            'max_length' => 50
        ],
        'senha' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 8
        ],
        'nome' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 2,
            'max_length' => 100
        ],
        'email' => [
            'required' => true,
            'type' => 'email',
            'max_length' => 100
        ],
        'nivel' => [
            'required' => false,
            'type' => 'string',
            'allowed' => ['admin', 'operador']
        ]
    ];
    
    /**
     * Autenticar gestor
     * 
     * @param array $credentials
     * @return array
     * @throws Exception
     */
    public function authenticate($credentials) {
        try {
            // Validar dados de entrada
            $validatedData = $this->validateData($credentials, $this->loginRules);
            
            // Buscar gestor no banco
            $sql = "SELECT id, usuario, senha, nome, email, nivel, ativo 
                    FROM {$this->table} 
                    WHERE usuario = ? AND ativo = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$validatedData['usuario']]);
            $gestor = $stmt->fetch();
            
            if (!$gestor) {
                // Log de tentativa de login inválida
                $this->logActivity('login_tentativa_invalida', [
                    'usuario' => $validatedData['usuario'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                throw new Exception('Usuário ou senha incorretos', 401);
            }
            
            // Verificar senha
            if (!SecurityUtils::verifyPassword($validatedData['senha'], $gestor['senha'])) {
                // Log de tentativa de login com senha incorreta
                $this->logActivity('login_senha_incorreta', [
                    'usuario' => $validatedData['usuario'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                
                throw new Exception('Usuário ou senha incorretos', 401);
            }
            
            // Criar sessão
            $this->createSession($gestor);
            
            // Atualizar último login
            $this->updateLastLogin($gestor['id']);
            
            // Log de login bem-sucedido
            $this->logActivity('login_sucesso', [
                'gestor_id' => $gestor['id'],
                'usuario' => $gestor['usuario'],
                'nivel' => $gestor['nivel']
            ]);
            
            // Retornar dados do gestor (sem senha)
            unset($gestor['senha']);
            return $gestor;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Criar sessão do gestor
     * 
     * @param array $gestor
     */
    private function createSession($gestor) {
        // Regenerar ID da sessão por segurança
        session_regenerate_id(true);
        
        $_SESSION['gestor_id'] = $gestor['id'];
        $_SESSION['gestor_usuario'] = $gestor['usuario'];
        $_SESSION['gestor_nome'] = $gestor['nome'];
        $_SESSION['gestor_nivel'] = $gestor['nivel'];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token'] = SecurityUtils::generateCSRFToken();
    }
    
    /**
     * Verificar se gestor está logado
     * 
     * @return array|null
     */
    public function getCurrentGestor() {
        if (!isset($_SESSION['gestor_id'])) {
            return null;
        }
        
        // Verificar timeout da sessão
        if (isset($_SESSION['last_activity'])) {
            $timeout = DatabaseConfig::getSessionTimeout();
            if (time() - $_SESSION['last_activity'] > $timeout) {
                $this->logout();
                return null;
            }
        }
        
        $_SESSION['last_activity'] = time();
        
        return [
            'id' => $_SESSION['gestor_id'],
            'usuario' => $_SESSION['gestor_usuario'],
            'nome' => $_SESSION['gestor_nome'],
            'nivel' => $_SESSION['gestor_nivel']
        ];
    }
    
    /**
     * Fazer logout
     */
    public function logout() {
        if (isset($_SESSION['gestor_id'])) {
            // Log de logout
            $this->logActivity('logout', [
                'gestor_id' => $_SESSION['gestor_id'],
                'usuario' => $_SESSION['gestor_usuario'] ?? 'unknown'
            ]);
        }
        
        // Limpar sessão
        session_unset();
        session_destroy();
        
        // Iniciar nova sessão
        session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Criar novo gestor (apenas admins)
     * 
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function create($data) {
        try {
            // Verificar se usuário atual é admin
            $currentGestor = $this->requireAuth();
            if ($currentGestor['nivel'] !== 'admin') {
                throw new Exception('Acesso negado. Apenas administradores podem criar gestores.', 403);
            }
            
            // Validar dados
            $validatedData = $this->validateData($data, $this->createRules);
            
            // Verificar se usuário já existe
            if ($this->findByUsuario($validatedData['usuario'])) {
                throw new Exception('Nome de usuário já existe', 400);
            }
            
            // Verificar se email já existe
            if ($this->findByEmail($validatedData['email'])) {
                throw new Exception('Email já cadastrado', 400);
            }
            
            // Hash da senha
            $hashedPassword = SecurityUtils::hashPassword($validatedData['senha']);
            
            // Definir nível padrão
            $nivel = $validatedData['nivel'] ?? 'operador';
            
            // Preparar dados para inserção
            $insertData = [
                'usuario' => $validatedData['usuario'],
                'senha' => $hashedPassword,
                'nome' => SecurityUtils::sanitizeHtml($validatedData['nome']),
                'email' => $validatedData['email'],
                'nivel' => $nivel,
                'ativo' => 1,
                'data_criacao' => date('Y-m-d H:i:s')
            ];
            
            // Inserir no banco
            $sql = "INSERT INTO {$this->table} 
                    (usuario, senha, nome, email, nivel, ativo, data_criacao) 
                    VALUES (:usuario, :senha, :nome, :email, :nivel, :ativo, :data_criacao)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($insertData);
            
            $gestorId = $this->db->lastInsertId();
            
            // Log da atividade
            $this->logActivity('gestor_criado', [
                'gestor_id' => $gestorId,
                'usuario' => $validatedData['usuario'],
                'nivel' => $nivel,
                'criado_por' => $currentGestor['id']
            ]);
            
            // Retornar dados (sem senha)
            unset($insertData['senha']);
            $insertData['id'] = $gestorId;
            
            return $insertData;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Listar gestores (apenas admins)
     * 
     * @return array
     * @throws Exception
     */
    public function list() {
        // Verificar se usuário atual é admin
        $currentGestor = $this->requireAuth();
        if ($currentGestor['nivel'] !== 'admin') {
            throw new Exception('Acesso negado. Apenas administradores podem listar gestores.', 403);
        }
        
        $sql = "SELECT id, usuario, nome, email, nivel, ativo, data_criacao, ultimo_login 
                FROM {$this->table} 
                ORDER BY data_criacao DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $gestores = $stmt->fetchAll();
        
        // Formatar datas
        foreach ($gestores as &$gestor) {
            $gestor['data_criacao_formatada'] = date('d/m/Y H:i', strtotime($gestor['data_criacao']));
            if ($gestor['ultimo_login']) {
                $gestor['ultimo_login_formatado'] = date('d/m/Y H:i', strtotime($gestor['ultimo_login']));
            }
        }
        
        return $gestores;
    }
    
    /**
     * Alterar status do gestor (ativar/desativar)
     * 
     * @param int $id
     * @param bool $ativo
     * @return bool
     * @throws Exception
     */
    public function changeStatus($id, $ativo) {
        // Verificar se usuário atual é admin
        $currentGestor = $this->requireAuth();
        if ($currentGestor['nivel'] !== 'admin') {
            throw new Exception('Acesso negado. Apenas administradores podem alterar status de gestores.', 403);
        }
        
        // Não permitir desativar a si mesmo
        if ($id == $currentGestor['id']) {
            throw new Exception('Você não pode desativar sua própria conta', 400);
        }
        
        $sql = "UPDATE {$this->table} SET ativo = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$ativo ? 1 : 0, $id]);
        
        if ($result) {
            $this->logActivity('gestor_status_alterado', [
                'gestor_id' => $id,
                'novo_status' => $ativo ? 'ativo' : 'inativo',
                'alterado_por' => $currentGestor['id']
            ]);
        }
        
        return $result;
    }
    
    /**
     * Alterar senha
     * 
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function changePassword($data) {
        $currentGestor = $this->requireAuth();
        
        $rules = [
            'senha_atual' => ['required' => true, 'type' => 'string'],
            'senha_nova' => ['required' => true, 'type' => 'string', 'min_length' => 8],
            'confirmar_senha' => ['required' => true, 'type' => 'string']
        ];
        
        $validatedData = $this->validateData($data, $rules);
        
        // Verificar se nova senha e confirmação coincidem
        if ($validatedData['senha_nova'] !== $validatedData['confirmar_senha']) {
            throw new Exception('Nova senha e confirmação não coincidem', 400);
        }
        
        // Buscar senha atual do gestor
        $stmt = $this->db->prepare("SELECT senha FROM {$this->table} WHERE id = ?");
        $stmt->execute([$currentGestor['id']]);
        $gestor = $stmt->fetch();
        
        // Verificar senha atual
        if (!SecurityUtils::verifyPassword($validatedData['senha_atual'], $gestor['senha'])) {
            throw new Exception('Senha atual incorreta', 400);
        }
        
        // Gerar hash da nova senha
        $newHash = SecurityUtils::hashPassword($validatedData['senha_nova']);
        
        // Atualizar senha
        $sql = "UPDATE {$this->table} SET senha = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$newHash, $currentGestor['id']]);
        
        if ($result) {
            $this->logActivity('senha_alterada', [
                'gestor_id' => $currentGestor['id']
            ]);
        }
        
        return $result;
    }
    
    /**
     * Atualizar último login
     * 
     * @param int $gestorId
     */
    private function updateLastLogin($gestorId) {
        $sql = "UPDATE {$this->table} SET ultimo_login = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$gestorId]);
    }
    
    /**
     * Buscar gestor por usuário
     * 
     * @param string $usuario
     * @return array|null
     */
    private function findByUsuario($usuario) {
        $sql = "SELECT id FROM {$this->table} WHERE usuario = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$usuario]);
        return $stmt->fetch();
    }
    
    /**
     * Buscar gestor por email
     * 
     * @param string $email
     * @return array|null
     */
    private function findByEmail($email) {
        $sql = "SELECT id FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}
?>