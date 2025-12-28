-- ========================================
-- MIGRAÇÃO: Remover id_empresa e manter apenas id_cliente
-- ========================================
-- Data: 2025-12-26
-- Descrição: Remove o campo id_empresa de todas as tabelas e mantém apenas id_cliente
-- Os valores são idênticos, então não há perda de dados
-- ========================================

-- IMPORTANTE: Faça backup do banco de dados antes de executar este script!
-- mysqldump -u renatorap -p conceit1_combustivel > backup_antes_migracao_$(date +%Y%m%d_%H%M%S).sql

-- ========================================
-- ETAPA 1: Garantir que id_cliente está preenchido em todas as tabelas
-- ========================================

-- Copiar valores de id_empresa para id_cliente onde id_cliente está NULL (redundância de segurança)
-- Apenas para tabelas que têm id_empresa
UPDATE aditamento_combustivel SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE cargo SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE combustivel_veiculo SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE condutor SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE consumo_combustivel SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE consumo_combustivel_original_fk_alterada SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE contrato SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE empresa SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE empresa_users SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE fornecedor SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE fornecedor_users SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE licitacao SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE preco_combustivel SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE requisicao SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE sec_users SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE sec_users_groups SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE setor SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE un_orcamentaria SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;
UPDATE veiculo SET id_cliente = id_empresa WHERE id_cliente IS NULL AND id_empresa IS NOT NULL;

-- Tabelas que NÃO têm id_empresa (apenas id_cliente): condutor_qrcode, veiculo_qrcode, licenca, extrato_combustivel
-- Estas tabelas não precisam de UPDATE pois não têm id_empresa para copiar

-- ========================================
-- ETAPA 2: Remover chaves estrangeiras que referenciam id_empresa
-- ========================================

-- Remover FK de empresa_users
ALTER TABLE empresa_users DROP FOREIGN KEY IF EXISTS fk_eu_id_empresa;

-- Remover FK de fornecedor
ALTER TABLE fornecedor DROP FOREIGN KEY IF EXISTS fk_fornecedor_id_empresa;

-- Remover FK de requisicao
ALTER TABLE requisicao DROP FOREIGN KEY IF EXISTS fk_requisicao_id_empresa;

-- ========================================
-- ETAPA 3: Remover índices relacionados a id_empresa
-- ========================================

-- Verificar e remover índices (os nomes podem variar)
ALTER TABLE aditamento_combustivel DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE cargo DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE combustivel_veiculo DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE condutor DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE consumo_combustivel DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE consumo_combustivel_original_fk_alterada DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE contrato DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE empresa DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE empresa DROP INDEX IF EXISTS id_empresa;
ALTER TABLE empresa_users DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE fornecedor DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE fornecedor_users DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE licitacao DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE preco_combustivel DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE requisicao DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE sec_users DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE sec_users_groups DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE setor DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE un_orcamentaria DROP INDEX IF EXISTS idx_id_empresa;
ALTER TABLE veiculo DROP INDEX IF EXISTS idx_id_empresa;

-- ========================================
-- ETAPA 4: Remover a coluna id_empresa de todas as tabelas
-- ========================================

-- Primeiro, remover as VIEWs que usam id_empresa
DROP VIEW IF EXISTS extrato_combustivel;
DROP VIEW IF EXISTS vl_comb_adit_ativo;

-- Remover coluna id_empresa das tabelas base
ALTER TABLE aditamento_combustivel DROP COLUMN id_empresa;
ALTER TABLE cargo DROP COLUMN id_empresa;
ALTER TABLE combustivel_veiculo DROP COLUMN id_empresa;
ALTER TABLE condutor DROP COLUMN id_empresa;
ALTER TABLE consumo_combustivel DROP COLUMN id_empresa;
ALTER TABLE consumo_combustivel_original_fk_alterada DROP COLUMN id_empresa;
ALTER TABLE contrato DROP COLUMN id_empresa;
ALTER TABLE empresa DROP COLUMN id_empresa;
ALTER TABLE empresa_users DROP COLUMN id_empresa;
ALTER TABLE fornecedor DROP COLUMN id_empresa;
ALTER TABLE fornecedor_users DROP COLUMN id_empresa;
ALTER TABLE licitacao DROP COLUMN id_empresa;
ALTER TABLE preco_combustivel DROP COLUMN id_empresa;
ALTER TABLE requisicao DROP COLUMN id_empresa;
ALTER TABLE sec_users DROP COLUMN id_empresa;
ALTER TABLE sec_users_groups DROP COLUMN id_empresa;
ALTER TABLE setor DROP COLUMN id_empresa;
ALTER TABLE un_orcamentaria DROP COLUMN id_empresa;
ALTER TABLE veiculo DROP COLUMN id_empresa;

-- Recriar VIEWs usando id_cliente ao invés de id_empresa
CREATE OR REPLACE VIEW extrato_combustivel AS 
SELECT 
    cc.id_cliente AS id_cliente,
    cc.id_veiculo AS id_veiculo,
    v.placa AS placa,
    v.modelo AS modelo,
    cc.data AS data,
    cc.hora AS hora,
    cc.km_veiculo AS km_veic_atu,
    LAG(cc.km_veiculo, 1) OVER (ORDER BY cc.id_cliente, cc.id_veiculo, cc.data, cc.hora) AS km_veic_ant,
    cc.km_veiculo - LAG(cc.km_veiculo, 1) OVER (ORDER BY cc.id_cliente, cc.id_veiculo, cc.data, cc.hora) AS km_rodado,
    ROUND((cc.km_veiculo - LAG(cc.km_veiculo, 1) OVER (ORDER BY cc.id_cliente, cc.id_veiculo, cc.data, cc.hora)) / cc.litragem, 2) AS km_litro,
    cc.litragem AS litragem,
    f.nome_fantasia AS nome_fant,
    c.nome AS condutor,
    s.descricao AS setor,
    p.descricao AS produto,
    cc.valor_unitario AS vl_unit,
    cc.valor_total AS vl_total
FROM consumo_combustivel cc
INNER JOIN veiculo v ON v.id_veiculo = cc.id_veiculo
INNER JOIN setor s ON s.id_setor = v.id_setor
INNER JOIN condutor c ON c.id_condutor = cc.id_condutor
INNER JOIN produto p ON cc.id_produto = p.id_produto
INNER JOIN fornecedor f ON f.id_fornecedor = cc.id_fornecedor;

CREATE OR REPLACE VIEW vl_comb_adit_ativo AS
SELECT DISTINCT
    f.id_fornecedor AS id_fornecedor,
    f.nome_fantasia AS nome_posto,
    p.id_produto AS id_produto,
    p.descricao AS produto,
    pc.inicio_vigencia AS inicio_vigencia,
    pc.valor AS valor,
    f.id_cliente AS id_cliente
FROM fornecedor f
INNER JOIN contrato c ON c.id_fornecedor = f.id_fornecedor
INNER JOIN aditamento_combustivel ac ON ac.id_contrato = c.id_contrato
INNER JOIN preco_combustivel pc ON pc.id_aditamento_combustivel = ac.id_aditamento_combustivel
INNER JOIN produto p ON p.id_produto = pc.id_produto
WHERE pc.fim_vigencia IS NULL;

-- ========================================
-- ETAPA 5: Criar índices otimizados para id_cliente (se não existirem)
-- ========================================

ALTER TABLE aditamento_combustivel ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE cargo ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE combustivel_veiculo ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE condutor ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE condutor_qrcode ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE consumo_combustivel ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE consumo_combustivel_original_fk_alterada ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE contrato ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE empresa ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE empresa_users ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE fornecedor ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE fornecedor_users ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE licitacao ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);

-- Nota: extrato_combustivel e vl_comb_adit_ativo são VIEWs, não tabelas, então não precisam de índices
ALTER TABLE preco_combustivel ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE requisicao ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE sec_users ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE sec_users_groups ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE setor ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE un_orcamentaria ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE veiculo ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);
ALTER TABLE veiculo_qrcode ADD INDEX IF NOT EXISTS idx_id_cliente (id_cliente);

-- Nota: vl_comb_adit_ativo é VIEW, não tabela

-- ========================================
-- ETAPA 6: Recriar chaves estrangeiras apontando para id_cliente
-- ========================================

-- FK para empresa_users (agora referenciando clientes ao invés de empresa)
ALTER TABLE empresa_users 
ADD CONSTRAINT fk_eu_id_cliente 
FOREIGN KEY (id_cliente) REFERENCES clientes(id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- FK para fornecedor (agora referenciando clientes ao invés de empresa)
ALTER TABLE fornecedor 
ADD CONSTRAINT fk_fornecedor_id_cliente 
FOREIGN KEY (id_cliente) REFERENCES clientes(id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- FK para requisicao (agora referenciando clientes ao invés de empresa)
ALTER TABLE requisicao 
ADD CONSTRAINT fk_requisicao_id_cliente 
FOREIGN KEY (id_cliente) REFERENCES clientes(id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- ========================================
-- VERIFICAÇÃO FINAL
-- ========================================

SELECT 'Migração concluída!' AS status;

-- Verificar que id_empresa não existe mais
SELECT 
    TABLE_NAME, 
    COLUMN_NAME 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'conceit1_combustivel' 
AND COLUMN_NAME = 'id_empresa';

-- Deve retornar 0 linhas

-- Verificar tabelas com id_cliente
SELECT 
    TABLE_NAME, 
    COLUMN_NAME,
    COLUMN_TYPE
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'conceit1_combustivel' 
AND COLUMN_NAME = 'id_cliente'
ORDER BY TABLE_NAME;

SELECT '========================================' AS separador;
SELECT 'MIGRAÇÃO CONCLUÍDA COM SUCESSO!' AS resultado;
SELECT 'Todas as referências a id_empresa foram removidas' AS detalhe1;
SELECT 'Todos os relacionamentos agora usam id_cliente' AS detalhe2;
SELECT '========================================' AS separador;
