<?php
require_once '../config/cache_control.php'; // Controle de cache e sessão
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

// Verificar licença do cliente
require_once '../config/license_checker.php';
$clienteId = $_SESSION['cliente_id'] ?? null;
$grupoId = $_SESSION['grupoId'] ?? null;
$statusLicenca = LicenseChecker::verificarEBloquear($clienteId, $grupoId);

// Controle de acesso para veículos
$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('veiculos', 'acessar');

$podeCriar = $accessControl->verificarPermissao('veiculos', 'criar');
$podeVisualizar = $accessControl->verificarPermissao('veiculos', 'visualizar');
$podeEditar = $accessControl->verificarPermissao('veiculos', 'editar');
$podeExcluir = $accessControl->verificarPermissao('veiculos', 'excluir');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Veículos - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content veiculo">
        <div class="page-title">
            <span><i class="fas fa-car"></i> Gestão de Veículos</span>
        </div>

        <div class="search-container">
            <div class="input-group search-input-group">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar por placa, modelo ou marca...">
                <button class="btn btn-secondary btn-sm" type="button"><i class="fas fa-search"></i></button>
            </div>
            <?php if ($podeCriar): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalVeiculo">
                    <i class="fas fa-plus"></i> Novo Veículo
                </button>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Modelo</th>
                        <th>Marca</th>
                        <th>Ano</th>
                        <th>Setor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="veiculosTable">
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

    <!-- Modal para criar/editar veículo -->
    <div class="modal fade" id="modalVeiculo" tabindex="-1">
        <div class="modal-dialog modal-dialog-wide">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo Veículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="veiculoForm">
                        <input type="hidden" id="veiculoId" name="id">
                        
                        <!-- Abas de Navegação -->
                        <ul class="nav nav-tabs mb-3" id="veiculoTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="dados-basicos-tab" data-bs-toggle="tab" data-bs-target="#dadosBasicos" type="button" role="tab">
                                    <i class="fas fa-car"></i> Dados Básicos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="operacional-tab" data-bs-toggle="tab" data-bs-target="#operacional" type="button" role="tab">
                                    <i class="fas fa-cogs"></i> Características e Operacional
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="documentacao-tab" data-bs-toggle="tab" data-bs-target="#documentacao" type="button" role="tab">
                                    <i class="fas fa-file-alt"></i> Documentação
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="combustiveis-tab" data-bs-toggle="tab" data-bs-target="#combustiveis" type="button" role="tab">
                                    <i class="fas fa-gas-pump"></i> Combustíveis
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qrcode-tab" data-bs-toggle="tab" data-bs-target="#qrcode" type="button" role="tab">
                                    <i class="fas fa-qrcode"></i> Via QR Code
                                </button>
                            </li>
                        </ul>

                        <!-- Conteúdo das Abas -->
                        <div class="tab-content" id="veiculoTabContent">
                            <!-- Aba Dados Básicos -->
                            <div class="tab-pane fade show active" id="dadosBasicos" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="placa">Placa *</label>
                                            <input type="text" class="form-control" id="placa" name="placa" required placeholder="ABC-1234" maxlength="8">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="placa_patrimonio">Placa Patrimônio</label>
                                            <input type="text" class="form-control" id="placa_patrimonio" name="placa_patrimonio" maxlength="15">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="ano">Ano *</label>
                                            <input type="number" class="form-control" id="ano" name="ano" required min="1900" max="2100" placeholder="2024">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="modelo">Modelo *</label>
                                            <input type="text" class="form-control" id="modelo" name="modelo" required placeholder="Ex: Gol 1.0" maxlength="45">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="id_marca_veiculo">Marca *</label>
                                            <select class="form-control" id="id_marca_veiculo" name="id_marca_veiculo" required>
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="id_cor_veiculo">Cor *</label>
                                            <select class="form-control" id="id_cor_veiculo" name="id_cor_veiculo" required>
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_tp_veiculo">Tipo de Veículo *</label>
                                            <select class="form-control" id="id_tp_veiculo" name="id_tp_veiculo" required>
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_cat_cnh">Categoria CNH</label>
                                            <select class="form-control" id="id_cat_cnh" name="id_cat_cnh">
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Documentação -->
                            <div class="tab-pane fade" id="documentacao" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="chassi">Chassi</label>
                                            <input type="text" class="form-control" id="chassi" name="chassi" maxlength="45" placeholder="9BWZZZ377VT004251">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="renavam">RENAVAM</label>
                                            <input type="text" class="form-control" id="renavam" name="renavam" maxlength="15" placeholder="00000000000">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="data_aquisicao">Data de Aquisição</label>
                                            <input type="date" class="form-control" id="data_aquisicao" name="data_aquisicao">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Características e Operacional -->
                            <div class="tab-pane fade" id="operacional" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="capacidade_combustivel">Capacidade (L) *</label>
                                            <input type="number" class="form-control" id="capacidade_combustivel" name="capacidade_combustivel" required min="0" value="9999">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="id_setor">Setor *</label>
                                            <select class="form-control" id="id_setor" name="id_setor" required>
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="id_forma_trabalho">Forma de Trabalho *</label>
                                            <select class="form-control" id="id_forma_trabalho" name="id_forma_trabalho" required>
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="id_situacao">Situação *</label>
                                            <select class="form-control" id="id_situacao" name="id_situacao" required>
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="atividade">Atividade</label>
                                            <input type="text" class="form-control" id="atividade" name="atividade" maxlength="100" placeholder="Ex: Transporte de cargas">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="tel_seguradora">Tel. Seguradora</label>
                                            <input type="text" class="form-control" id="tel_seguradora" name="tel_seguradora" maxlength="15" placeholder="(00) 0000-0000">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mt-4">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="tem_seguro" name="tem_seguro" value="1">
                                                <label class="form-check-label" for="tem_seguro">Possui Seguro</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" id="controle-km-hora">
                                    <div class="col-md-6" id="campo-km-inicial" style="display: none;">
                                        <div class="form-group">
                                            <label for="km_inicial_operacional">KM Inicial *</label>
                                            <input type="number" class="form-control" id="km_inicial_operacional" name="km_inicial_operacional" min="0" placeholder="0">
                                            <small class="form-text text-muted">Preencha o KM inicial do veículo</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6" id="campo-hora-inicial" style="display: none;">
                                        <div class="form-group">
                                            <label for="hora_inicial_operacional">Hora Inicial (Horímetro) *</label>
                                            <input type="number" class="form-control" id="hora_inicial_operacional" name="hora_inicial_operacional" min="0" placeholder="0">
                                            <small class="form-text text-muted">Preencha a hora inicial do horímetro</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6" style="display: none;">
                                        <div class="form-group">
                                            <label for="id_situacao_old">Situação *</label>
                                            <select class="form-control" id="id_situacao_old" name="id_situacao_old">
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Combustíveis -->
                            <div class="tab-pane fade" id="combustiveis" role="tabpanel">
                                <div id="combustiveis-message" class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Salve o veículo primeiro para gerenciar os combustíveis aceitos.
                                </div>
                                <div id="combustiveis-content" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Selecione os combustíveis que este veículo pode utilizar:</label>
                                                <div id="combustiveis-list" class="mt-2">
                                                    <!-- Carregado via AJAX -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-success btn-sm" onclick="salvarCombustiveis()">
                                                <i class="fas fa-save"></i> Salvar Combustíveis
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Via QR Code -->
                            <div class="tab-pane fade" id="qrcode" role="tabpanel">
                                <div class="row" id="qrcode-section" style="display: none;">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-qrcode"></i> Gerenciamento de QR Code</h6>
                                            <p class="mb-2"><small>O QR Code é gerado automaticamente quando o veículo é criado ou reativado. Você pode gerar uma nova via manualmente se necessário.</small></p>
                                            <div id="qrcode-info"></div>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="gerarNovaViaVeiculo()">
                                                    <i class="fas fa-plus"></i> Gerar Nova Via
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="verHistoricoQRCode()">
                                                    <i class="fas fa-history"></i> Ver Histórico
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="qrcode-vazio">
                                    <div class="col-md-12">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-info-circle"></i> Salve o veículo primeiro para gerenciar o QR Code.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para visualizar veículo -->
    <div class="modal fade" id="modalVisualizacao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visualizar Veículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Placa</label>
                                <input type="text" class="form-control" id="vizPlaca" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Modelo</label>
                                <input type="text" class="form-control" id="vizModelo" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ano</label>
                                <input type="text" class="form-control" id="vizAno" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Marca</label>
                                <input type="text" class="form-control" id="vizMarca" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Cor</label>
                                <input type="text" class="form-control" id="vizCor" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de Veículo</label>
                                <input type="text" class="form-control" id="vizTipoVeiculo" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Setor</label>
                                <input type="text" class="form-control" id="vizSetor" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Chassi</label>
                                <input type="text" class="form-control" id="vizChassi" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>RENAVAM</label>
                                <input type="text" class="form-control" id="vizRenavam" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Capacidade (L)</label>
                                <input type="text" class="form-control" id="vizCapacidade" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>KM Inicial</label>
                                <input type="text" class="form-control" id="vizKmInicial" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Situação</label>
                                <input type="text" class="form-control" id="vizSituacao" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Permissões vindas do PHP para o JavaScript
        const podeCriarVeiculo = <?php echo $podeCriar ? 'true' : 'false'; ?>;
        const podeVisualizarVeiculo = <?php echo $podeVisualizar ? 'true' : 'false'; ?>;
        const podeEditarVeiculo = <?php echo $podeEditar ? 'true' : 'false'; ?>;
        const podeExcluirVeiculo = <?php echo $podeExcluir ? 'true' : 'false'; ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/veiculo.js"></script>
</body>
</html>