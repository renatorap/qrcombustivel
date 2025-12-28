let currentPage = 1;
let consumosData = [];
let currentOrdenacao = 'data';

// Função para formatar números no padrão brasileiro
function formatarNumero(numero, decimais = 2) {
    return parseFloat(numero || 0).toLocaleString('pt-BR', {
        minimumFractionDigits: decimais,
        maximumFractionDigits: decimais
    });
}

$(document).ready(function() {
    // Carregar selects de veículos e condutores
    carregarVeiculos();
    carregarCondutores();

    // Controlar exibição dos campos de data
    $('#tipoData').change(function() {
        const tipo = $(this).val();
        
        $('#dataInicioDiv').hide();
        $('#dataFimDiv').hide();
        $('#dataUnicaDiv').hide();
        
        switch(tipo) {
            case 'intervalo':
                $('#dataInicioDiv').show();
                $('#dataFimDiv').show();
                break;
            case 'unica':
                $('#dataUnicaDiv').show();
                break;
            case 'maior':
                $('#dataInicioDiv').show();
                $('#dataInicioDiv label').text('Data Maior Que');
                break;
            case 'menor':
                $('#dataFimDiv').show();
                $('#dataFimDiv label').text('Data Menor Que');
                break;
        }
    });

    // Submissão do formulário
    $('#filterForm').submit(function(e) {
        e.preventDefault();
        currentPage = 1;
        pesquisar();
    });

    // Limpar filtros
    $('#btnLimpar').click(function() {
        $('#filterForm')[0].reset();
        $('#tipoData').trigger('change');
        $('#resultsSection').hide();
        $('#statsSection').hide();
        $('#actionButtons').hide();
        $('#filterSection').show();
    });

    // Exportar Excel
    $('#btnExportarExcel').click(function(e) {
        e.preventDefault();
        exportarExcel();
    });

    // Exportar PDF
    $('#btnExportarPDF').click(function(e) {
        e.preventDefault();
        exportarPDF();
    });
});

function voltarPesquisa() {
    $('#resultsSection').hide();
    $('#actionButtons').hide();
    $('#filterSection').show();
    $('html, body').animate({
        scrollTop: $('#filterSection').offset().top - 100
    }, 500);
}

function carregarVeiculos() {
    $.ajax({
        url: '../api/relatorio_consumo.php',
        method: 'GET',
        data: { action: 'listar_veiculos' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#veiculo');
                select.empty().append('<option value="">Todos</option>');
                
                response.data.forEach(function(veiculo) {
                    select.append(`<option value="${veiculo.id_veiculo}">${veiculo.placa} - ${veiculo.modelo}</option>`);
                });
            }
        },
        error: function() {
            console.error('Erro ao carregar veículos');
        }
    });
}

function carregarCondutores() {
    $.ajax({
        url: '../api/relatorio_consumo.php',
        method: 'GET',
        data: { action: 'listar_condutores' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#condutor');
                select.empty().append('<option value="">Todos</option>');
                
                response.data.forEach(function(condutor) {
                    select.append(`<option value="${condutor.id_condutor}">${condutor.nome} - CNH: ${condutor.cnh}</option>`);
                });
            }
        },
        error: function() {
            console.error('Erro ao carregar condutores');
        }
    });
}

function pesquisar() {
    const formData = {
        action: 'listar_consumo',
        tipoData: $('#tipoData').val(),
        dataInicio: $('#dataInicio').val(),
        dataFim: $('#dataFim').val(),
        dataUnica: $('#dataUnica').val(),
        horaInicio: $('#horaInicio').val(),
        horaFim: $('#horaFim').val(),
        veiculo: $('#veiculo').val(),
        condutor: $('#condutor').val(),
        page: currentPage
    };

    $.ajax({
        url: '../api/relatorio_consumo.php',
        method: 'GET',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                consumosData = response.data;
                renderResultados(consumosData);
                renderEstatisticas(response.stats);
                renderPaginacao(response.totalPaginas, response.paginaAtual);
                
                $('#filterSection').hide();
                $('#resultsSection').show();
                // $('#statsSection').show(); // Estatísticas não exibidas na tela, apenas no PDF
                $('#actionButtons').show();
            } else {
                alert(response.message || 'Erro ao buscar dados');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro:', error);
            alert('Erro ao buscar dados do relatório');
        }
    });
}

function renderEstatisticas(stats) {
    $('#totalAbastecimentos').text(formatarNumero(stats.total_abastecimentos || 0, 0));
    $('#totalLitros').text(formatarNumero(stats.total_litros || 0, 2) + ' L');
    $('#totalValor').text('R$ ' + formatarNumero(stats.total_valor || 0, 2));
    $('#mediaValor').text('R$ ' + formatarNumero(stats.media_valor || 0, 2));
}

function renderResultados(consumos) {
    const tbody = $('#resultsBody');
    tbody.empty();

    if (!consumos || consumos.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center text-muted">Nenhum resultado encontrado</td></tr>');
        return;
    }

    consumos.forEach(function(c) {
        const dataHora = c.data && c.hora ? `${c.data.split('-').reverse().join('/')} ${c.hora}` : '-';
        const veiculo = c.placa || '-';
        const condutor = c.condutor_nome || '-';
        const combustivel = c.produto_descricao || '-';
        const litros = formatarNumero(c.litragem || 0, 2);
        const valorUnit = formatarNumero(c.valor_unitario || 0, 3);
        const valorTotal = formatarNumero(c.valor_total || 0, 2);
        const kmHora = c.km_veiculo ? formatarNumero(c.km_veiculo, 0) : '-';

        const row = `
            <tr>
                <td>${dataHora}</td>
                <td>${veiculo}</td>
                <td>${condutor}</td>
                <td>${combustivel}</td>
                <td>${litros}</td>
                <td>R$ ${valorUnit}</td>
                <td>R$ ${valorTotal}</td>
                <td>${kmHora}</td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderPaginacao(totalPaginas, paginaAtual) {
    const paginacao = $('#paginacao');
    paginacao.empty();

    if (totalPaginas <= 1) {
        return;
    }

    const ul = $('<ul class="pagination"></ul>');

    // Botão Anterior
    ul.append(`
        <li class="page-item ${paginaAtual === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${paginaAtual - 1})">
                <i class="fas fa-angle-left"></i>
            </a>
        </li>
    `);

    // Páginas
    let startPage = Math.max(1, paginaAtual - 2);
    let endPage = Math.min(totalPaginas, paginaAtual + 2);

    if (endPage - startPage < 4) {
        if (startPage === 1) {
            endPage = Math.min(totalPaginas, startPage + 4);
        } else if (endPage === totalPaginas) {
            startPage = Math.max(1, endPage - 4);
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const active = i === paginaAtual ? 'active' : '';
        ul.append(`
            <li class="page-item ${active}">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${i})">${i}</a>
            </li>
        `);
    }

    // Botão Próxima
    ul.append(`
        <li class="page-item ${paginaAtual === totalPaginas ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${paginaAtual + 1})">
                <i class="fas fa-angle-right"></i>
            </a>
        </li>
    `);

    paginacao.append(ul);
}

function goToPage(page) {
    currentPage = page;
    pesquisar();
}

function exportarExcel() {
    const formData = {
        action: 'listar_consumo',
        tipoData: $('#tipoData').val(),
        dataInicio: $('#dataInicio').val(),
        dataFim: $('#dataFim').val(),
        dataUnica: $('#dataUnica').val(),
        horaInicio: $('#horaInicio').val(),
        horaFim: $('#horaFim').val(),
        veiculo: $('#veiculo').val(),
        condutor: $('#condutor').val(),
        export: 'excel'
    };

    const params = new URLSearchParams(formData);
    window.open(`relatorio_consumo_export.php?${params.toString()}`, '_blank');
}

function exportarPDF() {
    const formData = {
        action: 'listar_consumo',
        tipoData: $('#tipoData').val(),
        dataInicio: $('#dataInicio').val(),
        dataFim: $('#dataFim').val(),
        dataUnica: $('#dataUnica').val(),
        horaInicio: $('#horaInicio').val(),
        horaFim: $('#horaFim').val(),
        veiculo: $('#veiculo').val(),
        condutor: $('#condutor').val(),
        export: 'pdf'
    };

    const params = new URLSearchParams(formData);
    window.open(`relatorio_consumo_export.php?${params.toString()}`, '_blank');
}

function ordenar(campo) {
    currentOrdenacao = campo;
    
    let consumosOrdenados = [...consumosData];
    
    consumosOrdenados.sort((a, b) => {
        switch(campo) {
            case 'data':
                const dataA = (a.data || '') + ' ' + (a.hora || '');
                const dataB = (b.data || '') + ' ' + (b.hora || '');
                return dataB.localeCompare(dataA); // Mais recente primeiro
            case 'veiculo':
                return (a.placa || '').localeCompare(b.placa || '');
            case 'condutor':
                return (a.condutor_nome || '').localeCompare(b.condutor_nome || '');
            case 'valor':
                return (parseFloat(b.valor_total) || 0) - (parseFloat(a.valor_total) || 0); // Maior primeiro
            case 'litros':
                return (parseFloat(b.litragem) || 0) - (parseFloat(a.litragem) || 0); // Maior primeiro
            default:
                return 0;
        }
    });
    
    renderResultados(consumosOrdenados);
    return false;
}
