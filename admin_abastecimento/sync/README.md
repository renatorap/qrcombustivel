# Sistema de Sincronização de Dados

## Descrição

Sistema para sincronizar dados do servidor de **PRODUÇÃO** para o ambiente **LOCALHOST**.

- ✅ Sincroniza apenas registros novos ou alterados
- ✅ Respeita a ordem de dependências (Foreign Keys)
- ✅ Adiciona automaticamente `id_cliente = id_empresa` quando necessário
- ✅ Gera logs detalhados de cada sincronização
- ✅ Seguro: não remove dados, apenas insere/atualiza

## Configurações

### Servidor de Produção
- **IP**: 149.56.235.225
- **Usuário**: conceit1_renatorap
- **Porta**: 3306
- **Banco**: conceit1_combustivel

### Servidor Local
- **Host**: localhost
- **Usuário**: renatorap
- **Porta**: 3306
- **Banco**: conceit1_combustivel

> ⚠️ **Importante**: As credenciais estão em `config_sync.php`

## Como Usar

### Execução Rápida (Recomendado)

```bash
cd /var/www/html/admin_abastecimento/sync
./sync.sh
```

### Execução Manual

```bash
cd /var/www/html/admin_abastecimento/sync
php sincronizar_producao.php
```

### Agendar Execução Diária (Cron)

Adicione ao crontab:

```bash
# Editar crontab
crontab -e

# Adicionar linha (executa todo dia às 2h da manhã)
0 2 * * * cd /var/www/html/admin_abastecimento/sync && ./sync.sh >> logs/cron.log 2>&1
```

Ou para executar a cada 6 horas:
```bash
0 */6 * * * cd /var/www/html/admin_abastecimento/sync && ./sync.sh >> logs/cron.log 2>&1
```

## Tabelas Sincronizadas

O sistema sincroniza as seguintes tabelas (nesta ordem):

### 1. Base
- clientes
- empresa

### 2. Configurações
- grupos
- usuarios
- modulo, submenu, subsubmenu
- aplicacoes

### 3. Cadastros
- un_orcamentaria
- setor
- marca_veiculo
- modelo_veiculo
- cor_veiculo
- tp_veiculo
- cat_cnh
- cargo
- situacao
- forma_trabalho

### 4. Dados Principais
- veiculo
- condutor

### 5. Movimentações
- abastecimento

## Lógica de Sincronização

### Para cada tabela:

1. **Conecta aos dois servidores**
   - Produção (origem)
   - Localhost (destino)

2. **Compara estruturas**
   - Identifica campos comuns
   - Verifica se `id_cliente` existe no local

3. **Para cada registro da produção:**
   - Se `id_cliente` não existe na produção mas existe no local:
     - **Adiciona** `id_cliente = id_empresa`
   - Se o registro **não existe** no local:
     - **INSERE** novo registro
   - Se o registro **existe** no local:
     - Compara timestamps (se existir)
     - **ATUALIZA** se necessário

4. **Gera estatísticas:**
   - Novos registros inseridos
   - Registros atualizados
   - Erros encontrados
   - Tempo de execução

## Logs

Os logs são salvos automaticamente em:
```
/var/www/html/admin_abastecimento/sync/logs/
```

Formato do arquivo:
```
sync_2025-12-03_14-30-00.log
```

### Exemplo de Log

```
[2025-12-03 14:30:00] ✓ Conectado à PRODUÇÃO
[2025-12-03 14:30:01] ✓ Conectado ao LOCALHOST

======================================================================
Sincronizando tabela: clientes
======================================================================
Total de registros na produção: 5

📊 Resultado da sincronização:
   • Novos: 2
   • Atualizados: 3
   • Erros: 0
   • Tempo: 0.15s

======================================================================
RELATÓRIO FINAL DA SINCRONIZAÇÃO
======================================================================

📊 TOTAIS GERAIS:
   • Tabelas sincronizadas: 20
   • Registros novos: 150
   • Registros atualizados: 45
   • Erros: 0
   • Tempo total: 12.5s

✓ SINCRONIZAÇÃO CONCLUÍDA COM SUCESSO!
```

## Personalização

### Adicionar Nova Tabela

Edite `sincronizar_producao.php` e adicione na lista `$tabelasParaSincronizar`:

```php
'nova_tabela' => [
    'pk' => 'id_campo_chave',           // Chave primária
    'timestamp' => 'updated_at',        // Campo de timestamp (ou null)
    'campos_sync' => null,              // null = todos os campos
    'tem_id_cliente' => true            // Se precisa do campo id_cliente
]
```

### Alterar Configurações

Edite o arquivo `config_sync.php`:

```php
'opcoes' => [
    'sincronizar_apenas_novos' => false,  // true = não atualiza existentes
    'usar_transacoes' => true,            // Usar transações
    'timeout_conexao' => 30,              // Timeout em segundos
    'log_detalhado' => true               // Log verboso
]
```

## Troubleshooting

### Erro de Conexão

```
✗ Erro ao conectar à produção: Connection refused
```

**Solução**: Verificar se:
- O servidor de produção está acessível
- As credenciais estão corretas
- A porta 3306 está aberta no firewall

### Tabela não existe

```
⚠ Tabela xyz não existe na PRODUÇÃO - PULANDO
```

**Solução**: A tabela só existe em um dos bancos. Isso é normal e o sistema pula automaticamente.

### Erro de Foreign Key

```
✗ Erro ao executar INSERT: Cannot add or update a child row
```

**Solução**: A ordem das tabelas está incorreta. Certifique-se de que as tabelas pai são sincronizadas antes das tabelas filho.

### Muitos Erros

Se houver muitos erros, verifique:
1. Estrutura das tabelas é compatível?
2. Há campos NOT NULL sem valor?
3. Há restrições de UNIQUE sendo violadas?

## Segurança

### Boas Práticas

1. ✅ **Credenciais**: Mantenha `config_sync.php` seguro
2. ✅ **Backup**: Faça backup antes da primeira sincronização
3. ✅ **Teste**: Execute manualmente antes de agendar
4. ✅ **Logs**: Revise logs regularmente
5. ✅ **Permissões**: Apenas usuário autorizado pode executar

### Permissões Recomendadas

```bash
chmod 700 sync.sh
chmod 600 config_sync.php
chmod 755 logs/
```

## Manutenção

### Limpar Logs Antigos

```bash
# Remover logs com mais de 30 dias
find logs/ -name "sync_*.log" -mtime +30 -delete
```

### Verificar Última Sincronização

```bash
ls -lt logs/ | head -5
```

### Ver Log da Última Sincronização

```bash
tail -100 logs/$(ls -t logs/ | head -1)
```

## Performance

### Tempo Estimado

- Tabelas pequenas (< 1000 registros): ~1-2 segundos
- Tabelas médias (1000-10000 registros): ~5-10 segundos
- Tabelas grandes (> 10000 registros): ~20-60 segundos

**Total estimado**: 10-30 segundos para sincronização completa

### Otimizações

Para melhorar performance:
- Execute em horários de baixo tráfego
- Considere sincronizar apenas tabelas alteradas
- Use índices apropriados nas chaves primárias

## Suporte

Para problemas ou dúvidas:
1. Verificar logs em `sync/logs/`
2. Revisar este README
3. Contactar o administrador do sistema
