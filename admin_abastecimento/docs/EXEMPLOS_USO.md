# Exemplos Práticos - Usando os Novos Recursos

## 1. Como Criar uma Nova Página com Paginação

### Arquivo: `pages/exemplo.php`

```php
<?php
require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    $_SESSION = array();
    session_destroy();
    header('Location: ../index.php');
    exit;
}

require_once '../config/license_checker.php';
$clienteId = $_SESSION['cliente_id'] ?? null;
$grupoId = $_SESSION['grupoId'] ?? null;
$statusLicenca = LicenseChecker::verificarEBloquear($clienteId, $grupoId);

$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('exemplo', 'acessar');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exemplo - <?php echo COMPANY_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content exemplo">
        <div class="page-title">
            <span><i class="fas fa-box"></i> Exemplo</span>
        </div>

        <!-- Usar classe utilitária action-bar -->
        <div class="action-bar">
            <!-- Usar classe utilitária search-input-container -->
            <div class="input-group search-input-container">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar...">
                <button class="btn btn-secondary btn-sm btn-buscar" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <!-- Usar classe utilitária btn-nowrap -->
            <button class="btn btn-primary btn-sm btn-nowrap" data-bs-toggle="modal" data-bs-target="#modalExemplo">
                <i class="fas fa-plus"></i> Novo
            </button>
        </div>

        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody id="exemploTableBody">
                    <tr>
                        <td colspan="3" class="text-center">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Usar classe utilitária pagination-nav -->
        <nav aria-label="Paginação" class="pagination-nav">
            <ul class="pagination pagination-sm justify-content-center" id="paginacao"></ul>
        </nav>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- IMPORTANTE: Incluir pagination.js -->
    <script src="../js/pagination.js"></script>
    <script>
        let currentPage = 1;
        let totalPages = 1;

        $(document).ready(function() {
            carregarDados();

            $('#searchInput').on('keyup', function() {
                currentPage = 1;
                carregarDados();
            });
        });

        function carregarDados() {
            const search = $('#searchInput').val();
            
            $.ajax({
                url: '../api/exemplo.php',
                method: 'GET',
                data: {
                    action: 'list',
                    page: currentPage,
                    search: search
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderDados(response.data);
                        totalPages = response.totalPages || 1;
                        // USAR A FUNÇÃO UNIVERSAL renderPagination
                        renderPagination(currentPage, totalPages, 'paginacao', goToPage);
                    }
                }
            });
        }

        function renderDados(dados) {
            const tbody = $('#exemploTableBody');
            tbody.empty();

            if (dados.length === 0) {
                tbody.append('<tr><td colspan="3" class="text-center">Nenhum registro encontrado</td></tr>');
                return;
            }

            dados.forEach(function(item) {
                // USAR CLASSE UTILITÁRIA row-middle
                tbody.append(`
                    <tr class="row-middle">
                        <td>${item.codigo}</td>
                        <td><strong>${item.nome}</strong></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="editar(${item.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="excluir(${item.id})" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }

        function goToPage(page) {
            currentPage = page;
            carregarDados();
        }
    </script>
</body>
</html>
```

## 2. Como Criar uma Nova API Usando BaseAPI

### Arquivo: `api/exemplo.php`

```php
<?php
require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';
require_once '../config/BaseAPI.php';

class ExemploAPI extends BaseAPI {
    private $accessControl;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth(); // Valida token automaticamente
        $this->accessControl = new AccessControl($this->userId);
    }
    
    public function handleRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'list':
                $this->accessControl->requerPermissao('exemplo', 'acessar');
                $this->list();
                break;
                
            case 'get':
                $this->accessControl->requerPermissao('exemplo', 'visualizar');
                $this->get();
                break;
                
            case 'create':
                $this->accessControl->requerPermissao('exemplo', 'criar');
                $this->create();
                break;
                
            case 'update':
                $this->accessControl->requerPermissao('exemplo', 'editar');
                $this->update();
                break;
                
            case 'delete':
                $this->accessControl->requerPermissao('exemplo', 'excluir');
                $this->delete();
                break;
                
            default:
                $this->error('Ação inválida');
        }
    }
    
    private function list() {
        // Cliente obrigatório
        $this->requireCliente();
        
        // Parâmetros de paginação automáticos
        $pagination = $this->getPaginationParams();
        
        // Sanitização automática
        $search = $this->sanitizeSearch($_GET['search'] ?? '');
        
        // WHERE automático para cliente
        $where = $this->getClienteWhere('e');
        
        // Adicionar filtro de busca
        $where = $this->addSearchFilter($where, $search, ['codigo', 'nome'], 'e');
        
        // Contar total (método helper)
        $total = $this->countRecords('exemplo e', $where);
        
        // Buscar dados
        $sql = "SELECT e.id, e.codigo, e.nome, e.descricao 
                FROM exemplo e 
                WHERE $where 
                ORDER BY e.nome 
                LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}";
        
        $result = $this->executeQuery($sql);
        
        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }
        
        // Resposta paginada automática
        $this->jsonResponse($this->paginatedResponse($dados, $total, $pagination['page'], 'data'));
    }
    
    private function get() {
        $id = $this->validateId($_GET['id'] ?? 0);
        
        $sql = "SELECT * FROM exemplo WHERE id = ? AND id_cliente = ?";
        $result = $this->executeQuery($sql, [$id, $this->clienteId], 'ii');
        
        if ($row = $result->fetch_assoc()) {
            $this->success('Registro encontrado', ['data' => $row]);
        } else {
            $this->error('Registro não encontrado', 404);
        }
    }
    
    private function create() {
        // Validar campos obrigatórios
        $this->validateRequired($_POST, ['codigo', 'nome']);
        
        $codigo = Security::sanitize($_POST['codigo']);
        $nome = Security::sanitize($_POST['nome']);
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        
        $sql = "INSERT INTO exemplo (codigo, nome, descricao, id_cliente, id_empresa, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssii', $codigo, $nome, $descricao, $this->clienteId, $this->empresaId);
        
        if ($stmt->execute()) {
            $this->success('Registro criado com sucesso', ['id' => $stmt->insert_id]);
        } else {
            $this->error('Erro ao criar registro');
        }
    }
    
    private function update() {
        $id = $this->validateId($_POST['id'] ?? 0);
        $this->validateRequired($_POST, ['codigo', 'nome']);
        
        $codigo = Security::sanitize($_POST['codigo']);
        $nome = Security::sanitize($_POST['nome']);
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        
        $sql = "UPDATE exemplo 
                SET codigo = ?, nome = ?, descricao = ?, updated_at = NOW() 
                WHERE id = ? AND id_cliente = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssii', $codigo, $nome, $descricao, $id, $this->clienteId);
        
        if ($stmt->execute()) {
            $this->success('Registro atualizado com sucesso');
        } else {
            $this->error('Erro ao atualizar registro');
        }
    }
    
    private function delete() {
        $id = $this->validateId($_POST['id'] ?? 0);
        
        $sql = "DELETE FROM exemplo WHERE id = ? AND id_cliente = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $id, $this->clienteId);
        
        if ($stmt->execute()) {
            $this->success('Registro excluído com sucesso');
        } else {
            $this->error('Erro ao excluir registro');
        }
    }
}

// Executar API
$api = new ExemploAPI();
$api->handleRequest();
```

## 3. Exemplos de Uso das Classes CSS

### Preview de Imagem
```html
<!-- ANTES (inline) -->
<div id="logoPreview" style="display: none; margin-top: 0.5rem;">
    <img src="" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
</div>

<!-- DEPOIS (classes utilitárias) -->
<div id="logoPreview" class="image-preview">
    <img src="" alt="Logo">
</div>
```

### Filtros e Busca
```html
<!-- ANTES (inline) -->
<div style="display: flex; gap: 10px; align-items: center; margin-bottom: 0.5rem;">
    <div class="input-group" style="max-width: 720px;">
        <input type="text" class="form-control form-control-sm">
    </div>
    <select class="form-select form-select-sm" style="max-width: 180px;">
        <option>Filtro</option>
    </select>
</div>

<!-- DEPOIS (classes utilitárias) -->
<div class="action-bar">
    <div class="input-group search-input-container">
        <input type="text" class="form-control form-control-sm">
    </div>
    <select class="form-select form-select-sm filter-select">
        <option>Filtro</option>
    </select>
</div>
```

### Input Uppercase
```html
<!-- ANTES (inline) -->
<input type="text" class="form-control" style="text-transform: uppercase;">

<!-- DEPOIS (classe utilitária) -->
<input type="text" class="form-control input-uppercase">
```

### Seção Oculta
```html
<!-- ANTES (inline) -->
<div id="cracha-section" style="display: none;">
    <!-- conteúdo -->
</div>

<!-- DEPOIS (classe utilitária) -->
<div id="cracha-section" class="hidden-section">
    <!-- conteúdo -->
</div>
```

## 4. Migração Gradual

### Passo 1: Nova Página
Use o template completo com todas as classes e funções novas.

### Passo 2: Página Existente - Adicionar Paginação
```javascript
// No final do <body>, antes de </body>
<script src="../js/pagination.js"></script>

// No script da página, substituir função de paginação por:
renderPagination(currentPage, totalPages, 'paginacao', goToPage);
```

### Passo 3: Página Existente - Adicionar Classes CSS
Substituir gradualmente os `style="..."` inline pelas classes utilitárias.

### Passo 4: API Existente - Migrar para BaseAPI
Refatorar API para estender BaseAPI e usar métodos helpers.

## 5. Checklist de Revisão

Ao criar/modificar uma página, verificar:

- [ ] Incluiu `pagination.js`?
- [ ] Usou `renderPagination()` ao invés de função custom?
- [ ] Substituiu CSS inline por classes utilitárias?
- [ ] Removeu `console.log()` de debug?
- [ ] API usa `PAGINATION_LIMIT`?
- [ ] Validações usando métodos da BaseAPI?
- [ ] Nomenclatura seguindo padrões?
- [ ] Código comentado/teste foi removido?

## 6. Benefícios Práticos

### Manutenção
```php
// ANTES: Alterar limite em 9 arquivos diferentes
$limit = 10; // em cada API

// DEPOIS: Alterar em 1 lugar apenas
define('PAGINATION_LIMIT', 20); // em config.php
```

### Consistência
```javascript
// ANTES: 4 funções diferentes de paginação
renderPagination() // veiculo.js
renderPaginacao() // condutor.js
renderizarPaginacao() // grupos.js
// + funções inline em páginas PHP

// DEPOIS: 1 função universal
renderPagination() // em pagination.js
```

### Produtividade
```php
// ANTES: 50+ linhas de código repetido em cada API
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
// + validações, sanitização, etc...

// DEPOIS: 1 linha
$pagination = $this->getPaginationParams();
```
