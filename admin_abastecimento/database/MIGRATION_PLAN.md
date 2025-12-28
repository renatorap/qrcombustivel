# ========================================
# Script de Migração - Substituir id_empresa por id_cliente
# ========================================
# Data: 2025-12-26
# Descrição: Lista todos os arquivos PHP que precisam ser atualizados
# ========================================

## Tabelas afetadas (23 tabelas):

1. **aditamento_combustivel** - tem id_empresa e id_cliente
2. **cargo** - tem id_empresa e id_cliente
3. **combustivel_veiculo** - tem id_empresa e id_cliente
4. **condutor** - tem id_empresa e id_cliente
5. **condutor_qrcode** - tem id_cliente e id_empresa
6. **consumo_combustivel** - tem id_empresa e id_cliente
7. **consumo_combustivel_original_fk_alterada** - tem id_empresa e id_cliente
8. **contrato** - tem id_empresa e id_cliente
9. **empresa** - tem id_empresa e id_cliente
10. **empresa_users** - tem id_empresa e id_cliente
11. **extrato_combustivel** - tem apenas id_empresa
12. **fornecedor** - tem id_empresa e id_cliente
13. **fornecedor_users** - tem id_empresa e id_cliente
14. **licitacao** - tem id_empresa e id_cliente
15. **preco_combustivel** - tem id_empresa e id_cliente
16. **requisicao** - tem id_empresa e id_cliente
17. **sec_users** - tem id_empresa e id_cliente
18. **sec_users_groups** - tem id_empresa e id_cliente
19. **setor** - tem id_empresa e id_cliente
20. **un_orcamentaria** - tem id_empresa e id_cliente
21. **veiculo** - tem id_empresa e id_cliente
22. **veiculo_qrcode** - tem apenas id_cliente
23. **vl_comb_adit_ativo** - tem apenas id_empresa

## Arquivos PHP que precisam ser atualizados (10 arquivos):

### 1. /var/www/html/admin_abastecimento/sync/sincronizar_producao.php
- Linhas afetadas: 7, 28, 245, 407, 408, 425, 427, 428, 614, 631, 633, 779
- Ações: 
  - Remover lógica de mapeamento id_empresa → id_cliente
  - Atualizar PK de 'id_empresa' para 'id'
  - Atualizar queries SQL

### 2. /var/www/html/admin_abastecimento/pages/relatorio_extrato_abastecimento_export.php
- Linhas afetadas: 83, 223, 231, 281, 288
- Ações: Substituir cc.id_empresa por cc.id_cliente

### 3. /var/www/html/admin_abastecimento/api/fornecedor.php
- Linhas afetadas: 135, 137, 144
- Ações: 
  - Remover campo id_empresa do INSERT
  - Remover variável $id_empresa

### 4. /var/www/html/admin_abastecimento/api/veiculo.php
- Linhas afetadas: 217, 269, 503
- Ações: 
  - Remover $empresaId da sessão
  - Remover id_empresa do INSERT

### 5. /var/www/html/admin_abastecimento/api/setor.php
- Linha afetada: 123
- Ações: Remover id_empresa do INSERT

### 6. /var/www/html/admin_abastecimento/api/contrato.php
- Linha afetada: 277
- Ações: Remover id_empresa do INSERT

### 7. /var/www/html/admin_abastecimento/api/unidade_orcamentaria.php
- Linha afetada: 131
- Ações: Remover id_empresa do INSERT

### 8. /var/www/html/admin_abastecimento/api/relatorio_extrato_abastecimento.php
- Linhas afetadas: 57, 197, 205, 257, 264, 285, 292, 328, 353, 380, 403, 428
- Ações: Substituir cc.id_empresa por cc.id_cliente

### 9. /var/www/html/admin_abastecimento/api/licitacao.php
- Linha afetada: 177
- Ações: Remover id_empresa do INSERT

### 10. /var/www/html/admin_abastecimento/api/aditamento_combustivel.php
- Linhas afetadas: 338, 512
- Ações: Remover id_empresa do INSERT

### 11. /var/www/html/postoapp/salvar_abastecimento.php
- Linhas afetadas: 173, 188, 205
- Ações: Remover id_empresa do INSERT e do bind

### 12. /var/www/html/postoapp/pump_capt.php
- Linha afetada: 374
- Ações: Remover echo de id_empresa (debug)

## Etapas de Migração:

### ANTES DE EXECUTAR:
1. ✅ **BACKUP DO BANCO DE DADOS**
   ```bash
   mysqldump -u renatorap -p'J@melancia01' conceit1_combustivel > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. ✅ **BACKUP DOS ARQUIVOS PHP**
   ```bash
   tar -czf backup_php_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/html/admin_abastecimento /var/www/html/postoapp
   ```

### EXECUTAR:
3. ⚠️ **Colocar sistema em manutenção** (opcional mas recomendado)

4. 🗄️ **Executar migration SQL**
   ```bash
   mysql -u renatorap -p'J@melancia01' conceit1_combustivel < /var/www/html/admin_abastecimento/database/migration_remove_id_empresa.sql
   ```

5. 📝 **Aplicar alterações nos arquivos PHP** (será feito automaticamente)

6. ✅ **Testar aplicação**

7. ✅ **Remover modo de manutenção**

## Validações Pós-Migração:

1. Verificar que nenhuma coluna id_empresa existe mais:
   ```sql
   SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS 
   WHERE TABLE_SCHEMA = 'conceit1_combustivel' AND COLUMN_NAME = 'id_empresa';
   ```
   - Deve retornar 0 linhas

2. Verificar integridade dos dados:
   ```sql
   SELECT COUNT(*) FROM veiculo WHERE id_cliente IS NULL;
   SELECT COUNT(*) FROM condutor WHERE id_cliente IS NULL;
   SELECT COUNT(*) FROM consumo_combustivel WHERE id_cliente IS NULL;
   ```
   - Todos devem retornar 0

3. Testar funcionalidades principais:
   - Login
   - Listagem de veículos
   - Listagem de condutores
   - Relatórios
   - Abastecimento (PostoApp)

## Notas Importantes:

- ✅ **Dados confirmados**: id_empresa e id_cliente têm valores idênticos em todas as tabelas
- ✅ **Sem perda de dados**: Todos os valores serão preservados
- ✅ **Foreign Keys**: Serão recriadas apontando para clientes.id
- ⚠️ **Tempo estimado**: 2-5 minutos de downtime
- ⚠️ **Reversão**: Possível através do backup (se necessário)
