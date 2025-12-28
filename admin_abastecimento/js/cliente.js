let currentPage = 1;
let searchTerm = '';

$(document).ready(function() {
    loadClientes();

    // Máscaras
    $('#cnpj').on('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 14) {
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        }
        e.target.value = value;
    });

    $('#cep').on('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 8) {
            value = value.replace(/^(\d{5})(\d)/, '$1-$2');
        }
        e.target.value = value;
    });

    $('#telefone').on('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 10) {
            value = value.replace(/^(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
        }
        e.target.value = value;
    });

    $('#celular').on('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/^(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
        }
        e.target.value = value;
    });

    // Preview de imagem
    $('#logo').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Se usuário escolheu novo arquivo, garantir que não está marcando remoção
            $('#remove_logo').val('0');
            // Validar tamanho (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Arquivo muito grande! Tamanho máximo: 2MB');
                $(this).val('');
                return;
            }
            
            // Validar tipo
            if (!file.type.match('image.*')) {
                alert('Por favor, selecione uma imagem válida');
                $(this).val('');
                return;
            }
            
            // Mostrar preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#logoImg').attr('src', e.target.result);
                $('#logoPreview').show();
            };
            reader.readAsDataURL(file);
        }
    });

    // Novo/Editar cliente
    $('#clienteForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#clienteId').val();
        const razao_social = $('#razao_social').val().trim();
        const cnpj = $('#cnpj').val().trim();

        if (!razao_social || !cnpj) {
            alert('Por favor, preencha os campos obrigatórios');
            return;
        }

        // Usar FormData para enviar arquivo
        const formData = new FormData();
        formData.append('action', id ? 'update' : 'create');
        if (id) formData.append('id', id);
        // Flag de remoção de logo sempre enviada
        formData.append('remove_logo', $('#remove_logo').val());
        formData.append('razao_social', razao_social);
        formData.append('nome_fantasia', $('#nome_fantasia').val().trim());
        formData.append('cnpj', cnpj);
        formData.append('inscricao_estadual', $('#inscricao_estadual').val().trim());
        formData.append('inscricao_municipal', $('#inscricao_municipal').val().trim());
        formData.append('cep', $('#cep').val().trim());
        formData.append('logradouro', $('#logradouro').val().trim());
        formData.append('numero', $('#numero').val().trim());
        formData.append('complemento', $('#complemento').val().trim());
        formData.append('bairro', $('#bairro').val().trim());
        formData.append('cidade', $('#cidade').val().trim());
        formData.append('uf', $('#uf').val().trim().toUpperCase());
        formData.append('telefone', $('#telefone').val().trim());
        formData.append('celular', $('#celular').val().trim());
        formData.append('email', $('#email').val().trim());
        formData.append('site', $('#site').val().trim());
        formData.append('ativo', $('#ativo').is(':checked') ? 1 : 0);
        
        // Adicionar arquivo se houver
        const logoFile = $('#logo')[0].files[0];
        if (logoFile) {
            formData.append('logo', logoFile);
        } else if ($('#logo_path').val()) {
            formData.append('logo_path', $('#logo_path').val().trim());
        }

        $.ajax({
            url: '../api/cliente.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#modalCliente').modal('hide');
                    resetForm();
                    loadClientes();
                    alert(data.message);
                } else {
                    alert(data.message || 'Erro ao salvar');
                }
            },
            error: function() {
                alert('Erro na comunicação com o servidor');
            }
        });
    });

    // Buscar
    $('#searchInput').keyup(function() {
        searchTerm = $(this).val().trim();
        currentPage = 1;
        loadClientes();
    });

    $('.btn-buscar').click(function() {
        searchTerm = $('#searchInput').val().trim();
        currentPage = 1;
        loadClientes();
    });
});

function loadClientes() {
    $.ajax({
        url: '../api/cliente.php',
        method: 'GET',
        data: {
            action: 'list',
            search: searchTerm,
            page: currentPage
        },
        dataType: 'json',
        success: function(data) {
            renderTable(data.clientes);
            renderPagination(data.totalPages, data.currentPage);
        },
        error: function() {
            alert('Erro ao carregar clientes');
        }
    });
}

function renderTable(clientes) {
    const tbody = $('#clientesTable');
    tbody.empty();

    if (clientes.length === 0) {
        tbody.html('<tr><td colspan="6" class="text-center text-muted">Nenhum cliente encontrado</td></tr>');
        return;
    }

    clientes.forEach(cliente => {
        const statusBadge = cliente.ativo == 1 
            ? '<span class="badge bg-success">Ativo</span>' 
            : '<span class="badge bg-secondary">Inativo</span>';
        const cidadeUf = cliente.cidade && cliente.uf ? `${cliente.cidade}/${cliente.uf}` : '-';
        
        const botoes = [];
        // Visualizar sempre permitido (desde que acesse clientes)
        botoes.push(`
            <button class="btn btn-sm btn-outline-primary me-1" title="Visualizar" onclick="visualizar(${cliente.id})">
                <i class="fas fa-eye"></i>
            </button>
        `);

        if (typeof podeEditarCliente !== 'undefined' && podeEditarCliente) {
            botoes.push(`
                <button class="btn btn-sm btn-outline-warning me-1" title="Editar" onclick="editar(${cliente.id})">
                    <i class="fas fa-edit"></i>
                </button>
            `);
        }

        if (typeof podeExcluirCliente !== 'undefined' && podeExcluirCliente) {
            botoes.push(`
                <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="excluir(${cliente.id})">
                    <i class="fas fa-trash"></i>
                </button>
            `);
        }

        const row = `
            <tr style="vertical-align: middle;">
                <td>${cliente.razao_social}</td>
                <td>${cliente.nome_fantasia || '-'}</td>
                <td>${cliente.cnpj}</td>
                <td>${cidadeUf}</td>
                <td>${statusBadge}</td>
                <td>
                    ${botoes.join('')}
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderPagination(totalPages, currentPage) {
    const paginacao = $('#paginacao');
    paginacao.empty();

    if (totalPages <= 1) {
        return;
    }

    // Botão Primeira Página
    paginacao.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(1)" title="Primeira">
                <i class="fas fa-angle-double-left"></i>
            </a>
        </li>
    `);

    // Botão Anterior
    paginacao.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage - 1})" title="Anterior">
                <i class="fas fa-angle-left"></i>
            </a>
        </li>
    `);

    // Páginas (máximo 5 visíveis)
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);

    // Ajustar para sempre mostrar 5 páginas quando possível
    if (endPage - startPage < 4) {
        if (startPage === 1) {
            endPage = Math.min(totalPages, startPage + 4);
        } else if (endPage === totalPages) {
            startPage = Math.max(1, endPage - 4);
        }
    }

    // Mostrar primeira página se não estiver no range
    if (startPage > 1) {
        paginacao.append(`
            <li class="page-item">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a>
            </li>
        `);
        if (startPage > 2) {
            paginacao.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }

    // Páginas do range
    for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPage ? 'active' : '';
        paginacao.append(`
            <li class="page-item ${active}">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${i})">${i}</a>
            </li>
        `);
    }

    // Mostrar última página se não estiver no range
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginacao.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
        paginacao.append(`
            <li class="page-item">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${totalPages})">${totalPages}</a>
            </li>
        `);
    }

    // Botão Próxima
    paginacao.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${currentPage + 1})" title="Próxima">
                <i class="fas fa-angle-right"></i>
            </a>
        </li>
    `);

    // Botão Última Página
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
    loadClientes();
}

function visualizar(id) {
    $.ajax({
        url: '../api/cliente.php',
        method: 'GET',
        data: {
            action: 'get',
            id: id
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const c = data.cliente;
                $('#vizRazaoSocial').val(c.razao_social);
                $('#vizNomeFantasia').val(c.nome_fantasia || '-');
                $('#vizCnpj').val(c.cnpj);
                $('#vizTelefone').val(c.telefone || '-');
                $('#vizEmail').val(c.email || '-');
                
                let endereco = '';
                if (c.logradouro) endereco += c.logradouro;
                if (c.numero) endereco += ', ' + c.numero;
                if (c.bairro) endereco += ' - ' + c.bairro;
                if (c.cidade) endereco += ' - ' + c.cidade;
                if (c.uf) endereco += '/' + c.uf;
                if (c.cep) endereco += ' - CEP: ' + c.cep;
                $('#vizEndereco').val(endereco || '-');
                
                $('#vizStatus').val(c.ativo == 1 ? 'Ativo' : 'Inativo');
                $('#modalVisualizacao').modal('show');
            }
        }
    });
}

function editar(id) {
    $.ajax({
        url: '../api/cliente.php',
        method: 'GET',
        data: {
            action: 'get',
            id: id
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const c = data.cliente;
                $('#clienteId').val(c.id);
                $('#razao_social').val(c.razao_social);
                $('#nome_fantasia').val(c.nome_fantasia);
                $('#cnpj').val(c.cnpj);
                $('#inscricao_estadual').val(c.inscricao_estadual);
                $('#inscricao_municipal').val(c.inscricao_municipal);
                $('#cep').val(c.cep);
                $('#logradouro').val(c.logradouro);
                $('#numero').val(c.numero);
                $('#complemento').val(c.complemento);
                $('#bairro').val(c.bairro);
                $('#cidade').val(c.cidade);
                $('#uf').val(c.uf);
                $('#telefone').val(c.telefone);
                $('#celular').val(c.celular);
                $('#email').val(c.email);
                $('#site').val(c.site);
                $('#logo_path').val(c.logo_path);
                $('#remove_logo').val('0'); // reset flag
                $('#ativo').prop('checked', c.ativo == 1);
                $('#modalTitle').text('Editar Cliente');
                
                // Mostrar preview do logo se existir
                if (c.logo_path) {
                    $('#logoImg').attr('src', '../' + c.logo_path);
                    $('#logoPreview').show();
                } else {
                    $('#logoPreview').hide();
                }
                
                // Voltar para a primeira aba
                $('#dados-tab').tab('show');
                
                $('#modalCliente').modal('show');
            }
        }
    });
}

function excluir(id) {
    if (confirm('Tem certeza que deseja excluir este cliente?')) {
        $.ajax({
            url: '../api/cliente.php',
            method: 'POST',
            data: {
                action: 'delete',
                id: id
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    loadClientes();
                    alert(data.message);
                } else {
                    alert(data.message || 'Erro ao excluir');
                }
            }
        });
    }
}

function resetForm() {
    $('#clienteForm')[0].reset();
    $('#clienteId').val('');
    $('#logo_path').val('');
    $('#remove_logo').val('0');
    $('#modalTitle').text('Novo Cliente');
    $('#ativo').prop('checked', true);
    $('#logoPreview').hide();
    $('#logoImg').attr('src', '');
    
    // Voltar para a primeira aba
    $('#dados-tab').tab('show');
}

function removerLogo() {
    $('#logo').val('');
    $('#logo_path').val('');
    $('#logoPreview').hide();
    $('#logoImg').attr('src', '');
    $('#remove_logo').val('1');
}
