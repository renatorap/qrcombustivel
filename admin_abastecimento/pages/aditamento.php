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
$accessControl->requerPermissao('aditamento', 'acessar');

$podeCriar = $accessControl->verificarPermissao('aditamento', 'criar');
$podeEditar = $accessControl->verificarPermissao('aditamento', 'editar');
$podeExcluir = $accessControl->verificarPermissao('aditamento', 'excluir');
$podeVisualizar = $accessControl->verificarPermissao('aditamento', 'visualizar');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Aditamentos - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content veiculo">
        <div class="page-title">
            <span><i class="fas fa-file-signature"></i> Gestão de Aditamentos de Preço</span>
        </div>

        <div class="search-container">
            <div class="input-group search-input-group">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar por código, descrição ou contrato...">
                <button class="btn btn-secondary btn-sm" type="button"><i class="fas fa-search"></i></button>
            </div>
            <select id="statusFilter" class="form-select form-select-sm" style="max-width: 180px;">
                <option value="">Todos os Status</option>
                <option value="ativo">Ativos</option>
                <option value="futuro">Futuros</option>
                <option value="encerrado">Encerrados</option>
                <option value="sem_precos">Sem Preços</option>
            </select>
            <?php if ($podeCriar): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAditamento" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Novo Aditamento
                </button>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Contrato</th>
                        <th>Fornecedor</th>
                        <th>Data</th>
                        <th>Vigência</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="aditamentosTable">
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

    <!-- Modal para criar/editar aditamento -->
    <div class="modal fade" id="modalAditamento" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo Aditamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="aditamentoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
                                <i class="fas fa-file-signature"></i> Dados do Aditamento
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="precos-tab" data-bs-toggle="tab" data-bs-target="#precos" type="button" role="tab">
                                <i class="fas fa-dollar-sign"></i> Preços de Combustíveis
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="aditamentoTabContent">
                        <!-- Aba Dados do Aditamento -->
                        <div class="tab-pane fade show active" id="dados" role="tabpanel">
                            <form id="aditamentoForm">
                                <input type="hidden" id="aditamentoId" name="id">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_licitacao">Licitação *</label>
                                            <select class="form-select" id="id_licitacao" name="id_licitacao" required onchange="carregarContratos()">
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_contrato">Contrato *</label>
                                            <select class="form-select" id="id_contrato" name="id_contrato" required>
                                                <option value="">Selecione uma licitação primeiro...</option>
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
                                            <label for="data">Data do Aditamento *</label>
                                            <input type="date" class="form-control" id="data" name="data" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="descricao">Descrição *</label>
                                            <input type="text" class="form-control" id="descricao" name="descricao" required maxlength="120">
                                            <small class="form-text text-muted">Ex: Aditamento Inicial, Reajuste 1º Trimestre, etc.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 text-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Salvar Aditamento
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Aba Preços de Combustíveis -->
                        <div class="tab-pane fade" id="precos" role="tabpanel">
                            <div class="alert alert-info" id="infoSalvarAditamento">
                                <i class="fas fa-info-circle"></i> Salve o aditamento primeiro para cadastrar os preços dos combustíveis.
                            </div>
                            <div id="precosContent">
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Atenção:</strong> Ao cadastrar novos preços com vigência futura, os preços atuais continuarão válidos até a data de início da nova vigência.
                                </div>
                                
                                <!-- Formulário de Inserção em Massa -->
                                <div class="card mb-3" id="formNovosPrecos" style="display: none;">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-plus-circle"></i> Cadastrar Novos Preços
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label"><strong>Início da Vigência *</strong></label>
                                                <input type="datetime-local" class="form-control" id="inicioVigenciaGeral">
                                                <small class="text-muted">Este período será aplicado a todos os produtos</small>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 40%;">Combustível</th>
                                                        <th style="width: 20%;">Unidade</th>
                                                        <th style="width: 30%;">Valor (R$) *</th>
                                                        <th style="width: 10%; text-align: center;">
                                                            <input type="checkbox" id="checkAllProdutos" title="Selecionar todos">
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody id="produtosPrecoTable">
                                                    <!-- Carregado via AJAX -->
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-secondary" onclick="cancelarNovosPrecos()">
                                                <i class="fas fa-times"></i> Cancelar
                                            </button>
                                            <button type="button" class="btn btn-success" onclick="salvarNovosPrecos()">
                                                <i class="fas fa-save"></i> Salvar Preços Selecionados
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="btnAdicionarPreco">
                                    <button type="button" class="btn btn-sm btn-success" onclick="mostrarFormNovosPrecos()">
                                        <i class="fas fa-plus"></i> Cadastrar Novos Preços
                                    </button>
                                </div>
                                
                                <h6><strong>Preços Cadastrados</strong></h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Combustível</th>
                                                <th>Valor (R$)</th>
                                                <th>Início Vigência</th>
                                                <th>Fim Vigência</th>
                                                <th>Status</th>
                                                <th style="width: 100px;">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody id="precosTable">
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
        const podeCriarAditamento = <?php echo $podeCriar ? 'true' : 'false'; ?>;
        const podeEditarAditamento = <?php echo $podeEditar ? 'true' : 'false'; ?>;
        const podeExcluirAditamento = <?php echo $podeExcluir ? 'true' : 'false'; ?>;
        const podeVisualizarAditamento = <?php echo $podeVisualizar ? 'true' : 'false'; ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/aditamento.js"></script>
</body>
</html>
