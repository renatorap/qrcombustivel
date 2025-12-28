# Teste: Header com Logo e Cliente Corretos

## Objetivo
Verificar se o header exibe corretamente o logo e nome do cliente baseado no tipo de usuário.

## Comportamento Esperado

### Para Usuários NÃO Administradores (grupo_id ≠ 1)
1. **No Login**:
   - Sistema busca `cliente_id` do usuário
   - Busca dados completos do cliente (nome e logo)
   - Armazena em sessão:
     ```php
     $_SESSION['cliente_id'] = 1
     $_SESSION['cliente_nome'] = 'Prefeitura de Pindorama'
     $_SESSION['cliente_logo'] = 'storage/cliente/logo/logo_xxx.png'
     ```

2. **No Header**:
   - Exibe logo do cliente vinculado
   - Exibe nome do cliente vinculado
   - **NÃO exibe** seletor de clientes
   - Usuário vê apenas seu próprio cliente

### Para Administradores (grupo_id = 1)
1. **No Login**:
   - Sistema busca primeiro cliente disponível (ou mantém vazio)
   - Armazena dados na sessão

2. **No Header**:
   - Exibe logo do cliente selecionado
   - Exibe nome do cliente selecionado
   - **EXIBE** seletor dropdown de clientes
   - Administrador pode trocar entre clientes
   - Ao trocar, logo e nome são atualizados dinamicamente

## Testes Manuais

### Teste 1: Login como Operador Prefeitura

```bash
# 1. Acessar sistema
http://localhost/admin_abastecimento/

# 2. Fazer login
Usuário: prefeitura
Senha: [senha do usuário]

# 3. Verificar no header:
✅ Logo da "Prefeitura de Pindorama" aparece
✅ Nome "Prefeitura de Pindorama" aparece
✅ NÃO aparece seletor de clientes
✅ Dashboard mostra dados apenas deste cliente
```

### Teste 2: Login como Administrador

```bash
# 1. Acessar sistema
http://localhost/admin_abastecimento/

# 2. Fazer login
Usuário: administrador
Senha: [senha do administrador]

# 3. Verificar no header:
✅ Logo do cliente padrão/selecionado aparece
✅ Nome do cliente padrão/selecionado aparece
✅ APARECE seletor dropdown de clientes
✅ Ao trocar cliente, logo e nome mudam
✅ Dashboard atualiza com dados do novo cliente
```

### Teste 3: Verificar Variáveis de Sessão

No console do navegador (F12):
```javascript
// Em qualquer página após login, abrir Console e digitar:
fetch('../api/test_session.php')
  .then(r => r.json())
  .then(d => console.log(d));

// Deve retornar:
{
  cliente_id: 1,
  cliente_nome: "Prefeitura de Pindorama",
  cliente_logo: "storage/cliente/logo/logo_xxx.png",
  grupoId: 3, // ou 1 para admin
  grupoNome: "Operador Prefeitura"
}
```

## Verificações SQL

### Verificar vínculos de usuários:
```sql
SELECT 
    u.id,
    u.login,
    u.nome,
    g.nome as grupo,
    COALESCE(u.cliente_id, uc.cliente_id) as cliente_id,
    c.nome_fantasia,
    c.logo_path
FROM usuarios u
LEFT JOIN grupos g ON u.grupo_id = g.id
LEFT JOIN usuario_cliente uc ON u.id = uc.usuario_id AND uc.ativo = 1
LEFT JOIN clientes c ON COALESCE(u.cliente_id, uc.cliente_id) = c.id
WHERE u.ativo = 1;
```

### Resultado esperado:
```
id | login         | nome                   | grupo               | cliente_id | nome_fantasia           | logo_path
1  | administrador | Administrador da Silva | Administradores     | 1          | Prefeitura de Pindorama | storage/...
2  | prefeitura    | Operador da Prefeitura | Operador Prefeitura | 1          | Prefeitura de Pindorama | storage/...
```

## Problemas Comuns e Soluções

### ❌ Logo não aparece
**Causa**: Caminho do logo incorreto ou arquivo não existe

**Solução**:
```sql
-- Verificar se arquivo existe
SELECT id, nome_fantasia, logo_path 
FROM clientes 
WHERE id = 1;

-- Se caminho estiver errado, corrigir:
UPDATE clientes 
SET logo_path = 'storage/cliente/logo/nome_correto.png' 
WHERE id = 1;
```

### ❌ Nome do cliente não aparece
**Causa**: Sessão não foi preenchida no login

**Solução**:
1. Fazer logout completo
2. Limpar cookies do navegador
3. Fazer login novamente
4. Verificar se `$_SESSION['cliente_nome']` está preenchida

### ❌ Seletor aparece para não-administrador
**Causa**: Verificação de `grupoId` não está funcionando

**Solução**:
```php
// Verificar no header.php se há:
<?php if (isset($_SESSION['grupoId']) && $_SESSION['grupoId'] == 1): ?>
    <!-- seletor -->
<?php endif; ?>
```

### ❌ Administrador não vê seletor
**Causa**: `grupoId` não está na sessão

**Solução**:
```sql
-- Verificar grupo do usuário
SELECT id, login, grupo_id FROM usuarios WHERE login = 'administrador';

-- Deve retornar grupo_id = 1
```

## Checklist de Implementação

- [x] Login.php busca cliente_id do usuário
- [x] Login.php busca dados completos do cliente (nome + logo)
- [x] Login.php armazena na sessão: cliente_id, cliente_nome, cliente_logo
- [x] Header.php remove lógica de inicialização de cliente
- [x] Header.php usa variáveis de sessão para logo e nome
- [x] Header.php exibe seletor apenas se grupoId == 1
- [x] JavaScript só carrega seletor se elemento existir
- [x] Testes de sintaxe passaram
- [ ] Teste manual com operador prefeitura
- [ ] Teste manual com administrador
- [ ] Verificação de variáveis de sessão

## Arquivos Modificados

1. `/api/login.php`
   - Busca dados completos do cliente
   - Armazena cliente_nome e cliente_logo na sessão

2. `/includes/header.php`
   - Remove inicialização de cliente
   - Adiciona verificação de grupoId para exibir seletor
   - JavaScript atualizado para lidar com ausência do seletor

---

**Data**: 2025-12-02
**Status**: ✅ Implementado
