# Sistema de Licenciamento Mensal

## Visão Geral

O sistema de licenciamento mensal controla o acesso dos clientes ao sistema QR Combustível através de licenças com validade mensal que devem ser renovadas periodicamente.

## Características

- ✅ Licenças mensais por cliente
- ✅ Geração automática de códigos únicos
- ✅ Envio por e-mail
- ✅ Ativação controlada por perfil de usuário
- ✅ Bloqueio automático quando expirada
- ✅ Avisos de expiração próxima
- ✅ Possibilidade de adiar validade

## Estrutura do Banco de Dados

### Tabela `licenca`

```sql
- id_licenca: ID único da licença
- id_cliente: Cliente associado
- codigo_licenca: Código único (formato: LIC-YYYYMM-XXXXXXXX)
- data_geracao: Data/hora de geração
- data_ativacao: Data/hora de ativação (NULL se pendente)
- data_expiracao: Data limite de validade
- status: pendente|ativa|expirada|cancelada
- gerado_por: ID do usuário que gerou
- ativado_por: ID do usuário que ativou (NULL se pendente)
- observacao: Observações adicionais
```

## Perfis e Permissões

### Administrador (grupo_id = 1)
- ✅ Gerar licenças
- ✅ Visualizar todas as licenças
- ✅ Enviar por e-mail
- ✅ Adiar data de expiração
- ✅ Cancelar licenças
- ✅ Ativar licenças
- ✅ **Isento de verificação de licença** (acesso total mesmo com licença expirada)

### Operador Administrativo (grupo_id = 2)
- ✅ Ativar licenças
- ❌ Gerar/gerenciar licenças
- ⚠️ **Com licença expirada**: Acesso APENAS à página de ativação de licença
- ❌ **Com licença expirada**: Bloqueado para outras funcionalidades do sistema

### Operador Prefeitura (grupo_id = 3)
- ✅ Ativar licenças
- ❌ Gerar/gerenciar licenças
- ⚠️ **Com licença expirada**: Acesso APENAS à página de ativação de licença
- ❌ **Com licença expirada**: Bloqueado para outras funcionalidades do sistema

### Operador Posto (grupo_id = 4)
- ❌ Ativar/gerenciar licenças
- ❌ **Com licença expirada**: Bloqueio total do sistema

### Abastecimento (grupo_id = 10)
- ❌ Ativar/gerenciar licenças
- ❌ **Com licença expirada**: Bloqueio total do sistema

## Fluxo de Trabalho

### 1. Geração de Licença (Administrador)

1. Acesse: **Configuração > Licenças > Gerenciar Licenças**
2. Clique em "Gerar Nova Licença"
3. Selecione o cliente
4. Defina a data de expiração
5. Adicione observações (opcional)
6. Marque "Enviar por e-mail" se desejar envio automático
7. Clique em "Gerar Licença"

**Resultado:** Código único gerado (ex: `LIC-202412-A1B2C3D4`)

### 2. Envio por E-mail

- **Automático:** Marcar opção ao gerar
- **Manual:** Clicar no botão de envelope na lista de licenças

**E-mail contém:**
- Código da licença
- Data de validade
- Instruções de ativação

### 3. Ativação de Licença

1. Acesse: **Configuração > Licenças > Ativar Licença**
2. Digite o código completo recebido por e-mail
3. Clique em "Ativar Licença"

**Resultado:** Sistema liberado até a data de expiração

### 4. Renovação

Quando a licença estiver próxima da expiração:
- Sistema exibe aviso automático (7 dias antes)
- Administrador gera nova licença
- Cliente ativa a nova licença antes da expiração

## Gerenciamento de Licenças (Administrador)

### Visualizar Licenças

**Filtros disponíveis:**
- Todos os status
- Pendente
- Ativa
- Expirada
- Cancelada

### Ações Disponíveis

#### Enviar por E-mail
- Ícone: ✉️
- Envia código para e-mail cadastrado do cliente

#### Adiar Expiração
- Ícone: 📅
- Permite estender a validade da licença
- Útil para prorrogações especiais

#### Ver Detalhes
- Ícone: 👁️
- Exibe informações completas da licença

#### Cancelar Licença
- Ícone: ❌
- Cancela uma licença ativa ou pendente
- Ação irreversível

## Verificação Automática

O sistema verifica automaticamente a licença em todas as páginas:

```php
// Exemplo de implementação
require_once '../config/license_checker.php';

$statusLicenca = LicenseChecker::verificarEBloquear(
    $_SESSION['cliente_id'], 
    $_SESSION['grupoId']
);
```

### Comportamento

**Licença Ativa:**
- ✅ Acesso normal ao sistema para todos os grupos
- ⚠️ Aviso quando faltar 7 dias ou menos

**Licença Pendente:**
- 🔒 Redireciona para página de ativação
- Mensagem: "Licença pendente de ativação"

**Licença Expirada/Inexistente:**

*Administradores (grupo_id = 1):*
- ✅ Isentos de verificação de licença
- ✅ Acesso total ao sistema sempre
- Podem gerenciar e renovar licenças

*Operadores Administrativos e de Prefeitura (grupo_id = 2, 3):*
- 🔒 Redirecionados para página de ativação de licença
- ✅ Podem acessar APENAS a tela de ativação
- ❌ Bloqueados para demais funcionalidades
- Mensagem: "Licença expirada! Ative uma nova licença para continuar"

*Operadores de Posto e Abastecimento (grupo_id = 4, 10):*
- 🔒 Bloqueio total do sistema
- ❌ Redirecionados para tela de login
- ❌ Sem acesso a nenhuma funcionalidade
- Mensagem: "Entre em contato com o administrador"

## API Endpoints

### `/api/licenca.php`

**Ações disponíveis:**

```php
// Listar licenças
GET ?action=list&status=ativa

// Gerar licença
POST action=generate
    id_cliente, data_expiracao, observacao

// Ativar licença
POST action=activate
    codigo_licenca

// Adiar expiração
POST action=extend
    id_licenca, nova_data_expiracao

// Enviar e-mail
POST action=send_email
    id_licenca

// Cancelar licença
POST action=cancel
    id_licenca

// Verificar status (cliente logado)
GET ?action=check_status

// Listar clientes
GET ?action=get_clientes
```

## Páginas do Sistema

### `/pages/licencas.php`
- **Acesso:** Apenas administradores
- **Função:** Gerenciar todas as licenças

### `/pages/ativar_licenca.php`
- **Acesso:** Administradores e operadores
- **Função:** Ativar códigos de licença

## Formato do Código de Licença

```
LIC-YYYYMM-XXXXXXXX

LIC     = Prefixo fixo
YYYY    = Ano (4 dígitos)
MM      = Mês (2 dígitos)
XXXXXXXX = Hash único (8 caracteres)

Exemplo: LIC-202412-A1B2C3D4
```

## Status de Licença

| Status | Descrição | Pode Ativar | Pode Usar Sistema |
|--------|-----------|-------------|-------------------|
| **pendente** | Gerada, aguardando ativação | ✅ Sim | ❌ Não |
| **ativa** | Ativada e dentro da validade | ❌ Não | ✅ Sim |
| **expirada** | Data de validade ultrapassada | ❌ Não | ❌ Não |
| **cancelada** | Cancelada pelo administrador | ❌ Não | ❌ Não |

## Atualização Automática de Status

O sistema atualiza automaticamente licenças expiradas:

```php
UPDATE licenca 
SET status = 'expirada'
WHERE status = 'ativa'
AND data_expiracao < CURDATE()
```

Executado a cada verificação de licença.

## Avisos de Expiração

**7 dias antes:**
- ⚠️ Aviso amarelo
- Mensagem: "Sua licença expira em X dias"

**3 dias antes:**
- 🔴 Aviso vermelho
- Mensagem: "Atenção! Sua licença expira em X dias"

**No dia:**
- 🔴 Aviso crítico
- Mensagem: "Sua licença expira HOJE!"

## Troubleshooting

### Problema: Cliente não recebe e-mail

**Verificar:**
1. E-mail cadastrado no cliente
2. Configurações SMTP em `config.php`
3. Log de erros do Apache

### Problema: Código não funciona

**Verificar:**
1. Código digitado corretamente (case-sensitive)
2. Status da licença (deve estar "pendente")
3. Data de expiração (não pode estar expirada)

### Problema: Licença expira mas sistema continua funcionando

**Verificar:**
1. Se o usuário é administrador (isento)
2. Se a página tem `require_once '../config/license_checker.php'`
3. Se chama `LicenseChecker::verificarEBloquear()`

## Boas Práticas

1. **Gerar licenças com antecedência**
   - Recomendado: 5-7 dias antes da expiração

2. **Sempre enviar por e-mail**
   - Facilita para o cliente

3. **Adicionar observações**
   - Útil para controle interno
   - Ex: "Renovação mensal", "Período de teste"

4. **Monitorar licenças próximas da expiração**
   - Filtrar por "Ativa" regularmente
   - Observar datas de expiração

5. **Não cancelar sem necessidade**
   - Prefira deixar expirar naturalmente
   - Cancele apenas em casos especiais

## Exemplos de Cenários

### Cenário 1: Licença Expira - Operador Administrativo
1. Operador faz login no sistema
2. Sistema detecta licença expirada
3. Operador é redirecionado para `/pages/ativar_licenca.php?motivo=expirada`
4. Tela exibe: "⚠️ Licença Expirada! Ative uma nova licença..."
5. Operador insere código recebido por e-mail
6. Licença ativada → Acesso liberado

### Cenário 2: Licença Expira - Operador de Posto
1. Operador faz login no sistema
2. Sistema detecta licença expirada
3. Operador é redirecionado para `/index.php?erro=licenca_expirada`
4. Tela exibe: "❌ Acesso Negado! Entre em contato com o administrador"
5. Operador NÃO consegue acessar nenhuma página do sistema
6. Apenas administrador ou operadores administrativos podem renovar

### Cenário 3: Administrador Sempre Tem Acesso
1. Administrador faz login
2. Sistema detecta licença expirada
3. ✅ Administrador tem acesso total normalmente
4. Pode acessar página de gerenciamento de licenças
5. Gera nova licença para o cliente
6. Envia por e-mail para operadores ativarem

### Cenário 4: Tentativa de Acesso Direto (Licença Expirada)
1. Operador de posto tenta acessar `dashboard.php` diretamente
2. LicenseChecker intercepta a requisição
3. Verifica: grupo_id = 4 (bloqueio total)
4. Redireciona imediatamente para login com erro
5. Operador não consegue burlar a verificação

## Segurança

- ✅ Códigos únicos e não sequenciais
- ✅ Verificação em cada página protegida
- ✅ Controle granular por grupo de usuário
- ✅ Log de quem gerou e ativou
- ✅ Administradores sempre têm acesso
- ✅ Tokens de sessão validados
- ✅ Bloqueio diferenciado por perfil
- ✅ Impossível burlar verificação por URL direta

## Integrações Futuras

Possíveis melhorias:

- [ ] Notificações push de expiração
- [ ] Geração automática mensal
- [ ] Relatório de histórico de licenças
- [ ] Painel de renovações pendentes
- [ ] Gateway de pagamento integrado
- [ ] API para ativação via QR Code

---

**Desenvolvido para:** Sistema QR Combustível  
**Versão:** 1.0  
**Data:** Dezembro 2025
