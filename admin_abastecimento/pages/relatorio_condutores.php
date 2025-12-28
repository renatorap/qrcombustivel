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
$accessControl->requerPermissao('condutor', 'acessar');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Condutores - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .filter-section h5 {
            color: #2f6b8f;
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 1rem;
        }
        
        .results-section {
            background: #ffffff;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #ced4da;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .group-header {
            background: #e9ecef;
            padding: 10px 15px;
            font-weight: bold;
            border-left: 4px solid #2f6b8f;
            margin-top: 10px;
            color: #2f6b8f;
        }
        .condutor-row {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }
        .condutor-row:hover {
            background-color: #f8f9fa;
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
        .export-dropdown .dropdown-menu {
            min-width: 150px;
        }
        .autocomplete-dropdown {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        .autocomplete-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        .autocomplete-item:hover {
            background-color: #f8f9fa;
        }
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        .autocomplete-item strong {
            color: #667eea;
        }
        .badge-expirado {
            background-color: #dc3545;
        }
        .badge-a-expirar {
            background-color: #ffc107;
            color: #000;
        }
        .badge-valido {
            background-color: #28a745;
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

    <div class="main-content condutor">
        <div class="page-title">
            <span><i class="fas fa-id-card"></i> Relatório de Condutores</span>
        </div>

        <!-- Seção de Filtros -->
        <div class="filter-section no-print" id="filterSection">
            <h5 class="mb-3"><i class="fas fa-filter"></i> Filtros de Pesquisa</h5>
            <form id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group" style="position: relative;">
                            <label for="filtroNome">Nome</label>
                            <input type="text" class="form-control" id="filtroNome" placeholder="Nome do condutor" autocomplete="off">
                            <div id="nomeAutocomplete" class="autocomplete-dropdown"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group" style="position: relative;">
                            <label for="filtroCNH">CNH</label>
                            <input type="text" class="form-control" id="filtroCNH" placeholder="Número da CNH" autocomplete="off">
                            <div id="cnhAutocomplete" class="autocomplete-dropdown"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filtroValidadeCNH">Validade CNH</label>
                            <select class="form-select" id="filtroValidadeCNH">
                                <option value="">Todos</option>
                                <option value="valida">Válida</option>
                                <option value="a_expirar">A Expirar (30 dias)</option>
                                <option value="expirado">Expirado</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filtroSituacao">Situação</label>
                            <select class="form-select" id="filtroSituacao">
                                <option value="">Todos</option>
                                <option value="1">Ativo</option>
                                <option value="2">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Pesquisar
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="limparFiltros()">
                            <i class="fas fa-eraser"></i> Limpar Filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Seção de Resultados -->
        <div class="results-section" id="resultsSection" style="display: none;">
            <!-- Botões de Ação -->
            <div class="action-buttons no-print">
                <button class="btn btn-secondary btn-sm" onclick="voltarPesquisa()">
                    <i class="fas fa-arrow-left"></i>&nbsp;&nbsp;Voltar à Pesquisa
                </button>
                
                <div class="dropdown">
                    <button class="btn btn-info btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-sort"></i>&nbsp;&nbsp;Ordenar
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="ordenar('nome')">Por Nome</a></li>
                        <li><a class="dropdown-item" href="#" onclick="ordenar('cnh')">Por CNH</a></li>
                        <li><a class="dropdown-item" href="#" onclick="ordenar('validade_cnh')">Por Validade CNH</a></li>
                    </ul>
                </div>
                
                <div class="dropdown">
                    <button class="btn btn-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-layer-group"></i>&nbsp;&nbsp;Agrupar
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="agrupar('none')">Sem Agrupamento</a></li>
                        <li><a class="dropdown-item" href="#" onclick="agrupar('situacao')">Por Situação</a></li>
                        <li><a class="dropdown-item" href="#" onclick="agrupar('status_cnh')">Por Status CNH</a></li>
                    </ul>
                </div>
                
                <div class="dropdown export-dropdown">
                    <button class="btn btn-danger btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-file-export"></i>&nbsp;&nbsp;Exportar
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="exportar('pdf')">
                            <i class="fas fa-file-pdf text-danger"></i> PDF
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportar('csv')">
                            <i class="fas fa-file-csv text-success"></i> CSV
                        </a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportar('xls')">
                            <i class="fas fa-file-excel text-success"></i> Excel
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Grid de Resultados -->
            <div id="resultsGrid">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/relatorio_condutores.js"></script>
</body>
</html>
