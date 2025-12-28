let paginaAtual = 1;
let totalPaginas = 1;
let searchTerm = '';

$(document).ready(function() {
    // Carrega condutores ao iniciar
    loadCondutores();

    // Carrega os selects ao abrir o modal
    $('#modalCondutor').on('show.bs.modal', function() {
        loadSelects();
    });

    // Busca - keyup para pesquisa em tempo real
    $('#searchInput').on('keyup', function(e) {
        searchTerm = $(this).val().trim();
        paginaAtual = 1;
        loadCondutores();
    });

    // Busca - Enter
    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            searchTerm = $(this).val().trim();
            paginaAtual = 1;
            loadCondutores();
        }
    });

    // Busca - botão
    $('.btn-buscar').on('click', function() {
        searchTerm = $('#searchInput').val().trim();
        paginaAtual = 1;
        loadCondutores();
    });

    // Submit do formulário
    $('#condutorForm').on('submit', function(e) {
        e.preventDefault();
        saveCondutor();
    });

    // Reset form ao fechar modal
    $('#modalCondutor').on('hidden.bs.modal', function() {
        resetForm();
    });

    // Controla obrigatoriedade do cargo baseado no checkbox "É Motorista"
    $('#e_condutor').on('change', function() {
        if ($(this).is(':checked')) {
            // É motorista - cargo não é obrigatório
            $('#id_cargo').prop('required', false);
            $('#cargo-obrigatorio').hide();
        } else {
            // Não é motorista - cargo é obrigatório
            $('#id_cargo').prop('required', true);
            $('#cargo-obrigatorio').show();
        }
    });

    // Inicializar estado do campo cargo
    $('#e_condutor').trigger('change');

    // Carrega os selects para os dropdowns
    function loadSelects() {
        // Categorias CNH
        $.ajax({
            url: '../api/condutor_selects.php',
            method: 'GET',
            data: { action: 'categorias' },
            success: function(response) {
                if (response.success) {
                    const select = $('#id_cat_cnh');
                    select.empty().append('<option value="">Selecione...</option>');
                    response.data.forEach(item => {
                        select.append(`<option value="${item.id}">${item.nome}</option>`);
                    });
                }
            }
        });

        // Sexos
        $.ajax({
            url: '../api/condutor_selects.php',
            method: 'GET',
            data: { action: 'sexos' },
            success: function(response) {
                if (response.success) {
                    const select = $('#id_sexo');
                    select.empty().append('<option value="">Selecione...</option>');
                    response.data.forEach(item => {
                        select.append(`<option value="${item.id}">${item.nome}</option>`);
                    });
                }
            }
        });

        // Tipos Sanguíneos
        $.ajax({
            url: '../api/condutor_selects.php',
            method: 'GET',
            data: { action: 'tipos_sanguineos' },
            success: function(response) {
                if (response.success) {
                    const select = $('#id_tp_sanguineo');
                    select.empty().append('<option value="">Selecione...</option>');
                    response.data.forEach(item => {
                        select.append(`<option value="${item.id}">${item.nome}</option>`);
                    });
                }
            }
        });

        // Cargos
        $.ajax({
            url: '../api/condutor_selects.php',
            method: 'GET',
            data: { action: 'cargos' },
            success: function(response) {
                if (response.success) {
                    const select = $('#id_cargo');
                    select.empty().append('<option value="">Selecione...</option>');
                    response.data.forEach(item => {
                        select.append(`<option value="${item.id}">${item.nome}</option>`);
                    });
                }
            }
        });

        // Situações
        $.ajax({
            url: '../api/condutor_selects.php',
            method: 'GET',
            data: { action: 'situacoes' },
            success: function(response) {
                if (response.success) {
                    const select = $('#id_situacao');
                    select.empty().append('<option value="">Selecione...</option>');
                    response.data.forEach(item => {
                        select.append(`<option value="${item.id}">${item.nome}</option>`);
                    });
                }
            }
        });
    }

    // Carrega lista de condutores
    function loadCondutores() {
        $.ajax({
            url: '../api/condutor.php',
            method: 'GET',
            data: {
                action: 'list',
                page: paginaAtual,
                search: searchTerm
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.message && response.data && response.data.length === 0) {
                        // Mostrar mensagem informativa se retornar vazio
                        $('#condutoresTable').html(`<tr><td colspan="7" class="text-center text-warning">${response.message}</td></tr>`);
                        $('#paginacao').empty();
                    } else {
                        renderTable(response.data);
                        totalPaginas = response.totalPages;
                        renderPaginacao();
                    }
                }
            },
            error: function() {
                $('#condutoresTable').html(`
                    <tr>
                        <td colspan="7" class="text-center text-danger">Erro ao carregar condutores</td>
                    </tr>
                `);
            }
        });
    }

    // Renderiza tabela
    function renderTable(condutores) {
        const tbody = $('#condutoresTable');
        tbody.empty();

        if (!condutores || condutores.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center text-muted">Nenhum condutor encontrado</td></tr>');
            return;
        }

        condutores.forEach(function(condutor) {
            // Formata a data de validade da CNH
            let validadeCnh = condutor.validade_cnh ? formatarData(condutor.validade_cnh) : '-';
            
            // Verifica se a CNH está vencida ou próxima de vencer
            let classeCnh = '';
            if (condutor.validade_cnh) {
                const hoje = new Date();
                const validade = new Date(condutor.validade_cnh);
                const diasRestantes = Math.ceil((validade - hoje) / (1000 * 60 * 60 * 24));
                
                if (diasRestantes < 0) {
                    classeCnh = 'text-danger fw-bold';
                    validadeCnh += ' <i class="fas fa-exclamation-triangle"></i>';
                } else if (diasRestantes <= 30) {
                    classeCnh = 'text-warning fw-bold';
                    validadeCnh += ' <i class="fas fa-exclamation-circle"></i>';
                }
            }

            const botoes = [];
            
            // Visualizar se tiver permissão
            if (podeVisualizarCondutor) {
                botoes.push(`
                    <button class="btn btn-sm btn-outline-primary me-1" title="Visualizar" onclick="visualizar(${condutor.id_condutor})">
                        <i class="fas fa-eye"></i>
                    </button>
                `);
            }
            
            // Imprimir Crachá - sempre visível para condutores ativos
            if (condutor.id_situacao == 1) {
                botoes.push(`
                    <button class="btn btn-sm btn-outline-info me-1" title="Imprimir Crachá" onclick="imprimirCracha(${condutor.id_condutor})">
                        <i class="fas fa-id-card"></i>
                    </button>
                `);
            }
            
            // Editar se tiver permissão
            if (podeEditarCondutor) {
                botoes.push(`
                    <button class="btn btn-sm btn-outline-warning me-1" title="Editar" onclick="editar(${condutor.id_condutor})">
                        <i class="fas fa-edit"></i>
                    </button>
                `);
            }
            
            // Excluir se tiver permissão
            if (podeExcluirCondutor) {
                botoes.push(`
                    <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="excluir(${condutor.id_condutor})">
                        <i class="fas fa-trash"></i>
                    </button>
                `);
            }

            const row = `
                <tr style="vertical-align: middle;">
                    <td><strong>${condutor.nome || '-'}</strong></td>
                    <td>${condutor.cpf || '-'}</td>
                    <td>${condutor.cnh || '-'}</td>
                    <td class="${classeCnh}">${validadeCnh}</td>
                    <td>${condutor.cargo_nome || '-'}</td>
                    <td>${condutor.situacao_nome || '-'}</td>
                    <td>
                        ${botoes.join('')}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Renderiza paginação
    function renderPaginacao() {
        const paginacao = $('#paginacao');
        paginacao.empty();

        if (totalPaginas <= 1) {
            return;
        }

        // Botão Primeira Página
        paginacao.append(`
            <li class="page-item ${paginaAtual === 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="mudarPagina(1)" title="Primeira">
                    <i class="fas fa-angle-double-left"></i>
                </a>
            </li>
        `);

        // Botão Anterior
        paginacao.append(`
            <li class="page-item ${paginaAtual === 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="mudarPagina(${paginaAtual - 1})" title="Anterior">
                    <i class="fas fa-angle-left"></i>
                </a>
            </li>
        `);

        // Páginas (máximo 5 visíveis)
        let startPage = Math.max(1, paginaAtual - 2);
        let endPage = Math.min(totalPaginas, paginaAtual + 2);

        // Ajustar para sempre mostrar 5 páginas quando possível
        if (endPage - startPage < 4) {
            if (startPage === 1) {
                endPage = Math.min(totalPaginas, startPage + 4);
            } else if (endPage === totalPaginas) {
                startPage = Math.max(1, endPage - 4);
            }
        }

        // Mostrar primeira página se não estiver no range
        if (startPage > 1) {
            paginacao.append(`
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" onclick="mudarPagina(1)">1</a>
                </li>
            `);
            if (startPage > 2) {
                paginacao.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
        }

        // Páginas do range
        for (let i = startPage; i <= endPage; i++) {
            const active = i === paginaAtual ? 'active' : '';
            paginacao.append(`
                <li class="page-item ${active}">
                    <a class="page-link" href="javascript:void(0)" onclick="mudarPagina(${i})">${i}</a>
                </li>
            `);
        }

        // Mostrar última página se não estiver no range
        if (endPage < totalPaginas) {
            if (endPage < totalPaginas - 1) {
                paginacao.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
            paginacao.append(`
                <li class="page-item">
                    <a class="page-link" href="javascript:void(0)" onclick="mudarPagina(${totalPaginas})">${totalPaginas}</a>
                </li>
            `);
        }

        // Botão Próxima
        paginacao.append(`
            <li class="page-item ${paginaAtual === totalPaginas ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="mudarPagina(${paginaAtual + 1})" title="Próxima">
                    <i class="fas fa-angle-right"></i>
                </a>
            </li>
        `);

        // Botão Última Página
        paginacao.append(`
            <li class="page-item ${paginaAtual === totalPaginas ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="mudarPagina(${totalPaginas})" title="Última">
                    <i class="fas fa-angle-double-right"></i>
                </a>
            </li>
        `);
    }

    // Muda página
    window.mudarPagina = function(pagina) {
        if (pagina >= 1 && pagina <= totalPaginas) {
            paginaAtual = pagina;
            loadCondutores();
        }
    };

    // Salva condutor (criar ou editar)
    function saveCondutor() {
        const id = $('#condutorId').val();
        const formData = {
            action: id ? 'update' : 'create',
            nome: $('#nome').val().trim(),
            cnh: $('#cnh').val().trim(),
            validade_cnh: $('#validade_cnh').val(),
            e_condutor: $('#e_condutor').is(':checked') ? 1 : 0
        };

        // Adicionar campos opcionais somente se preenchidos
        const matricula = $('#matricula').val().trim();
        if (matricula) formData.matricula = matricula;
        
        const data_nascimento = $('#data_nascimento').val();
        if (data_nascimento) formData.data_nascimento = data_nascimento;
        
        const rg = $('#rg').val().trim();
        if (rg) formData.rg = rg;
        
        const cpf = $('#cpf').val().trim();
        if (cpf) formData.cpf = cpf;
        
        const telefone = $('#telefone').val().trim();
        if (telefone) formData.telefone = telefone;
        
        const email = $('#email').val().trim();
        if (email) formData.email = email;
        
        const id_cat_cnh = $('#id_cat_cnh').val();
        if (id_cat_cnh) formData.id_cat_cnh = id_cat_cnh;
        
        const id_sexo = $('#id_sexo').val();
        if (id_sexo) formData.id_sexo = id_sexo;
        
        const id_tp_sanguineo = $('#id_tp_sanguineo').val();
        if (id_tp_sanguineo) formData.id_tp_sanguineo = id_tp_sanguineo;
        
        const id_cargo = $('#id_cargo').val();
        if (id_cargo) formData.id_cargo = id_cargo;
        
        const id_situacao = $('#id_situacao').val();
        if (id_situacao) formData.id_situacao = id_situacao;

        if (id) {
            formData.id = id;
        }

        // Validações
        if (!formData.nome) {
            alert('O nome é obrigatório');
            $('#nome').focus();
            return;
        }

        if (!formData.cnh) {
            alert('A CNH é obrigatória');
            $('#cnh').focus();
            return;
        }

        if (!formData.validade_cnh) {
            alert('A validade da CNH é obrigatória');
            $('#validade_cnh').focus();
            return;
        }

        // Validar cargo se não for motorista
        if (!$('#e_condutor').is(':checked') && !formData.id_cargo) {
            alert('O cargo é obrigatório quando não for motorista');
            $('#id_cargo').focus();
            $('#profissional-tab').tab('show');
            return;
        }

        // Criar FormData para enviar arquivo
        const formDataObj = new FormData();
        Object.keys(formData).forEach(key => {
            formDataObj.append(key, formData[key]);
        });

        // Adicionar arquivo de foto se houver
        const fotoFile = $('#foto')[0].files[0];
        if (fotoFile) {
            formDataObj.append('foto', fotoFile);
        } else if ($('#foto_path').val()) {
            formDataObj.append('foto_path', $('#foto_path').val().trim());
        }

        // Flag de remoção de foto
        formDataObj.append('remove_foto', $('#remove_foto').val());

        $.ajax({
            url: '../api/condutor.php',
            method: 'POST',
            data: formDataObj,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#modalCondutor').modal('hide');
                    loadCondutores();
                    alert(response.message || 'Condutor salvo com sucesso!');
                } else {
                    alert('Erro: ' + (response.message || 'Erro desconhecido ao salvar condutor'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro AJAX:', xhr.responseText);
                alert('Erro ao comunicar com o servidor: ' + error + '\nDetalhes: ' + xhr.responseText);
            }
        });
    }

    // Visualiza condutor
    window.visualizar = function(id) {
        $.ajax({
            url: '../api/condutor.php',
            method: 'GET',
            data: {
                action: 'get',
                id: id
            },
            success: function(response) {
                if (response.success) {
                    const c = response.data;
                    $('#vizNome').val(c.nome || '');
                    $('#vizDataNasc').val(c.data_nascimento ? formatarData(c.data_nascimento) : '-');
                    $('#vizCpf').val(c.cpf || '-');
                    $('#vizRg').val(c.rg || '-');
                    $('#vizTelefone').val(c.telefone || '-');
                    $('#vizCnh').val(c.cnh || '');
                    $('#vizCategoria').val(c.categoria_nome || '-');
                    $('#vizValidadeCnh').val(c.validade_cnh ? formatarData(c.validade_cnh) : '-');
                    $('#vizCargo').val(c.cargo_nome || '-');
                    $('#vizSituacao').val(c.situacao_nome || '-');
                    
                    $('#modalVisualizacao').modal('show');
                } else {
                    alert(response.message || 'Erro ao carregar condutor');
                }
            },
            error: function() {
                alert('Erro ao carregar condutor');
            }
        });
    };

    // Edita condutor
    window.editar = function(id) {
        $.ajax({
            url: '../api/condutor.php',
            method: 'GET',
            data: {
                action: 'get',
                id: id
            },
            success: function(response) {
                if (response.success) {
                    const c = response.data;
                    
                    $('#modalTitle').text('Editar Condutor');
                    $('#condutorId').val(c.id_condutor);
                    $('#nome').val(c.nome || '');
                    $('#matricula').val(c.matricula || '');
                    $('#data_nascimento').val(c.data_nascimento || '');
                    $('#rg').val(c.rg || '');
                    $('#cpf').val(c.cpf || '');
                    $('#cnh').val(c.cnh || '');
                    $('#validade_cnh').val(c.validade_cnh || '');
                    $('#telefone').val(c.telefone || '');
                    $('#email').val(c.email || '');
                    $('#e_condutor').prop('checked', c.e_condutor == 1);
                    
                    // Definir todos os selects com pequeno delay para garantir que foram carregados via AJAX
                    setTimeout(function() {
                        $('#id_cat_cnh').val(c.id_cat_cnh || '');
                        $('#id_sexo').val(c.id_sexo || '');
                        $('#id_tp_sanguineo').val(c.id_tp_sanguineo || '');
                        $('#id_cargo').val(c.id_cargo || '');
                        $('#id_situacao').val(c.id_situacao || '');
                        // Atualizar estado do campo cargo após definir o checkbox
                        $('#e_condutor').trigger('change');
                    }, 100);
                    
                    // Foto
                    $('#foto_path').val(c.foto || '');
                    $('#remove_foto').val('0');
                    if (c.foto) {
                        $('#fotoImg').attr('src', '../' + c.foto);
                        $('#fotoPreview').show();
                    } else {
                        $('#fotoPreview').hide();
                    }
                    
                    // Carregar informações do crachá e mostrar aba
                    $('#cracha-section').show();
                    $('#cracha-vazio').hide();
                    carregarInfoCracha(c.id_condutor);
                    
                    $('#modalCondutor').modal('show');
                } else {
                    alert(response.message || 'Erro ao carregar condutor');
                }
            },
            error: function() {
                alert('Erro ao carregar condutor');
            }
        });
    };

    // Exclui condutor
    window.excluir = function(id) {
        if (!confirm('Deseja realmente excluir este condutor?')) {
            return;
        }

        $.ajax({
            url: '../api/condutor.php',
            method: 'POST',
            data: {
                action: 'delete',
                id: id
            },
            success: function(response) {
                if (response.success) {
                    loadCondutores();
                    alert(response.message || 'Condutor excluído com sucesso!');
                } else {
                    alert(response.message || 'Erro ao excluir condutor');
                }
            },
            error: function() {
                alert('Erro ao excluir condutor');
            }
        });
    };

    // Reset do formulário
    function resetForm() {
        $('#condutorForm')[0].reset();
        $('#condutorId').val('');
        $('#modalTitle').text('Novo Condutor');
        $('#e_condutor').prop('checked', true);
        
        // Limpar foto
        $('#foto_path').val('');
        $('#remove_foto').val('0');
        $('#fotoPreview').hide();
        
        // Ocultar seção de crachá (só aparece ao editar)
        $('#cracha-section').hide();
        $('#cracha-vazio').show();
        
        // Volta para primeira aba
        $('#dados-pessoais-tab').tab('show');
    }

    // Remover foto
    window.removerFoto = function() {
        $('#foto').val('');
        $('#foto_path').val('');
        $('#remove_foto').val('1');
        $('#fotoPreview').hide();
    };

    // Preview da foto ao selecionar
    $('#foto').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#fotoImg').attr('src', e.target.result);
                $('#fotoPreview').show();
                $('#remove_foto').val('0');
            };
            reader.readAsDataURL(file);
        }
    });

    // Imprimir Crachá
    window.imprimirCracha = function(id) {
        // Verificar se existe via válida antes de abrir o PDF
        $.ajax({
            url: '../api/condutor_cracha.php',
            method: 'GET',
            data: {
                action: 'verificar_via',
                id_condutor: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.tem_via) {
                    // Abrir PDF diretamente
                    window.open('condutor_cracha_pdf.php?id=' + id, '_blank');
                } else {
                    alert(response.message || 'Condutor não possui via de crachá válida. Gere uma nova via primeiro.');
                }
            },
            error: function() {
                alert('Erro ao verificar crachá do condutor');
            }
        });
    };

    // Baixar Crachá em PDF
    window.baixarCrachaPdf = function(id) {
        window.open('condutor_cracha_pdf.php?id=' + id + '&download=1', '_blank');
    };

    // Gerar Nova Via do Crachá
    window.gerarNovaVia = function() {
        const id = $('#condutorId').val();
        if (!id) {
            alert('Salve o condutor primeiro antes de gerar uma nova via do crachá');
            return;
        }

        if (!confirm('Tem certeza que deseja gerar uma nova via do crachá? A via atual será inativada.')) {
            return;
        }

        $.ajax({
            url: '../api/condutor_cracha.php',
            method: 'POST',
            data: {
                action: 'gerar_nova_via',
                id_condutor: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Nova via gerada com sucesso!');
                    carregarInfoCracha(id);
                } else {
                    alert(response.message || 'Erro ao gerar nova via');
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro:', xhr.responseText);
                alert('Erro ao gerar nova via: ' + error);
            }
        });
    };

    // Ver Histórico de Vias do Crachá
    window.verHistoricoCracha = function() {
        const id = $('#condutorId').val();
        if (!id) {
            alert('Salve o condutor primeiro');
            return;
        }

        $.ajax({
            url: '../api/condutor_cracha.php',
            method: 'GET',
            data: {
                action: 'listar_historico',
                id_condutor: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    mostrarHistoricoCracha(response.data);
                } else {
                    alert(response.message || 'Erro ao buscar histórico');
                }
            },
            error: function() {
                alert('Erro ao buscar histórico de vias');
            }
        });
    };

    // Mostrar histórico de vias em modal
    function mostrarHistoricoCracha(vias) {
        let html = '<div class="table-responsive"><table class="table table-sm">';
        html += '<thead><tr><th>Via</th><th>Situação</th><th>Início</th><th>Fim</th></tr></thead><tbody>';
        
        vias.forEach(function(via) {
            const inicio = via.inicio_vigencia ? new Date(via.inicio_vigencia).toLocaleString('pt-BR') : '-';
            const fim = via.fim_vigencia ? new Date(via.fim_vigencia).toLocaleString('pt-BR') : 'Vigente';
            html += `<tr>
                <td>#${via.id}</td>
                <td>${via.situacao_nome}</td>
                <td>${inicio}</td>
                <td>${fim}</td>
            </tr>`;
        });
        
        html += '</tbody></table></div>';
        
        const modal = $('<div class="modal fade" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">' +
            '<div class="modal-header"><h5 class="modal-title">Histórico de Vias do Crachá</h5>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
            '<div class="modal-body">' + html + '</div>' +
            '<div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button></div>' +
            '</div></div></div>');
        
        modal.modal('show');
        modal.on('hidden.bs.modal', function() {
            modal.remove();
        });
    }

    // Carregar informações do crachá ativo
    function carregarInfoCracha(idCondutor) {
        $.ajax({
            url: '../api/condutor_cracha.php',
            method: 'GET',
            data: {
                action: 'get_via_ativa',
                id_condutor: idCondutor
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const via = response.data;
                    const inicio = new Date(via.inicio_vigencia).toLocaleDateString('pt-BR');
                    $('#cracha-info').html(`
                        <strong>Via Ativa:</strong> #${via.id} | 
                        <strong>Início:</strong> ${inicio} | 
                        <strong>Situação:</strong> ${via.situacao_nome}
                    `);
                    $('#cracha-section').show();
                } else {
                    $('#cracha-info').html('<em>Nenhuma via ativa. Será gerada automaticamente ao salvar.</em>');
                    $('#cracha-section').show();
                }
            },
            error: function() {
                $('#cracha-section').hide();
            }
        });
    }

    // Formata data YYYY-MM-DD para DD/MM/YYYY
    function formatarData(data) {
        if (!data) return '-';
        const partes = data.split('-');
        if (partes.length === 3) {
            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        }
        return data;
    }
});
