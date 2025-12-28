# Vínculo Usuário-Cliente

## Visão Geral

O sistema permite que usuários sejam vinculados a clientes de duas formas:

1. **Vínculo Direto**: Campo `cliente_id` na tabela `usuarios`
2. **Vínculo via Tabela Relacionamento**: Tabela `usuario_cliente` (muitos-para-muitos)

## Estrutura do Banco de Dados

### Tabela `usuarios`
```sql
- id: ID do usuário
- login: Login único
- nome: Nome completo
- cliente_id: ID do cliente (pode ser NULL)
- grupo_id: Grupo/perfil do usuário
```

### Tabela `usuario_cliente` (Relacionamento N:N)
```sql
- id: ID do relacionamento
- usuario_id: ID do usuário
- cliente_id: ID do cliente
- ativo: Se o vínculo está ativo
- data_vinculo: Data da criação do vínculo
```

### Tabela `clientes`
```sql
- id: ID do cliente
- nome_fantasia: Nome fantasia
- razao_social: Razão social
- email: E-mail principal
```

## Como Funciona no Login

Durante o login, o sistema segue esta lógica:

```php
// 1. Busca dados do usuário incluindo cliente_id direto
SELECT u.cliente_id FROM usuarios u WHERE u.login = ?

// 2. Se cliente_id estiver preenchido, usa diretamente
if (!empty($user['cliente_id'])) {
    $_SESSION['cliente_id'] = $user['cliente_id'];
}

// 3. Se não, busca na tabela usuario_cliente
else {
    SELECT cliente_id FROM usuario_cliente 
    WHERE usuario_id = ? AND ativo = 1 
    ORDER BY data_vinculo DESC LIMIT 1
}
```

### Prioridade
1. **Campo direto** (`usuarios.cliente_id`) tem prioridade
2. Se NULL, busca em **`usuario_cliente`** (vínculo mais recente)

## Grupos e Relacionamento com Cliente

### Grupos que PRECISAM de Cliente

**Operador Administrativo (grupo_id = 2)**
- Necessita vínculo com cliente
- Licença verificada por cliente

**Operador Prefeitura (grupo_id = 3)**
- Necessita vínculo com cliente
- Licença verificada por cliente

**Operador Posto (grupo_id = 4)**
- Necessita vínculo com cliente
- Licença verificada por cliente

**Abastecimento (grupo_id = 10)**
- Necessita vínculo com cliente
- Licença verificada por cliente

### Grupos ISENTOS de Cliente

**Administradores (grupo_id = 1)**
- Pode ou não ter cliente vinculado
- Isento de verificação de licença
- Acesso a todos os clientes

## Uso no Sistema

### Variável de Sessão

Após login bem-sucedido:
```php
$_SESSION['cliente_id'] = 123; // ID do cliente vinculado
```

### Nas APIs

Todas as APIs que precisam filtrar por cliente usam:
```php
$clienteId = $_SESSION['cliente_id'] ?? null;

// Exemplo: Buscar veículos do cliente
$sql = "SELECT * FROM veiculo WHERE id_cliente = $clienteId";
```

### No Dashboard

```php
$clienteId = $_SESSION['cliente_id'] ?? null;

// Estatísticas filtradas por cliente
$sql = "SELECT COUNT(*) as total FROM veiculo 
        WHERE id_cliente = $clienteId AND id_situacao = 1";
```

### Verificação de Licença

```php
$clienteId = $_SESSION['cliente_id'] ?? null;
$grupoId = $_SESSION['grupoId'] ?? null;

LicenseChecker::verificarEBloquear($clienteId, $grupoId);
```

## Como Vincular Usuário a Cliente

### Método 1: Vínculo Direto (Recomendado para usuários com 1 cliente fixo)

```sql
-- Atualizar usuário existente
UPDATE usuarios 
SET cliente_id = 1 
WHERE id = 2;
```

### Método 2: Tabela de Relacionamento (Recomendado para múltiplos clientes)

```sql
-- Criar novo vínculo
INSERT INTO usuario_cliente (usuario_id, cliente_id, ativo) 
VALUES (2, 1, 1);

-- Ativar/desativar vínculo
UPDATE usuario_cliente 
SET ativo = 0 
WHERE id = 1;
```

### Interface Administrativa (Futuro)

Em desenvolvimento: Interface para gerenciar vínculos via painel administrativo.

## Cenários Comuns

### Cenário 1: Operador de Prefeitura
```sql
-- Usuário: prefeitura (grupo_id = 3)
-- Cliente: Prefeitura de Pindorama (id = 1)

INSERT INTO usuario_cliente (usuario_id, cliente_id, ativo) 
VALUES (2, 1, 1);
```

### Cenário 2: Administrador Multi-Cliente
```sql
-- Usuário: admin (grupo_id = 1)
-- Múltiplos clientes

INSERT INTO usuario_cliente (usuario_id, cliente_id, ativo) 
VALUES 
(1, 1, 1),
(1, 2, 1),
(1, 3, 1);

-- Sistema usa o mais recente, mas admin tem acesso a todos
```

### Cenário 3: Operador Posto
```sql
-- Usuário: posto1 (grupo_id = 4)
-- Cliente: Posto ABC (id = 5)

UPDATE usuarios 
SET cliente_id = 5 
WHERE id = 10;
```

## Verificação de Vínculos

### Query para Listar Vínculos
```sql
SELECT 
    u.id,
    u.login,
    u.nome,
    g.nome as grupo,
    COALESCE(c1.nome_fantasia, c2.nome_fantasia) as cliente
FROM usuarios u
LEFT JOIN grupos g ON u.grupo_id = g.id
LEFT JOIN clientes c1 ON u.cliente_id = c1.id
LEFT JOIN usuario_cliente uc ON u.id = uc.usuario_id AND uc.ativo = 1
LEFT JOIN clientes c2 ON uc.cliente_id = c2.id
WHERE u.ativo = 1;
```

### Query para Verificar Cliente de um Usuário
```sql
-- Pelo login
SELECT 
    COALESCE(u.cliente_id, uc.cliente_id) as cliente_id,
    c.nome_fantasia
FROM usuarios u
LEFT JOIN usuario_cliente uc ON u.id = uc.usuario_id AND uc.ativo = 1
LEFT JOIN clientes c ON COALESCE(u.cliente_id, uc.cliente_id) = c.id
WHERE u.login = 'prefeitura';
```

## Problemas Comuns e Soluções

### ❌ Problema: Usuário sem cliente vinculado
**Sintoma**: Dashboard vazio, sem dados de veículos ou condutores

**Solução**:
```sql
-- Verificar se usuário tem cliente
SELECT u.id, u.login, u.cliente_id 
FROM usuarios u 
WHERE u.login = 'usuario_problema';

-- Se cliente_id = NULL, verificar usuario_cliente
SELECT * FROM usuario_cliente WHERE usuario_id = X;

-- Criar vínculo
INSERT INTO usuario_cliente (usuario_id, cliente_id, ativo) 
VALUES (X, Y, 1);
```

### ❌ Problema: Cliente_id não aparece na sessão
**Sintoma**: `$_SESSION['cliente_id']` retorna null

**Solução**:
1. Fazer logout completo
2. Limpar cache do navegador
3. Fazer login novamente
4. Verificar se vínculo está ativo no banco

### ❌ Problema: Licença não funciona corretamente
**Sintoma**: Administrador bloqueado ou operador tem acesso indevido

**Solução**:
```php
// Verificar variáveis de sessão
print_r($_SESSION);

// Deve conter:
// 'cliente_id' => int
// 'grupoId' => int
// 'userId' => int
```

## Segurança

- ✅ Vínculo verificado a cada login
- ✅ Queries filtradas por cliente automaticamente
- ✅ Administradores isentos de restrições
- ✅ Operadores só acessam dados do próprio cliente
- ✅ Impossível acessar dados de outro cliente

---

**Última Atualização**: 2025-12-02
