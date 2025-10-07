-- =====================================================
-- CIDADE ABERTA SANTARÉM - SETUP COMPLETO DO BANCO
-- Script único e organizado para criar todo o sistema
-- Data: 06/10/2025
-- =====================================================

-- Criar banco se não existir
CREATE DATABASE IF NOT EXISTS cidade_aberta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cidade_aberta;

-- =====================================================
-- TABELA DE OCORRÊNCIAS
-- =====================================================
DROP TABLE IF EXISTS ocorrencias;
CREATE TABLE ocorrencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    tipo ENUM('buraco', 'lixo', 'iluminacao', 'agua', 'transporte', 'saude', 'educacao', 'seguranca', 'outros') NOT NULL,
    endereco VARCHAR(300) NOT NULL,
    descricao TEXT NOT NULL,
    status ENUM('pendente', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'pendente',
    prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    observacoes TEXT NULL,
    nome_cidadao VARCHAR(100) NULL,
    email_cidadao VARCHAR(100) NULL,
    telefone_cidadao VARCHAR(20) NULL,
    foto VARCHAR(255) NULL,
    
    -- Índices para otimização
    INDEX idx_codigo (codigo),
    INDEX idx_status (status),
    INDEX idx_tipo (tipo),
    INDEX idx_data (data_criacao),
    INDEX idx_localizacao (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE CONTATOS
-- =====================================================
DROP TABLE IF EXISTS contatos;
CREATE TABLE contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NULL,
    assunto VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_resposta TIMESTAMP NULL,
    status ENUM('novo', 'lido', 'respondido', 'arquivado') DEFAULT 'novo',
    resposta TEXT NULL,
    responsavel VARCHAR(100) NULL,
    
    -- Índices para otimização
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_data (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE GESTORES/ADMINISTRADORES
-- =====================================================
DROP TABLE IF EXISTS gestores;
CREATE TABLE gestores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    cargo VARCHAR(100) NULL,
    departamento VARCHAR(100) NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    
    -- Campos específicos para administradores
    nivel_acesso ENUM('admin', 'gestor', 'operador') DEFAULT 'gestor',
    permissoes JSON NULL,
    telefone VARCHAR(20) NULL,
    foto_perfil VARCHAR(255) NULL,
    
    -- Índices para otimização
    INDEX idx_email (email),
    INDEX idx_ativo (ativo),
    INDEX idx_nivel (nivel_acesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE CIDADÃOS (para sistema de login unificado)
-- =====================================================
DROP TABLE IF EXISTS cidadaos;
CREATE TABLE cidadaos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) NULL,
    cpf VARCHAR(14) NULL,
    endereco TEXT NULL,
    data_nascimento DATE NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    email_verificado BOOLEAN DEFAULT FALSE,
    token_verificacao VARCHAR(100) NULL,
    
    -- Índices para otimização
    INDEX idx_email (email),
    INDEX idx_cpf (cpf),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA DE LOGS DO SISTEMA (para auditoria)
-- =====================================================
DROP TABLE IF EXISTS admin_logs;
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    acao VARCHAR(100) NOT NULL,
    detalhes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (admin_id) REFERENCES gestores(id) ON DELETE CASCADE,
    INDEX idx_admin (admin_id),
    INDEX idx_acao (acao),
    INDEX idx_data (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS DE EXEMPLO - OCORRÊNCIAS
-- Coordenadas corretas do centro urbano de Santarém
-- =====================================================
INSERT INTO ocorrencias (codigo, tipo, endereco, descricao, status, latitude, longitude, data_criacao, nome_cidadao, email_cidadao) VALUES
('STM000001', 'buraco', 'Av. Tapajós, Centro', 'Buraco grande na pista causando transtornos ao trânsito', 'pendente', -2.419444, -54.708333, '2025-10-06 18:00:00', 'João Silva', 'joao@email.com'),
('STM000002', 'lixo', 'Rua Floriano Peixoto, Centro', 'Acúmulo de lixo na esquina há vários dias', 'em_andamento', -2.421111, -54.705833, '2025-10-06 18:30:00', 'Maria Santos', 'maria@email.com'),
('STM000003', 'iluminacao', 'Praça Tiradentes, Centro', 'Poste de luz queimado deixando área escura', 'pendente', -2.418889, -54.707222, '2025-10-06 19:00:00', 'Carlos Oliveira', 'carlos@email.com'),
('STM000004', 'agua', 'Rua Barão do Rio Branco, Centro', 'Vazamento de água causando erosão na rua', 'em_andamento', -2.420556, -54.706667, '2025-10-06 19:30:00', 'Ana Costa', 'ana@email.com'),
('STM000005', 'buraco', 'Av. Cuiabá, Aparecida', 'Buraco profundo no asfalto', 'pendente', -2.422222, -54.704444, '2025-10-06 20:00:00', 'Pedro Santos', 'pedro@email.com'),
('STM000006', 'lixo', 'Rua 15 de Novembro, Aparecida', 'Container de lixo quebrado', 'concluida', -2.424444, -54.702778, '2025-10-06 20:30:00', 'Laura Silva', 'laura@email.com'),
('STM000007', 'iluminacao', 'Av. Mendonça Furtado, Fatima', 'Falta de iluminação pública na avenida', 'concluida', -2.427778, -54.700000, '2025-10-06 21:00:00', 'Roberto Lima', 'roberto@email.com'),
('STM000008', 'transporte', 'Terminal Rodoviário', 'Parada de ônibus danificada', 'pendente', -2.426111, -54.703333, '2025-10-06 21:30:00', 'Fernanda Souza', 'fernanda@email.com');

-- Atualizar data_atualizacao para ocorrências concluídas
UPDATE ocorrencias 
SET data_atualizacao = DATE_ADD(data_criacao, INTERVAL FLOOR(RAND() * 48 + 12) HOUR) 
WHERE status = 'concluida';

UPDATE ocorrencias 
SET data_atualizacao = DATE_ADD(data_criacao, INTERVAL FLOOR(RAND() * 24 + 2) HOUR) 
WHERE status = 'em_andamento';

-- =====================================================
-- DADOS DE EXEMPLO - CONTATOS
-- =====================================================
INSERT INTO contatos (nome, email, telefone, assunto, mensagem, data_criacao, status) VALUES
('João Silva', 'joao.silva@email.com', '(93) 99999-1111', 'Problema na iluminação', 'Gostaria de reportar problema na iluminação da Rua das Flores. A rua está muito escura à noite.', '2025-10-06 18:00:00', 'novo'),
('Maria Santos', 'maria.santos@email.com', '(93) 99999-2222', 'Sugestão para o portal', 'Parabéns pelo portal Cidade Aberta! Muito útil para nossa cidade. Poderiam adicionar categoria para problemas de trânsito?', '2025-10-06 19:00:00', 'lido'),
('Pedro Oliveira', 'pedro.oliveira@email.com', NULL, 'Dúvida sobre ocorrência', 'Como posso acompanhar minha ocorrência STM000001? Não consigo encontrar no sistema de rastreamento.', '2025-10-06 20:00:00', 'respondido'),
('Ana Costa', 'ana.costa@email.com', '(93) 99999-3333', 'Vazamento de água', 'Há um vazamento grande na Av. Borges Leal que precisa de atenção urgente. Já está causando buracos.', '2025-10-06 20:30:00', 'novo'),
('Carlos Lima', 'carlos.lima@email.com', '(93) 99999-4444', 'Elogio ao atendimento', 'Quero parabenizar a equipe. Minha ocorrência foi resolvida rapidamente. Muito obrigado!', '2025-10-06 21:00:00', 'lido');

-- =====================================================
-- DADOS DE EXEMPLO - GESTORES
-- =====================================================
INSERT INTO gestores (nome, email, senha, cargo, departamento, ativo, nivel_acesso, telefone) VALUES
('Administrador do Sistema', 'admin@santarem.pa.gov.br', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', 'Administrador Geral', 'Tecnologia da Informação', TRUE, 'admin', '(93) 3523-1234'),
('João Gestor', 'joao.gestor@santarem.pa.gov.br', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', 'Coordenador de Obras', 'Secretaria de Obras', TRUE, 'gestor', '(93) 3523-2345'),
('Maria Supervisora', 'maria.supervisora@santarem.pa.gov.br', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', 'Supervisora de Limpeza', 'Secretaria de Limpeza Urbana', TRUE, 'gestor', '(93) 3523-3456');

-- =====================================================
-- DADOS DE EXEMPLO - CIDADÃOS
-- =====================================================
INSERT INTO cidadaos (nome, email, senha, telefone, ativo, email_verificado) VALUES
('João Silva', 'joao@email.com', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', '(93) 99999-1111', TRUE, TRUE),
('Maria Santos', 'maria@email.com', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', '(93) 99999-2222', TRUE, TRUE),
('Carlos Oliveira', 'carlos@email.com', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', '(93) 99999-3333', TRUE, TRUE),
('Ana Costa', 'ana@email.com', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', '(93) 99999-4444', TRUE, TRUE),
('Pedro Santos', 'pedro@email.com', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', '(93) 99999-5555', TRUE, TRUE),
('Laura Silva', 'laura@email.com', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', '(93) 99999-6666', TRUE, TRUE),
('Roberto Lima', 'roberto@email.com', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', '(93) 99999-7777', TRUE, TRUE),
('Fernanda Souza', 'fernanda@email.com', '$2y$10$EkKnwn/5Q5O5Y4LQsHHdMO.sOGFhFN3rHhHgqSG9fOGnQ3E8VnHDK', '(93) 99999-8888', TRUE, TRUE);

-- =====================================================
-- VERIFICAÇÕES E RELATÓRIOS FINAIS
-- =====================================================

-- Verificar tabelas criadas
SELECT 'TABELAS CRIADAS COM SUCESSO:' as status;
SHOW TABLES;

-- Estatísticas de ocorrências por status
SELECT 'OCORRÊNCIAS POR STATUS:' as relatorio;
SELECT 
    status,
    COUNT(*) as total,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM ocorrencias)), 2) as percentual
FROM ocorrencias 
GROUP BY status 
ORDER BY total DESC;

-- Estatísticas de ocorrências por tipo
SELECT 'OCORRÊNCIAS POR TIPO:' as relatorio;
SELECT 
    tipo,
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'concluida' THEN 1 END) as concluidas,
    ROUND((COUNT(CASE WHEN status = 'concluida' THEN 1 END) * 100.0 / COUNT(*)), 2) as taxa_resolucao
FROM ocorrencias 
GROUP BY tipo 
ORDER BY total DESC;

-- Estatísticas de contatos por status
SELECT 'CONTATOS POR STATUS:' as relatorio;
SELECT status, COUNT(*) as total FROM contatos GROUP BY status;

-- Resumo geral do sistema
SELECT 'RESUMO GERAL DO SISTEMA:' as relatorio;
SELECT 
    (SELECT COUNT(*) FROM ocorrencias) as total_ocorrencias,
    (SELECT COUNT(*) FROM ocorrencias WHERE status = 'pendente') as ocorrencias_pendentes,
    (SELECT COUNT(*) FROM ocorrencias WHERE status = 'concluida') as ocorrencias_concluidas,
    (SELECT COUNT(*) FROM contatos) as total_contatos,
    (SELECT COUNT(*) FROM contatos WHERE status = 'novo') as contatos_novos,
    (SELECT COUNT(*) FROM gestores WHERE ativo = TRUE) as gestores_ativos,
    (SELECT COUNT(*) FROM cidadaos WHERE ativo = TRUE) as cidadaos_ativos;

-- Verificar coordenadas das ocorrências
SELECT 'COORDENADAS DAS OCORRÊNCIAS:' as relatorio;
SELECT codigo, endereco, latitude, longitude FROM ocorrencias ORDER BY codigo;

-- =====================================================
-- INFORMAÇÕES DE LOGIN DO SISTEMA
-- =====================================================
SELECT 'INFORMAÇÕES DE LOGIN:' as info;
SELECT 
    'ADMINISTRADORES:' as tipo,
    'Email: admin@santarem.pa.gov.br | Senha: admin123' as credenciais
UNION ALL
SELECT 
    'CIDADÃOS:', 
    'Email: joao@email.com | Senha: cidadao123'
UNION ALL
SELECT 
    '', 
    'Email: maria@email.com | Senha: cidadao123';

SELECT 'SETUP COMPLETO FINALIZADO COM SUCESSO!' as status;
SELECT 'SENHAS PADRÃO: admin123 (administradores) | cidadao123 (cidadãos)' as info_importante;