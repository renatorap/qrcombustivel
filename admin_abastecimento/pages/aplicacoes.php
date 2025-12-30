<?php
require_once '../config/cache_control.php'; // Controle de cache e sessão
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('aplicacoes', 'acessar');

$podeCriar = $accessControl->verificarPermissao('aplicacoes', 'criar');
$podeEditar = $accessControl->verificarPermissao('aplicacoes', 'editar');
$podeExcluir = $accessControl->verificarPermissao('aplicacoes', 'excluir');

$pageTitle = 'Aplicações';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="page-title">
        <div>
            <h1>
                <i class="fas fa-th-large"></i>Aplicações
            </h1>
        </div>
        <div>
            <button class="btn btn-outline-primary btn-sm me-2" onclick="location.href='sincronizacao.php'">
                <i class="fas fa-sync"></i> Sincronizar
            </button>
            <?php if ($podeCriar): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAplicacao">
                    <i class="fas fa-plus"></i> Nova Aplicação
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="search-container">
        <div class="search-input-container">
            <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Pesquisar aplicações...">
        </div>
        <select class="form-select form-select-sm" id="filtroModulo" style="max-width: 200px;">
            <option value="">Todos os módulos</option>
        </select>
        <select class="form-select form-select-sm" id="filtroAtivo" style="max-width: 180px;">
            <option value="">Todos os status</option>
            <option value="1" selected>Ativas</option>
            <option value="0">Inativas</option>
        </select>
    </div>

    <div class="table-container">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Módulo</th>
                    <th class="text-center">Ordem</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody id="aplicacoesTableBody">
                <tr><td colspan="6" class="text-center"><div class="spinner-border"></div></td></tr>
            </tbody>
        </table>
    </div>
    <nav><ul class="pagination justify-content-center" id="pagination"></ul></nav>
</main>

<div class="modal fade" id="modalAplicacao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-th-large me-2"></i>Nova Aplicação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAplicacao">
                    <input type="hidden" id="aplicacaoId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Código *</label>
                        <input type="text" class="form-control" id="aplicacaoCodigo" name="codigo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="aplicacaoNome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Módulo</label>
                        <input type="text" class="form-control" id="aplicacaoModulo" name="modulo" value="sistema">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ícone</label>
                        <div class="input-group">
                            <span class="input-group-text" id="aplicacaoIconePreview" title="Clique para escolher um ícone">
                                <i class="fas fa-file"></i>
                            </span>
                            <input type="text" class="form-control" id="aplicacaoIcone" name="icone" 
                                   value="fa-file" placeholder="Clique para escolher">
                        </div>
                        <small class="text-muted">Clique no ícone ou no campo para selecionar</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ordem</label>
                        <input type="number" class="form-control" id="aplicacaoOrdem" name="ordem" value="999">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="aplicacaoAtivo" name="ativo" checked>
                        <label class="form-check-label">Aplicação ativa</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="salvarAplicacao()">
                    <i class="fas fa-save me-2"></i>Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="../js/icon_picker.js"></script>
<script>
let currentPage = 1;

document.addEventListener('DOMContentLoaded', () => {
    carregarModulos();
    carregarAplicacoes();
    document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; carregarAplicacoes(); });
    document.getElementById('filtroModulo').addEventListener('change', () => { currentPage = 1; carregarAplicacoes(); });
    document.getElementById('filtroAtivo').addEventListener('change', () => { currentPage = 1; carregarAplicacoes(); });
    
    // Inicializar icon picker
    setTimeout(() => {
        makeIconInputClickable('aplicacaoIcone', 'aplicacaoIconePreview');
    }, 100);
    
    // Atualizar preview do ícone quando o campo mudar
    document.getElementById('aplicacaoIcone').addEventListener('input', function() {
        const iconClass = this.value || 'fa-file';
        document.querySelector('#aplicacaoIconePreview i').className = 'fas ' + iconClass;
    });
});

function carregarModulos() {
    fetch('../api/aplicacoes.php?action=modulos')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filtroModulo');
                select.innerHTML += data.modulos.map(m => `<option value="${m}">${m}</option>`).join('');
            }
        });
}

function carregarAplicacoes() {
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        search: document.getElementById('searchInput').value,
        modulo: document.getElementById('filtroModulo').value,
        ativo: document.getElementById('filtroAtivo').value
    });
    
    fetch(`../api/aplicacoes.php?${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('aplicacoesTableBody');
                tbody.innerHTML = data.aplicacoes.length ? data.aplicacoes.map(a => `
                    <tr>
                        <td><code>${a.codigo}</code></td>
                        <td><i class="fas ${a.icone}"></i> ${a.nome}</td>
                        <td><span class="badge bg-info">${a.modulo}</span></td>
                        <td class="text-center">${a.ordem}</td>
                        <td class="text-center">
                            <span class="badge ${a.ativo == 1 ? 'bg-success' : 'bg-secondary'}">${a.ativo == 1 ? 'Ativa' : 'Inativa'}</span>
                        </td>
                        <td class="text-center">
                            <?php if ($podeEditar): ?>
                            <button class="btn btn-sm btn-outline-info" onclick="editarAplicacao(${a.id})"><i class="fas fa-edit"></i></button>
                            <?php endif; ?>
                            <?php if ($podeExcluir): ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="excluirAplicacao(${a.id}, '${a.codigo}')"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                `).join('') : '<tr><td colspan="6" class="text-center">Nenhuma aplicação encontrada</td></tr>';
            }
        });
}

function salvarAplicacao() {
    const form = new FormData(document.getElementById('formAplicacao'));
    form.append('action', document.getElementById('aplicacaoId').value ? 'update' : 'create');
    form.append('ativo', document.getElementById('aplicacaoAtivo').checked ? 1 : 0);
    
    fetch('../api/aplicacoes.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalAplicacao')).hide();
                carregarAplicacoes();
            }
        });
}

function editarAplicacao(id) {
    fetch(`../api/aplicacoes.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const a = data.aplicacao;
                document.getElementById('aplicacaoId').value = a.id;
                document.getElementById('aplicacaoCodigo').value = a.codigo;
                document.getElementById('aplicacaoNome').value = a.nome;
                document.getElementById('aplicacaoModulo').value = a.modulo;
                document.getElementById('aplicacaoIcone').value = a.icone;
                document.getElementById('aplicacaoOrdem').value = a.ordem;
                document.getElementById('aplicacaoAtivo').checked = a.ativo == 1;
                
                // Atualizar preview do ícone
                document.querySelector('#aplicacaoIconePreview i').className = 'fas ' + a.icone;
                
                new bootstrap.Modal(document.getElementById('modalAplicacao')).show();
            }
        });
}

function excluirAplicacao(id, codigo) {
    if (confirm(`Excluir aplicação "${codigo}"?`)) {
        const form = new FormData();
        form.append('action', 'delete');
        form.append('id', id);
        fetch('../api/aplicacoes.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) carregarAplicacoes();
            });
    }
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
</body>
</html>
