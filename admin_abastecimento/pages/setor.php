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
    <title>Setor - <?php echo COMPANY_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content setor">
        <div class="page-title">
            <span><i class="fas fa-sitemap"></i> Setor</span>
        </div>

        <div class="mb-2" style="display: flex; gap: 10px; align-items: center;">
            <div class="input-group" style="max-width:720px;">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar setor...">
                <button class="btn btn-secondary btn-sm" type="button"><i class="fas fa-search"></i></button>
            </div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSetor">
                <i class="fas fa-plus"></i> Novo Setor
            </button>
        </div>

        <div class="table-container">
            <table class="table table-enhanced">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descrição</th>
                        <th>Unidade Orçamentária</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody id="setoresTableBody">
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

    <!-- Modal Setor -->
    <div class="modal fade" id="modalSetor" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSetorLabel">Novo Setor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formSetor">
                        <input type="hidden" id="setorId" name="id">
                        
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="unidade_id" class="form-label">Unidade Orçamentária</label>
                            <select class="form-select" id="unidade_id" name="unidade_id">
                                <option value="">Selecione...</option>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" checked>
                            <label class="form-check-label" for="ativo">Ativo</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarSetor()">Salvar</button>
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
            carregarSetores();
            carregarUnidades();

            $('#searchInput').on('keyup', function() {
                currentPage = 1;
                carregarSetores();
            });
        });

        function carregarUnidades() {
            $.ajax({
                url: '../api/unidade_orcamentaria.php',
                method: 'GET',
                data: { action: 'list_all' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const select = $('#unidade_id');
                        select.find('option:not(:first)').remove();
                        response.unidades.forEach(function(unidade) {
                            select.append(`<option value="${unidade.id}">${unidade.nome}</option>`);
                        });
                    }
                }
            });
        }

        function carregarSetores() {
            const search = $('#searchInput').val();
            
            $.ajax({
                url: '../api/setor.php',
                method: 'GET',
                data: {
                    action: 'list',
                    page: currentPage,
                    search: search
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderSetores(response.setores);
                        totalPages = response.totalPages || 1;
                        renderPaginacao();
                    }
                }
            });
        }

        function renderSetores(setores) {
            const tbody = $('#setoresTableBody');
            tbody.empty();

            if (setores.length === 0) {
                tbody.append('<tr><td colspan="5" class="text-center">Nenhum setor encontrado</td></tr>');
                return;
            }

            setores.forEach(function(setor) {
                const statusBadge = setor.ativo == 1 ? 
                    '<span class="badge bg-success">Ativo</span>' : 
                    '<span class="badge bg-secondary">Inativo</span>';

                tbody.append(`
                    <tr style="vertical-align: middle;">
                        <td>${setor.codigo}</td>
                        <td><strong>${setor.descricao}</strong></td>
                        <td>${setor.unidade_nome || '-'}</td>
                        <td class="text-center">${statusBadge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="editarSetor(${setor.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="excluirSetor(${setor.id}, '${setor.descricao}')" title="Excluir">
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
            carregarSetores();
        }

        function salvarSetor() {
            const formData = new FormData($('#formSetor')[0]);
            const id = $('#setorId').val();
            formData.append('action', id ? 'update' : 'create');
            formData.append('ativo', $('#ativo').is(':checked') ? 1 : 0);

            $.ajax({
                url: '../api/setor.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#modalSetor').modal('hide');
                        $('#formSetor')[0].reset();
                        carregarSetores();
                        alert(response.message);
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        function editarSetor(id) {
            $.ajax({
                url: '../api/setor.php',
                method: 'GET',
                data: { action: 'get', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const setor = response.setor;
                        $('#setorId').val(setor.id);
                        $('#codigo').val(setor.codigo);
                        $('#descricao').val(setor.descricao);
                        $('#unidade_id').val(setor.unidade_id);
                        $('#ativo').prop('checked', setor.ativo == 1);
                        $('#modalSetorLabel').text('Editar Setor');
                        $('#modalSetor').modal('show');
                    }
                }
            });
        }

        function excluirSetor(id, nome) {
            if (confirm(`Deseja realmente excluir o setor "${nome}"?`)) {
                $.ajax({
                    url: '../api/setor.php',
                    method: 'POST',
                    data: { action: 'delete', id: id },
                    dataType: 'json',
                    success: function(response) {
                        alert(response.message);
                        if (response.success) {
                            carregarSetores();
                        }
                    }
                });
            }
        }

        $('#modalSetor').on('hidden.bs.modal', function() {
            $('#formSetor')[0].reset();
            $('#setorId').val('');
            $('#modalSetorLabel').text('Novo Setor');
        });
    </script>
</body>
</html>
