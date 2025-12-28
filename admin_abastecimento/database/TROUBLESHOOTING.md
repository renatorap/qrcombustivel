# 🔧 Solução de Problemas - Migração

## ❌ Erro: "Falha ao criar backup!"

### Causa
O erro ocorre quando o mysqldump encontra views ou procedures com definers que não existem mais no banco de dados.

**Mensagem completa do erro:**
```
mysqldump: Got error: 1449: "The user specified as a definer ('conceit1_renatorap'@'51.79.96.61') does not exist" when using LOCK TABLES
```

### ✅ Solução Aplicada

O script `executar_migracao.sh` foi atualizado com as seguintes flags:

```bash
mysqldump --single-transaction --skip-lock-tables --no-tablespaces
```

**O que cada flag faz:**
- `--single-transaction`: Usa transação ao invés de lock de tabelas
- `--skip-lock-tables`: Ignora bloqueio de tabelas (resolve erro de definer)
- `--no-tablespaces`: Não inclui tablespaces no dump

### 🔄 Como Executar Novamente

```bash
cd /var/www/html/admin_abastecimento/database
./executar_migracao.sh
```

O script agora deve funcionar corretamente e criar um backup de ~21 MB.

---

## ❌ Erro: "Backup criado mas parece pequeno"

### Causa
O backup foi criado mas tem menos de 10 KB, indicando que pode estar vazio.

### ✅ Solução

1. Verifique as credenciais do banco de dados
2. Verifique se o banco tem dados:
   ```bash
   mysql -u renatorap -p'J@melancia01' -D conceit1_combustivel -e "SHOW TABLES;"
   ```

3. Execute backup manual:
   ```bash
   mysqldump -u renatorap -p'J@melancia01' --single-transaction --skip-lock-tables --no-tablespaces conceit1_combustivel > backup_manual.sql
   ```

---

## ❌ Erro: "Conexão com banco de dados falhou"

### Causa
As credenciais no script podem estar incorretas.

### ✅ Solução

1. Verifique as credenciais em `config/config.php`
2. Atualize as variáveis no script `executar_migracao.sh`:
   ```bash
   DB_USER="seu_usuario"
   DB_PASS="sua_senha"
   DB_NAME="conceit1_combustivel"
   ```

3. Teste a conexão:
   ```bash
   mysql -u renatorap -p'J@melancia01' -D conceit1_combustivel -e "SELECT 1"
   ```

---

## ❌ Erro: "Permissão negada"

### Causa
O script não tem permissão de execução.

### ✅ Solução

```bash
chmod +x executar_migracao.sh
```

---

## ❌ Erro Durante a Migração SQL

### Causa
Alguma tabela ou índice não existe ou já foi alterado.

### ✅ Solução

1. **NÃO ENTRE EM PÂNICO** - O backup foi criado!
2. Restaure o backup:
   ```bash
   mysql -u renatorap -p'J@melancia01' conceit1_combustivel < backup_pre_migracao_XXXXXXXX.sql
   ```

3. Verifique o log de erro:
   ```bash
   cat migration_output.log
   ```

4. Ajuste o script SQL conforme necessário

---

## 🔍 Verificações Pós-Erro

### Verificar se id_empresa ainda existe
```sql
SELECT TABLE_NAME, COLUMN_NAME 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'conceit1_combustivel' 
AND COLUMN_NAME = 'id_empresa';
```

### Verificar integridade dos dados
```sql
SELECT COUNT(*) FROM veiculo;
SELECT COUNT(*) FROM condutor;
SELECT COUNT(*) FROM consumo_combustivel;
```

### Verificar backups disponíveis
```bash
ls -lh backup*.sql
```

---

## 📞 Suporte Adicional

Se o problema persistir:

1. ✅ Verifique os logs do MySQL:
   ```bash
   sudo tail -f /var/log/mysql/error.log
   ```

2. ✅ Verifique permissões:
   ```bash
   ls -la /var/www/html/admin_abastecimento/database/
   ```

3. ✅ Execute backup manual primeiro:
   ```bash
   mysqldump -u renatorap -p'J@melancia01' --single-transaction --skip-lock-tables --no-tablespaces conceit1_combustivel > backup_manual_$(date +%Y%m%d).sql
   ```

4. ✅ Execute migração SQL manualmente:
   ```bash
   mysql -u renatorap -p'J@melancia01' conceit1_combustivel < migration_remove_id_empresa.sql
   ```

---

## ✅ Status da Correção

- ✅ **Script atualizado** com flags corretas do mysqldump
- ✅ **Backup testado** e funcionando (21 MB)
- ✅ **Validações adicionadas** para verificar tamanho do backup
- ✅ **Mensagens de erro melhoradas** para facilitar debug

**Última atualização:** 2025-12-26 12:03
