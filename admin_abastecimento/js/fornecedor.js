// Gestão de Fornecedores
let paginaAtual = 1;
let buscaAtual = '';

$(document).ready(function() {
    carregarFornecedores();

    $('#searchInput').on('keyup', function(e) {
        if (e.key === 'Enter') {
            buscarFornecedores();
        }
    });

    $('.btn-buscar').on('click', function() {
        buscarFornecedores();
    });

    $('#fornecedorForm').on('submit', function(e) {
        e.preventDefault();
        salvarFornecedor();
    });

    // Busca CEP
    $('#cep').on('blur', function() {
        buscarCEP($(this).val());
    });
});

function buscarFornecedores() {
    buscaAtual = $('#searchInput').val();
    paginaAtual = 1;
    carregarFornecedores();
}

function carregarFornecedores(page = 1) {
    paginaAtual = page;
    
    $.ajax({
        url: '../api/fornecedor.php',
        method: 'GET',
        data: {
            action: 'list',
            page: page,
            search: buscaAtual
        },
        success: function(response) {
            if (response.success) {
                renderizarTabelaFornecedores(response.fornecedores);
                renderizarPaginacao(response.totalPages, response.currentPage);
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function() {
            showNotification('Erro ao carregar fornecedores', 'error');
        }
    });
}

function renderizarTabelaFornecedores(fornecedores) {
    const tbody = $('#fornecedoresTable');
    tbody.empty();

    if (fornecedores.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="6" class="text-center">Nenhum fornecedor encontrado</td>
            </tr>
        `);
        return;
    }

    fornecedores.forEach(function(f) {
        const telefone = f.tel_principal || '-';
        const cidade = f.cidade || '-';
        const uf = f.uf || '-';
        
        let acoesHtml = `
            <button class="btn btn-sm btn-outline-primary me-1" onclick="visualizar(${f.id_fornecedor})" title="Visualizar">
                <i class="fas fa-eye"></i>
            </button>
        `;
        
        if (typeof podeEditarFornecedor !== 'undefined' && podeEditarFornecedor) {
            acoesHtml += `
                <button class="btn btn-sm btn-outline-warning me-1" onclick="editar(${f.id_fornecedor})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
            `;
        }
        
        if (typeof podeExcluirFornecedor !== 'undefined' && podeExcluirFornecedor) {
            acoesHtml += `
                <button class="btn btn-sm btn-outline-danger" onclick="excluir(${f.id_fornecedor})" title="Excluir">
                    <i class="fas fa-trash"></i>
                </button>
            `;
        }

        tbody.append(`
            <tr>
                <td>${f.razao_social}</td>
                <td>${f.nome_fantasia || '-'}</td>
                <td>${formatCNPJ(f.cnpj)}</td>
                <td>${telefone}</td>
                <td>${cidade}/${uf}</td>
                <td>${acoesHtml}</td>
            </tr>
        `);
    });
}

function renderizarPaginacao(totalPages, currentPage) {
    const paginacao = $('#paginacao');
    paginacao.empty();

    if (totalPages <= 1) return;

    // Botão Anterior
    paginacao.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="carregarFornecedores(${currentPage - 1}); return false;">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `);

    // Páginas
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            paginacao.append(`
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="carregarFornecedores(${i}); return false;">${i}</a>
                </li>
            `);
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            paginacao.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
    }

    // Botão Próximo
    paginacao.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="carregarFornecedores(${currentPage + 1}); return false;">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

function resetForm() {
    $('#fornecedorForm')[0].reset();
    $('#fornecedorId').val('');
    $('#modalTitle').text('Novo Fornecedor');
    $('#dados-tab').tab('show');
}

function salvarFornecedor() {
    const id = $('#fornecedorId').val();
    const action = id ? 'update' : 'create';
    const formData = new FormData($('#fornecedorForm')[0]);
    formData.append('action', action);
    if (id) formData.append('id', id);

    $.ajax({
        url: '../api/fornecedor.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showNotification(response.message, 'success');
                $('#modalFornecedor').modal('hide');
                carregarFornecedores(paginaAtual);
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function() {
            showNotification('Erro ao salvar fornecedor', 'error');
        }
    });
}

function editar(id) {
    $.ajax({
        url: '../api/fornecedor.php',
        method: 'GET',
        data: {
            action: 'get',
            id: id
        },
        success: function(response) {
            if (response.success) {
                const f = response.fornecedor;
                $('#fornecedorId').val(f.id_fornecedor);
                $('#razao_social').val(f.razao_social);
                $('#nome_fantasia').val(f.nome_fantasia);
                $('#cnpj').val(f.cnpj);
                $('#insc_estadual').val(f.insc_estadual);
                $('#insc_municipal').val(f.insc_municipal);
                $('#cep').val(f.cep);
                $('#logradouro').val(f.logradouro);
                $('#numero').val(f.numero);
                $('#compl_endereco').val(f.compl_endereco);
                $('#bairro').val(f.bairro);
                $('#cidade').val(f.cidade);
                $('#uf').val(f.uf);
                $('#tel_principal').val(f.tel_principal);
                $('#tel_contato').val(f.tel_contato);
                $('#email').val(f.email);
                $('#modalTitle').text('Editar Fornecedor');
                
                // Voltar para a primeira aba
                $('#dados-tab').tab('show');
                
                $('#modalFornecedor').modal('show');
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function() {
            showNotification('Erro ao carregar fornecedor', 'error');
        }
    });
}

function excluir(id) {
    if (!confirm('Tem certeza que deseja excluir este fornecedor?')) {
        return;
    }

    $.ajax({
        url: '../api/fornecedor.php',
        method: 'POST',
        data: {
            action: 'delete',
            id: id
        },
        success: function(response) {
            if (response.success) {
                showNotification(response.message, 'success');
                carregarFornecedores(paginaAtual);
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function() {
            showNotification('Erro ao excluir fornecedor', 'error');
        }
    });
}

function visualizar(id) {
    $.ajax({
        url: '../api/fornecedor.php',
        method: 'GET',
        data: {
            action: 'get',
            id: id
        },
        success: function(response) {
            if (response.success) {
                const f = response.fornecedor;
                alert(`Fornecedor: ${f.razao_social}\nCNPJ: ${formatCNPJ(f.cnpj)}\nTelefone: ${f.tel_principal}\nEndereço: ${f.logradouro}, ${f.numero} - ${f.bairro}\n${f.cidade}/${f.uf}`);
            }
        }
    });
}

function buscarCEP(cep) {
    cep = cep.replace(/\D/g, '');
    
    if (cep.length !== 8) return;
    
    $.ajax({
        url: `https://viacep.com.br/ws/${cep}/json/`,
        method: 'GET',
        success: function(data) {
            if (!data.erro) {
                $('#logradouro').val(data.logradouro);
                $('#bairro').val(data.bairro);
                $('#cidade').val(data.localidade);
                $('#uf').val(data.uf);
            }
        }
    });
}

function formatCNPJ(cnpj) {
    if (!cnpj) return '-';
    cnpj = cnpj.replace(/\D/g, '');
    return cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
}
