# 🗄️ Migração: Remover id_empresa e Consolidar em id_cliente

## 📌 Visão Geral

Esta migração remove o campo redundante `id_empresa` de todas as tabelas do banco de dados, mantendo apenas `id_cliente` como campo de referência ao cliente/empresa.

## ✅ Status da Migração

- **Análise**: ✅ Completa
- **Script SQL**: ✅ Criado e validado
- **Código PHP**: ✅ Atualizado (11 arquivos)
- **Documentação**: ✅ Completa
- **Pronto para execução**: ✅ SIM

## 📂 Arquivos Criados

1. **migration_remove_id_empresa.sql** - Script SQL principal da migração
2. **executar_migracao.sh** - Script bash automatizado para execução
3. **MIGRATION_PLAN.md** - Plano detalhado da migração
4. **MIGRATION_SUMMARY.md** - Resumo completo com todas as análises
5. **README_MIGRACAO.md** - Este arquivo

## 🚀 Como Executar

### Opção 1: Script Automatizado (Recomendado)

```bash
cd /var/www/html/admin_abastecimento/database
./executar_migracao.sh
```

O script irá:
1. ✅ Solicitar confirmação
2. ✅ Criar backup automático
3. ✅ Executar a migração
4. ✅ Validar o resultado
5. ✅ Exibir relatório completo

### Opção 2: Manual

```bash
# 1. Backup
mysqldump -u renatorap -p'J@melancia01' conceit1_combustivel > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. Executar migração
mysql -u renatorap -p'J@melancia01' conceit1_combustivel < migration_remove_id_empresa.sql

# 3. Verificar
mysql -u renatorap -p'J@melancia01' -D conceit1_combustivel -e "
SELECT TABLE_NAME FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'conceit1_combustivel' AND COLUMN_NAME = 'id_empresa';"
```

## 📊 O Que Será Alterado

### Banco de Dados:
- ❌ Remove coluna `id_empresa` de **23 tabelas**
- ✅ Mantém coluna `id_cliente` em todas as tabelas
- 🔄 Atualiza **3 Foreign Keys** para apontar para `clientes.id`
- 📇 Cria índices otimizados em `id_cliente`

### Código PHP (Já Atualizado):
- ✅ **11 arquivos PHP** modificados
- ✅ **35+ alterações** aplicadas
- ✅ Todas as queries SQL atualizadas
- ✅ Todos os INSERTs corrigidos

## 🔍 Validações Realizadas

### ✅ Dados Idênticos
- Verificado que `id_empresa = id_cliente` em **100% dos registros**
- Total de registros analisados: **1.945+**
- Nenhuma inconsistência encontrada

### ✅ Arquivos Atualizados
| Arquivo | Alterações | Status |
|---------|-----------|--------|
| api/fornecedor.php | 1 INSERT | ✅ |
| api/veiculo.php | 2 INSERTs | ✅ |
| api/setor.php | 1 INSERT | ✅ |
| api/contrato.php | 1 INSERT | ✅ |
| api/unidade_orcamentaria.php | 1 INSERT | ✅ |
| api/licitacao.php | 1 INSERT | ✅ |
| api/aditamento_combustivel.php | 2 INSERTs | ✅ |
| api/relatorio_extrato_abastecimento.php | 9 queries | ✅ |
| pages/relatorio_extrato_abastecimento_export.php | 3 queries | ✅ |
| postoapp/salvar_abastecimento.php | 1 INSERT | ✅ |
| sync/sincronizar_producao.php | 6 alterações | ✅ |

## ⚠️ Importante

### Antes de Executar:
1. ✅ **FAÇA BACKUP** do banco de dados
2. ✅ Coloque o sistema em manutenção (opcional)
3. ✅ Avise os usuários sobre a manutenção
4. ✅ Teste em ambiente de desenvolvimento (se possível)

### Durante a Execução:
- ⏱️ Tempo estimado: **2-5 minutos**
- 🔒 Banco ficará bloqueado temporariamente
- 📊 Acompanhe o progresso no terminal

### Após a Execução:
- ✅ Teste login no sistema
- ✅ Teste seleção de cliente
- ✅ Teste CRUD de veículos
- ✅ Teste relatórios
- ✅ Teste abastecimento via PostoApp

## 🔄 Reversão

Se necessário, restaure o backup:

```bash
mysql -u renatorap -p'J@melancia01' conceit1_combustivel < backup_YYYYMMDD_HHMMSS.sql
```

## 📋 Checklist Pós-Migração

```
□ id_empresa não existe mais em nenhuma tabela
□ Todos os registros têm id_cliente preenchido
□ Foreign Keys recriadas corretamente
□ Índices criados em id_cliente
□ Sistema funciona normalmente
□ Relatórios funcionam corretamente
□ PostoApp funciona corretamente
□ Nenhum erro nos logs
```

## 📞 Suporte

- 📄 Consulte **MIGRATION_SUMMARY.md** para detalhes completos
- 📝 Consulte **MIGRATION_PLAN.md** para o plano detalhado
- 📊 Verifique os logs em `migration_output.log` após execução

## 🎯 Resultado Esperado

Após a migração bem-sucedida:

```
✅ 23 tabelas migradas
✅ 0 tabelas com id_empresa
✅ 23 tabelas com id_cliente
✅ 3 Foreign Keys recriadas
✅ 11 arquivos PHP atualizados
✅ Sistema funcionando normalmente
```

---

**Criado em**: 2025-12-26  
**Status**: ✅ Pronto para Execução  
**Versão**: 1.0
