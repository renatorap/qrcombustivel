<?php
require_once '../config/cache_control.php'; // Controle de cache e sessão
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';
require_once '../components/helpers.php';

// Verificar permissão
$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('grupos', 'acessar');

$podeCriar = $accessControl->verificarPermissao('grupos', 'criar');
$podeEditar = $accessControl->verificarPermissao('grupos', 'editar');
$podeExcluir = $accessControl->verificarPermissao('grupos', 'excluir');
$podeVisualizar = $accessControl->verificarPermissao('grupos', 'visualizar');

$pageTitle = 'Grupos de Usuários';
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
        <span><i class="fas fa-users-cog"></i> <?php echo $pageTitle; ?></span>
    </div>

    <div class="mb-2" style="display: flex; gap: 10px; align-items: center;">
        <div class="input-group" style="max-width:720px;">
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Pesquisar grupos...">
            <button class="btn btn-outline-secondary btn-sm" type="button">
                <i class="fas fa-search"></i>
            </button>
        </div>
        <select class="form-select form-select-sm" id="filtroAtivo" style="max-width: 180px;">
            <option value="">Todos os status</option>
            <option value="1" selected>Ativos</option>
            <option value="0">Inativos</option>
        </select>
        <?php if ($podeCriar): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGrupo" style="white-space: nowrap;">
            <i class="fas fa-plus"></i> Novo Grupo
        </button>
        <?php endif; ?>
    </div>

        <!-- Tabela de Grupos -->
        <div class="table-container">
            <table class="table table-enhanced">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th class="text-center">Usuários</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody id="gruposTableBody">
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

<!-- Modal de Grupo -->
<div class="modal fade" id="modalGrupo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGrupoLabel">Novo Grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formGrupo">
                    <input type="hidden" id="grupoId" name="id">
                    
                    <div class="form-group">
                        <label for="grupoNome">Nome *</label>
                        <input type="text" class="form-control" id="grupoNome" name="nome" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="grupoDescricao">Descrição</label>
                        <textarea class="form-control" id="grupoDescricao" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="grupoAtivo" name="ativo" checked>
                        <label class="form-check-label" for="grupoAtivo">Grupo ativo</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarGrupo()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Usuários do Grupo -->
<div class="modal fade" id="modalUsuarios" tabindex="-1">
    <div class="modal-dialog modal-dialog-wide">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuariosLabel">Usuários do Grupo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="usuariosLista">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Definir permissões do usuário
window.podeCriar = <?php echo $podeCriar ? 'true' : 'false'; ?>;
window.podeExcluir = <?php echo $podeExcluir ? 'true' : 'false'; ?>;
</script>
<script src="../js/grupos.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>

</body>
</html>
