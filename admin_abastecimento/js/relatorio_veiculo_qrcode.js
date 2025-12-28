let veiculos = [];
let veiculosSelecionados = new Set();

$(document).ready(function() {
    // Carregar situações para o filtro
    carregarSituacoes();
    
    // Carregar veículos ao iniciar
    carregarVeiculos();
    
    // Event listeners para filtros com debounce
    let debounceTimer;
    $('#filtroPlaca, #filtroModelo').on('keyup', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(aplicarFiltros, 500);
    });
    
    $('#filtroSituacao').on('change', aplicarFiltros);
});

// Carregar situações para o filtro
function carregarSituacoes() {
    $.ajax({
        url: '../api/veiculo_selects.php',
        method: 'GET',
        data: { action: 'situacoes' },
        success: function(response) {
            if (response.success) {
                const select = $('#filtroSituacao');
                select.empty().append('<option value="">Todas</option>');
                response.data.forEach(item => {
                    select.append(`<option value="${item.id}">${item.nome}</option>`);
                });
            }
        }
    });
}

// Carregar veículos do servidor
function carregarVeiculos() {
    $('#loadingIndicator').show();
    $('#veiculosList').hide();
    
    const filtros = {
        action: 'list_for_report',
        placa: $('#filtroPlaca').val().trim(),
        modelo: $('#filtroModelo').val().trim(),
        id_situacao: $('#filtroSituacao').val()
    };
    
    $.ajax({
        url: '../api/veiculo.php',
        method: 'GET',
        data: filtros,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                veiculos = response.data || response.veiculos || [];
                renderizarVeiculos();
                atualizarResumo();
            } else {
                mostrarMensagem('Erro ao carregar veículos', 'danger');
            }
        },
        error: function() {
            mostrarMensagem('Erro ao comunicar com o servidor', 'danger');
        },
        complete: function() {
            $('#loadingIndicator').hide();
            $('#veiculosList').show();
        }
    });
}

// Renderizar lista de veículos
function renderizarVeiculos() {
    const container = $('#veiculosList');
    container.empty();
    
    if (veiculos.length === 0) {
        container.html(`
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle"></i>
                Nenhum veículo encontrado com os filtros aplicados
            </div>
        `);
        return;
    }
    
    veiculos.forEach(veiculo => {
        const isSelected = veiculosSelecionados.has(veiculo.id_veiculo);
        
        const card = $(`
            <div class="veiculo-card ${isSelected ? 'selected' : ''}" data-id="${veiculo.id_veiculo}">
                <div class="d-flex align-items-start">
                    <input type="checkbox" 
                           class="form-check-input veiculo-checkbox" 
                           ${isSelected ? 'checked' : ''} 
                           data-id="${veiculo.id_veiculo}">
                    <div class="veiculo-info">
                        <div class="veiculo-placa">${veiculo.placa}</div>
                        <div class="veiculo-detalhes">
                            <span><i class="fas fa-car"></i> ${veiculo.modelo || '-'}</span>
                            <span class="ms-2"><i class="fas fa-tags"></i> ${veiculo.marca_nome || '-'}</span>
                            <span class="ms-2"><i class="fas fa-calendar"></i> ${veiculo.ano || '-'}</span>
                        </div>
                        <div class="veiculo-detalhes mt-1">
                            <small>
                                <span><i class="fas fa-briefcase"></i> ${veiculo.setor_nome || '-'}</span>
                                <span class="ms-2"><i class="fas fa-info-circle"></i> ${veiculo.situacao_nome || '-'}</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        // Click no card ou checkbox para selecionar/desselecionar
        card.on('click', function(e) {
            if (!$(e.target).is('input[type="checkbox"]')) {
                const checkbox = $(this).find('.veiculo-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked'));
                checkbox.trigger('change');
            }
        });
        
        card.find('.veiculo-checkbox').on('change', function(e) {
            e.stopPropagation();
            const id = parseInt($(this).data('id'));
            if ($(this).is(':checked')) {
                veiculosSelecionados.add(id);
                $(this).closest('.veiculo-card').addClass('selected');
            } else {
                veiculosSelecionados.delete(id);
                $(this).closest('.veiculo-card').removeClass('selected');
            }
            atualizarResumo();
        });
        
        container.append(card);
    });
}

// Aplicar filtros
function aplicarFiltros() {
    veiculosSelecionados.clear();
    carregarVeiculos();
}

// Limpar filtros
function limparFiltros() {
    $('#filtroPlaca').val('');
    $('#filtroModelo').val('');
    $('#filtroSituacao').val('');
    veiculosSelecionados.clear();
    carregarVeiculos();
}

// Selecionar todos os veículos visíveis
function selecionarTodos() {
    veiculos.forEach(v => veiculosSelecionados.add(v.id_veiculo));
    renderizarVeiculos();
    atualizarResumo();
}

// Limpar seleção
function limparSelecao() {
    veiculosSelecionados.clear();
    renderizarVeiculos();
    atualizarResumo();
}

// Atualizar resumo da seleção
function atualizarResumo() {
    $('#totalSelecionados').text(veiculosSelecionados.size);
    $('#totalDisponiveis').text(veiculos.length);
}

// Imprimir QR Codes selecionados
function imprimirSelecionados() {
    if (veiculosSelecionados.size === 0) {
        mostrarMensagem('Selecione pelo menos um veículo', 'warning');
        return;
    }
    
    const ids = Array.from(veiculosSelecionados).join(',');
    const url = `veiculo_qrcode_pdf.php?ids=${ids}`;
    window.open(url, '_blank');
}

// Baixar PDF dos selecionados
function baixarPdfSelecionados() {
    if (veiculosSelecionados.size === 0) {
        mostrarMensagem('Selecione pelo menos um veículo', 'warning');
        return;
    }
    
    const ids = Array.from(veiculosSelecionados).join(',');
    const url = `veiculo_qrcode_pdf.php?ids=${ids}&download=1`;
    window.location.href = url;
}

// Imprimir todos os veículos (aplicando filtros atuais)
function imprimirTodos() {
    if (veiculos.length === 0) {
        mostrarMensagem('Nenhum veículo disponível para impressão', 'warning');
        return;
    }
    
    const filtros = new URLSearchParams({
        placa: $('#filtroPlaca').val().trim(),
        modelo: $('#filtroModelo').val().trim(),
        id_situacao: $('#filtroSituacao').val()
    }).toString();
    
    const url = `veiculo_qrcode_pdf.php?${filtros}`;
    window.open(url, '_blank');
}

// Mostrar mensagem
function mostrarMensagem(mensagem, tipo = 'info') {
    const alertClass = `alert-${tipo}`;
    const iconClass = tipo === 'success' ? 'fa-check-circle' : 
                      tipo === 'danger' ? 'fa-exclamation-circle' : 
                      tipo === 'warning' ? 'fa-exclamation-triangle' : 
                      'fa-info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas ${iconClass}"></i> ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('.main-content').prepend(alertHtml);
    
    setTimeout(() => {
        $('.alert').fadeOut(500, function() {
            $(this).remove();
        });
    }, 3000);
}
