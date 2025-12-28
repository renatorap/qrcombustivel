-- ========================================
-- Remover Tabelas do Sistema Antigo
-- ========================================
-- Data: 2025-12-26
-- Descrição: Remove tabelas que são utilizadas apenas no sistema antigo
-- ========================================

-- IMPORTANTE: Faça backup do banco de dados antes de executar este script!

SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- Remover tabelas relacionadas a empresa (sistema antigo)
-- ========================================
DROP TABLE IF EXISTS empresa_users;
DROP TABLE IF EXISTS empresa;

-- ========================================
-- Remover tabelas de fornecedor_users (sistema antigo)
-- ========================================
DROP TABLE IF EXISTS fornecedor_users;

-- ========================================
-- Remover tabelas de segurança do sistema antigo (ScriptCase)
-- ========================================
DROP TABLE IF EXISTS sec_logged;
DROP TABLE IF EXISTS sec_users_groups;
DROP TABLE IF EXISTS sec_groups_apps;
DROP TABLE IF EXISTS sec_settings;
DROP TABLE IF EXISTS sec_users;
DROP TABLE IF EXISTS sec_apps;
DROP TABLE IF EXISTS sec_groups;

-- ========================================
-- Remover tabela de log do sistema antigo
-- ========================================
DROP TABLE IF EXISTS sc_log;

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- Verificação Final
-- ========================================
SELECT 'Tabelas removidas com sucesso!' AS status;

-- Verificar quais tabelas ainda existem dessas listadas
SELECT 
    TABLE_NAME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN (
    'empresa', 
    'empresa_users', 
    'fornecedor_users', 
    'sc_log', 
    'sec_apps', 
    'sec_groups', 
    'sec_groups_apps', 
    'sec_logged', 
    'sec_settings', 
    'sec_users'
)
ORDER BY TABLE_NAME;

-- Se retornar 0 linhas, todas foram removidas com sucesso
