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

// Controle de acesso para condutores
$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('condutores', 'acessar');

$clienteId = $_SESSION['cliente_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Crachás - QR Combustível</title>
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
            margin-bottom: 40px;
        }
        
        .filter-section h5 {
            color: #2f6b8f;
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 1rem;
        }
        
        .filter-section .form-group {
            margin-bottom: 12px;
        }
        
        .filter-section label {
            margin-bottom: 4px;
            color: #495057;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .form-control {
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        
        .form-control:focus {
            border-color: #2f6b8f;
            box-shadow: 0 0 4px rgba(27,81,117,0.3);
        }
        
        .btn-primary {
            background: #2f6b8f;
            color: #ffffff;
            border: none;
            border-radius: 4px;
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
            font-weight: 600;
        }
        
        .btn-secondary:hover {
            background: #d96a1f;
        }
        .condutor-card {
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.2s;
            cursor: pointer;
            background: #ffffff;
        }
        .condutor-card:hover {
            background: #f8f9fa;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .condutor-card.selected {
            background: #e3f2fd;
            border-color: #2f6b8f;
        }
        .condutor-card input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .condutor-info {
            flex: 1;
            margin-left: 15px;
        }
        .condutor-nome {
            font-weight: bold;
            font-size: 1.1em;
            color: #212529;
        }
        .condutor-detalhes {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }
        .condutor-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            margin-right: 5px;
        }
        .badge-motorista {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-cargo {
            background: #fff3cd;
            color: #856404;
        }
        .selection-summary {
            position: sticky;
            top: 20px;
            background: #ffffff;
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .selection-summary h5 {
            color: #2f6b8f;
            font-weight: 600;
            font-size: 1rem;
        }
        .btn-print-multiple {
            width: 100%;
            margin-top: 10px;
        }
        #condutoresList {
            max-height: 600px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-title">
            <span><i class="fas fa-print"></i> Relatório de Crachás</span>
        </div>

        <div class="row">
            <div class="col-md-9">
                <!-- Filtros -->
                <div class="filter-section">
                    <h5><i class="fas fa-filter"></i> Filtros</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filtroNome">Nome</label>
                                <input type="text" class="form-control" id="filtroNome" placeholder="Digite o nome...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filtroCPF">CPF</label>
                                <input type="text" class="form-control" id="filtroCPF" placeholder="000.000.000-00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filtroCNH">CNH</label>
                                <input type="text" class="form-control" id="filtroCNH" placeholder="Digite a CNH...">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filtroSituacao">Situação</label>
                                <select class="form-control" id="filtroSituacao">
                                    <option value="">Todas</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="filtroMotorista">Tipo</label>
                                <select class="form-control" id="filtroMotorista">
                                    <option value="">Todos</option>
                                    <option value="1">Motoristas</option>
                                    <option value="0">Outros</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary flex-fill" onclick="aplicarFiltros()">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                    <button class="btn btn-secondary" onclick="limparFiltros()" title="Limpar Filtros">
                                        <i class="fas fa-eraser"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Condutores -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-users"></i> Condutores</h5>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="selecionarTodos()">
                                <i class="fas fa-check-double"></i> Selecionar Todos
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="limparSelecao()">
                                <i class="fas fa-times"></i> Limpar Seleção
                            </button>
                        </div>
                    </div>
                </div>

                <div id="condutoresList">
                    <!-- Carregado via AJAX -->
                </div>

                <div id="loadingIndicator" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando condutores...</p>
                </div>
            </div>

            <div class="col-md-3">
                <!-- Resumo da Seleção -->
                <div class="selection-summary">
                    <h5 class="mb-3"><i class="fas fa-clipboard-check"></i> Seleção</h5>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Selecionados:</span>
                            <strong id="totalSelecionados">0</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Total disponível:</span>
                            <strong id="totalDisponiveis">0</strong>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-print-multiple" onclick="imprimirSelecionados()">
                            <i class="fas fa-print"></i> Imprimir Selecionados
                        </button>
                        <button class="btn btn-primary btn-print-multiple" onclick="baixarPdfSelecionados()">
                            <i class="fas fa-file-pdf"></i> Baixar PDF Selecionados
                        </button>
                        <button class="btn btn-info btn-print-multiple" onclick="imprimirTodos()">
                            <i class="fas fa-print"></i> Imprimir Todos
                        </button>
                    </div>
                    
                    <div class="alert alert-info mt-3 mb-0" style="font-size: 0.85em;">
                        <i class="fas fa-info-circle"></i>
                        <small>Clique nos cards para selecionar/desselecionar condutores</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/relatorio_cracha.js"></script>
</body>
</html>
