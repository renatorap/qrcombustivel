let condutores = [];
let condutoresSelecionados = new Set();

$(document).ready(function() {
    // Carregar situações para o filtro
    carregarSituacoes();
    
    // Carregar condutores ao iniciar
    carregarCondutores();
    
    // Event listeners para filtros com debounce
    let debounceTimer;
    $('#filtroNome, #filtroCPF, #filtroCNH').on('keyup', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(aplicarFiltros, 500);
    });
    
    $('#filtroSituacao, #filtroMotorista').on('change', aplicarFiltros);
});

// Carregar situações para o filtro
function carregarSituacoes() {
    $.ajax({
        url: '../api/condutor_selects.php',
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

// Carregar condutores do servidor
function carregarCondutores() {
    $('#loadingIndicator').show();
    $('#condutoresList').hide();
    
    const filtros = {
        action: 'list_for_report',
        nome: $('#filtroNome').val().trim(),
        cpf: $('#filtroCPF').val().trim(),
        cnh: $('#filtroCNH').val().trim(),
        id_situacao: $('#filtroSituacao').val(),
        e_condutor: $('#filtroMotorista').val()
    };
    
    $.ajax({
        url: '../api/condutor.php',
        method: 'GET',
        data: filtros,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                condutores = response.data;
                renderizarCondutores();
                atualizarResumo();
            } else {
                mostrarMensagem('Erro ao carregar condutores', 'danger');
            }
        },
        error: function() {
            mostrarMensagem('Erro ao comunicar com o servidor', 'danger');
        },
        complete: function() {
            $('#loadingIndicator').hide();
            $('#condutoresList').show();
        }
    });
}

// Renderizar lista de condutores
function renderizarCondutores() {
    const container = $('#condutoresList');
    container.empty();
    
    if (condutores.length === 0) {
        container.html(`
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle"></i>
                Nenhum condutor encontrado com os filtros aplicados
            </div>
        `);
        return;
    }
    
    condutores.forEach(condutor => {
        const isSelected = condutoresSelecionados.has(condutor.id_condutor);
        const cargo = condutor.e_condutor == 1 ? 'MOTORISTA' : (condutor.cargo_nome || '-');
        const badgeClass = condutor.e_condutor == 1 ? 'badge-motorista' : 'badge-cargo';
        
        // Formatar CPF
        let cpfFormatado = condutor.cpf || '-';
        if (condutor.cpf) {
            const cpfNum = condutor.cpf.replace(/\D/g, '');
            if (cpfNum.length === 11) {
                cpfFormatado = `${cpfNum.substr(0,3)}.${cpfNum.substr(3,3)}.${cpfNum.substr(6,3)}-${cpfNum.substr(9,2)}`;
            }
        }
        
        const card = $(`
            <div class="condutor-card ${isSelected ? 'selected' : ''}" data-id="${condutor.id_condutor}">
                <div class="d-flex align-items-start">
                    <input type="checkbox" 
                           class="form-check-input condutor-checkbox" 
                           ${isSelected ? 'checked' : ''} 
                           data-id="${condutor.id_condutor}">
                    <div class="condutor-info">
                        <div class="condutor-nome">${condutor.nome}</div>
                        <div class="condutor-detalhes">
                            <span class="condutor-badge ${badgeClass}">
                                <i class="fas ${condutor.e_condutor == 1 ? 'fa-car' : 'fa-briefcase'}"></i>
                                ${cargo}
                            </span>
                            <span class="ms-2"><i class="fas fa-id-card"></i> CNH: ${condutor.cnh}</span>
                            <span class="ms-2"><i class="fas fa-address-card"></i> CPF: ${cpfFormatado}</span>
                        </div>
                        <div class="condutor-detalhes mt-1">
                            <small>
                                <span><i class="fas fa-user-tag"></i> Matrícula: ${condutor.matricula || '-'}</span>
                                <span class="ms-2"><i class="fas fa-info-circle"></i> ${condutor.situacao_nome || '-'}</span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        // Click no card ou checkbox para selecionar/desselecionar
        card.on('click', function(e) {
            if (!$(e.target).is('input[type="checkbox"]')) {
                const checkbox = $(this).find('.condutor-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked'));
                checkbox.trigger('change');
            }
        });
        
        card.find('.condutor-checkbox').on('change', function(e) {
            e.stopPropagation();
            const id = parseInt($(this).data('id'));
            if ($(this).is(':checked')) {
                condutoresSelecionados.add(id);
                $(this).closest('.condutor-card').addClass('selected');
            } else {
                condutoresSelecionados.delete(id);
                $(this).closest('.condutor-card').removeClass('selected');
            }
            atualizarResumo();
        });
        
        container.append(card);
    });
}

// Aplicar filtros
function aplicarFiltros() {
    condutoresSelecionados.clear();
    carregarCondutores();
}

// Selecionar todos os condutores visíveis
function selecionarTodos() {
    condutores.forEach(c => condutoresSelecionados.add(c.id_condutor));
    renderizarCondutores();
    atualizarResumo();
}

// Limpar seleção
function limparSelecao() {
    condutoresSelecionados.clear();
    renderizarCondutores();
    atualizarResumo();
}

// Limpar filtros
function limparFiltros() {
    $('#filtroNome').val('');
    $('#filtroCPF').val('');
    $('#filtroCNH').val('');
    $('#filtroSituacao').val('');
    $('#filtroMotorista').val('');
    condutoresSelecionados.clear();
    carregarCondutores();
}

// Atualizar resumo da seleção
function atualizarResumo() {
    $('#totalSelecionados').text(condutoresSelecionados.size);
    $('#totalDisponiveis').text(condutores.length);
}

// Imprimir crachás selecionados
function imprimirSelecionados() {
    if (condutoresSelecionados.size === 0) {
        mostrarMensagem('Selecione pelo menos um condutor', 'warning');
        return;
    }
    
    const ids = Array.from(condutoresSelecionados).join(',');
    const url = `condutor_cracha_multiplo_pdf.php?ids=${ids}`;
    window.open(url, '_blank');
}

// Baixar PDF dos selecionados
function baixarPdfSelecionados() {
    if (condutoresSelecionados.size === 0) {
        mostrarMensagem('Selecione pelo menos um condutor', 'warning');
        return;
    }
    
    const ids = Array.from(condutoresSelecionados).join(',');
    const url = `condutor_cracha_multiplo_pdf.php?ids=${ids}&download=1`;
    window.location.href = url;
}

// Imprimir todos os condutores (aplicando filtros atuais)
function imprimirTodos() {
    if (condutores.length === 0) {
        mostrarMensagem('Nenhum condutor disponível para impressão', 'warning');
        return;
    }
    
    const filtros = new URLSearchParams({
        nome: $('#filtroNome').val().trim(),
        cpf: $('#filtroCPF').val().trim(),
        cnh: $('#filtroCNH').val().trim(),
        id_situacao: $('#filtroSituacao').val(),
        e_condutor: $('#filtroMotorista').val()
    }).toString();
    
    const url = `condutor_cracha_multiplo_pdf.php?${filtros}`;
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
