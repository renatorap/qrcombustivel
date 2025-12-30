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
$accessControl->requerPermissao('contrato', 'acessar');

$podeCriar = $accessControl->verificarPermissao('contrato', 'criar');
$podeEditar = $accessControl->verificarPermissao('contrato', 'editar');
$podeExcluir = $accessControl->verificarPermissao('contrato', 'excluir');
$podeVisualizar = $accessControl->verificarPermissao('contrato', 'visualizar');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Contratos - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content veiculo">
        <div class="page-title">
            <span><i class="fas fa-file-contract"></i> Gestão de Contratos</span>
        </div>

        <div class="mb-2" style="display: flex; gap: 10px; align-items: center;">
            <div class="input-group" style="max-width:720px;">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar por código, descrição ou fornecedor...">
                <button class="btn btn-secondary btn-sm" type="button"><i class="fas fa-search"></i></button>
            </div>
            <?php if ($podeCriar): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalContrato" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Novo Contrato
                </button>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Licitação</th>
                        <th>Fornecedor</th>
                        <th>Descrição</th>
                        <th>Data</th>
                        <th>Aditamentos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="contratosTable">
                    <!-- Carregado via AJAX -->
                </tbody>
            </table>
        </div>

        <nav aria-label="Paginação" style="margin-top: 10px; margin-bottom: 5px;">
            <ul class="pagination pagination-sm justify-content-center" id="paginacao">
                <!-- Paginação carregada via AJAX -->
            </ul>
        </nav>
    </div>

    <!-- Modal para criar/editar contrato -->
    <div class="modal fade" id="modalContrato" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo Contrato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="contratoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
                                <i class="fas fa-file-contract"></i> Dados do Contrato
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="produtos-tab" data-bs-toggle="tab" data-bs-target="#produtos" type="button" role="tab">
                                <i class="fas fa-gas-pump"></i> Produtos
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="contratoTabContent">
                        <!-- Aba Dados do Contrato -->
                        <div class="tab-pane fade show active" id="dados" role="tabpanel">
                            <form id="contratoForm">
                                <input type="hidden" id="contratoId" name="id">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_licitacao">Licitação *</label>
                                            <select class="form-select" id="id_licitacao" name="id_licitacao" required>
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_fornecedor">Fornecedor *</label>
                                            <select class="form-select" id="id_fornecedor" name="id_fornecedor" required>
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="codigo">Código *</label>
                                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="data">Data *</label>
                                            <input type="date" class="form-control" id="data" name="data" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="descricao">Descrição *</label>
                                            <input type="text" class="form-control" id="descricao" name="descricao" required maxlength="100">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvar Contrato
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Aba Produtos -->
                        <div class="tab-pane fade" id="produtos" role="tabpanel">
                            <div class="alert alert-info" id="infoSalvarContrato">
                                <i class="fas fa-info-circle"></i> Salve o contrato primeiro para vincular produtos.
                            </div>
                            <div id="produtosContent">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Produtos Disponíveis</strong></label>
                                    <div class="input-group">
                                        <select class="form-select" id="produtoSelect">
                                            <option value="">Selecione um produto para vincular...</option>
                                        </select>
                                        <button type="button" class="btn btn-success" onclick="vincularProduto()">
                                            <i class="fas fa-plus"></i> Vincular
                                        </button>
                                    </div>
                                </div>
                                <hr>
                                <h6><strong>Produtos Vinculados</strong></h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-modern">
                                        <thead>
                                            <tr>
                                                <th>Produto</th>
                                                <th>Unidade</th>
                                                <th>Data Vínculo</th>
                                                <th style="width: 100px;">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="produtosVinculadosTable">
                                            <!-- Carregado via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script>
        const podeCriarContrato = <?php echo $podeCriar ? 'true' : 'false'; ?>;
        const podeEditarContrato = <?php echo $podeEditar ? 'true' : 'false'; ?>;
        const podeExcluirContrato = <?php echo $podeExcluir ? 'true' : 'false'; ?>;
        const podeVisualizarContrato = <?php echo $podeVisualizar ? 'true' : 'false'; ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/contrato.js"></script>
</body>
</html>
