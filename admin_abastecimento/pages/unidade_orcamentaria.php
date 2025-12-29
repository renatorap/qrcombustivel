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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unidade Orçamentária - <?php echo COMPANY_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content unidade-orcamentaria">
        <div class="page-title">
            <span><i class="fas fa-building"></i> Unidade Orçamentária</span>
        </div>

        <div class="mb-2" style="display: flex; gap: 10px; align-items: center;">
            <div class="input-group" style="max-width:720px;">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar unidade orçamentária...">
                <button class="btn btn-secondary btn-sm btn-buscar" type="button"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalUnidade">
                <i class="fas fa-plus"></i> Nova Unidade
            </button>
        </div>

        <div class="table-container">
            <table class="table table-enhanced">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th colspan="2">Descrição</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody id="unidadesTableBody">
                    <tr>
                        <td colspan="5" class="text-center">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav aria-label="Paginação" style="margin-top: 10px; margin-bottom: 5px;">
            <ul class="pagination pagination-sm justify-content-center" id="paginacao">
                <!-- Paginação carregada via JavaScript -->
            </ul>
        </nav>
    </div>

    <!-- Modal Unidade -->
    <div class="modal fade" id="modalUnidade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUnidadeLabel">Nova Unidade Orçamentária</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formUnidade">
                        <input type="hidden" id="unidadeId" name="id">
                        
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" checked>
                            <label class="form-check-label" for="ativo">Ativo</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarUnidade()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentPage = 1;
        let totalPages = 1;

        $(document).ready(function() {
            carregarUnidades();

            $('#searchInput').on('keyup', function() {
                currentPage = 1;
                carregarUnidades();
            });
        });

        function carregarUnidades() {
            const search = $('#searchInput').val();
            
            $.ajax({
                url: '../api/unidade_orcamentaria.php',
                method: 'GET',
                data: {
                    action: 'list',
                    page: currentPage,
                    search: search
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderUnidades(response.unidades);
                        totalPages = response.totalPages || 1;
                        renderPaginacao();
                    }
                }
            });
        }

        function renderUnidades(unidades) {
            const tbody = $('#unidadesTableBody');
            tbody.empty();

            if (unidades.length === 0) {
                tbody.append('<tr><td colspan="5" class="text-center">Nenhuma unidade encontrada</td></tr>');
                return;
            }

            unidades.forEach(function(unidade) {
                const statusBadge = unidade.ativo == 1 ? 
                    '<span class="badge bg-success">Ativo</span>' : 
                    '<span class="badge bg-secondary">Inativo</span>';

                tbody.append(`
                    <tr style="vertical-align: middle;">
                        <td>${unidade.codigo}</td>
                        <td colspan="2"><strong>${unidade.descricao}</strong></td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="editarUnidade(${unidade.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="excluirUnidade(${unidade.id}, '${unidade.nome}')" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }

        function renderPaginacao() {
            const paginacao = $('#paginacao');
            paginacao.empty();

            if (totalPages <= 1) return;

            // Primeira página
            paginacao.append(`
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="goToPage(1)" title="Primeira">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </li>
            `);

            // Anterior
            paginacao.append(`
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage - 1})" title="Anterior">
                        <i class="fas fa-angle-left"></i>
                    </a>
                </li>
            `);

            // Páginas
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            if (endPage - startPage < 4) {
                if (startPage === 1) {
                    endPage = Math.min(totalPages, startPage + 4);
                } else if (endPage === totalPages) {
                    startPage = Math.max(1, endPage - 4);
                }
            }

            if (startPage > 1) {
                paginacao.append(`<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a></li>`);
                if (startPage > 2) {
                    paginacao.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const active = i === currentPage ? 'active' : '';
                paginacao.append(`
                    <li class="page-item ${active}">
                        <a class="page-link" href="javascript:void(0)" onclick="goToPage(${i})">${i}</a>
                    </li>
                `);
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginacao.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                }
                paginacao.append(`<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(${totalPages})">${totalPages}</a></li>`);
            }

            // Próxima
            paginacao.append(`
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage + 1})" title="Próxima">
                        <i class="fas fa-angle-right"></i>
                    </a>
                </li>
            `);

            // Última página
            paginacao.append(`
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="javascript:void(0)" onclick="goToPage(${totalPages})" title="Última">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                </li>
            `);
        }

        function goToPage(page) {
            currentPage = page;
            carregarUnidades();
        }

        function salvarUnidade() {
            const formData = new FormData($('#formUnidade')[0]);
            const id = $('#unidadeId').val();
            formData.append('action', id ? 'update' : 'create');
            formData.append('ativo', $('#ativo').is(':checked') ? 1 : 0);

            $.ajax({
                url: '../api/unidade_orcamentaria.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#modalUnidade').modal('hide');
                        $('#formUnidade')[0].reset();
                        carregarUnidades();
                        alert(response.message);
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        function editarUnidade(id) {
            $.ajax({
                url: '../api/unidade_orcamentaria.php',
                method: 'GET',
                data: { action: 'get', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const unidade = response.unidade;
                        $('#unidadeId').val(unidade.id);
                        $('#codigo').val(unidade.codigo);
                        $('#descricao').val(unidade.descricao);
                        $('#ativo').prop('checked', unidade.ativo == 1);
                        $('#modalUnidadeLabel').text('Editar Unidade Orçamentária');
                        $('#modalUnidade').modal('show');
                    }
                }
            });
        }

        function excluirUnidade(id, nome) {
            if (confirm(`Deseja realmente excluir a unidade "${nome}"?`)) {
                $.ajax({
                    url: '../api/unidade_orcamentaria.php',
                    method: 'POST',
                    data: { action: 'delete', id: id },
                    dataType: 'json',
                    success: function(response) {
                        alert(response.message);
                        if (response.success) {
                            carregarUnidades();
                        }
                    }
                });
            }
        }

        $('#modalUnidade').on('hidden.bs.modal', function() {
            $('#formUnidade')[0].reset();
            $('#unidadeId').val('');
            $('#modalUnidadeLabel').text('Nova Unidade Orçamentária');
        });
    </script>
</body>
</html>
