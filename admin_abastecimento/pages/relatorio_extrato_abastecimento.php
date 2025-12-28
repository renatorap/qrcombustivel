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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extrato de Abastecimento - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f6f7;
            font-family: 'Inter', 'Roboto', 'Open Sans', sans-serif;
        }
        
        .filter-section {
            background: #ffffff;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 12px;
        }
        
        .filters-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            align-items: start;
            margin-bottom: 16px;
        }
        
        .filters-container.two-cols {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .filters-container.full-width {
            grid-template-columns: 1fr;
        }
        
        .filters-container.four-cols {
            grid-template-columns: repeat(4, 1fr);
        }
        
        @media (max-width: 992px) {
            .filters-container {
                grid-template-columns: 1fr 1fr;
            }
            .filters-container.four-cols {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .filters-container {
                grid-template-columns: 1fr;
            }
            .filters-container.four-cols {
                grid-template-columns: 1fr;
            }
        }
        
        .filter-section h5 {
            color: #2f6b8f;
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 1rem;
        }
        
        .filter-section label {
            margin-left: 7px;
            margin-bottom: 4px;
            color: #495057;
            font-weight: 500;
        }
        
        .filter-section .form-group {
            margin-bottom: 8px;
        }
        
        .form-control-sm {
            padding: 0.25rem 0.4rem !important;
            background: #f8f9fa;
            border: 1px solid #c1c3c7;
            border-radius: 4px;
        }
        
        .form-control-sm:focus {
            border-color: #2f6b8f;
            box-shadow: 0 0 4px rgba(27,81,117,0.3);
            background: #ffffff;
        }
        
        select.form-control-sm[multiple] {
            height: auto !important;
        }
        
        .filter-group {
            background: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 0;
            min-height: 110px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .filter-group.tall {
            min-height: 140px;
        }
        
        .filter-group.auto-height {
            min-height: auto;
        }
        
        .filter-group-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #2f6b8f;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-left: 4px solid #f59b4c;
            padding-left: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .filter-group-title i {
            font-size: 0.9rem;
        }
        
        .results-section {
            background: #ffffff;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .stats-card {
            background: #ffffff;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            text-align: center;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .stats-card h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: #2f6b8f;
            font-weight: 700;
        }
        
        .stats-card small {
            color: #495057;
        }
        
        .btn-primary {
            background: #2f6b8f;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            background: #163f5a;
        }
        
        .btn-secondary {
            background: #f59b4c;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-weight: 600;
        }
        
        .btn-secondary:hover {
            background: #d96a1f;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .action-buttons button,
        .action-buttons .dropdown button {
            min-width: 150px;
        }
        
        .actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            margin-top: auto;
        }
        
        .actions .btn {
            flex: 1;
        }
        
        .quebra-header {
            background: #e9ecef;
            padding: 10px 15px;
            font-weight: bold;
            border-left: 4px solid #2f6b8f;
            margin-top: 10px;
            color: #2f6b8f;
        }
        
        .quebra-total {
            background: #f8f9fa;
            padding: 8px 15px;
            font-weight: bold;
            border-top: 2px solid #dee2e6;
        }
        
        .total-geral {
            background: #2f6b8f;
            color: #ffffff;
            padding: 12px 15px;
            font-weight: bold;
            margin-top: 15px;
        }
        
        .autocomplete-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #e9ecef;
        }
        
        .autocomplete-item:hover {
            background-color: #f8f9fa;
        }
        
        .autocomplete-list {
            position: absolute;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            width: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .results-section {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-title">
            <span><i class="fas fa-file-invoice"></i> Extrato de Abastecimento</span>
        </div>

        <!-- Seção de Filtros -->
        <div class="filter-section no-print" id="filterSection">
            <h5 class="mb-3"><i class="fas fa-filter"></i> Filtros de Pesquisa</h5>
            <form id="filterForm">
                <!-- Linha 1: Filtros de Data e Hora -->
                <div class="filters-container two-cols">
                    <!-- Grupo: Filtro de Data -->
                    <div class="filter-group auto-height">
                        <div class="filter-group-title"><i class="fas fa-calendar"></i> Filtro de Data</div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipoData" class="small">Tipo</label>
                                    <select class="form-control form-control-sm" id="tipoData" name="tipoData">
                                        <option value="intervalo">Intervalo</option>
                                        <option value="unica">Única</option>
                                        <option value="maior">Maior que</option>
                                        <option value="menor">Menor que</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4" id="dataInicioDiv">
                                <div class="form-group">
                                    <label for="dataInicio" class="small">Data Início</label>
                                    <input type="date" class="form-control form-control-sm" id="dataInicio" name="dataInicio">
                                </div>
                            </div>

                            <div class="col-md-4" id="dataFimDiv">
                                <div class="form-group">
                                    <label for="dataFim" class="small">Data Fim</label>
                                    <input type="date" class="form-control form-control-sm" id="dataFim" name="dataFim">
                                </div>
                            </div>

                            <div class="col-md-4" id="dataUnicaDiv" style="display: none;">
                                <div class="form-group">
                                    <label for="dataUnica" class="small">Data</label>
                                    <input type="date" class="form-control form-control-sm" id="dataUnica" name="dataUnica">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grupo: Filtro de Hora -->
                    <div class="filter-group auto-height">
                        <div class="filter-group-title"><i class="fas fa-clock"></i> Filtro de Hora</div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipoHora" class="small">Tipo</label>
                                    <select class="form-control form-control-sm" id="tipoHora" name="tipoHora">
                                        <option value="intervalo">Intervalo</option>
                                        <option value="maior">Maior que</option>
                                        <option value="menor">Menor que</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4" id="horaInicioDiv">
                                <div class="form-group">
                                    <label for="horaInicio" class="small">Hora Início</label>
                                    <input type="time" class="form-control form-control-sm" id="horaInicio" name="horaInicio">
                                </div>
                            </div>

                            <div class="col-md-4" id="horaFimDiv">
                                <div class="form-group">
                                    <label for="horaFim" class="small">Hora Fim</label>
                                    <input type="time" class="form-control form-control-sm" id="horaFim" name="horaFim">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Linha 2: Selects Múltiplos e Orientação PDF -->
                <div class="filters-container four-cols">
                    <!-- Grupo: Fornecedor -->
                    <div class="filter-group auto-height">
                        <div class="filter-group-title"><i class="fas fa-building"></i> Fornecedor</div>
                        <select class="form-control form-control-sm" id="fornecedor" name="fornecedor[]" multiple size="4">
                        </select>
                    </div>

                    <!-- Grupo: Setor -->
                    <div class="filter-group auto-height">
                        <div class="filter-group-title"><i class="fas fa-sitemap"></i> Setor</div>
                        <select class="form-control form-control-sm" id="setor" name="setor[]" multiple size="4">
                        </select>
                    </div>

                    <!-- Grupo: Produto -->
                    <div class="filter-group auto-height">
                        <div class="filter-group-title"><i class="fas fa-gas-pump"></i> Produto</div>
                        <select class="form-control form-control-sm" id="produto" name="produto[]" multiple size="4">
                        </select>
                    </div>
                    
                    <!-- Grupo: Orientação do PDF -->
                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-file-pdf"></i> Orientação do PDF</div>
                        <select class="form-control form-control-sm" id="orientacao" name="orientacao">
                            <option value="paisagem" selected>Paisagem (Horizontal)</option>
                            <option value="retrato">Retrato (Vertical)</option>
                        </select>
                        <small class="text-muted">Define layout do PDF exportado</small>
                    </div>
                </div>

                <!-- Linha 3: Colunas, Placa/Condutor e Botões -->
                <div class="filters-container four-cols">
                    <div class="filter-group tall">
                        <div class="filter-group-title"><i class="fas fa-columns"></i> Colunas a Exibir</div>
                        <select class="form-control form-control-sm" id="colunas" name="colunas[]" multiple size="6">
                            <option value="data" selected>Data</option>
                            <option value="hora" selected>Hora</option>
                            <option value="placa" selected>Placa</option>
                            <option value="condutor" selected>Condutor</option>
                            <option value="setor" selected>Setor</option>
                            <option value="fornecedor" selected>Fornecedor</option>
                            <option value="produto" selected>Produto</option>
                            <option value="km_atual" selected>KM Atual</option>
                            <option value="km_ant" selected>KM Anterior</option>
                            <option value="km_rodado" selected>KM Rodado</option>
                            <option value="litros" selected>Litros</option>
                            <option value="km_litro" selected>KM/L</option>
                            <option value="vl_unit" selected>Vl. Unitário</option>
                            <option value="vl_total" selected>Vl. Total</option>
                        </select>
                        <small class="text-muted">Ctrl+Clique para selecionar múltiplas colunas</small>
                    </div>

                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-car"></i> Placa</div>
                        <input type="text" class="form-control form-control-sm" id="placa" name="placa" placeholder="Digite a placa..." autocomplete="off">
                        <div id="placaList" class="autocomplete-list" style="display: none;"></div>
                    </div>

                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-user"></i> Condutor</div>
                        <input type="text" class="form-control form-control-sm" id="condutor" name="condutor" placeholder="Digite o nome..." autocomplete="off">
                        <input type="hidden" id="condutorId" name="condutorId">
                        <div id="condutorList" class="autocomplete-list" style="display: none;"></div>
                    </div>

                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-cogs"></i> Ações</div>
                        <div class="actions">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnLimpar">
                                <i class="fas fa-eraser"></i> Limpar
                            </button>
                        </div>
                    </div>
                </div>


            </form>
        </div>

        <!-- Resumo de Estatísticas -->
        <div class="results-section no-print" id="statsSection" style="display: none;">
            <div class="row">
                <div class="col-md-2">
                    <div class="stats-card">
                        <h3 id="totalAbastecimentos">0</h3>
                        <small>Abastecimentos</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card">
                        <h3 id="totalLitros">0</h3>
                        <small>Litros</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card">
                        <h3 id="totalValor">R$ 0,00</h3>
                        <small>Valor Total</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card">
                        <h3 id="mediaValor">R$ 0,00</h3>
                        <small>Valor Médio</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card">
                        <h3 id="totalKmRodado">0</h3>
                        <small>KM Rodados</small>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stats-card">
                        <h3 id="mediaKmLitro">0,00</h3>
                        <small>Média KM/L</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros Pós-Pesquisa -->
        <div class="filter-section no-print" id="postSearchFilters" style="display: none;">
            <div class="row">
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-layer-group"></i> Agrupamento</div>
                        <select class="form-control form-control-sm" id="quebra" name="quebra[]" multiple size="4">
                            <option value="fornecedor">Fornecedor</option>
                            <option value="setor">Setor</option>
                            <option value="produto">Produto</option>
                            <option value="placa">Placa</option>
                        </select>
                        <small class="text-muted">Ctrl+Clique para selecionar múltiplos agrupamentos</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-sort"></i> Ordenação</div>
                        <select class="form-control form-control-sm" id="ordenacao" name="ordenacao">
                            <option value="data_asc">Data (Crescente)</option>
                            <option value="data_desc">Data (Decrescente)</option>
                            <option value="placa_asc">Placa (A-Z)</option>
                            <option value="placa_desc">Placa (Z-A)</option>
                            <option value="fornecedor_asc">Fornecedor (A-Z)</option>
                            <option value="fornecedor_desc">Fornecedor (Z-A)</option>
                            <option value="setor_asc">Setor (A-Z)</option>
                            <option value="setor_desc">Setor (Z-A)</option>
                            <option value="produto_asc">Produto (A-Z)</option>
                            <option value="produto_desc">Produto (Z-A)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-group-title"><i class="fas fa-sync-alt"></i> Ações</div>
                        <button type="button" class="btn btn-primary btn-sm" id="btnAplicarFiltros">
                            <i class="fas fa-check"></i> Aplicar Filtros
                        </button>
                        <small class="text-muted d-block mt-2">Altere agrupamento ou ordenação e clique para atualizar</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="action-buttons no-print" id="actionButtons" style="display: none;">
            <button class="btn btn-secondary btn-sm" onclick="voltarPesquisa()">
                <i class="fas fa-arrow-left"></i>&nbsp;&nbsp;Voltar à Pesquisa
            </button>
            
            <div class="dropdown export-dropdown">
                <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-download"></i>&nbsp;&nbsp;Exportar
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" id="btnExportarExcel"><i class="fas fa-file-excel"></i> Excel</a></li>
                    <li><a class="dropdown-item" href="#" id="btnExportarPDF"><i class="fas fa-file-pdf"></i> PDF</a></li>
                </ul>
            </div>
        </div>

        <!-- Seção de Resultados -->
        <div class="results-section" id="resultsSection" style="display: none;">
            <h5 class="mb-3"><i class="fas fa-list"></i> Extrato de Abastecimento</h5>
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="resultsTable">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Hora</th>
                            <th>Placa</th>
                            <th>Condutor</th>
                            <th>Setor</th>
                            <th>Fornecedor</th>
                            <th>Produto</th>
                            <th class="text-end">KM Atual</th>
                            <th class="text-end">KM Ant.</th>
                            <th class="text-end">KM Rodado</th>
                            <th class="text-end">Litros</th>
                            <th class="text-end">KM/L</th>
                            <th class="text-end">Vl. Unit.</th>
                            <th class="text-end">Vl. Total</th>
                        </tr>
                    </thead>
                    <tbody id="resultsBody">
                        <!-- Preenchido via JavaScript -->
                    </tbody>
                </table>
            </div>

            <div id="paginacao" class="d-flex justify-content-center mt-3">
                <!-- Paginação -->
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/relatorio_extrato_abastecimento.js"></script>
</body>
</html>
