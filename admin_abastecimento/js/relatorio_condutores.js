let condutoresData = [];
let currentAgrupamento = 'none';
let currentOrdenacao = 'nome';
let filtrosAplicados = {};
let autocompleteTimeout = null;

document.addEventListener('DOMContentLoaded', function() {
    setupNomeAutocomplete();
    setupCNHAutocomplete();
    
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        aplicarFiltros();
    });
    
    // Fechar autocomplete ao clicar fora
    document.addEventListener('click', function(e) {
        const nomeDropdown = document.getElementById('nomeAutocomplete');
        const cnhDropdown = document.getElementById('cnhAutocomplete');
        const nomeInput = document.getElementById('filtroNome');
        const cnhInput = document.getElementById('filtroCNH');
        
        if (e.target !== nomeInput && e.target !== nomeDropdown) {
            nomeDropdown.style.display = 'none';
        }
        if (e.target !== cnhInput && e.target !== cnhDropdown) {
            cnhDropdown.style.display = 'none';
        }
    });
});

function setupNomeAutocomplete() {
    const input = document.getElementById('filtroNome');
    const dropdown = document.getElementById('nomeAutocomplete');
    
    input.addEventListener('input', function() {
        const value = this.value.trim();
        
        if (autocompleteTimeout) {
            clearTimeout(autocompleteTimeout);
        }
        
        if (value.length < 2) {
            dropdown.style.display = 'none';
            return;
        }
        
        autocompleteTimeout = setTimeout(() => {
            fetch(`../api/relatorio_condutores.php?action=autocomplete_nome&termo=${encodeURIComponent(value)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        renderAutocomplete(data.data, value, 'nome');
                    } else {
                        dropdown.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar nomes:', error);
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

function setupCNHAutocomplete() {
    const input = document.getElementById('filtroCNH');
    const dropdown = document.getElementById('cnhAutocomplete');
    
    input.addEventListener('input', function() {
        const value = this.value.trim();
        
        if (autocompleteTimeout) {
            clearTimeout(autocompleteTimeout);
        }
        
        if (value.length < 2) {
            dropdown.style.display = 'none';
            return;
        }
        
        autocompleteTimeout = setTimeout(() => {
            fetch(`../api/relatorio_condutores.php?action=autocomplete_cnh&termo=${encodeURIComponent(value)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        renderAutocomplete(data.data, value, 'cnh');
                    } else {
                        dropdown.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar CNHs:', error);
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

function renderAutocomplete(condutores, termo, tipo) {
    const dropdownId = tipo === 'nome' ? 'nomeAutocomplete' : 'cnhAutocomplete';
    const inputId = tipo === 'nome' ? 'filtroNome' : 'filtroCNH';
    
    const dropdown = document.getElementById(dropdownId);
    const input = document.getElementById(inputId);
    
    dropdown.innerHTML = '';
    
    condutores.forEach(condutor => {
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        
        if (tipo === 'nome') {
            const nomeHighlight = condutor.nome.replace(
                new RegExp(termo, 'gi'), 
                match => `<strong>${match}</strong>`
            );
            item.innerHTML = `
                <div>${nomeHighlight}</div>
                <small class="text-muted">CNH: ${condutor.cnh}</small>
            `;
            item.addEventListener('click', function() {
                input.value = condutor.nome;
                dropdown.style.display = 'none';
            });
        } else {
            const cnhHighlight = condutor.cnh.replace(
                new RegExp(termo, 'gi'), 
                match => `<strong>${match}</strong>`
            );
            item.innerHTML = `
                <div>${cnhHighlight}</div>
                <small class="text-muted">${condutor.nome}</small>
            `;
            item.addEventListener('click', function() {
                input.value = condutor.cnh;
                dropdown.style.display = 'none';
            });
        }
        
        dropdown.appendChild(item);
    });
    
    const rect = input.getBoundingClientRect();
    dropdown.style.width = rect.width + 'px';
    dropdown.style.display = 'block';
}

function aplicarFiltros() {
    const nome = document.getElementById('filtroNome').value;
    const cnh = document.getElementById('filtroCNH').value;
    const validadeCNH = document.getElementById('filtroValidadeCNH').value;
    const situacao = document.getElementById('filtroSituacao').value;
    
    const validadeCNHSelect = document.getElementById('filtroValidadeCNH');
    const situacaoSelect = document.getElementById('filtroSituacao');
    
    const validadeCNHTexto = validadeCNH ? validadeCNHSelect.options[validadeCNHSelect.selectedIndex].text : 'Todos';
    const situacaoTexto = situacao ? situacaoSelect.options[situacaoSelect.selectedIndex].text : 'Todos';
    
    filtrosAplicados = {
        nome: nome,
        cnh: cnh,
        validade_cnh: validadeCNH,
        situacao: situacao,
        validade_cnh_texto: validadeCNHTexto,
        situacao_texto: situacaoTexto
    };
    
    const params = new URLSearchParams();
    if (nome) params.append('nome', nome);
    if (cnh) params.append('cnh', cnh);
    if (validadeCNH) params.append('validade_cnh', validadeCNH);
    if (situacao) params.append('situacao', situacao);
    
    document.getElementById('filterSection').style.display = 'none';
    document.getElementById('resultsSection').style.display = 'block';
    
    fetch(`../api/relatorio_condutores.php?action=get_condutores&${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Erro na requisição');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                condutoresData = data.data;
                renderGrid();
            } else {
                alert('Erro ao carregar dados: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar relatório: ' + error.message);
        });
}

function renderGrid() {
    const grid = document.getElementById('resultsGrid');
    
    if (condutoresData.length === 0) {
        grid.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">Nenhum condutor encontrado com os filtros aplicados</p>
            </div>
        `;
        return;
    }
    
    // Ordenar dados
    let dados = [...condutoresData];
    dados.sort((a, b) => {
        switch(currentOrdenacao) {
            case 'nome':
                return a.nome.localeCompare(b.nome);
            case 'cnh':
                return a.cnh.localeCompare(b.cnh);
            case 'validade_cnh':
                return (a.validade_cnh || '').localeCompare(b.validade_cnh || '');
            default:
                return 0;
        }
    });
    
    let html = `
        <div class="mb-3">
            <strong>Total de condutores:</strong> ${dados.length}
        </div>
    `;
    
    if (currentAgrupamento === 'none') {
        // Sem agrupamento
        html += `
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>CNH</th>
                            <th>Validade CNH</th>
                            <th>Status CNH</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        dados.forEach(condutor => {
            html += `
                <tr class="condutor-row">
                    <td><strong>${condutor.nome}</strong></td>
                    <td>${condutor.cnh}</td>
                    <td>${condutor.validade_cnh_formatada || '-'}</td>
                    <td>${getStatusCNHBadge(condutor.status_cnh)}</td>
                    <td><span class="badge ${condutor.situacao === 'Ativo' ? 'bg-success' : 'bg-secondary'}">${condutor.situacao || 'Inativo'}</span></td>
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
        dados.forEach(condutor => {
            let chave;
            if (currentAgrupamento === 'situacao') {
                chave = condutor.situacao || 'Inativo';
            } else if (currentAgrupamento === 'status_cnh') {
                chave = condutor.status_cnh || 'Não informado';
            }
            
            if (!grupos[chave]) {
                grupos[chave] = [];
            }
            grupos[chave].push(condutor);
        });
        
        Object.keys(grupos).sort().forEach(grupo => {
            html += `
                <div class="group-header">
                    ${grupo} (${grupos[grupo].length} condutor${grupos[grupo].length !== 1 ? 'es' : ''})
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Nome</th>
                                <th>CNH</th>
                                <th>Validade CNH</th>
                                ${currentAgrupamento !== 'status_cnh' ? '<th>Status CNH</th>' : ''}
                                ${currentAgrupamento !== 'situacao' ? '<th>Situação</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            grupos[grupo].forEach(condutor => {
                html += `
                    <tr class="condutor-row">
                        <td><strong>${condutor.nome}</strong></td>
                        <td>${condutor.cnh}</td>
                        <td>${condutor.validade_cnh_formatada || '-'}</td>
                        ${currentAgrupamento !== 'status_cnh' ? `<td>${getStatusCNHBadge(condutor.status_cnh)}</td>` : ''}
                        ${currentAgrupamento !== 'situacao' ? `<td><span class="badge ${condutor.situacao === 'Ativo' ? 'bg-success' : 'bg-secondary'}">${condutor.situacao || 'Inativo'}</span></td>` : ''}
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

function getStatusCNHBadge(status) {
    switch(status) {
        case 'Válida':
            return '<span class="badge badge-valido">Válida</span>';
        case 'A Expirar':
            return '<span class="badge badge-a-expirar">A Expirar</span>';
        case 'Expirada':
            return '<span class="badge badge-expirado">Expirada</span>';
        default:
            return '<span class="badge bg-secondary">-</span>';
    }
}

function voltarPesquisa() {
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('filterSection').style.display = 'block';
}

function limparFiltros() {
    document.getElementById('filtroNome').value = '';
    document.getElementById('filtroCNH').value = '';
    document.getElementById('filtroValidadeCNH').value = '';
    document.getElementById('filtroSituacao').value = '';
    
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('filterSection').style.display = 'block';
    condutoresData = [];
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
    if (condutoresData.length === 0) {
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
    let csv = 'Nome;CNH;Validade CNH;Status CNH;Situação\n';
    
    condutoresData.forEach(condutor => {
        const situacao = condutor.situacao == 1 ? 'Ativo' : 'Inativo';
        csv += `${condutor.nome};${condutor.cnh};${condutor.validade_cnh_formatada || ''};${condutor.status_cnh || ''};${situacao}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `relatorio_condutores_${new Date().getTime()}.csv`;
    link.click();
}

function exportarExcel() {
    let html = '<table>';
    html += '<thead><tr><th>Nome</th><th>CNH</th><th>Validade CNH</th><th>Status CNH</th><th>Situação</th></tr></thead>';
    html += '<tbody>';
    
    condutoresData.forEach(condutor => {
        const situacao = condutor.situacao == 1 ? 'Ativo' : 'Inativo';
        html += `<tr>
            <td>${condutor.nome}</td>
            <td>${condutor.cnh}</td>
            <td>${condutor.validade_cnh_formatada || ''}</td>
            <td>${condutor.status_cnh || ''}</td>
            <td>${situacao}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `relatorio_condutores_${new Date().getTime()}.xls`;
    link.click();
}

function exportarPDF() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../api/exportar_pdf_condutores.php';
    form.target = '_blank';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'dados';
    input.value = JSON.stringify({
        condutores: condutoresData,
        agrupamento: currentAgrupamento,
        ordenacao: currentOrdenacao,
        filtros: filtrosAplicados
    });
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
