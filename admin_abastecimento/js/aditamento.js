let currentPage = 1;
let currentSearch = '';
let currentStatus = '';
let currentAditamentoId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData(1);
    loadLicitacoes();
    
    // Busca
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            currentSearch = this.value;
            loadData(1);
        }
    });
    
    document.querySelector('.btn-secondary').addEventListener('click', function() {
        currentSearch = document.getElementById('searchInput').value;
        loadData(1);
    });
    
    // Filtro de status
    document.getElementById('statusFilter').addEventListener('change', function() {
        currentStatus = this.value;
        loadData(1);
    });
    
    // Form submit
    document.getElementById('aditamentoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        salvar();
    });
    
    // Checkbox selecionar todos
    document.getElementById('checkAllProdutos')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('#produtosPrecoTable input[type="checkbox"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
    
    // Ao abrir tab de preços
    const precosTab = document.getElementById('precos-tab');
    if (precosTab) {
        precosTab.addEventListener('shown.bs.tab', function() {
            const aditamentoId = document.getElementById('aditamentoId').value;
            const infoAlert = document.getElementById('infoSalvarAditamento');
            const precosContent = document.getElementById('precosContent');
            
            if (aditamentoId) {
                if (infoAlert) infoAlert.style.display = 'none';
                if (precosContent) precosContent.style.display = 'block';
                loadPrecos(aditamentoId);
            } else {
                if (infoAlert) infoAlert.style.display = 'block';
                if (precosContent) precosContent.style.display = 'none';
            }
        });
    }
});

function loadData(page) {
    currentPage = page;
    const searchParam = currentSearch ? `&search=${encodeURIComponent(currentSearch)}` : '';
    const statusParam = currentStatus ? `&status=${encodeURIComponent(currentStatus)}` : '';
    
    fetch(`../api/aditamento_combustivel.php?action=list&page=${page}${searchParam}${statusParam}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderTable(data.data);
            renderPagination(data.pagination);
        } else {
            alert('Erro ao carregar aditamentos: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar aditamentos');
    });
}

function renderTable(aditamentos) {
    const tbody = document.getElementById('aditamentosTable');
    
    if (aditamentos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum aditamento encontrado</td></tr>';
        return;
    }
    
    tbody.innerHTML = aditamentos.map(aditamento => {
        const dataFormatada = formatDate(aditamento.data);
        
        // Formatar vigência
        let vigenciaText = '-';
        if (aditamento.inicio_vigencia) {
            vigenciaText = formatDateTime(aditamento.inicio_vigencia);
            if (aditamento.fim_vigencia) {
                vigenciaText += ' até ' + formatDateTime(aditamento.fim_vigencia);
            }
        }
        
        // Status badge
        let statusBadge = '';
        switch (aditamento.status_vigencia) {
            case 'futuro':
                statusBadge = '<span class="badge bg-info">Futuro</span>';
                break;
            case 'ativo':
                statusBadge = '<span class="badge bg-success">Ativo</span>';
                break;
            case 'encerrado':
                statusBadge = '<span class="badge bg-secondary">Encerrado</span>';
                break;
            default:
                statusBadge = '<span class="badge bg-warning">Sem Preços</span>';
        }
        
        let actionsHtml = '';
        if (podeVisualizarAditamento && !podeEditarAditamento) {
            actionsHtml += `<button class="btn btn-sm btn-outline-primary me-1" onclick="visualizar(${aditamento.id_aditamento_combustivel})" title="Visualizar" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-eye" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        if (podeEditarAditamento) {
            actionsHtml += `<button class="btn btn-sm btn-outline-warning me-1" onclick="editar(${aditamento.id_aditamento_combustivel})" title="Editar" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-edit" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        if (podeExcluirAditamento) {
            actionsHtml += `<button class="btn btn-sm btn-outline-danger" onclick="excluir(${aditamento.id_aditamento_combustivel})" title="Excluir" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-trash" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        
        return `
            <tr>
                <td style="vertical-align: middle;">${aditamento.codigo}</td>
                <td style="vertical-align: middle;">${aditamento.contrato_codigo}</td>
                <td style="vertical-align: middle;">${aditamento.fornecedor_nome || '-'}</td>
                <td style="vertical-align: middle;">${dataFormatada}</td>
                <td style="vertical-align: middle;"><small>${vigenciaText}</small></td>
                <td class="text-center" style="vertical-align: middle;">${statusBadge}</td>
                <td style="vertical-align: middle;">${actionsHtml}</td>
            </tr>
        `;
    }).join('');
}

function renderPagination(pagination) {
    const paginationContainer = document.getElementById('paginacao');
    
    if (pagination.totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let html = '';
    
    if (pagination.page > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadData(${pagination.page - 1}); return false;">Anterior</a></li>`;
    }
    
    for (let i = 1; i <= pagination.totalPages; i++) {
        if (i === pagination.page) {
            html += `<li class="page-item active"><a class="page-link" href="#">${i}</a></li>`;
        } else if (i === 1 || i === pagination.totalPages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadData(${i}); return false;">${i}</a></li>`;
        } else if (i === pagination.page - 3 || i === pagination.page + 3) {
            html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
        }
    }
    
    if (pagination.page < pagination.totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadData(${pagination.page + 1}); return false;">Próximo</a></li>`;
    }
    
    paginationContainer.innerHTML = html;
}

async function loadLicitacoes() {
    try {
        const response = await fetch('../api/licitacao.php?action=list&page=1&perPage=1000', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            const err = await response.json();
            throw new Error(err.message || 'Erro ao carregar licitações');
        }
        
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('id_licitacao');
            select.innerHTML = '<option value="">Selecione...</option>';
            data.data.forEach(licitacao => {
                const option = document.createElement('option');
                option.value = licitacao.id_licitacao;
                option.textContent = `${licitacao.codigo} - ${licitacao.objeto.substring(0, 60)}${licitacao.objeto.length > 60 ? '...' : ''}`;
                select.appendChild(option);
            });
        } else {
            console.error('Erro na resposta:', data.message);
        }
    } catch (error) {
        console.error('Erro ao carregar licitações:', error.message);
    }
}

async function carregarContratos(idLicitacaoParam = null) {
    const idLicitacao = idLicitacaoParam || document.getElementById('id_licitacao').value;
    const selectContrato = document.getElementById('id_contrato');
    
    if (!idLicitacao) {
        selectContrato.innerHTML = '<option value="">Selecione uma licitação primeiro...</option>';
        return;
    }
    
    try {
        const response = await fetch(`../api/aditamento_combustivel.php?action=get_contratos&id_licitacao=${idLicitacao}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            selectContrato.innerHTML = '<option value="">Selecione...</option>';
            data.data.forEach(contrato => {
                const option = document.createElement('option');
                option.value = contrato.id_contrato;
                option.textContent = `${contrato.codigo} - ${contrato.fornecedor_nome}`;
                selectContrato.appendChild(option);
            });
        } else {
            console.error('Erro ao carregar contratos:', data.message);
        }
    } catch (error) {
        console.error('Erro ao carregar contratos:', error);
    }
}

function loadProdutos(idAditamento) {
    fetch(`../api/aditamento_combustivel.php?action=get_produtos&id_aditamento=${idAditamento}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('id_produto');
            select.innerHTML = '<option value="">Selecione...</option>';
            
            if (data.data.length === 0) {
                select.innerHTML = '<option value="">Nenhum produto vinculado ao contrato</option>';
                alert('Este contrato não possui produtos vinculados. Vincule produtos ao contrato antes de adicionar preços.');
                return;
            }
            
            data.data.forEach(produto => {
                const option = document.createElement('option');
                option.value = produto.id_produto;
                option.textContent = `${produto.nome} (${produto.unidade})`;
                select.appendChild(option);
            });
        } else {
            alert('Erro ao carregar produtos: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro ao carregar produtos:', error);
        alert('Erro ao carregar produtos');
    });
}

function loadPrecos(idAditamento) {
    currentAditamentoId = idAditamento;
    
    fetch(`../api/aditamento_combustivel.php?action=get_precos&id_aditamento=${idAditamento}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderPrecos(data.data);
        } else {
            alert('Erro ao carregar preços: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar preços');
    });
}

function renderPrecos(precos) {
    const tbody = document.getElementById('precosTable');
    
    if (precos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Nenhum preço cadastrado</td></tr>';
        return;
    }
    
    tbody.innerHTML = precos.map(preco => {
        const valorFormatado = parseFloat(preco.valor).toFixed(3).replace('.', ',');
        const inicioVigencia = formatDateTime(preco.inicio_vigencia);
        const fimVigencia = preco.fim_vigencia ? formatDateTime(preco.fim_vigencia) : '-';
        
        let statusBadge = '';
        switch (preco.status) {
            case 'futuro':
                statusBadge = '<span class="badge bg-info">Futuro</span>';
                break;
            case 'ativo':
                statusBadge = '<span class="badge bg-success">Ativo</span>';
                break;
            case 'encerrado':
                statusBadge = '<span class="badge bg-secondary">Encerrado</span>';
                break;
        }
        
        let actionsHtml = '';
        if (podeEditarAditamento && preco.status !== 'encerrado') {
            actionsHtml = `<button class="btn btn-sm btn-danger" onclick="excluirPreco(${preco.id_preco_combustivel})" title="Excluir" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-trash" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        
        return `
            <tr>
                <td>${preco.produto_nome}</td>
                <td>R$ ${valorFormatado}</td>
                <td><small>${inicioVigencia}</small></td>
                <td><small>${fimVigencia}</small></td>
                <td class="text-center">${statusBadge}</td>
                <td>${actionsHtml}</td>
            </tr>
        `;
    }).join('');
}

function resetForm() {
    document.getElementById('aditamentoForm').reset();
    document.getElementById('aditamentoId').value = '';
    document.getElementById('modalTitle').textContent = 'Novo Aditamento';
    
    // Reabilitar todos os campos
    document.querySelectorAll('#modalAditamento input, #modalAditamento select, #modalAditamento textarea').forEach(el => {
        el.disabled = false;
    });
    // Reexibir botões de ação
    document.querySelectorAll('#modalAditamento button[type="submit"], #modalAditamento .btn-primary, #modalAditamento .btn-success').forEach(el => {
        el.style.display = 'block';
    });
    
    // Limpar contrato select
    document.getElementById('id_contrato').innerHTML = '<option value="">Selecione uma licitação primeiro...</option>';
    
    // Definir data atual
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('data').value = today;
    
    // Voltar para primeira aba
    document.getElementById('dados-tab').click();
}

function salvar() {
    const id = document.getElementById('aditamentoId').value;
    const codigo = document.getElementById('codigo').value.trim();
    const data = document.getElementById('data').value;
    const descricao = document.getElementById('descricao').value.trim();
    const id_licitacao = document.getElementById('id_licitacao').value;
    const id_contrato = document.getElementById('id_contrato').value;
    
    if (!codigo || !data || !descricao || !id_licitacao || !id_contrato) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }
    
    const payload = { codigo, data, descricao, id_licitacao, id_contrato };
    if (id) payload.id = id;
    
    const action = id ? 'update' : 'create';
    
    fetch(`../api/aditamento_combustivel.php?action=${action}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            if (!id) {
                // Novo aditamento - armazenar ID e habilitar aba de preços
                document.getElementById('aditamentoId').value = data.id;
                currentAditamentoId = data.id;
                alert('Aditamento criado! Agora você pode cadastrar os preços na aba "Preços de Combustíveis".');
                document.getElementById('precos-tab').click();
            } else {
                bootstrap.Modal.getInstance(document.getElementById('modalAditamento')).hide();
                loadData(currentPage);
            }
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar aditamento');
    });
}

function mostrarFormNovosPrecos() {
    if (!currentAditamentoId) {
        alert('Salve o aditamento primeiro');
        return;
    }
    
    // Carregar produtos vinculados ao contrato
    loadProdutosParaPreco(currentAditamentoId);
    
    // Definir data/hora atual + 1 hora como padrão
    const now = new Date();
    now.setHours(now.getHours() + 1);
    const datetimeLocal = now.toISOString().slice(0, 16);
    document.getElementById('inicioVigenciaGeral').value = datetimeLocal;
    
    // Mostrar formulário e esconder botão
    document.getElementById('formNovosPrecos').style.display = 'block';
    document.getElementById('btnAdicionarPreco').style.display = 'none';
}

function cancelarNovosPrecos() {
    document.getElementById('formNovosPrecos').style.display = 'none';
    document.getElementById('btnAdicionarPreco').style.display = 'block';
    document.getElementById('produtosPrecoTable').innerHTML = '';
}

function loadProdutosParaPreco(idAditamento) {
    fetch(`../api/aditamento_combustivel.php?action=get_produtos&id_aditamento=${idAditamento}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const tbody = document.getElementById('produtosPrecoTable');
            
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nenhum produto vinculado ao contrato</td></tr>';
                alert('Este contrato não possui produtos vinculados. Vincule produtos ao contrato antes de adicionar preços.');
                return;
            }
            
            tbody.innerHTML = data.data.map(produto => `
                <tr>
                    <td>${produto.nome}</td>
                    <td class="text-center">${produto.unidade}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm" 
                               id="valor_${produto.id_produto}" 
                               step="0.001" min="0" placeholder="0.000">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input" 
                               id="check_${produto.id_produto}" 
                               data-id-produto="${produto.id_produto}">
                    </td>
                </tr>
            `).join('');
        } else {
            alert('Erro ao carregar produtos: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro ao carregar produtos:', error);
        alert('Erro ao carregar produtos');
    });
}

function salvarNovosPrecos() {
    const inicioVigencia = document.getElementById('inicioVigenciaGeral').value;
    
    if (!inicioVigencia) {
        alert('Defina o início da vigência');
        return;
    }
    
    if (!currentAditamentoId) {
        alert('Erro: ID do aditamento não encontrado');
        return;
    }
    
    // Coletar produtos selecionados com valores preenchidos
    const checkboxes = document.querySelectorAll('#produtosPrecoTable input[type="checkbox"]:checked');
    const precos = [];
    
    checkboxes.forEach(checkbox => {
        const idProduto = checkbox.getAttribute('data-id-produto');
        const valor = document.getElementById(`valor_${idProduto}`).value;
        
        if (valor && parseFloat(valor) > 0) {
            precos.push({
                id_produto: parseInt(idProduto),
                valor: parseFloat(valor),
                inicio_vigencia: inicioVigencia
            });
        }
    });
    
    if (precos.length === 0) {
        alert('Selecione pelo menos um produto e preencha o valor');
        return;
    }
    
    // Salvar cada preço
    let sucessos = 0;
    let erros = 0;
    const total = precos.length;
    
    Promise.all(precos.map(preco => {
        const payload = {
            id_aditamento_combustivel: currentAditamentoId,
            id_produto: preco.id_produto,
            valor: preco.valor,
            inicio_vigencia: preco.inicio_vigencia
        };
        
        return fetch('../api/aditamento_combustivel.php?action=add_preco', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                sucessos++;
            } else {
                erros++;
                console.error('Erro ao salvar:', data.message);
            }
        })
        .catch(error => {
            erros++;
            console.error('Erro:', error);
        });
    }))
    .then(() => {
        if (sucessos > 0) {
            alert(`${sucessos} preço(s) cadastrado(s) com sucesso!${erros > 0 ? ` (${erros} erro(s))` : ''}`);
            cancelarNovosPrecos();
            loadPrecos(currentAditamentoId);
        } else {
            alert('Nenhum preço foi cadastrado. Verifique os erros.');
        }
    });
}

function excluirPreco(id) {
    if (!confirm('Tem certeza que deseja excluir este preço?')) {
        return;
    }
    
    fetch(`../api/aditamento_combustivel.php?action=delete_preco&id=${id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadPrecos(currentAditamentoId);
        } else {
            alert('Erro ao excluir: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir preço');
    });
}

async function visualizar(id) {
    try {
        const response = await fetch(`../api/aditamento_combustivel.php?action=get&id=${id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const aditamento = data.data;
            
            document.getElementById('aditamentoId').value = aditamento.id_aditamento_combustivel;
            document.getElementById('codigo').value = aditamento.codigo;
            document.getElementById('data').value = aditamento.data;
            document.getElementById('descricao').value = aditamento.descricao;
            
            document.getElementById('modalTitle').textContent = 'Visualizar Aditamento';
            currentAditamentoId = aditamento.id_aditamento_combustivel;
            
            // Carregar licitações primeiro (pode não ter sido carregado ainda)
            await loadLicitacoes();
            
            // Definir licitação - adicionar manualmente se não existir no select
            const selectLicitacao = document.getElementById('id_licitacao');
            
            // Verificar se a opção existe
            const optionExists = Array.from(selectLicitacao.options).some(opt => opt.value == aditamento.id_licitacao);
            
            if (!optionExists && aditamento.id_licitacao) {
                // Adicionar a opção manualmente com os dados do aditamento
                const option = document.createElement('option');
                option.value = aditamento.id_licitacao;
                option.textContent = aditamento.licitacao_codigo || `Licitação ${aditamento.id_licitacao}`;
                selectLicitacao.appendChild(option);
            }
            
            selectLicitacao.value = aditamento.id_licitacao;
            
            // Carregar contratos baseado na licitação selecionada
            await carregarContratos(aditamento.id_licitacao);
            
            // Definir contrato - adicionar manualmente se não existir
            const selectContrato = document.getElementById('id_contrato');
            
            // Verificar se a opção existe
            const contratoOptionExists = Array.from(selectContrato.options).some(opt => opt.value == aditamento.id_contrato);
            
            if (!contratoOptionExists && aditamento.id_contrato) {
                // Adicionar a opção manualmente com os dados do aditamento
                const option = document.createElement('option');
                option.value = aditamento.id_contrato;
                option.textContent = aditamento.contrato_codigo || `Contrato ${aditamento.id_contrato}`;
                selectContrato.appendChild(option);
            }
            
            selectContrato.value = aditamento.id_contrato;
            
            // Desabilitar todos os campos para modo visualização após carregar
            document.querySelectorAll('#modalAditamento input, #modalAditamento select, #modalAditamento textarea').forEach(el => {
                el.disabled = true;
            });
            // Ocultar botões de ação
            document.querySelectorAll('#modalAditamento button[type="submit"], #modalAditamento .btn-primary, #modalAditamento .btn-success').forEach(el => {
                el.style.display = 'none';
            });
            
            new bootstrap.Modal(document.getElementById('modalAditamento')).show();
        } else {
            alert('Erro ao carregar aditamento: ' + data.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao carregar aditamento');
    }
}

function editar(id) {
    fetch(`../api/aditamento_combustivel.php?action=get&id=${id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const aditamento = data.data;
            document.getElementById('aditamentoId').value = aditamento.id_aditamento_combustivel;
            document.getElementById('codigo').value = aditamento.codigo;
            document.getElementById('data').value = aditamento.data;
            document.getElementById('descricao').value = aditamento.descricao;
            document.getElementById('id_licitacao').value = aditamento.id_licitacao;
            
            // Carregar contratos e depois selecionar
            carregarContratos();
            setTimeout(() => {
                document.getElementById('id_contrato').value = aditamento.id_contrato;
            }, 500);
            
            document.getElementById('modalTitle').textContent = 'Editar Aditamento';
            currentAditamentoId = aditamento.id_aditamento_combustivel;
            
            // Reabilitar todos os campos para edição
            document.querySelectorAll('#modalAditamento input, #modalAditamento select, #modalAditamento textarea').forEach(el => {
                el.disabled = false;
            });
            // Reexibir botões de ação
            document.querySelectorAll('#modalAditamento button[type="submit"], #modalAditamento .btn-primary, #modalAditamento .btn-success').forEach(el => {
                el.style.display = 'block';
            });
            
            new bootstrap.Modal(document.getElementById('modalAditamento')).show();
        } else {
            alert('Erro ao carregar aditamento: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar aditamento');
    });
}

function excluir(id) {
    if (!confirm('Tem certeza que deseja excluir este aditamento? Todos os preços vinculados também serão excluídos.')) {
        return;
    }
    
    fetch(`../api/aditamento_combustivel.php?action=delete&id=${id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadData(currentPage);
        } else {
            alert('Erro ao excluir: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir aditamento');
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    return date.toLocaleString('pt-BR', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
