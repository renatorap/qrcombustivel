<?php
require_once '../config/cache_control.php'; // Controle de cache e sessão
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

// Verificar permissão
$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('permissoes', 'acessar');

$podeEditar = $accessControl->verificarPermissao('permissoes', 'editar');

$pageTitle = 'Gerenciar Permissões';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Abastecimento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<?php 
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="main-content">
    <div class="container-fluid">
        <div class="page-title">
            <span><i class="fas fa-shield-alt"></i> <?php echo $pageTitle; ?></span>
        </div>

        <!-- Seleção de Grupo -->
        <div class="row mb-4">
            <div class="col-md-6">
                <label for="grupoSelect" class="form-label fw-bold">Selecione o Grupo:</label>
                <select class="form-select" id="grupoSelect" onchange="carregarPermissoes()">
                    <option value="">-- Selecione um grupo --</option>
                </select>
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <button class="btn btn-outline-secondary me-2" onclick="copiarPermissoes()" id="btnCopiar" disabled>
                    <i class="fas fa-copy me-2"></i>Copiar Permissões
                </button>
                <?php if ($podeEditar): ?>
                    <button class="btn btn-success" onclick="salvarTodasPermissoes()" id="btnSalvar" disabled>
                        <i class="fas fa-save me-2"></i>Salvar Todas
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filtros e Pesquisa -->
        <div class="row mb-3" id="filtrosContainer" style="display: none;">
            <div class="col-md-4">
                <input type="text" class="form-control" id="searchAplicacao" placeholder="Pesquisar aplicação...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroModulo">
                    <option value="">Todos os módulos</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="mostrarInativos">
                    <label class="form-check-label" for="mostrarInativos">
                        Mostrar aplicações inativas
                    </label>
                </div>
            </div>
        </div>

        <!-- Matriz de Permissões -->
        <div class="row" id="permissoesContainer" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Matriz de Permissões</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0" id="permissoesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 20%">Aplicação</th>
                                        <th class="text-center" style="width: 10%">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input" type="checkbox" id="checkAllAcessar" 
                                                       onchange="marcarTodos('acessar', this.checked)">
                                                <label class="form-check-label">Acessar</label>
                                            </div>
                                        </th>
                                        <th class="text-center" style="width: 10%">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input" type="checkbox" id="checkAllCriar" 
                                                       onchange="marcarTodos('criar', this.checked)">
                                                <label class="form-check-label">Criar</label>
                                            </div>
                                        </th>
                                        <th class="text-center" style="width: 10%">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input" type="checkbox" id="checkAllVisualizar" 
                                                       onchange="marcarTodos('visualizar', this.checked)">
                                                <label class="form-check-label">Visualizar</label>
                                            </div>
                                        </th>
                                        <th class="text-center" style="width: 10%">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input" type="checkbox" id="checkAllEditar" 
                                                       onchange="marcarTodos('editar', this.checked)">
                                                <label class="form-check-label">Editar</label>
                                            </div>
                                        </th>
                                        <th class="text-center" style="width: 10%">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input" type="checkbox" id="checkAllExcluir" 
                                                       onchange="marcarTodos('excluir', this.checked)">
                                                <label class="form-check-label">Excluir</label>
                                            </div>
                                        </th>
                                        <th class="text-center" style="width: 10%">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input" type="checkbox" id="checkAllExportar" 
                                                       onchange="marcarTodos('exportar', this.checked)">
                                                <label class="form-check-label">Exportar</label>
                                            </div>
                                        </th>
                                        <th class="text-center" style="width: 10%">
                                            <div class="form-check d-inline-block">
                                                <input class="form-check-input" type="checkbox" id="checkAllImportar" 
                                                       onchange="marcarTodos('importar', this.checked)">
                                                <label class="form-check-label">Importar</label>
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="permissoesTableBody">
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Carregando...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Placeholder quando nenhum grupo selecionado -->
        <div class="row" id="placeholderContainer">
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h4>Selecione um grupo para gerenciar suas permissões</h4>
                    <p class="mb-0">Use o seletor acima para escolher um grupo e visualizar/editar suas permissões</p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal de Copiar Permissões -->
<div class="modal fade" id="modalCopiar" tabindex="-1" aria-labelledby="modalCopiarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCopiarLabel">Copiar Permissões</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Copiar permissões de:</p>
                <select class="form-select mb-3" id="grupoOrigemCopia">
                    <option value="">-- Selecione o grupo de origem --</option>
                </select>
                <p class="text-muted">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Esta ação irá sobrescrever todas as permissões do grupo atual.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarCopia()">Copiar</button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
<script>
const podeEditar = <?php echo $podeEditar ? 'true' : 'false'; ?>;
</script>
<script src="../js/permissoes.js"></script>

<style>
.table-responsive {
    max-height: 70vh;
    overflow-y: auto;
}

.table-bordered thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 10;
}

.form-check-input {
    cursor: pointer;
}

.form-check-input:disabled {
    cursor: not-allowed;
}

.modulo-header {
    background-color: #e9ecef;
    font-weight: bold;
}

.modulo-header td {
    padding: 0.75rem;
}

tr:hover {
    background-color: #f8f9fa;
}
</style>

</body>
</html>
