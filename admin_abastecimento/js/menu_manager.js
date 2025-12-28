/**
 * Menu Manager - Gerenciamento do Menu Hierárquico
 */

let menuData = [];
let modal = null;
let currentEditType = '';
let currentEditId = null;
let currentParentId = null;

$(document).ready(function() {
    modal = new bootstrap.Modal(document.getElementById('menuItemModal'));
    loadMenuTree();
    loadAplicacoes();
    
    // Inicializar icon picker
    setTimeout(() => {
        makeIconInputClickable('itemIcone', 'iconPreview');
    }, 100);
    
    // Form submit
    $('#menuItemForm').on('submit', function(e) {
        e.preventDefault();
        saveMenuItem();
    });
});

/**
 * Carrega a árvore de menu
 */
function loadMenuTree() {
    const status = $('#filterStatus').val();
    
    $.ajax({
        url: '../api/menu_manager.php?action=list' + (status !== '' ? '&status=' + status : ''),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                menuData = response.data;
                renderMenuTree();
            } else {
                showError('Erro ao carregar menu: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro AJAX:', {xhr: xhr, status: status, error: error});
            
            let errorMsg = 'Erro ao carregar menu: ';
            
            if (xhr.status === 401) {
                errorMsg += 'Não autenticado. Redirecionando para login...';
                setTimeout(() => window.location.href = '../index.php', 2000);
            } else if (xhr.status === 403) {
                errorMsg += 'Sem permissão de acesso.';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg += xhr.responseJSON.message;
            } else if (xhr.responseText) {
                errorMsg += xhr.responseText;
            } else {
                errorMsg += error || 'Erro desconhecido';
            }
            
            showError(errorMsg);
        }
    });
}

/**
 * Renderiza a árvore de menu
 */
function renderMenuTree() {
    const container = $('#menuTreeContainer');
    
    if (menuData.length === 0) {
        container.html(`
            <div class="empty-state">
                <i class="fas fa-sitemap"></i>
                <p>Nenhum módulo encontrado</p>
                <button class="btn btn-primary" onclick="openAddModal('modulo')">
                    <i class="fas fa-plus"></i> Adicionar Módulo
                </button>
            </div>
        `);
        return;
    }
    
    let html = '<ul class="menu-tree" id="modulosList">';
    
    menuData.forEach(modulo => {
        html += renderModulo(modulo);
    });
    
    html += '</ul>';
    container.html(html);
    
    // Inicializar Sortable para módulos
    initSortable('modulosList', 'modulo');
}

/**
 * Renderiza um módulo
 */
function renderModulo(modulo) {
    const expandidoLabel = modulo.expandido == 1 ? 'Expansível' : 'Link Direto';
    const statusLabel = modulo.ativo == 1 ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>';
    
    let html = `
        <li class="menu-item" data-id="${modulo.id}" data-tipo="modulo">
            <div class="menu-item-header">
                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                <div class="menu-item-info">
                    <div class="menu-item-icon">
                        <i class="fas ${modulo.icone}"></i>
                    </div>
                    <div class="menu-item-details">
                        <div class="menu-item-name">
                            <i class="fas fa-layer-group"></i> ${modulo.nome}
                            <span class="badge badge-level bg-primary">Nível 1</span>
                        </div>
                        <div class="menu-item-meta">
                            Código: <code>${modulo.codigo}</code> | ${expandidoLabel} | Ordem: ${modulo.ordem} | ${statusLabel}
                        </div>
                    </div>
                </div>
                <div class="menu-item-actions">
                    ${modulo.expandido == 1 ? `
                    <button class="btn btn-sm btn-outline-success" onclick="openAddModal('submenu', ${modulo.id})" title="Adicionar Submenu">
                        <i class="fas fa-plus"></i>
                    </button>
                    ` : ''}
                    <button class="btn btn-sm btn-outline-primary" onclick="editItem('modulo', ${modulo.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem('modulo', ${modulo.id}, '${modulo.nome}')" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
    `;
    
    if (modulo.submenus && modulo.submenus.length > 0) {
        html += `<div class="submenu-container"><ul class="menu-tree" id="submenus-${modulo.id}">`;
        modulo.submenus.forEach(submenu => {
            html += renderSubmenu(submenu, modulo.id);
        });
        html += '</ul></div>';
    }
    
    html += '</li>';
    
    return html;
}

/**
 * Renderiza um submenu
 */
function renderSubmenu(submenu, moduloId) {
    const expandidoLabel = submenu.expandido == 1 ? 'Expansível' : 'Link Direto';
    const statusLabel = submenu.ativo == 1 ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>';
    
    let html = `
        <li class="menu-item" data-id="${submenu.id}" data-tipo="submenu">
            <div class="menu-item-header">
                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                <div class="menu-item-info">
                    <div class="menu-item-icon">
                        <i class="fas ${submenu.icone}"></i>
                    </div>
                    <div class="menu-item-details">
                        <div class="menu-item-name">
                            <i class="fas fa-folder"></i> ${submenu.nome}
                            <span class="badge badge-level bg-info">Nível 2</span>
                        </div>
                        <div class="menu-item-meta">
                            Código: <code>${submenu.codigo}</code> | ${expandidoLabel} | Ordem: ${submenu.ordem} | ${statusLabel}
                        </div>
                    </div>
                </div>
                <div class="menu-item-actions">
                    ${submenu.expandido == 1 ? `
                    <button class="btn btn-sm btn-outline-success" onclick="openAddModal('subsubmenu', ${submenu.id})" title="Adicionar Sub-submenu">
                        <i class="fas fa-plus"></i>
                    </button>
                    ` : ''}
                    <button class="btn btn-sm btn-outline-primary" onclick="editItem('submenu', ${submenu.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem('submenu', ${submenu.id}, '${submenu.nome}')" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
    `;
    
    if (submenu.subsubmenus && submenu.subsubmenus.length > 0) {
        html += `<div class="subsubmenu-container"><ul class="menu-tree" id="subsubmenus-${submenu.id}">`;
        submenu.subsubmenus.forEach(subsubmenu => {
            html += renderSubsubmenu(subsubmenu);
        });
        html += '</ul></div>';
    }
    
    html += '</li>';
    
    return html;
}

/**
 * Renderiza um sub-submenu
 */
function renderSubsubmenu(subsubmenu) {
    const statusLabel = subsubmenu.ativo == 1 ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>';
    
    return `
        <li class="menu-item" data-id="${subsubmenu.id}" data-tipo="subsubmenu">
            <div class="menu-item-header">
                <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
                <div class="menu-item-info">
                    <div class="menu-item-icon">
                        <i class="fas ${subsubmenu.icone}"></i>
                    </div>
                    <div class="menu-item-details">
                        <div class="menu-item-name">
                            <i class="fas fa-file"></i> ${subsubmenu.nome}
                            <span class="badge badge-level bg-warning">Nível 3</span>
                        </div>
                        <div class="menu-item-meta">
                            Código: <code>${subsubmenu.codigo}</code> | Ordem: ${subsubmenu.ordem} | ${statusLabel}
                        </div>
                    </div>
                </div>
                <div class="menu-item-actions">
                    <button class="btn btn-sm btn-outline-primary" onclick="editItem('subsubmenu', ${subsubmenu.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem('subsubmenu', ${subsubmenu.id}, '${subsubmenu.nome}')" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </li>
    `;
}

/**
 * Inicializa Sortable.js para drag & drop
 */
function initSortable(listId, tipo) {
    const el = document.getElementById(listId);
    if (!el) return;
    
    Sortable.create(el, {
        animation: 150,
        handle: '.drag-handle',
        onEnd: function(evt) {
            const items = [];
            el.querySelectorAll('.menu-item').forEach(item => {
                items.push(item.dataset.id);
            });
            
            reorderItems(tipo, items);
        }
    });
    
    // Inicializar sortable para submenus e sub-submenus
    if (tipo === 'modulo') {
        menuData.forEach(modulo => {
            const submenuList = document.getElementById(`submenus-${modulo.id}`);
            if (submenuList) {
                initSortable(`submenus-${modulo.id}`, 'submenu');
            }
        });
    }
    
    if (tipo === 'submenu') {
        menuData.forEach(modulo => {
            modulo.submenus.forEach(submenu => {
                const subsubmenuList = document.getElementById(`subsubmenus-${submenu.id}`);
                if (subsubmenuList) {
                    initSortable(`subsubmenus-${submenu.id}`, 'subsubmenu');
                }
            });
        });
    }
}

/**
 * Reordena itens
 */
function reorderItems(tipo, items) {
    $.ajax({
        url: '../api/menu_manager.php?action=reorder',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ tipo: tipo, items: items }),
        success: function(response) {
            if (response.success) {
                showSuccess('Ordem atualizada com sucesso');
                loadMenuTree();
            } else {
                showError('Erro ao reordenar: ' + response.message);
            }
        },
        error: function(xhr) {
            showError('Erro ao reordenar: ' + xhr.responseText);
        }
    });
}

/**
 * Abre modal para adicionar item
 */
function openAddModal(tipo, parentId = null) {
    currentEditType = tipo;
    currentEditId = null;
    currentParentId = parentId;
    
    $('#menuItemForm')[0].reset();
    $('#itemId').val('');
    $('#itemType').val(tipo);
    $('#parentId').val(parentId || '');
    
    let title = '';
    if (tipo === 'modulo') {
        title = 'Adicionar Módulo';
        $('#expandidoGroup').show();
    } else if (tipo === 'submenu') {
        title = 'Adicionar Submenu';
        $('#expandidoGroup').show();
    } else {
        title = 'Adicionar Sub-submenu';
        $('#expandidoGroup').hide();
        $('#expandidoNao').prop('checked', true);
    }
    
    $('#modalTitle').text(title);
    toggleAplicacaoField();
    modal.show();
}

/**
 * Edita um item
 */
function editItem(tipo, id) {
    currentEditType = tipo;
    currentEditId = id;
    
    // Buscar dados do item
    let item = null;
    if (tipo === 'modulo') {
        item = menuData.find(m => m.id == id);
    } else if (tipo === 'submenu') {
        menuData.forEach(modulo => {
            const found = modulo.submenus.find(s => s.id == id);
            if (found) item = found;
        });
    } else {
        menuData.forEach(modulo => {
            modulo.submenus.forEach(submenu => {
                const found = submenu.subsubmenus.find(ss => ss.id == id);
                if (found) item = found;
            });
        });
    }
    
    if (!item) {
        showError('Item não encontrado');
        return;
    }
    
    // Preencher form
    $('#itemId').val(item.id);
    $('#itemType').val(tipo);
    $('#itemCodigo').val(item.codigo);
    $('#itemNome').val(item.nome);
    $('#itemIcone').val(item.icone);
    $('#itemOrdem').val(item.ordem);
    $('#itemAtivo').prop('checked', item.ativo == 1);
    
    if (tipo !== 'subsubmenu') {
        if (item.expandido == 1) {
            $('#expandidoSim').prop('checked', true);
        } else {
            $('#expandidoNao').prop('checked', true);
        }
        $('#expandidoGroup').show();
    } else {
        $('#expandidoGroup').hide();
    }
    
    if (item.aplicacao_id) {
        $('#itemAplicacao').val(item.aplicacao_id);
    }
    
    let title = tipo === 'modulo' ? 'Editar Módulo' : (tipo === 'submenu' ? 'Editar Submenu' : 'Editar Sub-submenu');
    $('#modalTitle').text(title);
    
    updateIconPreview();
    toggleAplicacaoField();
    modal.show();
}

/**
 * Salva item
 */
function saveMenuItem() {
    const formData = {
        tipo: $('#itemType').val(),
        codigo: $('#itemCodigo').val(),
        nome: $('#itemNome').val(),
        icone: $('#itemIcone').val() || 'fa-folder',
        ordem: parseInt($('#itemOrdem').val()) || 0,
        ativo: $('#itemAtivo').is(':checked') ? 1 : 0
    };
    
    if (currentEditType !== 'subsubmenu') {
        formData.expandido = $('input[name="expandido"]:checked').val() || 0;
    }
    
    if (formData.expandido == 0 || currentEditType === 'subsubmenu') {
        formData.aplicacao_id = $('#itemAplicacao').val();
    }
    
    if (currentEditType === 'submenu') {
        formData.modulo_id = currentParentId || $('#parentId').val();
    } else if (currentEditType === 'subsubmenu') {
        formData.submenu_id = currentParentId || $('#parentId').val();
    }
    
    const isEdit = currentEditId !== null;
    if (isEdit) {
        formData.id = currentEditId;
    }
    
    const action = isEdit ? 'update' : 'create';
    
    $.ajax({
        url: '../api/menu_manager.php?action=' + action,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                modal.hide();
                loadMenuTree();
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            showError('Erro ao salvar: ' + xhr.responseText);
        }
    });
}

/**
 * Exclui um item
 */
function deleteItem(tipo, id, nome) {
    if (!confirm(`Tem certeza que deseja excluir "${nome}"?\n\nAtenção: Itens filhos também serão excluídos!`)) {
        return;
    }
    
    $.ajax({
        url: '../api/menu_manager.php?action=delete',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ tipo: tipo, id: id }),
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                loadMenuTree();
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            showError('Erro ao excluir: ' + xhr.responseText);
        }
    });
}

/**
 * Carrega lista de aplicações
 */
function loadAplicacoes() {
    $.ajax({
        url: '../api/menu_manager.php?action=aplicacoes',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#itemAplicacao');
                select.empty();
                select.append('<option value="">Selecione...</option>');
                response.data.forEach(app => {
                    select.append(`<option value="${app.id}">${app.nome}</option>`);
                });
            }
        }
    });
}

/**
 * Atualiza preview do ícone
 */
function updateIconPreview() {
    const iconClass = $('#itemIcone').val() || 'fa-folder';
    $('#iconPreview').attr('class', 'fas ' + iconClass + ' icon-preview');
}

/**
 * Toggle campo aplicação baseado no tipo
 */
function toggleAplicacaoField() {
    const expandido = $('input[name="expandido"]:checked').val();
    const tipo = $('#itemType').val();
    
    if (tipo === 'subsubmenu' || expandido == 0) {
        $('#aplicacaoGroup').show();
        $('#itemAplicacao').prop('required', true);
    } else {
        $('#aplicacaoGroup').hide();
        $('#itemAplicacao').prop('required', false);
    }
}

/**
 * Exibe mensagem de sucesso
 */
function showSuccess(message) {
    console.log('SUCCESS:', message);
    
    // Criar alert Bootstrap
    const alertHtml = `
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-check-circle"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        $('.alert-success').fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);
}

/**
 * Exibe mensagem de erro
 */
function showError(message) {
    console.error('ERROR:', message);
    
    // Criar alert Bootstrap
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-exclamation-circle"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto-remover após 8 segundos
    setTimeout(() => {
        $('.alert-danger').fadeOut(300, function() {
            $(this).remove();
        });
    }, 8000);
}
