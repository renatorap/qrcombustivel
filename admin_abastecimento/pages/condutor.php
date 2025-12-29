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

// Verificar licença do cliente
require_once '../config/license_checker.php';
$clienteId = $_SESSION['cliente_id'] ?? null;
$grupoId = $_SESSION['grupoId'] ?? null;
$statusLicenca = LicenseChecker::verificarEBloquear($clienteId, $grupoId);

// Controle de acesso para condutores
$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('condutores', 'acessar');

$podeCriar = $accessControl->verificarPermissao('condutores', 'criar');
$podeVisualizar = $accessControl->verificarPermissao('condutores', 'visualizar');
$podeEditar = $accessControl->verificarPermissao('condutores', 'editar');
$podeExcluir = $accessControl->verificarPermissao('condutores', 'excluir');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Condutores - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content condutor">
        <div class="page-title">
            <span><i class="fas fa-user-tie"></i> Gestão de Condutores</span>
        </div>

        <div class="search-container">
            <div class="input-group search-input-group">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar por nome, CPF, CNH ou matrícula...">
                <button class="btn btn-secondary btn-sm" type="button"><i class="fas fa-search"></i></button>
            </div>
            <?php if ($podeCriar): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCondutor">
                    <i class="fas fa-plus"></i> Novo Condutor
                </button>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>CNH</th>
                        <th>Validade CNH</th>
                        <th>Cargo</th>
                        <th>Situação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="condutoresTable">
                    <!-- Carregado via AJAX -->
                </tbody>
            </table>
        </div>

        <nav aria-label="Paginação" style="margin-top: 10px; margin-bottom: 2px;">
            <ul class="pagination pagination-sm justify-content-center" id="paginacao">
                <!-- Paginação carregada via AJAX -->
            </ul>
        </nav>
    </div>

    <!-- Modal Novo/Editar Condutor -->
    <div class="modal fade" id="modalCondutor" tabindex="-1">
        <div class="modal-dialog modal-dialog-wide modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo Condutor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="condutorForm">
                        <input type="hidden" id="condutorId" name="id">
                        
                        <ul class="nav nav-tabs" id="condutorTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="dados-pessoais-tab" data-bs-toggle="tab" data-bs-target="#dados-pessoais" type="button">
                                    Dados Pessoais
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="documentacao-tab" data-bs-toggle="tab" data-bs-target="#documentacao" type="button">
                                    Documentação
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profissional-tab" data-bs-toggle="tab" data-bs-target="#profissional" type="button">
                                    Dados Profissionais
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="cracha-tab" data-bs-toggle="tab" data-bs-target="#cracha" type="button">
                                    Crachá
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3" id="condutorTabContent">
                            <!-- Aba Dados Pessoais -->
                            <div class="tab-pane fade show active" id="dados-pessoais" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="nome">Nome Completo *</label>
                                            <input type="text" class="form-control" id="nome" name="nome" required maxlength="45">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="data_nascimento">Data de Nascimento</label>
                                            <input type="date" class="form-control" id="data_nascimento" name="data_nascimento">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="id_sexo">Sexo</label>
                                            <select class="form-control" id="id_sexo" name="id_sexo">
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="id_tp_sanguineo">Tipo Sanguíneo</label>
                                            <select class="form-control" id="id_tp_sanguineo" name="id_tp_sanguineo">
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="telefone">Telefone</label>
                                            <input type="text" class="form-control" id="telefone" name="telefone" maxlength="15" placeholder="(00) 00000-0000">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="email">E-mail</label>
                                            <input type="email" class="form-control" id="email" name="email" maxlength="25" placeholder="exemplo@email.com">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Documentação -->
                            <div class="tab-pane fade" id="documentacao" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cpf">CPF</label>
                                            <input type="text" class="form-control" id="cpf" name="cpf" maxlength="15" placeholder="000.000.000-00">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="rg">RG</label>
                                            <input type="text" class="form-control" id="rg" name="rg" maxlength="15">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="cnh">CNH *</label>
                                            <input type="text" class="form-control" id="cnh" name="cnh" required maxlength="15">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="id_cat_cnh">Categoria CNH</label>
                                            <select class="form-control" id="id_cat_cnh" name="id_cat_cnh">
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="validade_cnh">Validade CNH *</label>
                                            <input type="date" class="form-control" id="validade_cnh" name="validade_cnh" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Dados Profissionais -->
                            <div class="tab-pane fade" id="profissional" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="matricula">Matrícula</label>
                                            <input type="text" class="form-control" id="matricula" name="matricula" maxlength="45">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_cargo">Cargo <span id="cargo-obrigatorio" style="display: none; color: red;">*</span></label>
                                            <select class="form-control" id="id_cargo" name="id_cargo">
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="id_situacao">Situação</label>
                                            <select class="form-control" id="id_situacao" name="id_situacao">
                                                <option value="">Selecione...</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mt-4">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="e_condutor" name="e_condutor" value="1" checked>
                                                <label class="form-check-label" for="e_condutor">
                                                    É Motorista (dispensa cargo)
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">Se não for motorista, o cargo é obrigatório</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="foto">Foto do Condutor</label>
                                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                                            <small class="form-text text-muted">Formatos aceitos: JPG, PNG, GIF (máx. 2MB)</small>
                                            <input type="hidden" id="foto_path" name="foto_path">
                                            <input type="hidden" id="remove_foto" name="remove_foto" value="0">
                                        </div>
                                        <div id="fotoPreview" class="mt-2" style="display: none;">
                                            <img id="fotoImg" src="" alt="Foto Preview" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
                                            <button type="button" class="btn btn-sm btn-danger mt-1" onclick="removerFoto()">
                                                <i class="fas fa-trash"></i> Remover
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Crachá -->
                            <div class="tab-pane fade" id="cracha" role="tabpanel">
                                <div class="row" id="cracha-section" style="display: none;">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <h6><i class="fas fa-id-card"></i> Gerenciamento de Crachá</h6>
                                            <p class="mb-2"><small>O crachá é gerado automaticamente quando o condutor é criado ou reativado. Você pode gerar uma nova via manualmente se necessário.</small></p>
                                            <div id="cracha-info"></div>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-sm btn-info" onclick="imprimirCracha($('#condutorId').val())">
                                                    <i class="fas fa-print"></i> Imprimir
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success" onclick="baixarCrachaPdf($('#condutorId').val())">
                                                    <i class="fas fa-file-pdf"></i> Baixar PDF
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="gerarNovaVia()">
                                                    <i class="fas fa-plus"></i> Gerar Nova Via
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="verHistoricoCracha()">
                                                    <i class="fas fa-history"></i> Ver Histórico
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="cracha-vazio">
                                    <div class="col-md-12">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-info-circle"></i> Salve o condutor primeiro para gerenciar o crachá.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="condutorForm" class="btn btn-primary">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visualização -->
    <div class="modal fade" id="modalVisualizacao" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Condutor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Nome</label>
                                <input type="text" class="form-control" id="vizNome" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Data de Nascimento</label>
                                <input type="text" class="form-control" id="vizDataNasc" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>CPF</label>
                                <input type="text" class="form-control" id="vizCpf" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>RG</label>
                                <input type="text" class="form-control" id="vizRg" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Telefone</label>
                                <input type="text" class="form-control" id="vizTelefone" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>CNH</label>
                                <input type="text" class="form-control" id="vizCnh" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Categoria</label>
                                <input type="text" class="form-control" id="vizCategoria" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Validade CNH</label>
                                <input type="text" class="form-control" id="vizValidadeCnh" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Cargo</label>
                                <input type="text" class="form-control" id="vizCargo" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Situação</label>
                                <input type="text" class="form-control" id="vizSituacao" readonly>
                            </div>
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
    <script src="../js/condutor.js"></script>
    <script>
        const podeVisualizarCondutor = <?php echo $podeVisualizar ? 'true' : 'false'; ?>;
        const podeEditarCondutor = <?php echo $podeEditar ? 'true' : 'false'; ?>;
        const podeExcluirCondutor = <?php echo $podeExcluir ? 'true' : 'false'; ?>;
    </script>
</body>
</html>
