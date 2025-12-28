# Remoção de Tabelas do Sistema Antigo

## Data: 2025-12-26

## Objetivo
Remover tabelas que eram utilizadas apenas no sistema antigo (ScriptCase) e que não são mais necessárias após a migração para o novo sistema.

## Tabelas Removidas

### 1. Tabelas de Empresa (Sistema Antigo)
- **empresa** - Substituída pela tabela `clientes`
- **empresa_users** - Substituída pela tabela `cliente_usuarios`

### 2. Tabelas de Fornecedor (Sistema Antigo)
- **fornecedor_users** - Substituída pela tabela `fornecedor_usuarios`

### 3. Tabelas de Segurança do ScriptCase
- **sec_apps** - Aplicações do sistema de segurança
- **sec_groups** - Grupos de usuários
- **sec_groups_apps** - Relacionamento grupos-aplicações
- **sec_logged** - Usuários logados
- **sec_settings** - Configurações de segurança
- **sec_users** - Usuários do sistema
- **sec_users_groups** - Relacionamento usuários-grupos

### 4. Tabelas de Log
- **sc_log** - Logs do ScriptCase

## Total de Tabelas Removidas: 11

## Script SQL Executado
- **Arquivo**: `database/remove_tabelas_sistema_antigo.sql`
- **Comando**: `mysql -u renatorap -p'J@melancia01' conceit1_combustivel < database/remove_tabelas_sistema_antigo.sql`
- **Status**: ✅ Executado com sucesso

## Arquivos de Configuração Atualizados

### 1. config_sync.php
- Removidas tabelas obsoletas da lista de sincronização
- Adicionado comentário explicativo sobre a remoção

### 2. sincronizar_producao.php
- Removidas configurações das tabelas obsoletas do array `$config_tabelas`
- Adicionado comentário documentando as tabelas removidas

## Verificação Final

Comando executado para verificar remoção:
```sql
SELECT TABLE_NAME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'conceit1_combustivel' 
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
    'sec_users',
    'sec_users_groups'
);
```

**Resultado**: Nenhuma tabela encontrada ✅

## Impacto

### Positivo
- ✅ Banco de dados mais limpo e organizado
- ✅ Menor complexidade de sincronização
- ✅ Remoção de código legado
- ✅ Melhor performance (menos tabelas para processar)

### Verificações Necessárias
- ✅ Código PHP verificado - apenas 1 comentário encontrado
- ✅ Scripts de sincronização atualizados
- ✅ Nenhuma dependência encontrada

## Próximos Passos

1. ✅ Testar a aplicação para garantir que tudo funciona sem as tabelas removidas
2. ⏳ Executar sincronização para validar o funcionamento
3. ⏳ Monitorar logs para detectar possíveis problemas

## Backup

Existe backup do banco de dados antes da migração:
- **Arquivo**: `backup_pre_migracao_20251226_120802.sql`
- **Tamanho**: 21 MB
- **Data**: 2025-12-26

## Observações

- Todas as tabelas foram removidas com `DROP TABLE IF EXISTS`
- Verificação de chaves estrangeiras foi desabilitada temporariamente durante a remoção
- Não foram encontradas dependências no código PHP das tabelas removidas
- A produção ainda mantém essas tabelas, mas elas não serão mais sincronizadas
