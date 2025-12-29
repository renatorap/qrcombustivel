<?php
require_once '../config/cache_control.php'; // Controle de cache e sessão
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

// Controle de acesso para aplicação "clientes"
$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('clientes', 'acessar');

$podeCriar = $accessControl->verificarPermissao('clientes', 'criar');
$podeEditar = $accessControl->verificarPermissao('clientes', 'editar');
$podeExcluir = $accessControl->verificarPermissao('clientes', 'excluir');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content veiculo">
        <div class="page-title">
            <span><i class="fas fa-building"></i> Gestão de Clientes</span>
        </div>

        <div class="mb-2" style="display: flex; gap: 10px; align-items: center;">
            <div class="input-group" style="max-width:720px;">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar por razão social, CNPJ ou nome fantasia...">
                <button class="btn btn-secondary btn-sm" type="button"><i class="fas fa-search"></i></button>
            </div>
            <?php if ($podeCriar): ?>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Novo Cliente
                </button>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="table table-enhanced">
                <thead>
                    <tr>
                        <th>Razão Social</th>
                        <th>Nome Fantasia</th>
                        <th>CNPJ</th>
                        <th>Cidade/UF</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="clientesTable">
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

    <!-- Modal para criar/editar cliente -->
    <div class="modal fade" id="modalCliente" tabindex="-1">
        <div class="modal-dialog modal-dialog-wide">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="clienteForm" enctype="multipart/form-data">
                        <input type="hidden" id="clienteId" name="id">
                        <input type="hidden" id="remove_logo" name="remove_logo" value="0">
                        
                        <!-- Abas de Navegação -->
                        <ul class="nav nav-tabs mb-3" id="clienteTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
                                    <i class="fas fa-building"></i> Dados Principais
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="endereco-tab" data-bs-toggle="tab" data-bs-target="#endereco" type="button" role="tab">
                                    <i class="fas fa-map-marker-alt"></i> Endereço
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="contato-tab" data-bs-toggle="tab" data-bs-target="#contato" type="button" role="tab">
                                    <i class="fas fa-phone"></i> Contatos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button" role="tab">
                                    <i class="fas fa-users"></i> Usuários Vinculados
                                </button>
                            </li>
                        </ul>

                        <!-- Conteúdo das Abas -->
                        <div class="tab-content" id="clienteTabContent">
                            <!-- Aba Dados Principais -->
                            <div class="tab-pane fade show active" id="dados" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="razao_social">Razão Social *</label>
                                            <input type="text" class="form-control" id="razao_social" name="razao_social" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nome_fantasia">Nome Fantasia</label>
                                            <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="cnpj">CNPJ *</label>
                                            <input type="text" class="form-control" id="cnpj" name="cnpj" required placeholder="00.000.000/0000-00">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="inscricao_estadual">Inscrição Estadual</label>
                                            <input type="text" class="form-control" id="inscricao_estadual" name="inscricao_estadual">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="inscricao_municipal">Inscrição Municipal</label>
                                            <input type="text" class="form-control" id="inscricao_municipal" name="inscricao_municipal">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="logo">Upload de Logo</label>
                                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                            <small class="form-text text-muted">Formatos aceitos: JPG, PNG, GIF (máx. 2MB)</small>
                                            <input type="hidden" id="logo_path" name="logo_path">
                                        </div>
                                        <div id="logoPreview" class="mt-2" style="display: none;">
                                            <img id="logoImg" src="" alt="Logo Preview" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
                                            <button type="button" class="btn btn-sm btn-danger mt-1" onclick="removerLogo()">
                                                <i class="fas fa-trash"></i> Remover
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" checked>
                                            <label class="form-check-label" for="ativo">Cliente Ativo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Endereço -->
                            <div class="tab-pane fade" id="endereco" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="cep">CEP</label>
                                            <input type="text" class="form-control" id="cep" name="cep" placeholder="00000-000">
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="form-group">
                                            <label for="logradouro">Logradouro</label>
                                            <input type="text" class="form-control" id="logradouro" name="logradouro">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label for="numero">Número</label>
                                            <input type="text" class="form-control" id="numero" name="numero">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="complemento">Complemento</label>
                                            <input type="text" class="form-control" id="complemento" name="complemento">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="bairro">Bairro</label>
                                            <input type="text" class="form-control" id="bairro" name="bairro">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="cidade">Cidade</label>
                                            <input type="text" class="form-control" id="cidade" name="cidade">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <label for="uf">UF</label>
                                            <input type="text" class="form-control" id="uf" name="uf" maxlength="2" style="text-transform: uppercase;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Contatos -->
                            <div class="tab-pane fade" id="contato" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="telefone">Telefone</label>
                                            <input type="text" class="form-control" id="telefone" name="telefone" placeholder="(00) 0000-0000">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="celular">Celular</label>
                                            <input type="text" class="form-control" id="celular" name="celular" placeholder="(00) 00000-0000">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="email">E-mail</label>
                                            <input type="email" class="form-control" id="email" name="email">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="site">Site</label>
                                            <input type="text" class="form-control" id="site" name="site" placeholder="www.exemplo.com.br">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Usuários -->
                            <div class="tab-pane fade" id="usuarios" role="tabpanel">
                                <div class="alert alert-info" id="infoSalvarCliente" style="display: none;">
                                    <i class="fas fa-info-circle"></i> Salve o cliente primeiro para vincular usuários.
                                </div>
                                <div id="usuariosContent" style="display: none;">
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-sm btn-success" onclick="adicionarUsuario()">
                                            <i class="fas fa-plus"></i> Vincular Usuário
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-enhanced">
                                            <thead>
                                                <tr>
                                                    <th>Usuário</th>
                                                    <th>Nome</th>
                                                    <th>E-mail</th>
                                                    <th>Status</th>
                                                    <th>Data Vínculo</th>
                                                    <th style="width: 100px;">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody id="usuariosVinculadosTable">
                                                <!-- Carregado via AJAX -->
                                            </tbody>
                                        </table>
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

    <!-- Modal para visualizar cliente -->
    <div class="modal fade" id="modalVisualizacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Visualizar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Razão Social</label>
                                <input type="text" class="form-control" id="vizRazaoSocial" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nome Fantasia</label>
                                <input type="text" class="form-control" id="vizNomeFantasia" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>CNPJ</label>
                                <input type="text" class="form-control" id="vizCnpj" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Telefone</label>
                                <input type="text" class="form-control" id="vizTelefone" disabled>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>E-mail</label>
                                <input type="text" class="form-control" id="vizEmail" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Endereço Completo</label>
                                <input type="text" class="form-control" id="vizEndereco" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" class="form-control" id="vizStatus" disabled>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

<!-- Modal Adicionar Usuário -->
<div class="modal fade" id="modalAdicionarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vincular Usuário ao Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="selectUsuario">Selecione o Usuário</label>
                    <select class="form-select" id="selectUsuario">
                        <option value="">Carregando...</option>
                    </select>
                </div>
                <div class="form-group mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="usuarioAtivo" checked>
                        <label class="form-check-label" for="usuarioAtivo">
                            Usuário Ativo
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarVinculoUsuario()">
                    <i class="fas fa-save"></i> Vincular
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>    <script>
        // Permissões vindas do PHP para o JavaScript
        const podeCriarCliente = <?php echo $podeCriar ? 'true' : 'false'; ?>;
        const podeEditarCliente = <?php echo $podeEditar ? 'true' : 'false'; ?>;
        const podeExcluirCliente = <?php echo $podeExcluir ? 'true' : 'false'; ?>;
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/cliente.js"></script>
    <script src="../js/cliente_usuarios.js"></script>
</body>
</html>
