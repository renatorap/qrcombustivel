// Variáveis globais
let currentPage = 1;
let searchTimeout = null;

// Carregar grupos ao iniciar
document.addEventListener('DOMContentLoaded', function() {
    carregarGrupos();
    
    // Event listeners
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            carregarGrupos();
        }, 500);
    });
    
    document.getElementById('filtroAtivo').addEventListener('change', function() {
        currentPage = 1;
        carregarGrupos();
    });
});

/**
 * Carregar lista de grupos
 */
function carregarGrupos() {
    const search = document.getElementById('searchInput').value;
    const ativo = document.getElementById('filtroAtivo').value;
    
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage
    });
    
    if (search) params.append('search', search);
    if (ativo !== '') params.append('ativo', ativo);
    
    fetch(`../api/grupos.php?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarGrupos(data.grupos);
                renderizarPaginacao(data.currentPage, data.totalPages);
            } else {
                mostrarErro(data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao carregar grupos');
        });
}

/**
 * Renderizar tabela de grupos
 */
function renderizarGrupos(grupos) {
    const tbody = document.getElementById('gruposTableBody');
    
    if (grupos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum grupo encontrado</td></tr>';
        return;
    }
    
    tbody.innerHTML = grupos.map(grupo => `
        <tr>
            <td>
                <strong>${escapeHtml(grupo.nome)}</strong>
            </td>
            <td>${escapeHtml(grupo.descricao || '-')}</td>
            <td class="text-center">
                <i class="fas fa-users"></i>&nbsp;&nbsp;&nbsp;${grupo.total_usuarios || 0}
            </td>
            <td class="text-center">
                ${grupo.ativo == 1 
                    ? '<span class="badge bg-success badge-status">Ativo</span>' 
                    : '<span class="badge bg-secondary badge-status">Inativo</span>'}
            </td>
            <td class="text-center">
                <div class="btn-group" role="group">
                    ${podeCriar ? `
                        <button class="btn btn-sm btn-outline-info" 
                                onclick="editarGrupo(${grupo.id})" 
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                    ` : ''}
                    ${podeExcluir ? `
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="excluirGrupo(${grupo.id}, '${escapeHtml(grupo.nome)}')" 
                                title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

/**
 * Renderizar paginação
 */
function renderizarPaginacao(current, total) {
    const pagination = document.getElementById('pagination');
    
    if (total <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Botão Primeira Página
    html += `
        <li class="page-item ${current === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="mudarPagina(1); return false;" title="Primeira">
                <i class="fas fa-angle-double-left"></i>
            </a>
        </li>
    `;
    
    // Botão Anterior
    html += `
        <li class="page-item ${current === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="mudarPagina(${current - 1}); return false;" title="Anterior">
                <i class="fas fa-angle-left"></i>
            </a>
        </li>
    `;
    
    // Páginas (máximo 5 visíveis)
    let startPage = Math.max(1, current - 2);
    let endPage = Math.min(total, current + 2);

    // Ajustar para sempre mostrar 5 páginas quando possível
    if (endPage - startPage < 4) {
        if (startPage === 1) {
            endPage = Math.min(total, startPage + 4);
        } else if (endPage === total) {
            startPage = Math.max(1, endPage - 4);
        }
    }

    // Mostrar primeira página se não estiver no range
    if (startPage > 1) {
        html += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="mudarPagina(1); return false;">1</a>
            </li>
        `;
        if (startPage > 2) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // Páginas do range
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <li class="page-item ${i === current ? 'active' : ''}">
                <a class="page-link" href="#" onclick="mudarPagina(${i}); return false;">${i}</a>
            </li>
        `;
    }

    // Mostrar última página se não estiver no range
    if (endPage < total) {
        if (endPage < total - 1) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        html += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="mudarPagina(${total}); return false;">${total}</a>
            </li>
        `;
    }
    
    // Botão Próxima
    html += `
        <li class="page-item ${current === total ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="mudarPagina(${current + 1}); return false;" title="Próxima">
                <i class="fas fa-angle-right"></i>
            </a>
        </li>
    `;
    
    // Botão Última Página
    html += `
        <li class="page-item ${current === total ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="mudarPagina(${total}); return false;" title="Última">
                <i class="fas fa-angle-double-right"></i>
            </a>
        </li>
    `;
    
    pagination.innerHTML = html;
}

/**
 * Mudar página
 */
function mudarPagina(page) {
    currentPage = page;
    carregarGrupos();
}

/**
 * Abrir modal para novo grupo
 */
document.querySelector('[data-bs-target="#modalGrupo"]')?.addEventListener('click', function() {
    document.getElementById('formGrupo').reset();
    document.getElementById('grupoId').value = '';
    document.getElementById('modalGrupoLabel').textContent = 'Novo Grupo';
});

/**
 * Editar grupo
 */
function editarGrupo(id) {
    fetch(`../api/grupos.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const grupo = data.grupo;
                document.getElementById('grupoId').value = grupo.id;
                document.getElementById('grupoNome').value = grupo.nome;
                document.getElementById('grupoDescricao').value = grupo.descricao || '';
                document.getElementById('grupoAtivo').checked = grupo.ativo == 1;
                document.getElementById('modalGrupoLabel').textContent = 'Editar Grupo';
                
                new bootstrap.Modal(document.getElementById('modalGrupo')).show();
            } else {
                mostrarErro(data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao carregar dados do grupo');
        });
}

/**
 * Salvar grupo (criar ou atualizar)
 */
function salvarGrupo() {
    const form = document.getElementById('formGrupo');
    const formData = new FormData(form);
    
    const id = document.getElementById('grupoId').value;
    formData.append('action', id ? 'update' : 'create');
    formData.append('ativo', document.getElementById('grupoAtivo').checked ? 1 : 0);
    
    fetch('../api/grupos.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarSucesso(data.message);
                bootstrap.Modal.getInstance(document.getElementById('modalGrupo')).hide();
                carregarGrupos();
            } else {
                mostrarErro(data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao salvar grupo');
        });
}

/**
 * Excluir grupo
 */
function excluirGrupo(id, nome) {
    if (!confirm(`Tem certeza que deseja excluir o grupo "${nome}"?\n\nTodos os usuários vinculados a este grupo perderão suas permissões.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    fetch('../api/grupos.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarSucesso(data.message);
                carregarGrupos();
            } else {
                mostrarErro(data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarErro('Erro ao excluir grupo');
        });
}

/**
 * Ver usuários do grupo
 */
function verUsuarios(id, nomeGrupo) {
    document.getElementById('modalUsuariosLabel').textContent = `Usuários do Grupo: ${nomeGrupo}`;
    document.getElementById('usuariosLista').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    `;
    
    new bootstrap.Modal(document.getElementById('modalUsuarios')).show();
    
    fetch(`../api/grupos.php?action=usuarios&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const usuarios = data.usuarios;
                
                if (usuarios.length === 0) {
                    document.getElementById('usuariosLista').innerHTML = '<p class="text-muted text-center">Nenhum usuário vinculado a este grupo</p>';
                    return;
                }
                
                const html = `
                    <div class="list-group">
                        ${usuarios.map(usuario => `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">${escapeHtml(usuario.nome)}</h6>
                                        <small class="text-muted">${escapeHtml(usuario.email)}</small>
                                    </div>
                                    <span class="badge ${usuario.ativo == 1 ? 'bg-success' : 'bg-secondary'}">
                                        ${usuario.ativo == 1 ? 'Ativo' : 'Inativo'}
                                    </span>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
                
                document.getElementById('usuariosLista').innerHTML = html;
            } else {
                document.getElementById('usuariosLista').innerHTML = `<p class="text-danger text-center">${data.message}</p>`;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            document.getElementById('usuariosLista').innerHTML = '<p class="text-danger text-center">Erro ao carregar usuários</p>';
        });
}

/**
 * Limpar filtros
 */
function limparFiltros() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filtroAtivo').value = '1';
    currentPage = 1;
    carregarGrupos();
}

/**
 * Funções auxiliares
 */
function mostrarSucesso(mensagem) {
    // Implementar toast ou alert de sucesso
    alert(mensagem);
}

function mostrarErro(mensagem) {
    // Implementar toast ou alert de erro
    alert('Erro: ' + mensagem);
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

// Variáveis de permissão (definidas no PHP inline)
let podeCriar = false;
let podeExcluir = false;

// Tentar obter do contexto PHP se disponível
if (typeof window.podeCriar !== 'undefined') podeCriar = window.podeCriar;
if (typeof window.podeExcluir !== 'undefined') podeExcluir = window.podeExcluir;
