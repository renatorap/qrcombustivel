// Variáveis globais
let paginaAtual = 1;
let filtrosAtuais = {};
let timeoutCondutor;
let timeoutPlaca;

// Inicialização ao carregar a página
$(document).ready(function() {
    carregarFornecedores();
    carregarSetores();
    carregarProdutos();
    configurarEventos();
    setDatasPadrao();
});

// Configurar datas padrão (primeiro dia do mês até hoje)
function setDatasPadrao() {
    const hoje = new Date();
    const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    
    $('#dataInicio').val(formatarData(primeiroDia));
    $('#dataFim').val(formatarData(hoje));
}

function formatarData(data) {
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

// Configurar eventos
function configurarEventos() {
    // Mudança no tipo de filtro de data
    $('#tipoData').on('change', function() {
        const tipo = $(this).val();
        $('#dataInicioDiv, #dataFimDiv, #dataUnicaDiv').hide();
        
        if (tipo === 'intervalo') {
            $('#dataInicioDiv, #dataFimDiv').show();
        } else if (tipo === 'unica') {
            $('#dataUnicaDiv').show();
        } else if (tipo === 'maior') {
            $('#dataInicioDiv').show();
        } else if (tipo === 'menor') {
            $('#dataFimDiv').show();
        }
    });
    
    // Mudança no tipo de filtro de hora
    $('#tipoHora').on('change', function() {
        const tipo = $(this).val();
        $('#horaInicioDiv, #horaFimDiv').hide();
        
        if (tipo === 'intervalo') {
            $('#horaInicioDiv, #horaFimDiv').show();
        } else if (tipo === 'maior') {
            $('#horaInicioDiv').show();
        } else if (tipo === 'menor') {
            $('#horaFimDiv').show();
        }
    });
    
    // Submit do formulário
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        paginaAtual = 1;
        pesquisar();
    });
    
    // Limpar filtros
    $('#btnLimpar').on('click', function() {
        $('#filterForm')[0].reset();
        $('#condutorId').val('');
        setDatasPadrao();
        $('#tipoData').trigger('change');
        $('#tipoHora').trigger('change');
        $('#filterSection').show();
        $('#statsSection, #postSearchFilters, #actionButtons, #resultsSection').hide();
    });
    
    // Aplicar filtros pós-pesquisa (agrupamento e ordenação)
    $('#btnAplicarFiltros').on('click', function() {
        pesquisar(paginaAtual);
    });
    
    // Autocomplete de condutor
    $('#condutor').on('keyup', function() {
        clearTimeout(timeoutCondutor);
        const termo = $(this).val();
        
        if (termo.length >= 2) {
            timeoutCondutor = setTimeout(function() {
                buscarCondutores(termo);
            }, 300);
        } else {
            $('#condutorList').hide();
            $('#condutorId').val('');
        }
    });
    
    // Autocomplete de placa
    $('#placa').on('keyup', function() {
        clearTimeout(timeoutPlaca);
        const termo = $(this).val();
        
        if (termo.length >= 2) {
            timeoutPlaca = setTimeout(function() {
                buscarPlacas(termo);
            }, 300);
        } else {
            $('#placaList').hide();
        }
    });
    
    // Fechar autocomplete ao clicar fora
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#condutor, #condutorList').length) {
            $('#condutorList').hide();
        }
        if (!$(e.target).closest('#placa, #placaList').length) {
            $('#placaList').hide();
        }
    });
    
    // Exportação Excel
    $('#btnExportarExcel').on('click', function(e) {
        e.preventDefault();
        exportar('excel');
    });
    
    // Exportação PDF
    $('#btnExportarPDF').on('click', function(e) {
        e.preventDefault();
        exportar('pdf');
    });
}

// Carregar fornecedores
function carregarFornecedores() {
    $.ajax({
        url: '../api/relatorio_extrato_abastecimento.php',
        method: 'GET',
        data: { action: 'listar_fornecedores' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#fornecedor');
                select.empty();
                
                response.data.forEach(function(fornecedor) {
                    select.append(
                        $('<option></option>')
                            .val(fornecedor.id_fornecedor)
                            .text(fornecedor.nome_fantasia)
                    );
                });
            }
        },
        error: function() {
            console.error('Erro ao carregar fornecedores');
        }
    });
}

// Carregar setores
function carregarSetores() {
    $.ajax({
        url: '../api/relatorio_extrato_abastecimento.php',
        method: 'GET',
        data: { action: 'listar_setores' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#setor');
                select.empty();
                
                response.data.forEach(function(setor) {
                    select.append(
                        $('<option></option>')
                            .val(setor.id_setor)
                            .text(setor.descricao)
                    );
                });
            }
        },
        error: function() {
            console.error('Erro ao carregar setores');
        }
    });
}

// Carregar produtos
function carregarProdutos() {
    $.ajax({
        url: '../api/relatorio_extrato_abastecimento.php',
        method: 'GET',
        data: { action: 'listar_produtos' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#produto');
                select.empty();
                
                response.data.forEach(function(produto) {
                    select.append(
                        $('<option></option>')
                            .val(produto.id_produto)
                            .text(produto.descricao)
                    );
                });
            }
        },
        error: function() {
            console.error('Erro ao carregar produtos');
        }
    });
}

// Buscar condutores (autocomplete)
function buscarCondutores(termo) {
    $.ajax({
        url: '../api/relatorio_extrato_abastecimento.php',
        method: 'GET',
        data: { 
            action: 'buscar_condutores',
            termo: termo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                const list = $('#condutorList');
                list.empty();
                
                response.data.forEach(function(condutor) {
                    const item = $('<div></div>')
                        .addClass('autocomplete-item')
                        .text(condutor.nome + ' - CNH: ' + condutor.cnh)
                        .data('id', condutor.id_condutor)
                        .data('nome', condutor.nome);
                    
                    item.on('click', function() {
                        $('#condutor').val($(this).data('nome'));
                        $('#condutorId').val($(this).data('id'));
                        list.hide();
                    });
                    
                    list.append(item);
                });
                
                list.show();
            } else {
                $('#condutorList').hide();
            }
        },
        error: function() {
            console.error('Erro ao buscar condutores');
        }
    });
}

// Buscar placas (autocomplete)
function buscarPlacas(termo) {
    $.ajax({
        url: '../api/relatorio_extrato_abastecimento.php',
        method: 'GET',
        data: { 
            action: 'buscar_placas',
            termo: termo
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                const list = $('#placaList');
                list.empty();
                
                response.data.forEach(function(veiculo) {
                    const item = $('<div></div>')
                        .addClass('autocomplete-item')
                        .text(veiculo.placa + ' - ' + veiculo.modelo)
                        .data('placa', veiculo.placa);
                    
                    item.on('click', function() {
                        $('#placa').val($(this).data('placa'));
                        list.hide();
                    });
                    
                    list.append(item);
                });
                
                list.show();
            } else {
                $('#placaList').hide();
            }
        },
        error: function() {
            console.error('Erro ao buscar placas');
        }
    });
}

// Pesquisar
function pesquisar(pagina = 1) {
    paginaAtual = pagina;
    
    // Coletar filtros (arrays para selects múltiplos)
    filtrosAtuais = {
        action: 'listar_extrato',
        tipoData: $('#tipoData').val(),
        dataInicio: $('#dataInicio').val(),
        dataFim: $('#dataFim').val(),
        dataUnica: $('#dataUnica').val(),
        tipoHora: $('#tipoHora').val(),
        horaInicio: $('#horaInicio').val(),
        horaFim: $('#horaFim').val(),
        'fornecedor[]': $('#fornecedor').val() || [],
        condutor: $('#condutorId').val(),
        'setor[]': $('#setor').val() || [],
        'produto[]': $('#produto').val() || [],
        placa: $('#placa').val(),
        'quebra[]': $('#quebra').val() || [],
        'colunas[]': $('#colunas').val() || ['data','hora','placa','condutor','setor','fornecedor','produto','km_atual','km_ant','km_rodado','litros','km_litro','vl_unit','vl_total'],
        ordenacao: $('#ordenacao').val(),
        page: pagina
    };
    
    $.ajax({
        url: '../api/relatorio_extrato_abastecimento.php',
        method: 'GET',
        data: filtrosAtuais,
        dataType: 'json',
        beforeSend: function() {
            const colspan = filtrosAtuais['colunas[]'].length;
            $('#resultsBody').html('<tr><td colspan="' + colspan + '" class="text-center">Carregando...</td></tr>');
        },
        success: function(response) {
            if (response.success) {
                exibirResultados(response.data, response.stats);
                renderizarPaginacao(response.paginaAtual, response.totalPaginas);
                
                // Mostrar seções
                $('#filterSection').hide();
                $('#statsSection, #postSearchFilters, #actionButtons, #resultsSection').show();
            } else {
                alert('Erro: ' + response.message);
            }
        },
        error: function() {
            alert('Erro ao buscar dados');
        }
    });
}

// Renderizar cabeçalho da tabela dinamicamente
function renderizarCabecalho(colunas, thead) {
    const labels = {
        data: 'Data',
        hora: 'Hora',
        placa: 'Placa',
        condutor: 'Condutor',
        setor: 'Setor',
        fornecedor: 'Fornecedor',
        produto: 'Produto',
        km_atual: 'KM Atual',
        km_ant: 'KM Ant.',
        km_rodado: 'KM Rodado',
        litros: 'Litros',
        km_litro: 'KM/L',
        vl_unit: 'Vl. Unit.',
        vl_total: 'Vl. Total'
    };
    
    colunas.forEach(function(col) {
        const th = $('<th></th>').text(labels[col] || col);
        if (['km_atual', 'km_ant', 'km_rodado', 'litros', 'km_litro', 'vl_unit', 'vl_total'].includes(col)) {
            th.addClass('text-end');
        }
        thead.append(th);
    });
}

// Renderizar linha de dados dinamicamente
function renderizarLinha(item, colunas) {
    const row = $('<tr></tr>');
    
    colunas.forEach(function(col) {
        const cell = $('<td></td>');
        
        switch(col) {
            case 'data':
                cell.text(formatarDataBR(item.data));
                break;
            case 'hora':
                cell.text(item.hora ? item.hora.substring(0, 5) : '');
                break;
            case 'placa':
                cell.text(item.placa);
                break;
            case 'condutor':
                cell.text(item.condutor);
                break;
            case 'setor':
                cell.text(item.setor);
                break;
            case 'fornecedor':
                cell.text(item.nome_fant);
                break;
            case 'produto':
                cell.text(item.produto);
                break;
            case 'km_atual':
                cell.addClass('text-end').text(parseFloat(item.km_veic_atu || 0).toLocaleString('pt-BR', { minimumFractionDigits: 0 }));
                break;
            case 'km_ant':
                cell.addClass('text-end').text(parseFloat(item.km_veic_ant || 0).toLocaleString('pt-BR', { minimumFractionDigits: 0 }));
                break;
            case 'km_rodado':
                cell.addClass('text-end').text(parseFloat(item.km_rodado || 0).toLocaleString('pt-BR', { minimumFractionDigits: 0 }));
                break;
            case 'litros':
                cell.addClass('text-end').text(parseFloat(item.litragem || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
                break;
            case 'km_litro':
                cell.addClass('text-end').text(parseFloat(item.km_litro || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
                break;
            case 'vl_unit':
                cell.addClass('text-end').text('R$ ' + parseFloat(item.vl_unit || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
                break;
            case 'vl_total':
                cell.addClass('text-end').text('R$ ' + parseFloat(item.vl_total || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
                break;
        }
        
        row.append(cell);
    });
    
    return row;
}

// Exibir resultados
function exibirResultados(dados, stats) {
    const colunasOriginais = $('#colunas').val() || ['data','hora','placa','condutor','setor','fornecedor','produto','km_atual','km_ant','km_rodado','litros','km_litro','vl_unit','vl_total'];
    const quebras = $('#quebra').val() || [];
    
    // Mapear quebras para nomes de colunas
    const quebrasColunas = quebras.map(q => {
        if (q === 'fornecedor') return 'fornecedor';
        if (q === 'setor') return 'setor';
        if (q === 'produto') return 'produto';
        if (q === 'placa') return 'placa';
        return null;
    }).filter(c => c !== null);
    
    // Filtrar colunas: remover as que são quebras
    const colunas = colunasOriginais.filter(col => !quebrasColunas.includes(col));
    
    const tbody = $('#resultsBody');
    const thead = $('#resultsTable thead tr');
    tbody.empty();
    thead.empty();
    
    // Renderizar cabeçalho dinâmico
    renderizarCabecalho(colunas, thead);
    
    // Atualizar estatísticas
    $('#totalAbastecimentos').text(parseInt(stats.total_abastecimentos || 0).toLocaleString('pt-BR'));
    $('#totalLitros').text(parseFloat(stats.total_litros || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#totalValor').text('R$ ' + parseFloat(stats.total_valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#mediaValor').text('R$ ' + parseFloat(stats.media_valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#totalKmRodado').text(parseFloat(stats.total_km_rodado || 0).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
    $('#mediaKmLitro').text(parseFloat(stats.media_km_litro || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    
    if (dados.length === 0) {
        tbody.html('<tr><td colspan="' + colunas.length + '" class="text-center">Nenhum registro encontrado</td></tr>');
        return;
    }
    let gruposAtuais = [];
    let totaisNiveis = [];
    
    dados.forEach(function(item, index) {
        let camposQuebra = [];
        
        // Determinar campos de quebra para cada quebra selecionada (ordem = hierarquia)
        quebras.forEach(function(quebra) {
            let valor = '';
            switch(quebra) {
                case 'fornecedor':
                    valor = item.nome_fant;
                    break;
                case 'setor':
                    valor = item.setor;
                    break;
                case 'produto':
                    valor = item.produto;
                    break;
                case 'placa':
                    valor = item.placa;
                    break;
            }
            if (valor) {
                camposQuebra.push({ tipo: quebra, valor: valor });
            }
        });
        
        // Verificar mudanças de nível hierárquico
        let nivelMudanca = -1;
        for (let i = 0; i < camposQuebra.length; i++) {
            if (!gruposAtuais[i] || gruposAtuais[i].valor !== camposQuebra[i].valor) {
                nivelMudanca = i;
                break;
            }
        }
        
        // Se houve mudança, totalizar níveis que mudaram (do mais profundo ao nível de mudança)
        if (nivelMudanca >= 0 && gruposAtuais.length > 0) {
            for (let i = gruposAtuais.length - 1; i >= nivelMudanca; i--) {
                if (totaisNiveis[i] && totaisNiveis[i].count > 0) {
                    inserirTotalNivel(tbody, gruposAtuais[i], totaisNiveis[i], i);
                }
            }
            
            // Resetar grupos e totais a partir do nível de mudança
            gruposAtuais = gruposAtuais.slice(0, nivelMudanca);
            totaisNiveis = totaisNiveis.slice(0, nivelMudanca);
        }
        
        // Inserir cabeçalhos dos novos níveis
        for (let i = gruposAtuais.length; i < camposQuebra.length; i++) {
            gruposAtuais.push(camposQuebra[i]);
            totaisNiveis.push({ litros: 0, valor: 0, kmRodado: 0, count: 0 });
            
            const headerRow = $('<tr class="quebra-header"></tr>');
            const indentacao = '&nbsp;&nbsp;&nbsp;&nbsp;'.repeat(i);
            headerRow.append(`<td colspan="${colunas.length}">${indentacao}<strong>${camposQuebra[i].tipo.toUpperCase()}: ${camposQuebra[i].valor}</strong></td>`);
            tbody.append(headerRow);
        }
        
        // Linha de dados usando renderização dinâmica
        const row = renderizarLinha(item, colunas);
        tbody.append(row);
        
        // Acumular totais de todos os níveis ativos
        if (quebras.length > 0) {
            for (let i = 0; i < totaisNiveis.length; i++) {
                totaisNiveis[i].litros += parseFloat(item.litragem || 0);
                totaisNiveis[i].valor += parseFloat(item.vl_total || 0);
                totaisNiveis[i].kmRodado += parseFloat(item.km_rodado || 0);
                totaisNiveis[i].count++;
            }
        }
        
        // Se for o último item e houver quebra, totalizar todos os níveis
        if (quebras.length > 0 && index === dados.length - 1) {
            for (let i = gruposAtuais.length - 1; i >= 0; i--) {
                if (totaisNiveis[i] && totaisNiveis[i].count > 0) {
                    inserirTotalNivel(tbody, gruposAtuais[i], totaisNiveis[i], i);
                }
            }
        }
    });
    
    // Total geral (sempre exibir)
    inserirTotalGeral(tbody, stats);
}

// Inserir total de nível hierárquico
function inserirTotalNivel(tbody, campo, totais, nivel) {
    const colunas = $('#colunas').val() || [];
    const mediaKmLitro = totais.litros > 0 ? (totais.kmRodado / totais.litros) : 0;
    const indentacao = '&nbsp;&nbsp;&nbsp;&nbsp;'.repeat(nivel);
    
    const row = $('<tr class="quebra-total"></tr>');
    
    // Calcular quantas colunas antes dos totalizadores
    let colsAntes = colunas.indexOf('km_rodado');
    if (colsAntes < 0) colsAntes = colunas.length - 4; // Fallback
    
    row.append(`<td colspan="${colsAntes}" class="text-end">${indentacao}<strong>SUBTOTAL ${campo.tipo.toUpperCase()}: ${campo.valor}</strong></td>`);
    
    // Adicionar células de totais apenas se as colunas estiverem selecionadas
    colunas.slice(colsAntes).forEach(function(col) {
        const cell = $('<td class="text-end"></td>');
        switch(col) {
            case 'km_rodado':
                cell.html(`<strong>${totais.kmRodado.toLocaleString('pt-BR', { minimumFractionDigits: 0 })}</strong>`);
                break;
            case 'litros':
                cell.html(`<strong>${totais.litros.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>`);
                break;
            case 'km_litro':
                cell.html(`<strong>${mediaKmLitro.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>`);
                break;
            case 'vl_total':
                cell.html(`<strong>R$ ${totais.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>`);
                break;
            default:
                cell.html('');
        }
        row.append(cell);
    });
    
    tbody.append(row);
    tbody.append($('<tr><td colspan="' + colunas.length + '" style="height: 10px;"></td></tr>'));
}

// Inserir total do grupo (função legada, mantida para compatibilidade)
function inserirTotalGrupo(tbody, nomeGrupo, totais) {
    const mediaKmLitro = totais.litros > 0 ? (totais.kmRodado / totais.litros) : 0;
    
    const row = $('<tr class="quebra-total"></tr>');
    row.append(`<td colspan="9" class="text-end"><strong>TOTAL ${nomeGrupo}:</strong></td>`);
    row.append(`<td class="text-end"><strong>${totais.kmRodado.toLocaleString('pt-BR', { minimumFractionDigits: 0 })}</strong></td>`);
    row.append(`<td class="text-end"><strong>${totais.litros.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong></td>`);
    row.append(`<td class="text-end"><strong>${mediaKmLitro.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong></td>`);
    row.append(`<td></td>`);
    row.append(`<td class="text-end"><strong>R$ ${totais.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong></td>`);
    
    tbody.append(row);
}

// Inserir total geral
function inserirTotalGeral(tbody, stats) {
    const colunas = $('#colunas').val() || [];
    const row = $('<tr class="total-geral"></tr>');
    
    // Calcular quantas colunas antes dos totalizadores
    let colsAntes = colunas.indexOf('km_rodado');
    if (colsAntes < 0) colsAntes = colunas.length - 4;
    
    row.append(`<td colspan="${colsAntes}" class="text-end"><strong>TOTAL GERAL:</strong></td>`);
    
    // Adicionar células de totais apenas se as colunas estiverem selecionadas
    colunas.slice(colsAntes).forEach(function(col) {
        const cell = $('<td class="text-end"></td>');
        switch(col) {
            case 'km_rodado':
                cell.html(`<strong>${parseFloat(stats.total_km_rodado || 0).toLocaleString('pt-BR', { minimumFractionDigits: 0 })}</strong>`);
                break;
            case 'litros':
                cell.html(`<strong>${parseFloat(stats.total_litros || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>`);
                break;
            case 'km_litro':
                cell.html(`<strong>${parseFloat(stats.media_km_litro || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>`);
                break;
            case 'vl_total':
                cell.html(`<strong>R$ ${parseFloat(stats.total_valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</strong>`);
                break;
            default:
                cell.html('');
        }
        row.append(cell);
    });
    
    tbody.append(row);
}

// Renderizar paginação
function renderizarPaginacao(paginaAtual, totalPaginas) {
    const paginacao = $('#paginacao');
    paginacao.empty();
    
    if (totalPaginas <= 1) return;
    
    const ul = $('<ul class="pagination"></ul>');
    
    // Botão anterior
    const liPrev = $('<li class="page-item"></li>');
    if (paginaAtual === 1) liPrev.addClass('disabled');
    const aPrev = $('<a class="page-link" href="#">Anterior</a>');
    aPrev.on('click', function(e) {
        e.preventDefault();
        if (paginaAtual > 1) pesquisar(paginaAtual - 1);
    });
    liPrev.append(aPrev);
    ul.append(liPrev);
    
    // Páginas
    const inicio = Math.max(1, paginaAtual - 2);
    const fim = Math.min(totalPaginas, paginaAtual + 2);
    
    for (let i = inicio; i <= fim; i++) {
        const li = $('<li class="page-item"></li>');
        if (i === paginaAtual) li.addClass('active');
        
        const a = $('<a class="page-link" href="#"></a>').text(i);
        a.on('click', function(e) {
            e.preventDefault();
            pesquisar(i);
        });
        
        li.append(a);
        ul.append(li);
    }
    
    // Botão próximo
    const liNext = $('<li class="page-item"></li>');
    if (paginaAtual === totalPaginas) liNext.addClass('disabled');
    const aNext = $('<a class="page-link" href="#">Próximo</a>');
    aNext.on('click', function(e) {
        e.preventDefault();
        if (paginaAtual < totalPaginas) pesquisar(paginaAtual + 1);
    });
    liNext.append(aNext);
    ul.append(liNext);
    
    paginacao.append(ul);
}

// Formatar data para BR
function formatarDataBR(data) {
    if (!data) return '';
    const partes = data.split('-');
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

// Voltar para pesquisa
function voltarPesquisa() {
    $('#filterSection').show();
    $('#statsSection, #postSearchFilters, #actionButtons, #resultsSection').hide();
}

// Exportar
function exportar(tipo) {
    // Construir URL manualmente para arrays
    let params = [];
    
    for (let key in filtrosAtuais) {
        if (key === 'page' || key === 'action') continue; // Pular paginação e action
        
        const value = filtrosAtuais[key];
        
        if (Array.isArray(value)) {
            // Para arrays, adicionar cada item com a mesma chave
            value.forEach(v => {
                if (v !== null && v !== '') {
                    params.push(encodeURIComponent(key) + '=' + encodeURIComponent(v));
                }
            });
        } else if (value !== null && value !== '') {
            params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
        }
    }
    
    params.push('export=' + tipo);
    
    // Adicionar orientação do PDF (apenas para PDF)
    if (tipo === 'pdf') {
        const orientacao = $('#orientacao').val() || 'retrato';
        params.push('orientacao=' + encodeURIComponent(orientacao));
    }
    
    const url = '../pages/relatorio_extrato_abastecimento_export.php?' + params.join('&');
    window.open(url, '_blank');
}
