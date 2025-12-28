# Renomeação do Campo 'usuario' para 'login'

## Resumo da Alteração
O campo `usuario` na tabela `usuarios` foi renomeado para `login` em todo o sistema.

## Comando SQL Necessário
**IMPORTANTE:** Execute este comando no MySQL para renomear a coluna no banco de dados:

```sql
USE conceit1_combustivel;
ALTER TABLE usuarios CHANGE COLUMN usuario login VARCHAR(100) NOT NULL;
```

### Como executar:
```bash
mysql -u renatorap -p -e "USE conceit1_combustivel; ALTER TABLE usuarios CHANGE COLUMN usuario login VARCHAR(100) NOT NULL;"
```

## Arquivos Atualizados

### API Files (Backend PHP)
1. **api/login.php**
   - `$_POST['usuario']` → `$_POST['login']`
   - SQL: `WHERE u.usuario = ?` → `WHERE u.login = ?`
   - `SELECT u.usuario` → `SELECT u.login`
   - `$_SESSION['usuario']` → `$_SESSION['login']`
   - Mensagens de erro atualizadas

2. **api/usuarios.php**
   - SQL WHERE: `u.usuario LIKE` → `u.login LIKE`
   - SQL ORDER BY: `ORDER BY u.usuario` → `ORDER BY u.login`
   - SQL INSERT: `INSERT INTO usuarios (usuario, ...)` → `INSERT INTO usuarios (login, ...)`
   - SQL UPDATE: `usuario = '$usuario'` → `login = '$login'`
   - SQL SELECT: `SELECT nome, email` → `SELECT login, email`
   - Variável `$usuario` renomeada para `$login`
   - Audit logs atualizados

3. **api/password_reset_request.php**
   - `$_POST['usuario']` → `$_POST['login']`
   - SQL: `WHERE usuario = ?` → `WHERE login = ?`
   - SQL SELECT: `SELECT id, usuario, email` → `SELECT id, login, email`
   - Email message: `{$user['usuario']}` → `{$user['login']}`

### Frontend Files (Pages)
4. **includes/header.php**
   - `$_SESSION['usuario']` → `$_SESSION['login']`

5. **index.php (Login Form)**
   - Form field: `id="usuario" name="usuario"` → `id="login" name="login"`
   - Label: "Usuário" → "Login"
   - Placeholder: "Seu nome de usuário" → "Seu login"

6. **pages/usuarios.php**
   - Table header: "Nome" → "Login"
   - Form label: "Nome *" → "Login *"
   - JavaScript: `u.usuario` → `u.login` (3 ocorrências)
   - Table display, edit form, delete confirmation

### JavaScript Files
7. **js/auth.js**
   - jQuery selectors: `$('#usuario')` → `$('#login')` (7 ocorrências)
   - Variable: `const usuario` → `const login`
   - AJAX data: `usuario: usuario` → `login: login`
   - Error handling: `data.field === 'usuario'` → `data.field === 'login'`

8. **js/password_reset.js**
   - AJAX data: `{ usuario: val }` → `{ login: val }`

## Impacto da Alteração

### Banco de Dados
- Coluna `usuario` renomeada para `login` na tabela `usuarios`
- Tipo permanece: `VARCHAR(100) NOT NULL`
- Índices e constraints permanecem inalterados

### Autenticação
- Login agora usa campo `login` em vez de `usuario`
- Sessão armazena `$_SESSION['login']` em vez de `$_SESSION['usuario']`
- Headers exibem valor de `$_SESSION['login']`

### CRUD de Usuários
- Formulários agora usam "Login" como label
- API recebe parâmetro `nome` mas salva na coluna `login`
- Listagem e pesquisa funcionam com coluna `login`

### Reset de Senha
- Aceita `login` ou `email` como input
- Busca usuário por campo `login`

## Teste Após Alteração

1. Execute o comando SQL acima
2. Teste login com usuário existente
3. Verifique se o nome aparece corretamente no header
4. Teste criação de novo usuário
5. Teste edição de usuário existente
6. Teste reset de senha
7. Verifique auditoria

## Rollback (Se Necessário)

Se precisar reverter a alteração:

```sql
USE conceit1_combustivel;
ALTER TABLE usuarios CHANGE COLUMN login usuario VARCHAR(100) NOT NULL;
```

E reverta as alterações nos arquivos usando git:
```bash
cd /var/www/html/admin_abastecimento
git checkout HEAD -- api/ includes/ index.php pages/ js/
```

## Data da Alteração
2024 (conforme solicitação do usuário)

## Observações
- Esta alteração melhora a clareza do sistema
- "Login" é mais preciso que "Usuário" para identificação de acesso
- Campo "nome" não foi afetado (seria para nome completo do usuário, se existisse)
- Compatibilidade mantida com resto do sistema RBAC
