-- ========================================
-- MULTICLIENTE - Sistema Multiempresa
-- ========================================
-- Permite que usuários acessem múltiplos clientes
-- Vincula dados de usuários, veículos e dashboard a clientes específicos

-- ========================================
-- 1. Tabela de relacionamento usuário-cliente
-- ========================================
CREATE TABLE IF NOT EXISTS usuario_cliente (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) NOT NULL,
    cliente_id INT(11) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    data_vinculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_usuario_cliente (usuario_id, cliente_id),
    KEY idx_usuario (usuario_id),
    KEY idx_cliente (cliente_id),
    CONSTRAINT fk_usuario_cliente_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_cliente_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 2. Adicionar cliente_id em tabelas pertinentes
-- ========================================

-- 2.1. Tabela usuarios (já deve ter cliente_id, mas vamos garantir)
-- Verifica se a coluna existe antes de adicionar
SET @exist_usuarios := (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'usuarios' 
    AND COLUMN_NAME = 'cliente_id'
);

SET @sql_usuarios := IF(
    @exist_usuarios = 0,
    'ALTER TABLE usuarios ADD COLUMN cliente_id INT(11) NULL AFTER grupo_id, ADD KEY idx_cliente (cliente_id), ADD CONSTRAINT fk_usuarios_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL',
    'SELECT "Coluna cliente_id já existe em usuarios" AS message'
);

PREPARE stmt_usuarios FROM @sql_usuarios;
EXECUTE stmt_usuarios;
DEALLOCATE PREPARE stmt_usuarios;

-- 2.2. Tabela veiculo (já possui id_cliente, não precisa adicionar cliente_id)
-- A tabela veiculo já tem o campo id_cliente para identificar o cliente
-- Portanto, vamos apenas garantir que não há valores NULL
SELECT 'Tabela veiculo já possui campo id_cliente' AS message;

-- ========================================
-- 3. Vincular usuário Administrador a todos os clientes existentes
-- ========================================
-- Busca grupo Administradores
SET @admin_grupo_id := (SELECT id FROM grupos WHERE nome = 'Administradores' LIMIT 1);

-- Vincular todos os usuários do grupo Administradores a todos os clientes
INSERT INTO usuario_cliente (usuario_id, cliente_id, ativo)
SELECT u.id, c.id, 1
FROM usuarios u
INNER JOIN clientes c ON c.ativo = 1
WHERE u.grupo_id = @admin_grupo_id
ON DUPLICATE KEY UPDATE ativo = 1;

-- ========================================
-- 4. Vincular usuários comuns ao seu cliente específico
-- ========================================
-- Usuários que já têm cliente_id definido
INSERT INTO usuario_cliente (usuario_id, cliente_id, ativo)
SELECT id, cliente_id, 1
FROM usuarios
WHERE cliente_id IS NOT NULL
ON DUPLICATE KEY UPDATE ativo = 1;

-- ========================================
-- 5. Definir cliente padrão para veículos sem cliente
-- ========================================
-- Atribuir primeiro cliente ativo aos veículos sem id_cliente
UPDATE veiculo v
SET v.id_cliente = (SELECT id FROM clientes WHERE ativo = 1 ORDER BY id LIMIT 1)
WHERE v.id_cliente IS NULL OR v.id_cliente = 0;

-- ========================================
-- 6. Criar índices para performance
-- ========================================
CREATE INDEX IF NOT EXISTS idx_usuario_cliente_ativo ON usuario_cliente(ativo);
CREATE INDEX IF NOT EXISTS idx_usuarios_cliente_ativo ON usuarios(cliente_id, ativo);
-- A tabela veiculo usa id_cliente, não cliente_id
-- CREATE INDEX IF NOT EXISTS idx_veiculo_cliente_ativo ON veiculo(id_cliente, ativo);

-- ========================================
-- VERIFICAÇÃO FINAL
-- ========================================
SELECT 
    'usuario_cliente' AS tabela,
    COUNT(*) AS total_registros
FROM usuario_cliente
UNION ALL
SELECT 
    'usuarios com cliente' AS tabela,
    COUNT(*) AS total_registros
FROM usuarios WHERE cliente_id IS NOT NULL
UNION ALL
SELECT 
    'veiculos com cliente' AS tabela,
    COUNT(*) AS total_registros
FROM veiculo WHERE id_cliente IS NOT NULL;

SELECT '✓ Script de multicliente executado com sucesso!' AS status;
