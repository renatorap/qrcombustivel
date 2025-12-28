let currentPage = 1;
let currentSearch = '';

document.addEventListener('DOMContentLoaded', function() {
    loadData(1);
    
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
    document.getElementById('licitacaoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        salvar();
    });
});

function loadData(page) {
    currentPage = page;
    const searchParam = currentSearch ? `&search=${encodeURIComponent(currentSearch)}` : '';
    
    fetch(`../api/licitacao.php?action=list&page=${page}${searchParam}`, {
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
            alert('Erro ao carregar licitações: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar licitações');
    });
}

function renderTable(licitacoes) {
    const tbody = document.getElementById('licitacoesTable');
    
    if (licitacoes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhuma licitação encontrada</td></tr>';
        return;
    }
    
    tbody.innerHTML = licitacoes.map(licitacao => {
        const dataFormatada = formatDate(licitacao.data);
        const objetoTruncado = licitacao.objeto.length > 80 ? licitacao.objeto.substring(0, 80) + '...' : licitacao.objeto;
        
        let actionsHtml = '';
        if (podeEditarLicitacao) {
            actionsHtml += `<button class="btn btn-sm btn-primary me-1" onclick="editar(${licitacao.id_licitacao})" title="Editar" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-edit" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        if (podeExcluirLicitacao) {
            actionsHtml += `<button class="btn btn-sm btn-danger" onclick="excluir(${licitacao.id_licitacao})" title="Excluir" style="padding: 0.25rem 0.5rem; width: 32px; height: 32px;">
                <i class="fas fa-trash" style="font-size: 0.875rem;"></i>
            </button>`;
        }
        
        const contratoLink = licitacao.total_contratos > 0 
            ? `<a href="contrato.php?licitacao=${licitacao.id_licitacao}" style="color: #0d6efd; text-decoration: none; cursor: pointer;">${licitacao.total_contratos}</a>`
            : '0';
        
        return `
            <tr>
                <td style="vertical-align: middle;">${licitacao.codigo}</td>
                <td style="vertical-align: middle;" title="${licitacao.objeto}">${objetoTruncado}</td>
                <td style="vertical-align: middle;">${dataFormatada}</td>                
                <td class="text-center" style="vertical-align: middle;">${contratoLink}</td>
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

function resetForm() {
    document.getElementById('licitacaoForm').reset();
    document.getElementById('licitacaoId').value = '';
    document.getElementById('modalTitle').textContent = 'Nova Licitação';
    
    // Definir data atual
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('data').value = today;
}

function salvar() {
    const id = document.getElementById('licitacaoId').value;
    const codigo = document.getElementById('codigo').value.trim();
    const data = document.getElementById('data').value;
    const objeto = document.getElementById('objeto').value.trim();
    
    if (!codigo || !data || !objeto) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }
    
    const payload = { codigo, data, objeto };
    if (id) payload.id = id;
    
    const action = id ? 'update' : 'create';
    
    fetch(`../api/licitacao.php?action=${action}`, {
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
            bootstrap.Modal.getInstance(document.getElementById('modalLicitacao')).hide();
            loadData(currentPage);
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar licitação');
    });
}

function editar(id) {
    fetch(`../api/licitacao.php?action=get&id=${id}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const licitacao = data.data;
            document.getElementById('licitacaoId').value = licitacao.id_licitacao;
            document.getElementById('codigo').value = licitacao.codigo;
            document.getElementById('data').value = licitacao.data;
            document.getElementById('objeto').value = licitacao.objeto;
            document.getElementById('modalTitle').textContent = 'Editar Licitação';
            
            new bootstrap.Modal(document.getElementById('modalLicitacao')).show();
        } else {
            alert('Erro ao carregar licitação: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar licitação');
    });
}

function excluir(id) {
    if (!confirm('Tem certeza que deseja excluir esta licitação?')) {
        return;
    }
    
    fetch(`../api/licitacao.php?action=delete&id=${id}`, {
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
        alert('Erro ao excluir licitação');
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}
