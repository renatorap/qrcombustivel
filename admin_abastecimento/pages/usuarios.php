<?php
require_once '../config/cache_control.php'; // Controle de cache e sessão
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/license_checker.php';
require_once '../config/access_control.php';
require_once '../components/helpers.php';

// Verificar licença do cliente
$clienteId = $_SESSION['cliente_id'] ?? null;
$grupoId = $_SESSION['grupoId'] ?? null;
$statusLicenca = LicenseChecker::verificarEBloquear($clienteId, $grupoId);

// Verificar permissão
$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('usuarios', 'acessar');

$podeCriar = $accessControl->verificarPermissao('usuarios', 'criar');
$podeVisualizar = $accessControl->verificarPermissao('usuarios', 'visualizar');
$podeEditar = $accessControl->verificarPermissao('usuarios', 'editar');
$podeExcluir = $accessControl->verificarPermissao('usuarios', 'excluir');

$pageTitle = 'Usuários';
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

<div class="main-content">
    <?php echo renderBreadcrumb($accessControl); ?>
    
    <div class="page-title">
        <span><i class="fas fa-users"></i> <?php echo $pageTitle; ?></span>
    </div>

    <div class="mb-2" style="display: flex; gap: 10px; align-items: center;">
        <div class="input-group" style="max-width:720px;">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Pesquisar usuários...">
            <button class="btn btn-outline-secondary btn-sm" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
        <select class="form-select form-select-sm" id="filtroGrupo" style="max-width: 180px;">
            <option value="">Todos os grupos</option>
        </select>
        <select class="form-select form-select-sm" id="filtroAtivo" style="max-width: 150px;">
            <option value="">Todos os status</option>
            <option value="1" selected>Ativos</option>
            <option value="0">Inativos</option>
        </select>
        <?php if ($podeCriar): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario" style="white-space: nowrap;">
            <i class="fas fa-plus"></i> Novo Usuário
        </button>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <table class="table table-enhanced">
            <thead>
                <tr>
                    <th>Login</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Grupo</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody id="usuariosTableBody">
                <!-- Carregado via AJAX -->
            </tbody>
        </table>
    </div>

    <nav aria-label="Paginação" style="margin-top: 10px; margin-bottom: 5px;">
        <ul class="pagination pagination-sm justify-content-center" id="pagination">
            <!-- Paginação carregada via AJAX -->
        </ul>
    </nav>
</div>

<!-- Modal de Visualização -->
<div class="modal fade" id="modalVisualizacao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Visualizar Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Login</label>
                            <input type="text" class="form-control" id="vizLogin" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nome</label>
                            <input type="text" class="form-control" id="vizNome" disabled>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>E-mail</label>
                            <input type="text" class="form-control" id="vizEmail" disabled>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Grupo</label>
                            <input type="text" class="form-control" id="vizGrupo" disabled>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Status</label>
                            <input type="text" class="form-control" id="vizStatus" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioLabel">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formUsuario">
                    <input type="hidden" id="usuarioId" name="id">
                    <div class="form-group">
                        <label for="usuarioLogin">Login *</label>
                        <input type="text" class="form-control" id="usuarioLogin" name="login" required>
                    </div>
                    <div class="form-group">
                        <label for="usuarioNome">Nome</label>
                        <input type="text" class="form-control" id="usuarioNome" name="nome">
                    </div>
                    <div class="form-group">
                        <label for="usuarioEmail">E-mail *</label>
                        <input type="email" class="form-control" id="usuarioEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="usuarioSenha">Senha *</label>
                        <input type="password" class="form-control" id="usuarioSenha" name="senha">
                        <small class="form-text text-muted">Deixe em branco para manter a senha atual (ao editar)</small>
                    </div>
                    <div class="form-group">
                        <label for="usuarioGrupo">Grupo</label>
                        <select class="form-select" id="usuarioGrupo" name="grupo_id"></select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="usuarioAtivo" name="ativo" checked>
                        <label class="form-check-label" for="usuarioAtivo">Usuário ativo</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarUsuario()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
let currentPage = 1;
const podeCriar = <?php echo $podeCriar ? 'true' : 'false'; ?>;
const podeVisualizar = <?php echo $podeVisualizar ? 'true' : 'false'; ?>;
const podeEditar = <?php echo $podeEditar ? 'true' : 'false'; ?>;
const podeExcluir = <?php echo $podeExcluir ? 'true' : 'false'; ?>;

document.addEventListener('DOMContentLoaded', () => {
    carregarGrupos();
    carregarUsuarios();
    document.getElementById('searchInput').addEventListener('input', () => { currentPage = 1; carregarUsuarios(); });
    document.getElementById('filtroGrupo').addEventListener('change', () => { currentPage = 1; carregarUsuarios(); });
    document.getElementById('filtroAtivo').addEventListener('change', () => { currentPage = 1; carregarUsuarios(); });
});

function carregarGrupos() {
    fetch('../api/grupos.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('usuarioGrupo');
                const filtro = document.getElementById('filtroGrupo');
                select.innerHTML = '<option value="">Sem grupo</option>';
                filtro.innerHTML += data.grupos.map(g => `<option value="${g.id}">${g.nome}</option>`).join('');
                select.innerHTML += data.grupos.map(g => `<option value="${g.id}">${g.nome}</option>`).join('');
            }
        });
}

function carregarUsuarios() {
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        search: document.getElementById('searchInput').value,
        grupo_id: document.getElementById('filtroGrupo').value,
        ativo: document.getElementById('filtroAtivo').value
    });
    
    fetch(`../api/usuarios.php?${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('usuariosTableBody');
                tbody.innerHTML = data.usuarios.length ? data.usuarios.map(u => `
                    <tr>
                        <td><strong>${u.login}</strong></td>
                        <td>${u.nome || '<em class="text-muted">-</em>'}</td>
                        <td>${u.email}</td>
                        <td>${u.grupo_nome || '<em class="text-muted">Sem grupo</em>'}</td>
                        <td class="text-center">
                            <span class="badge ${u.ativo == 1 ? 'bg-success' : 'bg-secondary'}">${u.ativo == 1 ? 'Ativo' : 'Inativo'}</span>
                        </td>
                        <td class="text-center">
                            ${podeVisualizar ? `<button class="btn btn-sm btn-outline-primary" onclick="visualizarUsuario(${u.id})" title="Visualizar"><i class="fas fa-eye"></i></button> ` : ''}
                            ${podeEditar ? `<button class="btn btn-sm btn-outline-info" onclick="editarUsuario(${u.id})"><i class="fas fa-edit"></i></button>` : ''}
                            ${podeExcluir ? `<button class="btn btn-sm btn-outline-danger" onclick="excluirUsuario(${u.id}, '${u.nome || u.login}')"><i class="fas fa-trash"></i></button>` : ''}
                        </td>
                    </tr>
                `).join('') : '<tr><td colspan="6" class="text-center">Nenhum usuário encontrado</td></tr>';
            }
        });
}

function salvarUsuario() {
    const form = new FormData(document.getElementById('formUsuario'));
    form.append('action', document.getElementById('usuarioId').value ? 'update' : 'create');
    form.append('ativo', document.getElementById('usuarioAtivo').checked ? 1 : 0);
    
    fetch('../api/usuarios.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
                carregarUsuarios();
            }
        });
}

function editarUsuario(id) {
    fetch(`../api/usuarios.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const u = data.usuario;
                document.getElementById('usuarioId').value = u.id;
                document.getElementById('usuarioLogin').value = u.login;
                document.getElementById('usuarioNome').value = u.nome || '';
                document.getElementById('usuarioEmail').value = u.email;
                document.getElementById('usuarioSenha').value = '';
                document.getElementById('usuarioGrupo').value = u.grupo_id || '';
                document.getElementById('usuarioAtivo').checked = u.ativo == 1;
                document.getElementById('modalUsuarioLabel').textContent = 'Editar Usuário';
                new bootstrap.Modal(document.getElementById('modalUsuario')).show();
            }
        });
}

function visualizarUsuario(id) {
    fetch(`../api/usuarios.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const u = data.usuario;
                document.getElementById('vizLogin').value = u.login;
                document.getElementById('vizNome').value = u.nome || '-';
                document.getElementById('vizEmail').value = u.email;
                document.getElementById('vizGrupo').value = u.grupo_nome || 'Sem grupo';
                document.getElementById('vizStatus').value = u.ativo == 1 ? 'Ativo' : 'Inativo';
                new bootstrap.Modal(document.getElementById('modalVisualizacao')).show();
            }
        });
}

function excluirUsuario(id, nome) {
    if (confirm(`Excluir usuário "${nome}"?`)) {
        const form = new FormData();
        form.append('action', 'delete');
        form.append('id', id);
        fetch('../api/usuarios.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) carregarUsuarios();
            });
    }
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
</body>
</html>
