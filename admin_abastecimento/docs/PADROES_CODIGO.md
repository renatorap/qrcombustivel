# Guia de Padrões de Código - QR Combustível

## 1. Estrutura de Arquivos Criados

### JavaScript Reutilizável
- **`js/pagination.js`** - Biblioteca de paginação reutilizável
  - `renderPagination(currentPage, totalPages, containerId, callback)` - Função principal
  - Aliases: `renderPaginacao()` e `renderizarPaginacao()` para compatibilidade

### PHP Reutilizável
- **`config/BaseAPI.php`** - Classe base para APIs com métodos comuns:
  - Paginação automatizada
  - Validação de dados
  - Respostas JSON padronizadas
  - Sanitização de entrada
  - Contagem de registros

### CSS Classes Utilitárias
Adicionadas em `css/style.css`:
- `.action-bar` - Container flex para ações e busca
- `.search-input-container` - Limita largura do campo de busca
- `.filter-select`, `.filter-select-sm` - Selects de filtro
- `.pagination-nav` - Espaçamento de paginação
- `.image-preview` - Preview de imagens
- `.row-middle` - Alinhamento vertical em tabelas
- `.input-uppercase` - Input em maiúsculas
- `.required-indicator` - Indicador de campo obrigatório
- `.hidden-section` - Seção oculta
- `.loading-indicator` - Indicador de carregamento
- `.btn-nowrap` - Botão sem quebra de linha

## 2. Padrões de Uso

### Paginação em JavaScript
```javascript
// Incluir o arquivo
<script src="../js/pagination.js"></script>

// Uso básico
function carregarDados() {
    $.ajax({
        url: '../api/endpoint.php',
        data: { page: currentPage },
        success: function(response) {
            // Renderizar dados
            renderDados(response.data);
            
            // Renderizar paginação
            renderPagination(response.currentPage, response.totalPages, 'paginacao', goToPage);
        }
    });
}

function goToPage(page) {
    currentPage = page;
    carregarDados();
}
```

### API com BaseAPI
```php
<?php
require_once '../config/BaseAPI.php';

class MinhaAPI extends BaseAPI {
    public function list() {
        $this->requireAuth();
        $this->requireCliente();
        
        $pagination = $this->getPaginationParams();
        $search = $this->sanitizeSearch($_GET['search'] ?? '');
        
        $where = $this->getClienteWhere('t');
        $where = $this->addSearchFilter($where, $search, ['campo1', 'campo2'], 't');
        
        $total = $this->countRecords('tabela t', $where);
        $data = // buscar dados...
        
        $this->jsonResponse($this->paginatedResponse($data, $total, $pagination['page'], 'items'));
    }
}
```

### HTML com Classes Utilitárias
```html
<!-- Barra de ações -->
<div class="action-bar">
    <div class="input-group search-input-container">
        <input type="text" class="form-control form-control-sm" placeholder="Buscar...">
        <button class="btn btn-secondary btn-sm"><i class="fas fa-search"></i></button>
    </div>
    <button class="btn btn-primary btn-sm btn-nowrap">
        <i class="fas fa-plus"></i> Novo
    </button>
</div>

<!-- Paginação -->
<nav aria-label="Paginação" class="pagination-nav">
    <ul class="pagination pagination-sm justify-content-center" id="paginacao"></ul>
</nav>

<!-- Tabela -->
<tr class="row-middle">
    <td>Conteúdo</td>
</tr>
```

## 3. Parâmetros Centralizados (config.php)

```php
// Paginação
define('PAGINATION_LIMIT', 10);        // Registros por página
define('PAGINATION_MAX_LINKS', 5);     // Links visíveis na paginação

// Segurança
define('JWT_SECRET', '...');
define('TOKEN_EXPIRY', 3600);

// Sistema
define('COMPANY_NAME', 'QR Combustível');
define('BASE_URL', 'http://...');
```

## 4. Convenções de Código

### Nomenclatura
- **Variáveis PHP**: `$camelCase`
- **Funções PHP**: `camelCase()`
- **Classes PHP**: `PascalCase`
- **Constantes PHP**: `UPPER_SNAKE_CASE`
- **Variáveis JS**: `camelCase`
- **Funções JS**: `camelCase()`
- **Classes CSS**: `kebab-case`
- **IDs HTML**: `camelCase`

### Estrutura de Página
```php
<?php
// 1. Requires
require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

// 2. Validação de token
$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    $_SESSION = array();
    session_destroy();
    header('Location: ../index.php');
    exit;
}

// 3. Verificação de licença
require_once '../config/license_checker.php';
$clienteId = $_SESSION['cliente_id'] ?? null;
$grupoId = $_SESSION['grupoId'] ?? null;
$statusLicenca = LicenseChecker::verificarEBloquear($clienteId, $grupoId);

// 4. Controle de acesso
$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('modulo', 'acessar');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Título - <?php echo COMPANY_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content nome-modulo">
        <div class="page-title">
            <span><i class="fas fa-icon"></i> Título</span>
        </div>

        <div class="action-bar">
            <!-- Busca e ações -->
        </div>

        <div class="table-container">
            <!-- Tabela -->
        </div>

        <nav aria-label="Paginação" class="pagination-nav">
            <ul class="pagination pagination-sm justify-content-center" id="paginacao"></ul>
        </nav>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/pagination.js"></script>
    <script src="../js/script.js"></script>
</body>
</html>
```

### Estrutura de API
```php
<?php
require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';
require_once '../config/BaseAPI.php';

class MinhaAPI extends BaseAPI {
    private $accessControl;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->accessControl = new AccessControl($this->userId);
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'list':
                $this->accessControl->requerPermissao('modulo', 'acessar');
                $this->list();
                break;
            case 'create':
                $this->accessControl->requerPermissao('modulo', 'criar');
                $this->create();
                break;
            // ...
            default:
                $this->error('Ação inválida');
        }
    }
}

$api = new MinhaAPI();
$api->handleRequest();
```

## 5. Boas Práticas

### Evitar
- ❌ CSS inline (`style="..."`)
- ❌ `console.log()` em produção
- ❌ Código comentado não utilizado
- ❌ Valores hardcoded (usar constantes)
- ❌ Repetição de código

### Preferir
- ✅ Classes CSS reutilizáveis
- ✅ Funções e métodos compartilhados
- ✅ Constantes centralizadas
- ✅ Validação consistente
- ✅ Nomenclatura padronizada
- ✅ Comentários descritivos
- ✅ Tratamento de erros

## 6. Checklist para Nova Página

- [ ] Estrutura HTML seguindo o padrão
- [ ] Validação de token e licença
- [ ] Controle de acesso implementado
- [ ] Classes CSS utilitárias utilizadas
- [ ] Paginação usando `pagination.js`
- [ ] API usando `BaseAPI` se aplicável
- [ ] Sem CSS inline
- [ ] Sem `console.log()` de debug
- [ ] Responsiva (testar em mobile)
- [ ] Acessibilidade (aria-labels, titles)

## 7. Manutenção

### Atualizar Limite de Paginação
Editar apenas em `config/config.php`:
```php
define('PAGINATION_LIMIT', 20); // Altera para 20 registros
```

### Adicionar Nova Classe Utilitária
Adicionar em `css/style.css` na seção de classes utilitárias:
```css
.nova-classe {
    /* estilos */
}
```

### Estender BaseAPI
```php
class MinhaAPI extends BaseAPI {
    protected function metodoCustomizado() {
        // implementação
    }
}
```
