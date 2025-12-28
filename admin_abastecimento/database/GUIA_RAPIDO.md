# ⚡ GUIA RÁPIDO DE EXECUÇÃO

## 🎯 Objetivo
Remover campo redundante `id_empresa` e usar apenas `id_cliente`

## ⏱️ Tempo Total
5-10 minutos (incluindo backup e testes)

## 🚀 Execução em 3 Passos

### 1️⃣ Preparação (2 minutos)
```bash
cd /var/www/html/admin_abastecimento/database
ls -lh migration_remove_id_empresa.sql
```

### 2️⃣ Execução (3 minutos)
```bash
./executar_migracao.sh
```
- Digite **SIM** quando solicitado
- Aguarde a conclusão
- Verifique se todas as validações passaram

### 3️⃣ Validação (2 minutos)
```bash
# Testar aplicação
firefox http://localhost/admin_abastecimento/

# Verificar logs
tail -f /var/log/apache2/error.log
```

## ✅ O Script Faz Automaticamente

1. ✅ Cria backup do banco
2. ✅ Executa migração SQL
3. ✅ Valida resultado
4. ✅ Verifica integridade
5. ✅ Exibe relatório

## 📊 Saída Esperada

```
========================================
  MIGRAÇÃO CONCLUÍDA COM SUCESSO!
========================================

📊 Resumo:
  • Tabelas migradas: 23
  • Backup salvo em: backup_pre_migracao_XXXXXXXX.sql
  • Log da migração: migration_output.log

✅ Próximos passos:
  1. Testar a aplicação
  2. Verificar funcionalidades principais
  3. Monitorar logs de erro
```

## ⚠️ Se Algo Der Errado

```bash
# Restaurar backup (o script mostra o comando exato)
mysql -u renatorap -p'J@melancia01' conceit1_combustivel < backup_pre_migracao_XXXXXXXX.sql
```

## 📱 Teste Rápido

1. Login → ✅
2. Selecionar cliente → ✅
3. Listar veículos → ✅
4. Ver relatório → ✅
5. PostoApp → ✅

## ✨ Resultado

- ❌ `id_empresa` removido de 23 tabelas
- ✅ `id_cliente` consolidado como único campo
- ✅ Código PHP atualizado (11 arquivos)
- ✅ Foreign Keys recriadas
- ✅ Índices otimizados

---

**Dúvidas?** Consulte [README_MIGRACAO.md](README_MIGRACAO.md) ou [MIGRATION_SUMMARY.md](MIGRATION_SUMMARY.md)
