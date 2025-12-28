<?php
require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

// Validar token JWT
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

// Controle de acesso
$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('licitacao', 'acessar');

$podeCriar = $accessControl->verificarPermissao('licitacao', 'criar');
$podeEditar = $accessControl->verificarPermissao('licitacao', 'editar');
$podeExcluir = $accessControl->verificarPermissao('licitacao', 'excluir');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Licitações - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content veiculo">
        <div class="page-title">
            <span><i class="fas fa-gavel"></i> Gestão de Licitações</span>
        </div>

        <div class="mb-2" style="display: flex; gap: 10px; align-items: center;">
            <div class="input-group" style="max-width:720px;">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar por código ou objeto...">
                <button class="btn btn-secondary btn-sm btn-buscar" type="button"><i class="fas fa-search"></i></button>
            </div>
            <?php if ($podeCriar): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalLicitacao" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Nova Licitação
                </button>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Objeto</th>
                        <th>Data</th>
                        <th>Contratos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="licitacoesTable">
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

    <!-- Modal para criar/editar licitação -->
    <div class="modal fade" id="modalLicitacao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nova Licitação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="licitacaoForm">
                        <input type="hidden" id="licitacaoId" name="id">
                        
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
                                    <label for="objeto">Objeto *</label>
                                    <textarea class="form-control" id="objeto" name="objeto" rows="3" required maxlength="150"></textarea>
                                    <small class="form-text text-muted">Máximo 150 caracteres</small>
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

    <?php include '../includes/footer.php'; ?>
    <script>
        const podeCriarLicitacao = <?php echo $podeCriar ? 'true' : 'false'; ?>;
        const podeEditarLicitacao = <?php echo $podeEditar ? 'true' : 'false'; ?>;
        const podeExcluirLicitacao = <?php echo $podeExcluir ? 'true' : 'false'; ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/licitacao.js"></script>
</body>
</html>
