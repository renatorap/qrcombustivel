-- Script de Verificação e Correção de Vínculos Usuário-Cliente
-- Sistema QR Combustível

-- ============================================
-- PARTE 1: DIAGNÓSTICO
-- ============================================

-- 1. Listar todos os usuários e seus vínculos
SELECT 
    u.id,
    u.login,
    u.nome,
    g.nome as grupo,
    u.cliente_id as cliente_direto,
    GROUP_CONCAT(DISTINCT c2.nome_fantasia ORDER BY uc.data_vinculo DESC) as clientes_vinculados,
    u.ativo as usuario_ativo
FROM usuarios u
LEFT JOIN grupos g ON u.grupo_id = g.id
LEFT JOIN usuario_cliente uc ON u.id = uc.usuario_id AND uc.ativo = 1
LEFT JOIN clientes c2 ON uc.cliente_id = c2.id
GROUP BY u.id
ORDER BY g.id, u.login;

-- 2. Usuários SEM vínculo com cliente (potencial problema)
SELECT 
    u.id,
    u.login,
    u.nome,
    g.nome as grupo,
    'SEM VÍNCULO!' as status
FROM usuarios u
LEFT JOIN grupos g ON u.grupo_id = g.id
LEFT JOIN usuario_cliente uc ON u.id = uc.usuario_id AND uc.ativo = 1
WHERE u.ativo = 1 
AND u.grupo_id NOT IN (1) -- Não incluir administradores
AND u.cliente_id IS NULL 
AND uc.id IS NULL;

-- 3. Verificar vínculos duplicados (usuário com múltiplos clientes ativos)
SELECT 
    u.id,
    u.login,
    COUNT(*) as total_vinculos,
    GROUP_CONCAT(c.nome_fantasia) as clientes
FROM usuario_cliente uc
JOIN usuarios u ON uc.usuario_id = u.id
JOIN clientes c ON uc.cliente_id = c.id
WHERE uc.ativo = 1
GROUP BY u.id
HAVING COUNT(*) > 1;

-- 4. Clientes sem usuários vinculados
SELECT 
    c.id,
    c.nome_fantasia,
    c.razao_social,
    'SEM USUÁRIOS' as status
FROM clientes c
LEFT JOIN usuarios u ON c.id = u.cliente_id AND u.ativo = 1
LEFT JOIN usuario_cliente uc ON c.id = uc.cliente_id AND uc.ativo = 1
WHERE c.ativo = 1
AND u.id IS NULL
AND uc.id IS NULL;

-- ============================================
-- PARTE 2: CORREÇÕES COMUNS
-- ============================================

-- Exemplo 1: Vincular usuário existente a cliente
-- IMPORTANTE: Ajustar IDs conforme necessário
-- INSERT INTO usuario_cliente (usuario_id, cliente_id, ativo) 
-- VALUES (2, 1, 1);

-- Exemplo 2: Atualizar vínculo direto na tabela usuarios
-- UPDATE usuarios 
-- SET cliente_id = 1 
-- WHERE id = 2;

-- Exemplo 3: Desativar vínculo antigo
-- UPDATE usuario_cliente 
-- SET ativo = 0 
-- WHERE id = 1;

-- Exemplo 4: Ativar vínculo desativado
-- UPDATE usuario_cliente 
-- SET ativo = 1 
-- WHERE id = 1;

-- ============================================
-- PARTE 3: CRIAÇÃO DE VÍNCULOS EM MASSA
-- ============================================

-- Vincular todos os operadores de um grupo a um cliente específico
-- CUIDADO: Revisar antes de executar!

-- Exemplo: Vincular todos Operadores de Prefeitura ao cliente 1
-- INSERT INTO usuario_cliente (usuario_id, cliente_id, ativo)
-- SELECT u.id, 1, 1
-- FROM usuarios u
-- WHERE u.grupo_id = 3 -- Operador Prefeitura
-- AND u.ativo = 1
-- AND NOT EXISTS (
--     SELECT 1 FROM usuario_cliente uc 
--     WHERE uc.usuario_id = u.id AND uc.ativo = 1
-- );

-- ============================================
-- PARTE 4: LIMPEZA E MANUTENÇÃO
-- ============================================

-- Remover vínculos de usuários inativos
-- DELETE FROM usuario_cliente 
-- WHERE usuario_id IN (
--     SELECT id FROM usuarios WHERE ativo = 0
-- );

-- Remover vínculos de clientes inativos
-- DELETE FROM usuario_cliente 
-- WHERE cliente_id IN (
--     SELECT id FROM clientes WHERE ativo = 0
-- );

-- ============================================
-- PARTE 5: VERIFICAÇÃO PÓS-CORREÇÃO
-- ============================================

-- Verificar se todos os operadores ativos têm cliente
SELECT 
    'VERIFICAÇÃO FINAL' as status,
    COUNT(*) as usuarios_sem_cliente
FROM usuarios u
LEFT JOIN usuario_cliente uc ON u.id = uc.usuario_id AND uc.ativo = 1
WHERE u.ativo = 1 
AND u.grupo_id NOT IN (1) -- Excluir administradores
AND u.cliente_id IS NULL 
AND uc.id IS NULL;

-- Resultado esperado: 0 usuários sem cliente

-- ============================================
-- QUERIES ÚTEIS PARA DEBUG
-- ============================================

-- Ver dados completos de um usuário específico
-- SELECT * FROM usuarios WHERE login = 'nome_usuario';

-- Ver todos os vínculos de um usuário
-- SELECT 
--     uc.*,
--     c.nome_fantasia,
--     c.email
-- FROM usuario_cliente uc
-- JOIN clientes c ON uc.cliente_id = c.id
-- WHERE uc.usuario_id = X;

-- Ver todos os usuários de um cliente
-- SELECT 
--     u.id,
--     u.login,
--     u.nome,
--     g.nome as grupo,
--     uc.data_vinculo
-- FROM usuario_cliente uc
-- JOIN usuarios u ON uc.usuario_id = u.id
-- JOIN grupos g ON u.grupo_id = g.id
-- WHERE uc.cliente_id = Y
-- AND uc.ativo = 1;
