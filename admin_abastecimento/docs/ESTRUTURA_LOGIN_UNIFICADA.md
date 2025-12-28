# Estrutura de Login Unificada

## Visão Geral

O sistema agora utiliza uma página de login centralizada no diretório raiz (`/login.php`) que redireciona os usuários para o projeto apropriado baseado no grupo ao qual pertencem.

## Estrutura de Diretórios

```
/var/www/html/
├── index.php                           # Redireciona para /login.php
├── login.php                           # Página de login unificada
├── admin_abastecimento/
│   ├── index.php                       # Redireciona para /login.php
│   ├── api/
│   │   ├── login.php                   # API de autenticação (com redirecionamento por grupo)
│   │   └── logout.php                  # Redireciona para /login.php
│   ├── pages/
│   │   └── dashboard.php               # Dashboard do admin
│   └── ...
└── postoapp/
    ├── index.php                       # Login do posto
    ├── logout.php                      # Redireciona para /login.php
    └── ...
```

## Fluxo de Autenticação

### 1. Acesso Inicial
- Usuário acessa `http://localhost/` ou `http://localhost/admin_abastecimento/`
- Ambos redirecionam para `http://localhost/login.php`

### 2. Login
- Usuário preenche credenciais em `/login.php`
- JavaScript envia requisição para `/admin_abastecimento/api/login.php`
- API valida credenciais e verifica o grupo do usuário

### 3. Redirecionamento por Grupo

**Grupos 1, 2, 3 e 4:**
- Redirecionados para `/admin_abastecimento/pages/dashboard.php`
- Acesso ao sistema administrativo de abastecimento
- `$_SESSION['projeto'] = 'admin'`

**Grupo 10:**
- Redirecionados para `/postoapp/pages/dashboard.php`
- Acesso ao aplicativo do posto
- `$_SESSION['projeto'] = 'posto'`

**Outros Grupos:**
- Retornam erro: "Seu grupo não tem permissão de acesso ao sistema"

### 4. Logout
- Qualquer projeto ao fazer logout redireciona para `/login.php`
- Session é destruída completamente
- Cache do navegador é limpo

## Arquivos Modificados

### 1. `/var/www/html/login.php` (NOVO)
Página de login unificada com:
- Interface visual moderna
- Validação de credenciais via AJAX
- Redirecionamento automático baseado na resposta da API
- Recuperação de senha

### 2. `/var/www/html/index.php` (NOVO)
```php
<?php
header('Location: /login.php');
exit;
?>
```

### 3. `/var/www/html/admin_abastecimento/index.php` (MODIFICADO)
```php
<?php
header('Location: /login.php');
exit;
?>
```

### 4. `/var/www/html/admin_abastecimento/api/login.php` (MODIFICADO)
Adicionado lógica de redirecionamento:
```php
// Determinar redirecionamento baseado no grupo
$grupoId = (int)$user['grupo_id'];
$redirectPath = '';

if (in_array($grupoId, [1, 2, 3, 4])) {
    $redirectPath = '/admin_abastecimento/pages/dashboard.php';
    $_SESSION['projeto'] = 'admin';
} elseif ($grupoId === 10) {
    $redirectPath = '/postoapp/pages/dashboard.php';
    $_SESSION['projeto'] = 'posto';
} else {
    // Grupo não autorizado
    $response['message'] = 'Seu grupo não tem permissão de acesso ao sistema';
    $response['code'] = 'unauthorized_group';
    echo json_encode($response);
    exit;
}

$response['redirect'] = $redirectPath;
```

### 5. `/var/www/html/admin_abastecimento/api/logout.php` (MODIFICADO)
```php
// Redirecionar para login na raiz
header('Location: /login.php');
exit;
```

### 6. `/var/www/html/postoapp/logout.php` (MODIFICADO)
```php
// Redireciona para a página de login na raiz
header("Location: /login.php");
exit;
```

## Segurança Mantida

### Validações Preservadas
✅ Verificação de token de sessão
✅ Sanitização de inputs
✅ Validação de licença
✅ Controle de acesso por grupo
✅ Verificação de usuário ativo
✅ Limpeza de cache ao fazer logout
✅ Destruição completa de sessão

### Novas Validações
✅ Verificação de grupo autorizado (1-4 ou 10)
✅ Redirecionamento automático por grupo
✅ Identificação do projeto na sessão (`$_SESSION['projeto']`)

## Grupos de Usuários

| Grupo | ID | Redirecionamento | Descrição |
|-------|----|--------------------|-----------|
| Grupo 1 | 1 | admin_abastecimento | Administradores |
| Grupo 2 | 2 | admin_abastecimento | Gerentes |
| Grupo 3 | 3 | admin_abastecimento | Usuários |
| Grupo 4 | 4 | admin_abastecimento | Operadores |
| Grupo 10 | 10 | postoapp | Posto de Combustível |
| Outros | * | Acesso Negado | Não autorizado |

## Variáveis de Sessão

Após login bem-sucedido:
```php
$_SESSION['token']         // Token de segurança
$_SESSION['userId']        // ID do usuário
$_SESSION['grupoId']       // ID do grupo
$_SESSION['grupoNome']     // Nome do grupo
$_SESSION['userRole']      // Perfil/Role
$_SESSION['login']         // Login do usuário
$_SESSION['nome']          // Nome do usuário
$_SESSION['cliente_id']    // ID do cliente
$_SESSION['cliente_nome']  // Nome do cliente
$_SESSION['cliente_logo']  // Logo do cliente
$_SESSION['projeto']       // 'admin' ou 'posto'
```

## Testes Recomendados

### Teste 1: Login Grupo Admin (1-4)
1. Acessar `http://localhost/`
2. Fazer login com usuário do grupo 1, 2, 3 ou 4
3. Verificar redirecionamento para `/admin_abastecimento/pages/dashboard.php`
4. Fazer logout
5. Verificar redirecionamento para `/login.php`

### Teste 2: Login Grupo Posto (10)
1. Acessar `http://localhost/`
2. Fazer login com usuário do grupo 10
3. Verificar redirecionamento para `/postoapp/pages/dashboard.php`
4. Fazer logout
5. Verificar redirecionamento para `/login.php`

### Teste 3: Grupo Não Autorizado
1. Acessar `http://localhost/login.php`
2. Tentar fazer login com usuário de grupo diferente de 1-4 e 10
3. Verificar mensagem: "Seu grupo não tem permissão de acesso ao sistema"

### Teste 4: Acesso Direto
1. Acessar `http://localhost/admin_abastecimento/`
2. Verificar redirecionamento para `/login.php`
3. Acessar `http://localhost/admin_abastecimento/pages/dashboard.php` sem estar logado
4. Verificar redirecionamento para `/login.php` (via validação de sessão)

## Compatibilidade

- ✅ Mantém compatibilidade com sistema existente
- ✅ Não quebra funcionalidades atuais
- ✅ Preserva todas as regras de segurança
- ✅ Mantém estrutura de banco de dados inalterada
- ✅ Preserva sistema de permissões existente

## Configuração do Servidor

### Apache (.htaccess)
Se necessário, adicionar em `/var/www/html/.htaccess`:
```apache
DirectoryIndex index.php index.html
```

### Permissões
```bash
chmod 644 /var/www/html/index.php
chmod 644 /var/www/html/login.php
chmod 644 /var/www/html/admin_abastecimento/index.php
chmod 644 /var/www/html/admin_abastecimento/api/login.php
chmod 644 /var/www/html/admin_abastecimento/api/logout.php
chmod 644 /var/www/html/postoapp/logout.php
```

## Troubleshooting

### Problema: Loop de redirecionamento
**Causa:** Sessão não está sendo criada corretamente
**Solução:** Verificar se `session_start()` está sendo chamado nos arquivos de validação

### Problema: Erro 404 ao acessar /login.php
**Causa:** Arquivo não foi criado ou permissões incorretas
**Solução:** 
```bash
ls -l /var/www/html/login.php
chmod 644 /var/www/html/login.php
```

### Problema: CSS não carrega na página de login
**Causa:** Caminho das imagens e assets está relativo ao admin_abastecimento
**Solução:** Login.php já usa caminhos absolutos (`/admin_abastecimento/assets/...`)

### Problema: API de login retorna erro
**Causa:** Caminho da API pode estar incorreto
**Solução:** Verificar se a requisição está sendo feita para `admin_abastecimento/api/login.php`

## Manutenção

### Adicionar Novo Grupo
1. Editar `/var/www/html/admin_abastecimento/api/login.php`
2. Adicionar ID do grupo na condição apropriada:
```php
if (in_array($grupoId, [1, 2, 3, 4, 5])) { // Adicionado grupo 5
    $redirectPath = '/admin_abastecimento/pages/dashboard.php';
    $_SESSION['projeto'] = 'admin';
}
```

### Adicionar Novo Projeto
1. Criar novo diretório em `/var/www/html/novo_projeto/`
2. Adicionar condição em `login.php`:
```php
} elseif ($grupoId === 11) {
    $redirectPath = '/novo_projeto/pages/dashboard.php';
    $_SESSION['projeto'] = 'novo';
}
```

## Data da Implementação
- **Data:** 08 de dezembro de 2025
- **Versão:** 1.0
- **Autor:** Sistema Automatizado
