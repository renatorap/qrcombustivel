let currentPage = 1;
let searchTerm = '';

$(document).ready(function() {
    loadVeiculos();
    loadSelects();

    // Novo/Editar veículo
    $('#veiculoForm').submit(function(e) {
        e.preventDefault();
        saveVeiculo();
    });

    // Buscar
    $('#searchInput').keyup(function() {
        searchTerm = $(this).val().trim();
        currentPage = 1;
        loadVeiculos();
    });

    $('.btn-buscar').click(function() {
        searchTerm = $('#searchInput').val().trim();
        currentPage = 1;
        loadVeiculos();
    });

    // Limpar formulário ao fechar modal
    $('#modalVeiculo').on('hidden.bs.modal', function () {
        resetForm();
    });

    // Controle de exibição KM/Hora baseado na forma de trabalho
    $('#id_forma_trabalho').change(function() {
        controlarCamposKmHora();
    });
});

function controlarCamposKmHora() {
    const formaTrabalho = $('#id_forma_trabalho').val();
    const formaTrabalhoTexto = $('#id_forma_trabalho option:selected').text().toLowerCase();
    
    // Ocultar todos os campos primeiro
    $('#campo-km-inicial').hide();
    $('#campo-hora-inicial').hide();
    $('#km_inicial_operacional').val('').prop('required', false);
    $('#hora_inicial_operacional').val('').prop('required', false);
    
    // Mostrar o campo apropriado baseado na seleção
    // id_forma_trabalho: 1 = Kilometragem, 2 = Hora, 3 = --
    if (formaTrabalho == '1' || formaTrabalhoTexto.includes('km') || formaTrabalhoTexto.includes('kilometragem')) {
        $('#campo-km-inicial').show();
        $('#km_inicial_operacional').prop('required', true);
    } else if (formaTrabalho == '2' || formaTrabalhoTexto.includes('hora')) {
        $('#campo-hora-inicial').show();
        $('#hora_inicial_operacional').prop('required', true);
    }
}

function loadSelects() {
    // Carregar Marcas
    $.get('../api/veiculo_selects.php?action=marcas', function(data) {
        if (data.success) {
            let options = '<option value="">Selecione...</option>';
            data.data.forEach(item => {
                options += `<option value="${item.id}">${item.nome}</option>`;
            });
            $('#id_marca_veiculo').html(options);
        }
    });

    // Carregar Cores
    $.get('../api/veiculo_selects.php?action=cores', function(data) {
        if (data.success) {
            let options = '<option value="">Selecione...</option>';
            data.data.forEach(item => {
                options += `<option value="${item.id}">${item.nome}</option>`;
            });
            $('#id_cor_veiculo').html(options);
        }
    });

    // Carregar Tipos
    $.get('../api/veiculo_selects.php?action=tipos', function(data) {
        if (data.success) {
            let options = '<option value="">Selecione...</option>';
            data.data.forEach(item => {
                options += `<option value="${item.id}">${item.nome}</option>`;
            });
            $('#id_tp_veiculo').html(options);
        }
    });

    // Carregar Categorias CNH
    $.get('../api/veiculo_selects.php?action=categorias', function(data) {
        if (data.success) {
            let options = '<option value="">Selecione...</option>';
            data.data.forEach(item => {
                options += `<option value="${item.id}">${item.nome}</option>`;
            });
            $('#id_cat_cnh').html(options);
        }
    });

    // Carregar Setores
    $.get('../api/veiculo_selects.php?action=setores', function(data) {
        if (data.success) {
            let options = '<option value="">Selecione...</option>';
            data.data.forEach(item => {
                options += `<option value="${item.id}">${item.nome}</option>`;
            });
            $('#id_setor').html(options);
        }
    });

    // Carregar Situações
    $.get('../api/veiculo_selects.php?action=situacoes', function(data) {
        if (data.success) {
            let options = '<option value="">Selecione...</option>';
            data.data.forEach(item => {
                options += `<option value="${item.id}">${item.nome}</option>`;
            });
            $('#id_situacao').html(options);
        }
    });

    // Carregar Formas de Trabalho
    $.get('../api/veiculo_selects.php?action=formas_trabalho', function(data) {
        if (data.success) {
            let options = '<option value="">Selecione...</option>';
            data.data.forEach(item => {
                options += `<option value="${item.id}">${item.nome}</option>`;
            });
            $('#id_forma_trabalho').html(options);
        }
    });
}

function saveVeiculo() {
    const formData = new FormData();
    const id = $('#veiculoId').val();
    
    formData.append('action', id ? 'update' : 'create');
    if (id) formData.append('id', id);
    
    // Dados Básicos
    formData.append('placa', $('#placa').val().trim());
    formData.append('placa_patrimonio', $('#placa_patrimonio').val().trim());
    formData.append('ano', $('#ano').val());
    formData.append('modelo', $('#modelo').val().trim());
    formData.append('id_marca_veiculo', $('#id_marca_veiculo').val());
    formData.append('id_cor_veiculo', $('#id_cor_veiculo').val());
    formData.append('id_tp_veiculo', $('#id_tp_veiculo').val());
    formData.append('id_cat_cnh', $('#id_cat_cnh').val() || '');
    
    // Características
    formData.append('capacidade_combustivel', $('#capacidade_combustivel').val());
    formData.append('atividade', $('#atividade').val().trim());
    formData.append('tem_seguro', $('#tem_seguro').is(':checked') ? '1' : '0');
    formData.append('tel_seguradora', $('#tel_seguradora').val().trim());
    
    // KM/Hora baseado na forma de trabalho
    const formaTrabalho = $('#id_forma_trabalho').val();
    if (formaTrabalho == '1') {
        // Kilometragem
        formData.append('km_inicial', $('#km_inicial_operacional').val() || '0');
        formData.append('hora_inicial', '');
    } else if (formaTrabalho == '2') {
        // Hora
        formData.append('km_inicial', '');
        formData.append('hora_inicial', $('#hora_inicial_operacional').val() || '0');
    } else {
        // Nenhum
        formData.append('km_inicial', '');
        formData.append('hora_inicial', '');
    }
    
    // Documentação
    formData.append('chassi', $('#chassi').val().trim());
    formData.append('renavam', $('#renavam').val().trim());
    formData.append('data_aquisicao', $('#data_aquisicao').val());
    
    // Operacional
    formData.append('id_setor', $('#id_setor').val());
    formData.append('id_forma_trabalho', $('#id_forma_trabalho').val());
    formData.append('id_situacao', $('#id_situacao').val());

    $.ajax({
        url: '../api/veiculo.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#modalVeiculo').modal('hide');
                resetForm();
                loadVeiculos();
                alert(data.message);
            } else {
                alert(data.message || 'Erro ao salvar');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro:', error);
            alert('Erro na comunicação com o servidor');
        }
    });
}

function loadVeiculos() {
    $.ajax({
        url: '../api/veiculo.php',
        method: 'GET',
        data: {
            action: 'list',
            search: searchTerm,
            page: currentPage
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                if (data.message && data.veiculos && data.veiculos.length === 0) {
                    // Mostrar mensagem informativa se retornar vazio
                    $('#veiculosTable').html(`<tr><td colspan="6" class="text-center text-warning">${data.message}</td></tr>`);
                    $('#paginacao').empty();
                } else {
                    renderTable(data.veiculos);
                    renderPagination(data.totalPages, data.currentPage);
                }
            }
        },
        error: function() {
            alert('Erro ao carregar veículos');
        }
    });
}

function renderTable(veiculos) {
    const tbody = $('#veiculosTable');
    tbody.empty();

    if (!veiculos || veiculos.length === 0) {
        tbody.html('<tr><td colspan="6" class="text-center text-muted">Nenhum veículo encontrado</td></tr>');
        return;
    }

    veiculos.forEach(veiculo => {
        const botoes = [];
        
        // Visualizar se tiver permissão
        if (typeof podeVisualizarVeiculo !== 'undefined' && podeVisualizarVeiculo) {
            botoes.push(`
                <button class="btn btn-sm btn-outline-primary me-1" title="Visualizar" onclick="visualizar(${veiculo.id_veiculo})">
                    <i class="fas fa-eye"></i>
                </button>
            `);
        }
        
        // Imprimir QR Code
        botoes.push(`
            <button class="btn btn-sm btn-outline-info me-1" title="Imprimir QR Code" onclick="imprimirQRCode(${veiculo.id_veiculo})">
                <i class="fas fa-qrcode"></i>
            </button>
        `);
        
        // Editar se tiver permissão
        if (typeof podeEditarVeiculo !== 'undefined' && podeEditarVeiculo) {
            botoes.push(`
                <button class="btn btn-sm btn-outline-warning me-1" title="Editar" onclick="editar(${veiculo.id_veiculo})">
                    <i class="fas fa-edit"></i>
                </button>
            `);
        }
        
        // Excluir se tiver permissão
        if (typeof podeExcluirVeiculo !== 'undefined' && podeExcluirVeiculo) {
            botoes.push(`
                <button class="btn btn-sm btn-outline-danger" title="Excluir" onclick="excluir(${veiculo.id_veiculo})">
                    <i class="fas fa-trash"></i>
                </button>
            `);
        }

        const row = `
            <tr style="vertical-align: middle;">
                <td><strong>${veiculo.placa || '-'}</strong></td>
                <td>${veiculo.modelo || '-'}</td>
                <td>${veiculo.marca_nome || '-'}</td>
                <td>${veiculo.ano || '-'}</td>
                <td>${veiculo.setor_nome || '-'}</td>
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
    loadVeiculos();
}

function visualizar(id) {
    $.ajax({
        url: '../api/veiculo.php',
        method: 'GET',
        data: {
            action: 'get',
            id: id
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const v = data.veiculo;
                $('#vizPlaca').val(v.placa || '');
                $('#vizModelo').val(v.modelo || '');
                $('#vizAno').val(v.ano || '');
                $('#vizMarca').val(v.marca_nome || '');
                $('#vizCor').val(v.cor_nome || '');
                $('#vizTipoVeiculo').val(v.tipo_nome || '');
                $('#vizSetor').val(v.setor_nome || '');
                $('#vizChassi').val(v.chassi || '');
                $('#vizRenavam').val(v.renavam || '');
                $('#vizCapacidade').val(v.capacidade_combustivel || '');
                $('#vizKmInicial').val(v.km_inicial || '');
                $('#vizSituacao').val(v.situacao_nome || '');
                $('#modalVisualizacao').modal('show');
            } else {
                alert(data.message || 'Erro ao carregar dados do veículo');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao visualizar:', error);
            alert('Erro ao carregar dados do veículo');
        }
    });
}

function editar(id) {
    $.ajax({
        url: '../api/veiculo.php',
        method: 'GET',
        data: {
            action: 'get',
            id: id
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const v = data.veiculo;
                $('#veiculoId').val(v.id_veiculo);
                
                // Dados Básicos
                $('#placa').val(v.placa || '');
                $('#placa_patrimonio').val(v.placa_patrimonio || '');
                $('#ano').val(v.ano || '');
                $('#modelo').val(v.modelo || '');
                $('#id_marca_veiculo').val(v.id_marca_veiculo || '');
                $('#id_cor_veiculo').val(v.id_cor_veiculo || '');
                $('#id_tp_veiculo').val(v.id_tp_veiculo || '');
                $('#id_cat_cnh').val(v.id_cat_cnh || '');
                
                // Características
                $('#capacidade_combustivel').val(v.capacidade_combustivel || 9999);
                $('#atividade').val(v.atividade || '');
                $('#tem_seguro').prop('checked', v.tem_seguro == 1);
                $('#tel_seguradora').val(v.tel_seguradora || '');
                
                // Documentação
                $('#chassi').val(v.chassi || '');
                $('#renavam').val(v.renavam || '');
                $('#data_aquisicao').val(v.data_aquisicao || '');
                
                // Operacional
                $('#id_setor').val(v.id_setor || '');
                $('#id_forma_trabalho').val(v.id_forma_trabalho || '');
                $('#id_situacao').val(v.id_situacao || '');
                
                // Controlar campos KM/Hora após popular forma de trabalho
                controlarCamposKmHora();
                
                // Popular KM ou Hora baseado na forma de trabalho
                if (v.id_forma_trabalho == '1' && v.km_inicial) {
                    $('#km_inicial_operacional').val(v.km_inicial);
                } else if (v.id_forma_trabalho == '2' && v.hora_inicial) {
                    $('#hora_inicial_operacional').val(v.hora_inicial);
                }
                
                $('#modalTitle').text('Editar Veículo');
                
                // Carregar combustíveis do veículo
                carregarCombustiveis(v.id_veiculo);
                
                // Carregar informações do QR Code
                carregarInfoQRCode(v.id_veiculo);
                
                $('#modalVeiculo').modal('show');
            } else {
                alert(data.message || 'Erro ao carregar dados do veículo');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao editar:', error);
            alert('Erro ao carregar dados do veículo');
        }
    });
}

function excluir(id) {
    if (confirm('Tem certeza que deseja excluir este veículo?')) {
        $.ajax({
            url: '../api/veiculo.php',
            method: 'POST',
            data: {
                action: 'delete',
                id: id
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    loadVeiculos();
                    alert(data.message);
                } else {
                    alert(data.message || 'Erro ao excluir');
                }
            }
        });
    }
}

// Imprimir QR Code do veículo
function imprimirQRCode(id) {
    window.open(`veiculo_qrcode_pdf.php?id=${id}`, '_blank');
}

function resetForm() {
    $('#veiculoForm')[0].reset();
    $('#veiculoId').val('');
    $('#modalTitle').text('Novo Veículo');
    $('#capacidade_combustivel').val(9999);
    
    // Ocultar campos KM/Hora e remover required
    $('#campo-km-inicial').hide();
    $('#campo-hora-inicial').hide();
    $('#km_inicial_operacional').val('').prop('required', false);
    $('#hora_inicial_operacional').val('').prop('required', false);
    
    // Reset aba de combustíveis
    $('#combustiveis-message').show();
    $('#combustiveis-content').hide();
    
    // Reset aba de QR Code
    $('#qrcode-section').hide();
    $('#qrcode-vazio').show();
    
    // Voltar para primeira aba
    $('#dados-basicos-tab').tab('show');
}

// Carregar combustíveis do veículo
function carregarCombustiveis(idVeiculo) {
    if (!idVeiculo) {
        $('#combustiveis-message').show();
        $('#combustiveis-content').hide();
        return;
    }
    
    $.ajax({
        url: '../api/veiculo.php',
        method: 'GET',
        data: {
            action: 'list_combustiveis',
            id: idVeiculo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderCombustiveis(response.produtos, response.selecionados);
                $('#combustiveis-message').hide();
                $('#combustiveis-content').show();
            } else {
                alert(response.message || 'Erro ao carregar combustíveis');
            }
        },
        error: function(xhr, status, error) {
            alert('Erro ao carregar combustíveis');
        }
    });
}

// Renderizar lista de combustíveis
function renderCombustiveis(produtos, selecionados) {
    const container = $('#combustiveis-list');
    container.empty();
    
    if (!produtos || produtos.length === 0) {
        container.html('<p class="text-muted">Nenhum combustível disponível</p>');
        return;
    }
    
    produtos.forEach(function(produto) {
        const idProduto = parseInt(produto.id_produto);
        const isChecked = selecionados.includes(idProduto);
        const checked = isChecked ? 'checked' : '';
        const checkbox = `
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" value="${produto.id_produto}" 
                       id="combustivel_${produto.id_produto}" ${checked}>
                <label class="form-check-label" for="combustivel_${produto.id_produto}">
                    ${produto.descricao}
                </label>
            </div>
        `;
        container.append(checkbox);
    });
}

// Salvar combustíveis do veículo
window.salvarCombustiveis = function() {
    const idVeiculo = $('#veiculoId').val();
    
    if (!idVeiculo) {
        alert('Salve o veículo primeiro');
        return;
    }
    
    // Coletar IDs dos combustíveis selecionados
    const combustiveisSelecionados = [];
    $('#combustiveis-list input[type="checkbox"]:checked').each(function() {
        combustiveisSelecionados.push($(this).val());
    });
    
    $.ajax({
        url: '../api/veiculo.php',
        method: 'POST',
        data: {
            action: 'save_combustiveis',
            id_veiculo: idVeiculo,
            combustiveis: combustiveisSelecionados.join(',')
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Combustíveis salvos com sucesso!');
                // Recarregar a lista para refletir as mudanças
                carregarCombustiveis(idVeiculo);
            } else {
                alert(response.message || 'Erro ao salvar combustíveis');
            }
        },
        error: function(xhr, status, error) {
            alert('Erro ao salvar combustíveis');
        }
    });
};

// Gerar Nova Via do QR Code
window.gerarNovaViaVeiculo = function() {
    const id = $('#veiculoId').val();
    if (!id) {
        alert('Salve o veículo primeiro antes de gerar uma nova via do QR Code');
        return;
    }

    if (!confirm('Tem certeza que deseja gerar uma nova via do QR Code? A via atual será inativada.')) {
        return;
    }

    $.ajax({
        url: '../api/veiculo_qrcode.php',
        method: 'POST',
        data: {
            action: 'gerar_nova_via',
            id_veiculo: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Nova via gerada com sucesso!');
                carregarInfoQRCode(id);
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

// Ver Histórico de Vias do QR Code
window.verHistoricoQRCode = function() {
    const id = $('#veiculoId').val();
    if (!id) {
        alert('Salve o veículo primeiro');
        return;
    }

    $.ajax({
        url: '../api/veiculo_qrcode.php',
        method: 'GET',
        data: {
            action: 'listar_historico',
            id_veiculo: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarHistoricoQRCode(response.data);
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
function mostrarHistoricoQRCode(vias) {
    if (!vias || vias.length === 0) {
        alert('Nenhuma via encontrada no histórico');
        return;
    }

    let html = `
        <div class="modal fade" id="modalHistoricoQRCode" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-history"></i> Histórico de Vias - QR Code</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Código Único</th>
                                    <th>Início Vigência</th>
                                    <th>Fim Vigência</th>
                                    <th>Situação</th>
                                </tr>
                            </thead>
                            <tbody>
    `;

    vias.forEach(function(via) {
        const inicioVigencia = via.inicio_vigencia ? new Date(via.inicio_vigencia).toLocaleString('pt-BR') : '-';
        const fimVigencia = via.fim_vigencia ? new Date(via.fim_vigencia).toLocaleString('pt-BR') : 'Ativa';
        const situacao = via.situacao_nome || '-';
        const codigoUnico = via.codigo_unico || '-';

        html += `
            <tr>
                <td><code>${codigoUnico}</code></td>
                <td>${inicioVigencia}</td>
                <td>${fimVigencia}</td>
                <td><span class="badge bg-${via.id_situacao == 1 ? 'success' : 'secondary'}">${situacao}</span></td>
            </tr>
        `;
    });

    html += `
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal anterior se existir
    $('#modalHistoricoQRCode').remove();
    
    // Adicionar e mostrar novo modal
    $('body').append(html);
    $('#modalHistoricoQRCode').modal('show');
}

// Carregar informações da via ativa do QR Code
function carregarInfoQRCode(idVeiculo) {
    if (!idVeiculo) {
        $('#qrcode-section').hide();
        $('#qrcode-vazio').show();
        return;
    }

    $.ajax({
        url: '../api/veiculo_qrcode.php',
        method: 'GET',
        data: {
            action: 'get_via_ativa',
            id_veiculo: idVeiculo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const via = response.data;
                const inicioVigencia = via.inicio_vigencia ? new Date(via.inicio_vigencia).toLocaleString('pt-BR') : '-';
                const codigoUnico = via.codigo_unico || '-';
                
                $('#qrcode-info').html(`
                    <p><strong>Código Único:</strong> <code>${codigoUnico}</code></p>
                    <p><strong>Situação:</strong> <span class="badge bg-success">${via.situacao_nome || 'Ativa'}</span></p>
                    <p><strong>Data de Geração:</strong> ${inicioVigencia}</p>
                `);
                $('#qrcode-section').show();
                $('#qrcode-vazio').hide();
            } else {
                $('#qrcode-info').html('<em>Nenhuma via ativa. Será gerada automaticamente ao salvar.</em>');
                $('#qrcode-section').show();
                $('#qrcode-vazio').hide();
            }
        },
        error: function() {
            $('#qrcode-section').hide();
            $('#qrcode-vazio').show();
        }
    });
}