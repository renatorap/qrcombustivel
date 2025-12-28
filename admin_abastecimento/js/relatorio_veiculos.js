let veiculosData = [];
let currentAgrupamento = 'none';
let currentOrdenacao = 'placa';
let filtrosAplicados = {};
let autocompleteTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    loadSetores();
    loadSituacoes();
    setupPlacaAutocomplete();
    
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        aplicarFiltros();
    });
    
    // Fechar autocomplete ao clicar fora
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('placaAutocomplete');
        const input = document.getElementById('filtroPlaca');
        if (e.target !== input && e.target !== dropdown) {
            dropdown.style.display = 'none';
        }
    });
});

function setupPlacaAutocomplete() {
    const input = document.getElementById('filtroPlaca');
    const dropdown = document.getElementById('placaAutocomplete');
    
    input.addEventListener('input', function() {
        const value = this.value.trim();
        
        // Limpar timeout anterior
        if (autocompleteTimeout) {
            clearTimeout(autocompleteTimeout);
        }
        
        // Se campo vazio, esconder dropdown
        if (value.length < 2) {
            dropdown.style.display = 'none';
            return;
        }
        
        // Aguardar 300ms após parar de digitar
        autocompleteTimeout = setTimeout(() => {
            fetch(`../api/relatorio_veiculos.php?action=autocomplete_placa&termo=${encodeURIComponent(value)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        renderAutocomplete(data.data, value);
                    } else {
                        dropdown.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar placas:', error);
                    dropdown.style.display = 'none';
                });
        }, 300);
    });
    
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });
}

function renderAutocomplete(veiculos, termo) {
    const dropdown = document.getElementById('placaAutocomplete');
    const input = document.getElementById('filtroPlaca');
    
    dropdown.innerHTML = '';
    
    veiculos.forEach(veiculo => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        
        // Destacar termo buscado
        const placaHighlight = veiculo.placa.replace(
            new RegExp(termo, 'gi'), 
            match => `<strong>${match}</strong>`
        );
        
        item.innerHTML = `
            <div>${placaHighlight}</div>
            <small class="text-muted">${veiculo.modelo} - ${veiculo.marca}</small>
        `;
        
        item.addEventListener('click', function() {
            input.value = veiculo.placa;
            dropdown.style.display = 'none';
        });
        
        dropdown.appendChild(item);
    });
    
    // Posicionar dropdown
    const rect = input.getBoundingClientRect();
    dropdown.style.width = rect.width + 'px';
    dropdown.style.display = 'block';
}

function loadSetores() {
    fetch('../api/relatorio_veiculos.php?action=get_setores')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filtroSetor');
                data.data.forEach(setor => {
                    const option = document.createElement('option');
                    option.value = setor.id_setor;
                    option.textContent = setor.descricao;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Erro ao carregar setores:', error));
}

function loadSituacoes() {
    fetch('../api/relatorio_veiculos.php?action=get_situacoes')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filtroSituacao');
                data.data.forEach(situacao => {
                    const option = document.createElement('option');
                    option.value = situacao.id_situacao;
                    option.textContent = situacao.descricao;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Erro ao carregar situações:', error));
}

function aplicarFiltros() {
    const placa = document.getElementById('filtroPlaca').value;
    const setorSelect = document.getElementById('filtroSetor');
    const idSituacao = document.getElementById('filtroSituacao').value;
    
    // Capturar todos os setores selecionados
    const setoresSelecionados = Array.from(setorSelect.selectedOptions)
        .filter(opt => opt.value !== '')
        .map(opt => opt.value);
    
    const setoresTexto = Array.from(setorSelect.selectedOptions)
        .filter(opt => opt.value !== '')
        .map(opt => opt.text)
        .join(', ');
    
    // Capturar textos dos filtros para exibição
    const situacaoSelect = document.getElementById('filtroSituacao');
    const situacaoTexto = idSituacao ? situacaoSelect.options[situacaoSelect.selectedIndex].text : '';
    
    filtrosAplicados = {
        placa: placa,
        id_setor: setoresSelecionados.join(','),
        id_situacao: idSituacao,
        setor: setoresTexto,
        situacao: situacaoTexto
    };
    
    const params = new URLSearchParams();
    if (placa) params.append('placa', placa);
    if (setoresSelecionados.length > 0) {
        setoresSelecionados.forEach(setor => params.append('id_setor[]', setor));
    }
    if (idSituacao) params.append('id_situacao', idSituacao);
    
    document.getElementById('filterSection').style.display = 'none';
    document.getElementById('resultsSection').style.display = 'block';
    
    fetch(`../api/relatorio_veiculos.php?action=get_veiculos&${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                veiculosData = data.data;
                renderGrid();
            } else {
                alert('Erro ao carregar dados: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar relatório');
        });
}

function renderGrid() {
    const grid = document.getElementById('resultsGrid');
    
    if (veiculosData.length === 0) {
        grid.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">Nenhum veículo encontrado com os filtros aplicados</p>
            </div>
        `;
        return;
    }
    
    // Ordenar dados
    let dados = [...veiculosData];
    dados.sort((a, b) => {
        switch(currentOrdenacao) {
            case 'placa':
                return a.placa.localeCompare(b.placa);
            case 'modelo':
                return a.modelo.localeCompare(b.modelo);
            case 'marca':
                return a.marca.localeCompare(b.marca);
            case 'setor':
                return (a.setor || '').localeCompare(b.setor || '');
            default:
                return 0;
        }
    });
    
    let html = `
        <div class="mb-3">
            <strong>Total de veículos:</strong> ${dados.length}
        </div>
    `;
    
    if (currentAgrupamento === 'none') {
        // Sem agrupamento
        html += `
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Setor</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        dados.forEach(veiculo => {
            html += `
                <tr class="veiculo-row">
                    <td><strong>${veiculo.placa}</strong></td>
                    <td>${veiculo.modelo}</td>
                    <td>${veiculo.marca}</td>
                    <td>${veiculo.setor || '-'}</td>
                    <td><span class="badge bg-secondary">${veiculo.situacao}</span></td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    } else {
        // Com agrupamento
        const grupos = {};
        dados.forEach(veiculo => {
            const chave = currentAgrupamento === 'setor' ? 
                          (veiculo.setor || 'Sem Setor') : 
                          veiculo.situacao;
            if (!grupos[chave]) {
                grupos[chave] = [];
            }
            grupos[chave].push(veiculo);
        });
        
        Object.keys(grupos).sort().forEach(grupo => {
            html += `
                <div class="group-header">
                    ${grupo} (${grupos[grupo].length} veículo${grupos[grupo].length !== 1 ? 's' : ''})
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Placa</th>
                                <th>Modelo</th>
                                <th>Marca</th>
                                ${currentAgrupamento !== 'setor' ? '<th>Setor</th>' : ''}
                                ${currentAgrupamento !== 'situacao' ? '<th>Situação</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            grupos[grupo].forEach(veiculo => {
                html += `
                    <tr class="veiculo-row">
                        <td><strong>${veiculo.placa}</strong></td>
                        <td>${veiculo.modelo}</td>
                        <td>${veiculo.marca}</td>
                        ${currentAgrupamento !== 'setor' ? `<td>${veiculo.setor || '-'}</td>` : ''}
                        ${currentAgrupamento !== 'situacao' ? `<td><span class="badge bg-secondary">${veiculo.situacao}</span></td>` : ''}
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
        });
    }
    
    grid.innerHTML = html;
}

function voltarPesquisa() {
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('filterSection').style.display = 'block';
}

function limparFiltros() {
    document.getElementById('filtroPlaca').value = '';
    document.getElementById('filtroSetor').value = '';
    document.getElementById('filtroSituacao').value = '';
    
    // Esconder resultados se estiverem visíveis
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('filterSection').style.display = 'block';
    veiculosData = [];
    filtrosAplicados = {};
}

function ordenar(campo) {
    currentOrdenacao = campo;
    renderGrid();
    return false;
}

function agrupar(tipo) {
    currentAgrupamento = tipo;
    renderGrid();
    return false;
}

function exportar(formato) {
    if (veiculosData.length === 0) {
        alert('Não há dados para exportar');
        return false;
    }
    
    switch(formato) {
        case 'csv':
            exportarCSV();
            break;
        case 'xls':
            exportarExcel();
            break;
        case 'pdf':
            exportarPDF();
            break;
    }
    
    return false;
}

function exportarCSV() {
    let csv = 'Placa;Modelo;Marca;Setor;Situação\n';
    
    veiculosData.forEach(veiculo => {
        csv += `${veiculo.placa};${veiculo.modelo};${veiculo.marca};${veiculo.setor || ''};${veiculo.situacao}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `relatorio_veiculos_${new Date().getTime()}.csv`;
    link.click();
}

function exportarExcel() {
    let html = '<table>';
    html += '<thead><tr><th>Placa</th><th>Modelo</th><th>Marca</th><th>Setor</th><th>Situação</th></tr></thead>';
    html += '<tbody>';
    
    veiculosData.forEach(veiculo => {
        html += `<tr>
            <td>${veiculo.placa}</td>
            <td>${veiculo.modelo}</td>
            <td>${veiculo.marca}</td>
            <td>${veiculo.setor || ''}</td>
            <td>${veiculo.situacao}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `relatorio_veiculos_${new Date().getTime()}.xls`;
    link.click();
}

function exportarPDF() {
    // Criar form para enviar dados via POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../api/exportar_pdf_veiculos.php';
    form.target = '_blank';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'dados';
    input.value = JSON.stringify({
        veiculos: veiculosData,
        agrupamento: currentAgrupamento,
        ordenacao: currentOrdenacao,
        filtros: filtrosAplicados
    });
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
