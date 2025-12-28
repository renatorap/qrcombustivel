-- ============================================
-- MÓDULO DE SEGURANÇA - QR COMBUSTÍVEL
-- Versão: 1.0
-- Data: 16/11/2024
-- ============================================

-- Tabela de Grupos de Usuários
CREATE TABLE IF NOT EXISTS grupos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nome do grupo (ex: Administradores, Gerentes)',
    descricao TEXT COMMENT 'Descrição do grupo',
    ativo TINYINT(1) DEFAULT 1 COMMENT '1=ativo, 0=inativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nome (nome),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos de usuários do sistema';

-- Adicionar campo grupo_id na tabela usuarios (executar manualmente se não existir)
-- ALTER TABLE usuarios ADD COLUMN grupo_id INT DEFAULT NULL COMMENT 'FK para grupos' AFTER perfil;
-- ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL;
-- ALTER TABLE usuarios ADD INDEX idx_grupo_id (grupo_id);

-- Tabela de Aplicações (Páginas do Sistema)
CREATE TABLE IF NOT EXISTS aplicacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código único da aplicação (ex: veiculos, dashboard)',
    nome VARCHAR(100) NOT NULL COMMENT 'Nome amigável da aplicação',
    descricao TEXT COMMENT 'Descrição da funcionalidade',
    url VARCHAR(255) COMMENT 'URL da página (ex: pages/veiculo.php)',
    icone VARCHAR(50) DEFAULT 'fa-file' COMMENT 'Ícone FontAwesome',
    ordem INT DEFAULT 0 COMMENT 'Ordem de exibição no menu',
    modulo VARCHAR(50) DEFAULT 'sistema' COMMENT 'Módulo ao qual pertence',
    ativo TINYINT(1) DEFAULT 1 COMMENT '1=ativo, 0=inativo',
    requer_autenticacao TINYINT(1) DEFAULT 1 COMMENT '1=requer login, 0=público',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_modulo (modulo),
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Aplicações/Páginas do sistema';

-- Tabela de Permissões de Grupo
CREATE TABLE IF NOT EXISTS permissoes_grupo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grupo_id INT NOT NULL COMMENT 'FK para grupos',
    aplicacao_id INT NOT NULL COMMENT 'FK para aplicacoes',
    pode_acessar TINYINT(1) DEFAULT 0 COMMENT 'Permite acessar a aplicação',
    pode_criar TINYINT(1) DEFAULT 0 COMMENT 'Permite criar novos registros',
    pode_visualizar TINYINT(1) DEFAULT 0 COMMENT 'Permite visualizar registros',
    pode_editar TINYINT(1) DEFAULT 0 COMMENT 'Permite editar registros',
    pode_excluir TINYINT(1) DEFAULT 0 COMMENT 'Permite excluir registros',
    pode_exportar TINYINT(1) DEFAULT 0 COMMENT 'Permite exportar dados',
    pode_importar TINYINT(1) DEFAULT 0 COMMENT 'Permite importar dados',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id) ON DELETE CASCADE,
    UNIQUE KEY uk_grupo_aplicacao (grupo_id, aplicacao_id),
    INDEX idx_grupo_id (grupo_id),
    INDEX idx_aplicacao_id (aplicacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permissões de acesso dos grupos às aplicações';

-- Tabela de Ações de Usuário (Log de Auditoria)
CREATE TABLE IF NOT EXISTS acoes_usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL COMMENT 'FK para usuarios',
    aplicacao_id INT COMMENT 'FK para aplicacoes',
    acao ENUM('criar', 'visualizar', 'editar', 'excluir', 'exportar', 'importar', 'login', 'logout', 'acesso_negado') NOT NULL,
    descricao TEXT COMMENT 'Descrição da ação realizada',
    ip_address VARCHAR(45) COMMENT 'IP do usuário',
    user_agent TEXT COMMENT 'Navegador/dispositivo',
    dados_alterados JSON COMMENT 'Dados antes/depois da alteração',
    resultado ENUM('sucesso', 'falha', 'negado') DEFAULT 'sucesso',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id) ON DELETE SET NULL,
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_aplicacao_id (aplicacao_id),
    INDEX idx_acao (acao),
    INDEX idx_resultado (resultado),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de ações realizadas pelos usuários';

-- Tabela de Permissões Especiais por Usuário (Sobrescreve grupo)
CREATE TABLE IF NOT EXISTS permissoes_usuario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL COMMENT 'FK para usuarios',
    aplicacao_id INT NOT NULL COMMENT 'FK para aplicacoes',
    pode_acessar TINYINT(1) DEFAULT NULL COMMENT 'NULL=usar grupo, 0=negar, 1=permitir',
    pode_criar TINYINT(1) DEFAULT NULL,
    pode_visualizar TINYINT(1) DEFAULT NULL,
    pode_editar TINYINT(1) DEFAULT NULL,
    pode_excluir TINYINT(1) DEFAULT NULL,
    pode_exportar TINYINT(1) DEFAULT NULL,
    pode_importar TINYINT(1) DEFAULT NULL,
    observacao TEXT COMMENT 'Motivo da permissão especial',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id) ON DELETE CASCADE,
    UNIQUE KEY uk_usuario_aplicacao (usuario_id, aplicacao_id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_aplicacao_id (aplicacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Permissões especiais que sobrescrevem as do grupo';

-- ============================================
-- VIEWS
-- ============================================

-- View que combina permissões do grupo com permissões especiais do usuário
CREATE OR REPLACE VIEW v_permissoes_efetivas AS
SELECT 
    u.id as usuario_id,
    a.id as aplicacao_id,
    a.codigo,
    a.nome as aplicacao_nome,
    -- Usa permissão especial do usuário se existir, senão usa do grupo
    COALESCE(pu.pode_acessar, pg.pode_acessar, 0) as pode_acessar,
    COALESCE(pu.pode_criar, pg.pode_criar, 0) as pode_criar,
    COALESCE(pu.pode_visualizar, pg.pode_visualizar, 0) as pode_visualizar,
    COALESCE(pu.pode_editar, pg.pode_editar, 0) as pode_editar,
    COALESCE(pu.pode_excluir, pg.pode_excluir, 0) as pode_excluir,
    COALESCE(pu.pode_exportar, pg.pode_exportar, 0) as pode_exportar,
    COALESCE(pu.pode_importar, pg.pode_importar, 0) as pode_importar
FROM usuarios u
CROSS JOIN aplicacoes a
LEFT JOIN permissoes_grupo pg ON u.grupo_id = pg.grupo_id AND a.id = pg.aplicacao_id
LEFT JOIN permissoes_usuario pu ON u.id = pu.usuario_id AND a.id = pu.aplicacao_id
WHERE u.ativo = 1;

-- ============================================
-- DADOS INICIAIS
-- ============================================

-- Inserir Grupos Padrão
INSERT INTO grupos (nome, descricao, ativo) VALUES
('Administradores', 'Acesso total ao sistema', 1),
('Gerentes', 'Acesso gerencial com restrições', 1),
('Operadores', 'Acesso operacional básico', 1),
('Visualizadores', 'Apenas visualização de dados', 1)
ON DUPLICATE KEY UPDATE nome=nome;

-- Inserir Aplicações do Sistema
INSERT INTO aplicacoes (codigo, nome, descricao, url, icone, ordem, modulo, ativo) VALUES
('dashboard', 'Dashboard', 'Painel principal com estatísticas', 'pages/dashboard.php', 'fa-chart-line', 1, 'principal', 1),
('veiculos', 'Veículos', 'Gestão de veículos da frota', 'pages/veiculo.php', 'fa-car', 2, 'frota', 1),
('abastecimentos', 'Abastecimentos', 'Registro de abastecimentos', 'pages/abastecimento.php', 'fa-gas-pump', 3, 'frota', 1),
('usuarios', 'Usuários', 'Gestão de usuários do sistema', 'pages/usuarios.php', 'fa-users', 4, 'seguranca', 1),
('grupos', 'Grupos', 'Gestão de grupos de usuários', 'pages/grupos.php', 'fa-users-cog', 5, 'seguranca', 1),
('aplicacoes', 'Aplicações', 'Gestão de aplicações do sistema', 'pages/aplicacoes.php', 'fa-th-large', 6, 'seguranca', 1),
('permissoes', 'Permissões', 'Gestão de permissões de grupos', 'pages/permissoes.php', 'fa-shield-alt', 7, 'seguranca', 1),
('auditoria', 'Auditoria', 'Log de ações dos usuários', 'pages/auditoria.php', 'fa-history', 8, 'seguranca', 1),
('sincronizacao', 'Sincronização', 'Sincronizar aplicações do sistema', 'pages/sincronizacao.php', 'fa-sync', 9, 'seguranca', 1),
('configuracoes', 'Configurações', 'Configurações gerais do sistema', 'pages/configuracoes.php', 'fa-cogs', 10, 'sistema', 1)
ON DUPLICATE KEY UPDATE nome=nome;

-- Dar permissões totais para o grupo Administradores
INSERT INTO permissoes_grupo (grupo_id, aplicacao_id, pode_acessar, pode_criar, pode_visualizar, pode_editar, pode_excluir, pode_exportar, pode_importar)
SELECT 
    g.id,
    a.id,
    1, 1, 1, 1, 1, 1, 1
FROM grupos g
CROSS JOIN aplicacoes a
WHERE g.nome = 'Administradores'
ON DUPLICATE KEY UPDATE pode_acessar=1, pode_criar=1, pode_visualizar=1, pode_editar=1, pode_excluir=1, pode_exportar=1, pode_importar=1;

-- Atualizar usuários existentes para seus respectivos grupos
-- Administradores: usuários com perfil 'admin' ou usuario 'admin'
UPDATE usuarios 
SET grupo_id = (SELECT id FROM grupos WHERE nome = 'Administradores' LIMIT 1)
WHERE (usuario = 'admin' OR perfil = 'admin') AND grupo_id IS NULL;

-- Operadores: demais usuários ativos
UPDATE usuarios 
SET grupo_id = (SELECT id FROM grupos WHERE nome = 'Operadores' LIMIT 1)
WHERE grupo_id IS NULL AND ativo = 1;
