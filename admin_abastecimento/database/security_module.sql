-- ============================================
-- MÓDULO DE SEGURANÇA - QR COMBUSTÍVEL
-- ============================================

-- Tabela de Grupos de Usuários
CREATE TABLE IF NOT EXISTS grupos_usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nome do grupo',
    descricao TEXT COMMENT 'Descrição do grupo',
    ativo TINYINT(1) DEFAULT 1 COMMENT '1=ativo, 0=inativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nome (nome),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos de usuários';

-- Tabela de Aplicações (Páginas do Sistema)
CREATE TABLE IF NOT EXISTS aplicacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nome da aplicação',
    descricao TEXT COMMENT 'Descrição da aplicação',
    url VARCHAR(255) NOT NULL COMMENT 'URL/caminho da página',
    icone VARCHAR(50) COMMENT 'Ícone FontAwesome',
    ordem INT DEFAULT 0 COMMENT 'Ordem de exibição no menu',
    ativo TINYINT(1) DEFAULT 1 COMMENT '1=ativo, 0=inativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nome (nome),
    INDEX idx_url (url),
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Aplicações/Páginas do sistema';

-- Tabela de Relacionamento Usuários-Grupos
CREATE TABLE IF NOT EXISTS usuarios_grupos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL COMMENT 'FK para usuarios',
    grupo_id INT NOT NULL COMMENT 'FK para grupos_usuarios',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos_usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY uk_usuario_grupo (usuario_id, grupo_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_grupo (grupo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relacionamento usuários-grupos';

-- Tabela de Permissões Grupo-Aplicação
CREATE TABLE IF NOT EXISTS grupos_aplicacoes_permissoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grupo_id INT NOT NULL COMMENT 'FK para grupos_usuarios',
    aplicacao_id INT NOT NULL COMMENT 'FK para aplicacoes',
    pode_acessar TINYINT(1) DEFAULT 0 COMMENT 'Permissão de acesso',
    pode_criar TINYINT(1) DEFAULT 0 COMMENT 'Permissão de criação',
    pode_visualizar TINYINT(1) DEFAULT 0 COMMENT 'Permissão de visualização',
    pode_editar TINYINT(1) DEFAULT 0 COMMENT 'Permissão de edição',
    pode_excluir TINYINT(1) DEFAULT 0 COMMENT 'Permissão de exclusão',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos_usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id) ON DELETE CASCADE,
    UNIQUE KEY uk_grupo_aplicacao (grupo_id, aplicacao_id),
    INDEX idx_grupo (grupo_id),
    INDEX idx_aplicacao (aplicacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permissões de grupos por aplicação';

-- ============================================
-- DADOS INICIAIS
-- ============================================

-- Inserir grupos padrão
INSERT INTO grupos_usuarios (nome, descricao, ativo) VALUES
('Administradores', 'Grupo com acesso total ao sistema', 1),
('Gestores', 'Grupo com acesso a relatórios e gestão', 1),
('Operadores', 'Grupo com acesso básico às funcionalidades', 1)
ON DUPLICATE KEY UPDATE nome=nome;

-- Inserir aplicações existentes
INSERT INTO aplicacoes (nome, descricao, url, icone, ordem, ativo) VALUES
('Dashboard', 'Painel principal com estatísticas', 'pages/dashboard.php', 'fa-chart-line', 1, 1),
('Veículos', 'Gestão de veículos da frota', 'pages/veiculo.php', 'fa-car', 2, 1),
('Abastecimentos', 'Registro de abastecimentos', 'pages/abastecimento.php', 'fa-gas-pump', 3, 1),
('Usuários', 'Gestão de usuários do sistema', 'pages/usuarios.php', 'fa-users', 4, 1),
('Grupos', 'Gestão de grupos de usuários', 'pages/grupos.php', 'fa-user-friends', 5, 1),
('Aplicações', 'Gestão de aplicações do sistema', 'pages/aplicacoes.php', 'fa-th-large', 6, 1),
('Permissões', 'Gestão de permissões por grupo', 'pages/permissoes.php', 'fa-key', 7, 1)
ON DUPLICATE KEY UPDATE nome=nome;

-- Vincular usuários admin ao grupo Administradores
INSERT INTO usuarios_grupos (usuario_id, grupo_id)
SELECT u.id, g.id
FROM usuarios u
CROSS JOIN grupos_usuarios g
WHERE u.perfil = 'admin' AND g.nome = 'Administradores'
ON DUPLICATE KEY UPDATE usuario_id=usuario_id;

-- Conceder todas as permissões ao grupo Administradores
INSERT INTO grupos_aplicacoes_permissoes (grupo_id, aplicacao_id, pode_acessar, pode_criar, pode_visualizar, pode_editar, pode_excluir)
SELECT g.id, a.id, 1, 1, 1, 1, 1
FROM grupos_usuarios g
CROSS JOIN aplicacoes a
WHERE g.nome = 'Administradores'
ON DUPLICATE KEY UPDATE pode_acessar=1, pode_criar=1, pode_visualizar=1, pode_editar=1, pode_excluir=1;

-- ============================================
-- VIEWS ÚTEIS
-- ============================================

-- View de usuários com seus grupos
CREATE OR REPLACE VIEW v_usuarios_grupos AS
SELECT 
    u.id AS usuario_id,
    u.usuario,
    u.email,
    u.perfil,
    u.ativo AS usuario_ativo,
    g.id AS grupo_id,
    g.nome AS grupo_nome,
    g.descricao AS grupo_descricao
FROM usuarios u
LEFT JOIN usuarios_grupos ug ON u.id = ug.usuario_id
LEFT JOIN grupos_usuarios g ON ug.grupo_id = g.id;

-- View de permissões detalhadas
CREATE OR REPLACE VIEW v_permissoes_detalhadas AS
SELECT 
    g.id AS grupo_id,
    g.nome AS grupo_nome,
    a.id AS aplicacao_id,
    a.nome AS aplicacao_nome,
    a.url AS aplicacao_url,
    a.icone AS aplicacao_icone,
    gap.pode_acessar,
    gap.pode_criar,
    gap.pode_visualizar,
    gap.pode_editar,
    gap.pode_excluir
FROM grupos_usuarios g
LEFT JOIN grupos_aplicacoes_permissoes gap ON g.id = gap.grupo_id
LEFT JOIN aplicacoes a ON gap.aplicacao_id = a.id
WHERE g.ativo = 1 AND a.ativo = 1;

-- View de menu por usuário
CREATE OR REPLACE VIEW v_menu_usuario AS
SELECT DISTINCT
    u.id AS usuario_id,
    a.id AS aplicacao_id,
    a.nome AS aplicacao_nome,
    a.url AS aplicacao_url,
    a.icone AS aplicacao_icone,
    a.ordem AS aplicacao_ordem,
    MAX(gap.pode_acessar) AS pode_acessar
FROM usuarios u
INNER JOIN usuarios_grupos ug ON u.id = ug.usuario_id
INNER JOIN grupos_usuarios g ON ug.grupo_id = g.id
INNER JOIN grupos_aplicacoes_permissoes gap ON g.id = gap.grupo_id
INNER JOIN aplicacoes a ON gap.aplicacao_id = a.id
WHERE u.ativo = 1 AND g.ativo = 1 AND a.ativo = 1
GROUP BY u.id, a.id, a.nome, a.url, a.icone, a.ordem
HAVING pode_acessar = 1
ORDER BY a.ordem;

-- ============================================
-- STORED PROCEDURES
-- ============================================

-- Procedure para verificar permissão do usuário
DELIMITER $$
CREATE PROCEDURE sp_verificar_permissao(
    IN p_usuario_id INT,
    IN p_aplicacao_url VARCHAR(255),
    IN p_acao VARCHAR(20),
    OUT p_tem_permissao TINYINT
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    -- Verifica se usuário tem permissão para a ação na aplicação
    SELECT COUNT(*) INTO v_count
    FROM usuarios u
    INNER JOIN usuarios_grupos ug ON u.id = ug.usuario_id
    INNER JOIN grupos_usuarios g ON ug.grupo_id = g.id
    INNER JOIN grupos_aplicacoes_permissoes gap ON g.id = gap.grupo_id
    INNER JOIN aplicacoes a ON gap.aplicacao_id = a.id
    WHERE u.id = p_usuario_id
        AND a.url = p_aplicacao_url
        AND u.ativo = 1
        AND g.ativo = 1
        AND a.ativo = 1
        AND (
            (p_acao = 'acessar' AND gap.pode_acessar = 1) OR
            (p_acao = 'criar' AND gap.pode_criar = 1) OR
            (p_acao = 'visualizar' AND gap.pode_visualizar = 1) OR
            (p_acao = 'editar' AND gap.pode_editar = 1) OR
            (p_acao = 'excluir' AND gap.pode_excluir = 1)
        );
    
    SET p_tem_permissao = IF(v_count > 0, 1, 0);
END$$
DELIMITER ;

-- Procedure para sincronizar aplicações
DELIMITER $$
CREATE PROCEDURE sp_sincronizar_aplicacoes()
BEGIN
    -- Esta procedure será usada para sincronizar automaticamente
    -- as aplicações quando novas páginas forem criadas
    -- Por enquanto, apenas retorna sucesso
    SELECT 'Sincronização concluída' AS mensagem;
END$$
DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger para log de alterações de permissões (auditoria)
CREATE TABLE IF NOT EXISTS log_permissoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grupo_id INT,
    aplicacao_id INT,
    acao VARCHAR(50),
    usuario_alteracao_id INT,
    dados_anteriores JSON,
    dados_novos JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_grupo (grupo_id),
    INDEX idx_aplicacao (aplicacao_id),
    INDEX idx_data (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
CREATE TRIGGER trg_log_permissoes_insert
AFTER INSERT ON grupos_aplicacoes_permissoes
FOR EACH ROW
BEGIN
    INSERT INTO log_permissoes (grupo_id, aplicacao_id, acao, dados_novos)
    VALUES (NEW.grupo_id, NEW.aplicacao_id, 'INSERT', 
        JSON_OBJECT(
            'pode_acessar', NEW.pode_acessar,
            'pode_criar', NEW.pode_criar,
            'pode_visualizar', NEW.pode_visualizar,
            'pode_editar', NEW.pode_editar,
            'pode_excluir', NEW.pode_excluir
        )
    );
END$$

CREATE TRIGGER trg_log_permissoes_update
AFTER UPDATE ON grupos_aplicacoes_permissoes
FOR EACH ROW
BEGIN
    INSERT INTO log_permissoes (grupo_id, aplicacao_id, acao, dados_anteriores, dados_novos)
    VALUES (NEW.grupo_id, NEW.aplicacao_id, 'UPDATE',
        JSON_OBJECT(
            'pode_acessar', OLD.pode_acessar,
            'pode_criar', OLD.pode_criar,
            'pode_visualizar', OLD.pode_visualizar,
            'pode_editar', OLD.pode_editar,
            'pode_excluir', OLD.pode_excluir
        ),
        JSON_OBJECT(
            'pode_acessar', NEW.pode_acessar,
            'pode_criar', NEW.pode_criar,
            'pode_visualizar', NEW.pode_visualizar,
            'pode_editar', NEW.pode_editar,
            'pode_excluir', NEW.pode_excluir
        )
    );
END$$
DELIMITER ;

-- ============================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ============================================

-- Índices compostos para otimizar queries comuns
CREATE INDEX idx_permissoes_lookup ON grupos_aplicacoes_permissoes(grupo_id, aplicacao_id, pode_acessar);
CREATE INDEX idx_usuarios_grupos_lookup ON usuarios_grupos(usuario_id, grupo_id);
