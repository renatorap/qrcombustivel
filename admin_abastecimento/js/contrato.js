let currentPage = 1;
let currentSearch = '';
let currentContratoId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadData(1);
    loadLicitacoes();
    loadFornecedores();
    
    // Busca
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            currentSearch = this.value;
            loadData(1);
        }
    });
    
    document.querySelector('.btn-buscar').addEventListener('click', function() {
        currentSearch = document.getElementById('searchInput').value;
        loadData(1);
    });
    
    // Form submit
    document.getElementById('contratoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        salvar();
    });
    
    // Ao abrir tab de produtos
    const produtosTab = document.getElementById('produtos-tab');
    if (produtosTab) {
        produtosTab.addEventListener('shown.bs.tab', function() {
            const contratoId = document.getElementById('contratoId').value;
            const infoAlert = document.getElementById('infoSalvarContrato');
            const produtosContent = document.getElementById('produtosContent');
            
            if (contratoId || currentContratoId) {
                const idToUse = contratoId || currentContratoId;
                
                // Esconder alerta e mostrar conteúdo
                if (infoAlert) infoAlert.style.display = 'none';
                if (produtosContent) produtosContent.style.display = 'block';
                
                loadProdutosDisponiveis(idToUse);
                loadProdutosVinculados(idToUse);
            } else {
                if (infoAlert) infoAlert.style.display = 'block';
                if (produtosContent) produtosContent.style.display = 'none';
            }
        });
    }
});

function loadData(page) {
    currentPage = page;
    const searchParam = currentSearch ? `&search=${encodeURIComponent(currentSearch)}` : '';
    
    fetch(`../api/contrato.php?action=list&page=${page}${searchParam}`, {
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
            alert('Erro ao carregar contratos: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar contratos');
    });
}

function renderTable(contratos) {
    const tbody = document.getElementById('contratosTable');
    
    if (contratos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum contrato encontrado</td></tr>';
        return;
    }
    
    tbody.innerHTML = contratos.map(contrato => {
        const dataFormatada = formatDate(contrato.data);
        const descricaoTruncada = contrato.descricao.length > 50 ? contrato.descricao.substring(0, 50) + '...' : contrato.descricao;
        
        let actionsHtml = '';
        if (podeVisualizarContrato && !podeEditarContrato) {
            actionsHtml += `<button class="btn btn-sm btn-outline-primary me-1" onclick="visualizar(${contrato.id_contrato})" title="Visualizar" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-eye" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        if (podeEditarContrato) {
            actionsHtml += `<button class="btn btn-sm btn-outline-warning me-1" onclick="editar(${contrato.id_contrato})" title="Editar" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-edit" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        if (podeExcluirContrato) {
            actionsHtml += `<button class="btn btn-sm btn-outline-danger" onclick="excluir(${contrato.id_contrato})" title="Excluir" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-trash" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        
        const aditamentoLink = contrato.total_aditamentos > 0 
            ? `<a href="aditamento.php?contrato=${contrato.id_contrato}" style="color: #0d6efd; text-decoration: none; cursor: pointer;">${contrato.total_aditamentos}</a>`
            : '0';
        
        return `
            <tr>
                <td style="vertical-align: middle;">${contrato.codigo}</td>
                <td style="vertical-align: middle;">${contrato.licitacao_codigo}</td>
                <td style="vertical-align: middle;">${contrato.fornecedor_nome}</td>
                <td style="vertical-align: middle;" title="${contrato.descricao}">${descricaoTruncada}</td>
                <td style="vertical-align: middle;">${dataFormatada}</td>
                <td class="text-center" style="vertical-align: middle;">${aditamentoLink}</td>
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
    
    // Anterior
    if (pagination.page > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadData(${pagination.page - 1}); return false;">Anterior</a></li>`;
    }
    
    // Páginas
    for (let i = 1; i <= pagination.totalPages; i++) {
        if (i === pagination.page) {
            html += `<li class="page-item active"><a class="page-link" href="#">${i}</a></li>`;
        } else if (i === 1 || i === pagination.totalPages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="loadData(${i}); return false;">${i}</a></li>`;
        } else if (i === pagination.page - 3 || i === pagination.page + 3) {
            html += `<li class="page-item disabled"><a class="page-link" href="#">...</a></li>`;
        }
    }
    
    // Próximo
    if (pagination.page < pagination.totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadData(${pagination.page + 1}); return false;">Próximo</a></li>`;
    }
    
    paginationContainer.innerHTML = html;
}

function loadLicitacoes() {
    fetch('../api/contrato.php?action=get_licitacoes', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('id_licitacao');
            select.innerHTML = '<option value="">Selecione...</option>';
            data.data.forEach(licitacao => {
                const option = document.createElement('option');
                option.value = licitacao.id_licitacao;
                option.textContent = `${licitacao.codigo} - ${licitacao.objeto.substring(0, 60)}${licitacao.objeto.length > 60 ? '...' : ''}`;
                select.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Erro ao carregar licitações:', error);
    });
}

function loadFornecedores() {
    fetch('../api/contrato.php?action=get_fornecedores', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('id_fornecedor');
            select.innerHTML = '<option value="">Selecione...</option>';
            data.data.forEach(fornecedor => {
                const option = document.createElement('option');
                option.value = fornecedor.id_fornecedor;
                option.textContent = fornecedor.razao_social;
                select.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Erro ao carregar fornecedores:', error);
    });
}

function resetForm() {
    document.getElementById('contratoForm').reset();
    document.getElementById('contratoId').value = '';
    document.getElementById('modalTitle').textContent = 'Novo Contrato';
    currentContratoId = null;
    
    // Reabilitar todos os campos
    document.querySelectorAll('#modalContrato input, #modalContrato select, #modalContrato textarea').forEach(el => {
        el.disabled = false;
    });
    document.querySelector('#modalContrato button[type="submit"]').style.display = 'block';
    
    // Definir data atual
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('data').value = today;
    
    // Voltar para primeira aba
    document.getElementById('dados-tab').click();
}

function salvar() {
    const id = document.getElementById('contratoId').value;
    const codigo = document.getElementById('codigo').value.trim();
    const data = document.getElementById('data').value;
    const descricao = document.getElementById('descricao').value.trim();
    const id_licitacao = document.getElementById('id_licitacao').value;
    const id_fornecedor = document.getElementById('id_fornecedor').value;
    
    if (!codigo || !data || !descricao || !id_licitacao || !id_fornecedor) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }
    
    const payload = { codigo, data, descricao, id_licitacao, id_fornecedor };
    if (id) payload.id = id;
    
    const action = id ? 'update' : 'create';
    
    fetch(`../api/contrato.php?action=${action}`, {
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
                // Novo contrato - armazenar ID e habilitar aba de produtos
                document.getElementById('contratoId').value = data.id;
                currentContratoId = data.id;
                document.getElementById('modalTitle').textContent = 'Editar Contrato';
                alert('Contrato criado! Agora você pode vincular produtos na aba "Produtos".');
                document.getElementById('produtos-tab').click();
            } else {
                bootstrap.Modal.getInstance(document.getElementById('modalContrato')).hide();
                loadData(currentPage);
            }
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar contrato');
    });
}

function visualizar(id) {
    fetch(`../api/contrato.php?action=get&id=${id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const contrato = data.data;
            document.getElementById('contratoId').value = contrato.id_contrato;
            document.getElementById('codigo').value = contrato.codigo;
            document.getElementById('data').value = contrato.data;
            document.getElementById('descricao').value = contrato.descricao;
            document.getElementById('id_licitacao').value = contrato.id_licitacao;
            document.getElementById('id_fornecedor').value = contrato.id_fornecedor;
            document.getElementById('modalTitle').textContent = 'Visualizar Contrato';
            currentContratoId = contrato.id_contrato;
            
            // Desabilitar todos os campos para modo visualização
            document.querySelectorAll('#modalContrato input, #modalContrato select, #modalContrato textarea').forEach(el => {
                el.disabled = true;
            });
            document.querySelector('#modalContrato button[type="submit"]').style.display = 'none';
            
            new bootstrap.Modal(document.getElementById('modalContrato')).show();
        } else {
            alert('Erro ao carregar contrato: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar contrato');
    });
}

function editar(id) {
    fetch(`../api/contrato.php?action=get&id=${id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const contrato = data.data;
            document.getElementById('contratoId').value = contrato.id_contrato;
            document.getElementById('codigo').value = contrato.codigo;
            document.getElementById('data').value = contrato.data;
            document.getElementById('descricao').value = contrato.descricao;
            document.getElementById('id_licitacao').value = contrato.id_licitacao;
            document.getElementById('id_fornecedor').value = contrato.id_fornecedor;
            document.getElementById('modalTitle').textContent = 'Editar Contrato';
            currentContratoId = contrato.id_contrato;
            
            // Reabilitar todos os campos para edição
            document.querySelectorAll('#modalContrato input, #modalContrato select, #modalContrato textarea').forEach(el => {
                el.disabled = false;
            });
            document.querySelector('#modalContrato button[type="submit"]').style.display = 'block';
            
            new bootstrap.Modal(document.getElementById('modalContrato')).show();
        } else {
            alert('Erro ao carregar contrato: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar contrato');
    });
}

function excluir(id) {
    if (!confirm('Tem certeza que deseja excluir este contrato?')) {
        return;
    }
    
    fetch(`../api/contrato.php?action=delete&id=${id}`, {
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
        alert('Erro ao excluir contrato');
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

function loadProdutosDisponiveis(contratoId) {
    currentContratoId = contratoId;
    
    fetch(`../api/contrato_produtos.php?action=get_disponiveis&id_contrato=${contratoId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('produtoSelect');
            if (!select) return;
            
            select.innerHTML = '<option value="">Selecione um produto para vincular...</option>';
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
        console.error('Erro ao carregar produtos disponíveis:', error);
        alert('Erro ao carregar produtos disponíveis');
    });
}

function loadProdutosVinculados(contratoId) {
    fetch(`../api/contrato_produtos.php?action=list&id_contrato=${contratoId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderProdutosVinculados(data.data);
        } else {
            alert('Erro ao carregar produtos: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar produtos vinculados');
    });
}

function renderProdutosVinculados(produtos) {
    const tbody = document.getElementById('produtosVinculadosTable');
    
    if (produtos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Nenhum produto vinculado</td></tr>';
        return;
    }
    
    tbody.innerHTML = produtos.map(produto => {
        const dataVinculo = formatDateTime(produto.data_vinculo);
        
        let actionsHtml = '';
        if (podeEditarContrato) {
            actionsHtml = `<button class="btn btn-sm btn-danger" onclick="desvincularProduto(${produto.id_contrato_produto})" title="Desvincular" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-unlink" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        
        return `
            <tr>
                <td style="vertical-align: middle;">${produto.produto_nome}</td>
                <td style="vertical-align: middle;">${produto.unidade}</td>
                <td style="vertical-align: middle;"><small>${dataVinculo}</small></td>
                <td style="vertical-align: middle;">${actionsHtml}</td>
            </tr>
        `;
    }).join('');
}

function vincularProduto() {
    const idProduto = document.getElementById('produtoSelect').value;
    
    if (!idProduto) {
        alert('Selecione um produto');
        return;
    }
    
    if (!currentContratoId) {
        alert('Salve o contrato primeiro');
        return;
    }
    
    const payload = {
        id_contrato: parseInt(currentContratoId),
        id_produto: parseInt(idProduto)
    };
    
    fetch('../api/contrato_produtos.php?action=vincular', {
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
            loadProdutosDisponiveis(currentContratoId);
            loadProdutosVinculados(currentContratoId);
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro ao vincular produto:', error);
        alert('Erro ao vincular produto');
    });
}

function desvincularProduto(id) {
    if (!confirm('Tem certeza que deseja desvincular este produto do contrato?')) {
        return;
    }
    
    fetch(`../api/contrato_produtos.php?action=desvincular&id=${id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadProdutosDisponiveis(currentContratoId);
            loadProdutosVinculados(currentContratoId);
        } else {
            alert('Erro ao desvincular: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao desvincular produto');
    });
}
