# Guia de Teste - Sistema de Licenciamento

## Pré-requisitos

1. Banco de dados atualizado com tabela `licenca`
2. Itens de menu criados e permissões configuradas
3. Cliente com e-mail cadastrado no sistema
4. Usuário administrador para testes

## Teste 1: Gerar Licença

### Como Administrador:

1. **Login como administrador**
   - Usuário: admin
   - Senha: (sua senha de admin)

2. **Acesse o menu**
   - Navegue: `Configuração > Licenças > Gerenciar Licenças`
   - URL direta: `http://localhost/admin_abastecimento/pages/licencas.php`

3. **Gerar nova licença**
   - Clique em "Gerar Nova Licença"
   - Selecione um cliente do dropdown
   - Defina data de expiração (ex: 30 dias à frente)
   - Adicione observação: "Teste de licença mensal"
   - Marque "Enviar por e-mail"
   - Clique em "Gerar Licença"

4. **Verificar resultado**
   - ✅ Código gerado (formato: LIC-202412-XXXXXXXX)
   - ✅ Alert de sucesso exibido
   - ✅ Licença aparece na lista com status "Pendente"
   - ✅ E-mail enviado (verificar inbox do cliente)

## Teste 2: Enviar E-mail Manualmente

1. **Na lista de licenças**
   - Localize a licença gerada
   - Clique no ícone de envelope (✉️)
   - Confirme o envio

2. **Verificar**
   - ✅ Mensagem de sucesso
   - ✅ E-mail recebido com código e instruções

## Teste 3: Ativar Licença

### Como Operador ou Administrador:

1. **Acesse a ativação**
   - Navegue: `Configuração > Licenças > Ativar Licença`
   - URL direta: `http://localhost/admin_abastecimento/pages/ativar_licenca.php`

2. **Inserir código**
   - Digite o código completo recebido por e-mail
   - Exemplo: `LIC-202412-A1B2C3D4`
   - Clique em "Ativar Licença"

3. **Verificar resultado**
   - ✅ Modal de sucesso exibido
   - ✅ Informações do cliente e validade mostradas
   - ✅ Status da licença atualizado para "Ativa"
   - ✅ Seção "Status da Licença Atual" mostra licença ativa

## Teste 4: Visualizar Detalhes

1. **Na lista de licenças**
   - Clique no ícone de olho (👁️) em uma licença
   - Verificar modal com todas as informações:
     - Código
     - Cliente
     - Status
     - Datas (geração, ativação, expiração)
     - Gerado por / Ativado por
     - Observações

## Teste 5: Adiar Expiração

1. **Na lista de licenças (licença ativa ou pendente)**
   - Clique no ícone de calendário (📅)
   - Selecione uma nova data (ex: + 15 dias)
   - Clique em "Adiar"

2. **Verificar**
   - ✅ Mensagem de sucesso
   - ✅ Data de expiração atualizada na lista
   - ✅ Cliente pode continuar usando até a nova data

## Teste 6: Filtros de Status

1. **No dropdown de status**
   - Selecione "Pendente" - ver apenas pendentes
   - Selecione "Ativa" - ver apenas ativas
   - Selecione "Expirada" - ver apenas expiradas
   - Selecione "Todos os Status" - ver todas

2. **Verificar**
   - ✅ Lista atualiza conforme filtro selecionado

## Teste 7: Cancelar Licença

1. **Na lista de licenças (licença ativa ou pendente)**
   - Clique no ícone X vermelho (❌)
   - Confirme o cancelamento
   - **ATENÇÃO:** Ação irreversível!

2. **Verificar**
   - ✅ Status alterado para "Cancelada"
   - ✅ Cliente bloqueado (se era a única licença ativa)

## Teste 8: Verificação de Licença no Sistema

### Cenário A: Licença Ativa

1. **Com licença ativa válida**
   - Acesse qualquer página do sistema
   - Ex: Dashboard

2. **Verificar**
   - ✅ Acesso normal permitido
   - ✅ Se faltar ≤ 7 dias: aviso amarelo/vermelho no topo
   - ✅ Mensagem: "Sua licença expira em X dias"

### Cenário B: Licença Pendente

1. **Com licença pendente (não ativada)**
   - Faça logout
   - Login como cliente com licença pendente
   - Tente acessar dashboard

2. **Verificar**
   - ✅ Redirecionado para `ativar_licenca.php`
   - ✅ URL contém `?motivo=pendente`
   - ✅ Mensagem informativa exibida

### Cenário C: Licença Expirada

1. **Simular expiração (via banco)**
   ```sql
   UPDATE licenca 
   SET data_expiracao = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
   WHERE id_licenca = X;
   ```

2. **Teste com Administrador (grupo_id = 1)**
   - Login como administrador
   - Tentar acessar qualquer página
   - **Verificar:**
     - ✅ Acesso total permitido
     - ✅ Nenhum bloqueio ou redirecionamento
     - ✅ Pode gerenciar licenças normalmente

3. **Teste com Operador Administrativo (grupo_id = 2)**
   - Login como operador administrativo
   - Tentar acessar dashboard ou outra página
   - **Verificar:**
     - ✅ Redirecionado para `ativar_licenca.php?motivo=expirada`
     - ✅ Alerta vermelho: "Licença Expirada!"
     - ✅ Pode inserir código e ativar nova licença
     - ❌ Bloqueado para outras páginas enquanto não ativar

4. **Teste com Operador Prefeitura (grupo_id = 3)**
   - Login como operador prefeitura
   - Tentar acessar dashboard ou outra página
   - **Verificar:**
     - ✅ Redirecionado para `ativar_licenca.php?motivo=expirada`
     - ✅ Alerta vermelho: "Licença Expirada!"
     - ✅ Pode inserir código e ativar nova licença
     - ❌ Bloqueado para outras páginas enquanto não ativar

5. **Teste com Operador Posto (grupo_id = 4)**
   - Login como operador posto
   - Tentar acessar dashboard ou outra página
   - **Verificar:**
     - ✅ Redirecionado para `index.php?erro=licenca_expirada`
     - ✅ Alerta vermelho: "Acesso Negado!"
     - ❌ Bloqueio total - não pode ativar licença
     - ❌ Não tem acesso a nenhuma funcionalidade

6. **Teste com Abastecimento (grupo_id = 10)**
   - Login como usuário de abastecimento
   - Tentar acessar dashboard ou outra página
   - **Verificar:**
     - ✅ Redirecionado para `index.php?erro=licenca_expirada`
     - ✅ Alerta vermelho: "Acesso Negado!"
     - ❌ Bloqueio total - não pode ativar licença
     - ❌ Não tem acesso a nenhuma funcionalidade

7. **Teste de Tentativa de Burlar Bloqueio**
   - Como operador posto (grupo_id = 4), tente acessar URLs diretas:
     - `http://localhost/.../pages/ativar_licenca.php`
     - `http://localhost/.../pages/dashboard.php`
     - `http://localhost/.../pages/veiculo.php`
   - **Verificar:**
     - ✅ Sempre redirecionado para `index.php?erro=licenca_expirada`
     - ✅ Bloqueio consistente em todas as tentativas
     - ✅ Impossível acessar qualquer funcionalidade

8. **Verificar Atualização Automática de Status**
   - **Verificar no banco:**
     - ✅ Status automaticamente alterado de "ativa" para "expirada"
     - ✅ Atualização ocorre na primeira verificação após expiração

### Cenário D: Administrador (Isento)

1. **Login como administrador**
   - Mesmo com todas as licenças expiradas
   - Acesse qualquer página do sistema

2. **Verificar**
   - ✅ Acesso total independente de licença
   - ✅ Nenhum bloqueio ou redirecionamento
   - ✅ Pode gerenciar e gerar novas licenças
   - ✅ Nenhuma mensagem de erro

## Teste 9: Atualização Automática de Status

1. **Criar licença com data já expirada**
   ```sql
   -- Via SQL para teste
   INSERT INTO licenca (id_cliente, codigo_licenca, data_geracao, data_expiracao, status, gerado_por)
   VALUES (1, 'LIC-TEST-EXPIRED', NOW(), DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'ativa', 1);
   ```

2. **Acessar sistema**
   - Login como qualquer usuário
   - Navegar para dashboard

3. **Verificar**
   - ✅ Status automaticamente alterado para "Expirada"
   - ✅ Verificar na lista de licenças

## Teste 10: Múltiplas Licenças

### Cenário: Cliente com licença ativa tenta gerar outra

1. **Com cliente já tendo licença ativa**
   - Tentar gerar nova licença para o mesmo cliente

2. **Verificar**
   - ✅ Erro exibido
   - ✅ Mensagem: "Cliente já possui licença ativa ou pendente"
   - ✅ Não permite duplicação

## Teste 11: E-mail sem Configuração SMTP

1. **Se SMTP não configurado**
   - Tentar enviar e-mail
   - Verificar logs do Apache

2. **Esperado**
   - ❌ Erro ao enviar
   - ℹ️ Mensagem clara do problema
   - ℹ️ Verificar `error.log` do Apache

## Teste 12: Permissões de Perfis

### Operador Administrativo (perfil 2):

1. **Login como operador administrativo**
2. **Verificar menu**
   - ✅ Vê "Ativar Licença"
   - ❌ NÃO vê "Gerenciar Licenças"

3. **Tentar acessar diretamente**
   - URL: `pages/licencas.php`
   - ✅ Redirecionado para dashboard (sem permissão)

### Operador Prefeitura (perfil 3):

1. **Login como operador prefeitura**
2. **Verificar menu**
   - ✅ Vê "Ativar Licença"
   - ❌ NÃO vê "Gerenciar Licenças"

3. **Pode ativar licenças**
   - ✅ Acesso à página de ativação
   - ✅ Pode inserir e ativar códigos

## Checklist Final

### Funcionalidades ✅

- [ ] Gerar licença
- [ ] Enviar por e-mail
- [ ] Ativar licença
- [ ] Adiar expiração
- [ ] Cancelar licença
- [ ] Ver detalhes
- [ ] Filtrar por status
- [ ] Bloqueio automático
- [ ] Aviso de expiração
- [ ] Atualização automática de status

### Permissões ✅

- [ ] Administrador: acesso total
- [ ] Operador Admin: apenas ativar
- [ ] Operador Prefeitura: apenas ativar
- [ ] Outros perfis: bloqueados

### Interface ✅

- [ ] Layout responsivo
- [ ] Modais funcionando
- [ ] Botões com ícones corretos
- [ ] Badges de status coloridos
- [ ] Formulários validados
- [ ] Mensagens de erro/sucesso claras

### API ✅

- [ ] Todas as actions funcionando
- [ ] Validações de dados
- [ ] Retornos JSON corretos
- [ ] Tratamento de erros

### Banco de Dados ✅

- [ ] Tabela criada corretamente
- [ ] Índices funcionando
- [ ] Foreign keys respeitadas
- [ ] Status ENUM correto

## Problemas Comuns e Soluções

### 1. Menu não aparece

**Solução:**
```sql
-- Verificar se aplicacao_id está correto
SELECT * FROM subsubmenu WHERE codigo IN ('licencas', 'ativar_licenca');

-- Verificar permissões
SELECT * FROM permissoes_grupo WHERE aplicacao_id IN (39, 40);
```

### 2. E-mail não envia

**Solução:**
- Verificar `config/config.php` - constantes SMTP
- Testar com `tests/validate_mail_config.php`
- Verificar logs do Apache

### 3. Licença não bloqueia

**Solução:**
- Verificar se página tem `require_once '../config/license_checker.php'`
- Verificar se chama `LicenseChecker::verificarEBloquear()`
- Verificar se usuário não é administrador

### 4. Status não atualiza automaticamente

**Solução:**
- Sistema atualiza em cada verificação
- Executar manualmente se necessário:
```sql
UPDATE licenca 
SET status = 'expirada'
WHERE status = 'ativa'
AND data_expiracao < CURDATE();
```

## Logs para Monitoramento

### Apache Error Log
```bash
sudo tail -f /var/log/apache2/error.log | grep -i licen
```

### Verificar licenças no banco
```sql
-- Ver todas as licenças
SELECT 
    l.id_licenca,
    c.nome_fantasia,
    l.codigo_licenca,
    l.status,
    l.data_expiracao,
    DATEDIFF(l.data_expiracao, CURDATE()) as dias_restantes
FROM licenca l
LEFT JOIN cliente c ON l.id_cliente = c.id_cliente
ORDER BY l.data_geracao DESC;

-- Ver licenças que expiram nos próximos 7 dias
SELECT 
    c.nome_fantasia,
    l.codigo_licenca,
    l.data_expiracao,
    DATEDIFF(l.data_expiracao, CURDATE()) as dias_restantes
FROM licenca l
LEFT JOIN cliente c ON l.id_cliente = c.id_cliente
WHERE l.status = 'ativa'
AND l.data_expiracao BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
ORDER BY l.data_expiracao ASC;
```

---

**Conclusão:** Se todos os testes passarem, o sistema de licenciamento está funcionando corretamente! 🎉
