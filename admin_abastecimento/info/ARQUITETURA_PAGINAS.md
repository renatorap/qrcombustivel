# Padrão de Arquitetura das Páginas do Sistema

## Visão Geral
Todas as páginas do sistema devem seguir a arquitetura estabelecida pela página de **Veículos** (`veiculo.php`), que implementa um padrão CRUD completo com modal, tabela, paginação e separação de responsabilidades.

### Padrão de Modais e Formulários (Global)
- Os modais são sempre exibidos no topo da janela, alinhados ao header (12px), com o cabeçalho fixo e a rolagem ocorrendo apenas no corpo do modal.
- O corpo do modal (`.modal-body`) possui rolagem vertical e padding compacto.
- Campos de formulários dentro de modais usam espaçamentos compactos (labels e inputs com fonte 13px e padding reduzido).
- Largura padrão do modal: `max-width: 800px` (95% da viewport). Para formulários complexos, adicionar a classe `modal-dialog-wide` ao `.modal-dialog` para usar `max-width: 1200px` (também 95% da viewport).
- Estes padrões são globais via `css/style.css` e valem para novas páginas automaticamente.

## Estrutura de Arquivos

Para cada módulo/entidade, criar 3 arquivos principais:

### 1. Página Frontend (`pages/[nome].php`)
**Responsabilidades:**
- Validação de sessão e token
- Estrutura HTML básica com Bootstrap 5.3
- Inclusão de header, sidebar e footer
- Tabela para listagem de registros
- Modal para criação/edição
- Modal para visualização
- Campo de busca e botão "Novo"
- Container de paginação

**Estrutura Padrão:**
```php
<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';

// Validação de sessão
if (empty($_SESSION['token'])) {
    header('Location: ../index.php');
    exit;
}

$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de [Entidade] - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content veiculo">
        <div class="page-title">
            <span><i class="fas fa-[icone]"></i> Gestão de [Entidade]</span>
        </div>

        <!-- Busca e Novo -->
        <div class="mb-2" style="display: flex; gap: 10px; align-items: center;">
            <div class="input-group" style="max-width:720px;">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar...">
                <button class="btn btn-secondary btn-sm btn-buscar" type="button"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal[Entidade]">
                <i class="fas fa-plus"></i> Novo [Entidade]
            </button>
        </div>

        <!-- Tabela -->
        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <!-- Colunas específicas -->
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="[entidade]sTable">
                    <!-- Carregado via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <nav aria-label="Paginação" style="margin-top: 10px; margin-bottom: 5px;">
            <ul class="pagination pagination-sm justify-content-center" id="paginacao">
                <!-- Paginação carregada via AJAX -->
            </ul>
        </nav>
    </div>

    <!-- Modal Criar/Editar -->
    <div class="modal fade" id="modal[Entidade]" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo [Entidade]</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="[entidade]Form">
                        <input type="hidden" id="[entidade]Id" name="id">
                        <!-- Campos do formulário -->
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visualização -->
    <div class="modal fade" id="modalVisualizacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visualizar [Entidade]</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Campos disabled para visualização -->
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/[entidade].js"></script>
</body>
</html>
```

### 2. API Backend (`api/[nome].php`)
**Responsabilidades:**
- Validação de autenticação
- Implementação das ações CRUD: list, get, create, update, delete
- Sanitização de inputs
- Retorno JSON padronizado

**Estrutura Padrão:**
```php
<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

if (empty($_SESSION['token'])) {
    $response['message'] = 'Não autenticado';
    echo json_encode($response);
    exit;
}

$db = new Database();
$db->connect();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        // Busca com paginação
        $search = Security::sanitize($_GET['search'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $where = "1=1";
        if ($search) {
            $where = "([campo] LIKE '%$search%' OR [campo2] LIKE '%$search%')";
        }

        // Total de registros
        $sql = "SELECT COUNT(*) as total FROM [tabela] WHERE $where";
        $result = $db->query($sql);
        $row = $result->fetch_assoc();
        $totalPages = ceil($row['total'] / $limit);

        // Buscar registros
        $sql = "SELECT * FROM [tabela] WHERE $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $result = $db->query($sql);

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $response['success'] = true;
        $response['[entidade]s'] = $items;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;

    case 'get':
        // Buscar registro por ID
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM [tabela] WHERE id = $id";
        $result = $db->query($sql);

        if ($result->num_rows === 1) {
            $response['success'] = true;
            $response['[entidade]'] = $result->fetch_assoc();
        } else {
            $response['message'] = '[Entidade] não encontrado';
        }
        break;

    case 'create':
        // Sanitizar campos
        $campo1 = Security::sanitize($_POST['campo1']);
        $campo2 = Security::sanitize($_POST['campo2']);

        if (!$campo1) {
            $response['message'] = 'Campo obrigatório não preenchido';
            break;
        }

        $sql = "INSERT INTO [tabela] (campo1, campo2) VALUES ('$campo1', '$campo2')";
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = '[Entidade] criado com sucesso';
        } else {
            $response['message'] = 'Erro ao criar [entidade]';
        }
        break;

    case 'update':
        // Sanitizar campos
        $id = intval($_POST['id']);
        $campo1 = Security::sanitize($_POST['campo1']);
        $campo2 = Security::sanitize($_POST['campo2']);

        if (!$campo1) {
            $response['message'] = 'Campo obrigatório não preenchido';
            break;
        }

        $sql = "UPDATE [tabela] SET campo1='$campo1', campo2='$campo2' WHERE id=$id";
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = '[Entidade] atualizado com sucesso';
        } else {
            $response['message'] = 'Erro ao atualizar [entidade]';
        }
        break;

    case 'delete':
        $id = intval($_POST['id']);
        $sql = "DELETE FROM [tabela] WHERE id=$id";
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = '[Entidade] excluído com sucesso';
        } else {
            $response['message'] = 'Erro ao excluir [entidade]';
        }
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
```

### 3. JavaScript Frontend (`js/[nome].js`)
**Responsabilidades:**
- Gerenciamento de estado (página atual, termo de busca)
- Funções AJAX para comunicação com API
- Renderização dinâmica de tabela e paginação
- Manipulação de modais
- Validação de formulários

**Estrutura Padrão:**
```javascript
let currentPage = 1;
let searchTerm = '';

$(document).ready(function() {
    load[Entidade]s();

    // Submit do formulário
    $('#[entidade]Form').submit(function(e) {
        e.preventDefault();
        
        const id = $('#[entidade]Id').val();
        const campo1 = $('#campo1').val().trim();

        if (!campo1) {
            alert('Por favor, preencha todos os campos');
            return;
        }

        $.ajax({
            url: '../api/[entidade].php',
            method: 'POST',
            data: {
                action: id ? 'update' : 'create',
                id: id,
                campo1: campo1,
                // outros campos
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#modal[Entidade]').modal('hide');
                    resetForm();
                    load[Entidade]s();
                    alert(data.message);
                } else {
                    alert(data.message || 'Erro ao salvar');
                }
            },
            error: function() {
                alert('Erro na comunicação com o servidor');
            }
        });
    });

    // Busca
    $('#searchInput').keyup(function() {
        searchTerm = $(this).val().trim();
        currentPage = 1;
        load[Entidade]s();
    });

    $('.btn-buscar').click(function() {
        searchTerm = $('#searchInput').val().trim();
        currentPage = 1;
        load[Entidade]s();
    });
});

function load[Entidade]s() {
    $.ajax({
        url: '../api/[entidade].php',
        method: 'GET',
        data: {
            action: 'list',
            search: searchTerm,
            page: currentPage
        },
        dataType: 'json',
        success: function(data) {
            renderTable(data.[entidade]s);
            renderPagination(data.totalPages, data.currentPage);
        },
        error: function() {
            alert('Erro ao carregar [entidade]s');
        }
    });
}

function renderTable(items) {
    const tbody = $('#[entidade]sTable');
    tbody.empty();

    if (items.length === 0) {
        tbody.html('<tr><td colspan="X" class="text-center text-muted">Nenhum [entidade] encontrado</td></tr>');
        return;
    }

    items.forEach(item => {
        const row = `
            <tr style="vertical-align: middle;">
                <td>${item.id}</td>
                <td>${item.campo1}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-1" title="Visualizar" onclick="visualizar(${item.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning me-1" title="Editar" onclick="editar(${item.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="excluir(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderPagination(totalPages, currentPage) {
    const paginacao = $('#paginacao');
    paginacao.empty();

    for (let i = 1; i <= totalPages; i++) {
        const active = i === currentPage ? 'active' : '';
        paginacao.append(`
            <li class="page-item ${active}" style="align-self: center;">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${i})">${i}</a>
            </li>
        `);
    }
}

function goToPage(page) {
    currentPage = page;
    load[Entidade]s();
}

function visualizar(id) {
    $.ajax({
        url: '../api/[entidade].php',
        method: 'GET',
        data: {
            action: 'get',
            id: id
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const item = data.[entidade];
                // Preencher campos de visualização
                $('#modalVisualizacao').modal('show');
            }
        }
    });
}

function editar(id) {
    $.ajax({
        url: '../api/[entidade].php',
        method: 'GET',
        data: {
            action: 'get',
            id: id
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const item = data.[entidade];
                $('#[entidade]Id').val(item.id);
                // Preencher campos do formulário
                $('#modalTitle').text('Editar [Entidade]');
                $('#modal[Entidade]').modal('show');
            }
        }
    });
}

function excluir(id) {
    if (confirm('Tem certeza que deseja excluir este [entidade]?')) {
        $.ajax({
            url: '../api/[entidade].php',
            method: 'POST',
            data: {
                action: 'delete',
                id: id
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    load[Entidade]s();
                    alert(data.message);
                } else {
                    alert(data.message || 'Erro ao excluir');
                }
            }
        });
    }
}

function resetForm() {
    $('#[entidade]Form')[0].reset();
    $('#[entidade]Id').val('');
    $('#modalTitle').text('Novo [Entidade]');
}
```

## Princípios da Arquitetura

### 1. Separação de Responsabilidades
- **Frontend (PHP)**: Apenas estrutura HTML e validação de sessão
- **Backend (API PHP)**: Toda lógica de negócio e acesso a dados
- **JavaScript**: Interação do usuário e comunicação assíncrona

### 2. Comunicação via AJAX
- Todas as operações CRUD são assíncronas
- Respostas sempre em formato JSON padronizado
- Feedback visual para o usuário (alerts, modais)

### 3. Segurança
- Validação de token em todas as páginas e APIs
- Sanitização de todos os inputs via `Security::sanitize()`
- Uso de prepared statements (quando possível) ou escape de strings
- RBAC integrado com AccessControl (quando necessário)

### 4. UX Consistente
- Modal para criação/edição (mesmo formulário)
- Modal separado para visualização (campos desabilitados)
- Botões de ação padronizados: visualizar (azul), editar (amarelo), excluir (vermelho)
- Busca com debounce via keyup
- Paginação com limite de 10 registros por página

### 5. Responsividade
- Bootstrap 5.3 para grid system
- Tabelas responsivas
- Modais adaptáveis
- FontAwesome 6.4 para ícones

## Checklist para Nova Página

- [ ] Criar página PHP em `pages/` com validação de sessão
- [ ] Criar API PHP em `api/` com actions: list, get, create, update, delete
- [ ] Criar JavaScript em `js/` com funções: load, render, visualizar, editar, excluir, resetForm
- [ ] Incluir header, sidebar e footer
- [ ] Implementar busca e paginação
- [ ] Criar modal de criação/edição
- [ ] Criar modal de visualização
- [ ] Adicionar máscaras de input quando necessário (CNPJ, CPF, telefone, CEP)
- [ ] Testar todas as operações CRUD
- [ ] Registrar aplicação na tabela `aplicacoes` (se necessário RBAC)
- [ ] Adicionar permissões ao grupo apropriado em `permissoes_grupo`

## Exemplos Implementados

### Veículos (`veiculo.php`, `veiculo.js`, `api/veiculo.php`)
- Campos: placa, modelo, marca
- Busca por placa, modelo ou marca
- CRUD completo

### Clientes (`cliente.php`, `cliente.js`, `api/cliente.php`)
- Campos: razão social, nome fantasia, CNPJ, endereço completo, contatos
- Busca por razão social, CNPJ ou nome fantasia
- Máscaras para CNPJ, CEP e telefone
- Campo ativo/inativo (checkbox)
- CRUD completo

## Boas Práticas

1. **Nomenclatura**: Usar singular para entidade (cliente, veiculo, produto)
2. **IDs HTML**: Prefixar com nome da entidade (`clienteId`, `veiculoForm`)
3. **Mensagens**: Sempre fornecer feedback ao usuário
4. **Validação**: Validar no frontend E no backend
5. **Erros**: Tratar erros de AJAX e SQL adequadamente
6. **Paginação**: Manter limite de 10 registros por página
7. **Busca**: Implementar busca case-insensitive com LIKE
8. **Modais**: Reutilizar modal para criar/editar, modal separado para visualizar
9. **Reset**: Sempre resetar formulário ao fechar modal ou após sucesso
10. **Loading**: Considerar adicionar indicadores de loading em operações longas
