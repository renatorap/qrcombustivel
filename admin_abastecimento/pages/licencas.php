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

// Apenas administradores podem acessar
$grupoId = $_SESSION['grupoId'] ?? null;
if ($grupoId != 1) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Licenças - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content licencas">
        <div class="page-title">
            <div>
                <h1>
                    <i class="fas fa-key"></i>Gerenciamento de Licenças
                </h1>
            </div>
        </div>

        <div class="licencas-filters">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGerarLicenca">
                <i class="fas fa-plus"></i> Gerar Nova Licença
            </button>
            
            <?php if ($_SESSION['grupoId'] == 1): ?>
            <select class="form-select form-select-sm" id="filtroCliente">
                <option value="">Todos os Clientes</option>
            </select>
            <?php endif; ?>
            
            <select class="form-select form-select-sm" id="filtroStatus">
                <option value="">Todos os Status</option>
                <option value="pendente">Pendente</option>
                <option value="ativa">Ativa</option>
                <option value="expirada">Expirada</option>
                <option value="cancelada">Cancelada</option>
            </select>
            
            <button class="btn btn-secondary btn-sm" onclick="carregarLicencas()">
                <i class="fas fa-sync"></i> Atualizar
            </button>
        </div>

        <div class="table-container">
            <table class="table table-enhanced">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Código</th>
                        <th>Data Geração</th>
                        <th>Data Ativação</th>
                        <th>Data Expiração</th>
                        <th>Status</th>
                        <th>Gerado Por</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="licencasTable">
                    <tr>
                        <td colspan="8" class="text-center">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Gerar Licença -->
    <div class="modal fade" id="modalGerarLicenca" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Gerar Nova Licença</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formGerarLicenca">
                        <div class="mb-3">
                            <label for="clienteSelect" class="form-label">Cliente *</label>
                            <select class="form-select" id="clienteSelect" required>
                                <option value="">Selecione o cliente...</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="dataExpiracao" class="form-label">Data de Expiração *</label>
                            <input type="date" class="form-control" id="dataExpiracao" required>
                            <small class="form-text text-muted">Defina até quando a licença será válida</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacao" class="form-label">Observação</label>
                            <textarea class="form-control" id="observacao" rows="3" placeholder="Observações sobre esta licença..."></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="enviarEmail">
                            <label class="form-check-label" for="enviarEmail">
                                Enviar licença por e-mail após gerar
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="gerarLicenca()">
                        <i class="fas fa-key me-2"></i>Gerar Licença
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adiar Expiração -->
    <div class="modal fade" id="modalAdiarExpiracao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Adiar Data de Expiração</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idLicencaAdiar">
                    <div class="mb-3">
                        <label class="form-label">Cliente:</label>
                        <p class="form-control-plaintext" id="clienteAdiar"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data Atual de Expiração:</label>
                        <p class="form-control-plaintext" id="dataAtualAdiar"></p>
                    </div>
                    <div class="mb-3">
                        <label for="novaDataExpiracao" class="form-label">Nova Data de Expiração *</label>
                        <input type="date" class="form-control" id="novaDataExpiracao" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="adiarExpiracao()">
                        <i class="fas fa-calendar-plus me-2"></i>Adiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalhes da Licença -->
    <div class="modal fade" id="modalDetalhesLicenca" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detalhes da Licença</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info alert-licenca-info">
                                <h4>Código da Licença</h4>
                                <h2 id="codigoLicencaDetalhe" class="codigo-licenca-display"></h2>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Cliente:</strong>
                            <p id="clienteDetalhe"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            <p id="statusDetalhe"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Data de Geração:</strong>
                            <p id="dataGeracaoDetalhe"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Data de Ativação:</strong>
                            <p id="dataAtivacaoDetalhe"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Data de Expiração:</strong>
                            <p id="dataExpiracaoDetalhe"></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Gerado Por:</strong>
                            <p id="geradoPorDetalhe"></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Observação:</strong>
                            <p id="observacaoDetalhe" class="text-muted"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/licenca.js"></script>
</body>
</html>
