let grupoAtual = null;
let aplicacoes = [];
let modulos = new Set();

// Carregar grupos ao iniciar
$(document).ready(function() {
    carregarGrupos();
});

function carregarGrupos() {
    $.ajax({
        url: '../api/grupos.php',
        method: 'GET',
        data: {
            action: 'list',
            page: 1
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const select = $('#grupoSelect');
                const selectCopia = $('#grupoOrigemCopia');
                
                select.empty().append('<option value="">-- Selecione um grupo --</option>');
                selectCopia.empty().append('<option value="">-- Selecione o grupo de origem --</option>');
                
                if (data.grupos && data.grupos.length > 0) {
                    data.grupos.forEach(grupo => {
                        if (grupo.ativo == 1) {
                            select.append(`<option value="${grupo.id}">${grupo.nome}</option>`);
                            selectCopia.append(`<option value="${grupo.id}">${grupo.nome}</option>`);
                        }
                    });
                } else {
                    select.append('<option value="" disabled>Nenhum grupo disponível</option>');
                }
            } else {
                console.error('Erro ao carregar grupos:', data.message);
                alert('Erro ao carregar grupos: ' + (data.message || 'Erro desconhecido'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro AJAX ao carregar grupos:', {xhr, status, error});
            alert('Erro na comunicação com o servidor ao carregar grupos');
        }
    });
}

function carregarPermissoes() {
    const grupoId = $('#grupoSelect').val();
    
    if (!grupoId) {
        $('#permissoesContainer').hide();
        $('#filtrosContainer').hide();
        $('#placeholderContainer').show();
        $('#btnSalvar').prop('disabled', true);
        $('#btnCopiar').prop('disabled', true);
        grupoAtual = null;
        return;
    }
    
    grupoAtual = grupoId;
    $('#placeholderContainer').hide();
    $('#permissoesContainer').show();
    $('#filtrosContainer').show();
    $('#btnSalvar').prop('disabled', false);
    $('#btnCopiar').prop('disabled', false);
    
    // Mostrar loading
    $('#permissoesTableBody').html(`
        <tr>
            <td colspan="8" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </td>
        </tr>
    `);
    
    $.ajax({
        url: '../api/permissoes.php',
        method: 'GET',
        data: {
            action: 'get_grupo',
            grupo_id: grupoId
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                aplicacoes = data.aplicacoes;
                modulos.clear();
                
                // Extrair módulos únicos
                aplicacoes.forEach(app => {
                    if (app.modulo) {
                        modulos.add(app.modulo);
                    }
                });
                
                // Preencher filtro de módulos
                const filtroModulo = $('#filtroModulo');
                filtroModulo.empty().append('<option value="">Todos os módulos</option>');
                Array.from(modulos).sort().forEach(modulo => {
                    filtroModulo.append(`<option value="${modulo}">${modulo}</option>`);
                });
                
                renderizarTabela();
            } else {
                alert('Erro ao carregar permissões: ' + (data.message || 'Erro desconhecido'));
            }
        },
        error: function() {
            alert('Erro na comunicação com o servidor');
        }
    });
}

function renderizarTabela() {
    const tbody = $('#permissoesTableBody');
    tbody.empty();
    
    const search = $('#searchAplicacao').val().toLowerCase();
    const filtroMod = $('#filtroModulo').val();
    
    let moduloAtual = null;
    let count = 0;
    
    aplicacoes.forEach(app => {
        // Filtrar por busca
        if (search && !app.nome.toLowerCase().includes(search) && !app.codigo.toLowerCase().includes(search)) {
            return;
        }
        
        // Filtrar por módulo
        if (filtroMod && app.modulo !== filtroMod) {
            return;
        }
        
        // Adicionar cabeçalho de módulo
        if (app.modulo !== moduloAtual) {
            moduloAtual = app.modulo;
            tbody.append(`
                <tr class="modulo-header">
                    <td colspan="8">
                        <i class="fas fa-folder me-2"></i>
                        <strong>${app.modulo || 'Sem módulo'}</strong>
                    </td>
                </tr>
            `);
        }
        
        const disabled = podeEditar ? '' : 'disabled';
        
        tbody.append(`
            <tr data-app-id="${app.id}">
                <td>
                    <strong>${app.nome}</strong>
                    <br>
                    <small class="text-muted">${app.descricao || app.codigo}</small>
                </td>
                <td class="text-center">
                    <input class="form-check-input perm-check" type="checkbox" 
                           data-permissao="acessar" 
                           ${app.pode_acessar == 1 ? 'checked' : ''} ${disabled}>
                </td>
                <td class="text-center">
                    <input class="form-check-input perm-check" type="checkbox" 
                           data-permissao="criar" 
                           ${app.pode_criar == 1 ? 'checked' : ''} ${disabled}>
                </td>
                <td class="text-center">
                    <input class="form-check-input perm-check" type="checkbox" 
                           data-permissao="visualizar" 
                           ${app.pode_visualizar == 1 ? 'checked' : ''} ${disabled}>
                </td>
                <td class="text-center">
                    <input class="form-check-input perm-check" type="checkbox" 
                           data-permissao="editar" 
                           ${app.pode_editar == 1 ? 'checked' : ''} ${disabled}>
                </td>
                <td class="text-center">
                    <input class="form-check-input perm-check" type="checkbox" 
                           data-permissao="excluir" 
                           ${app.pode_excluir == 1 ? 'checked' : ''} ${disabled}>
                </td>
                <td class="text-center">
                    <input class="form-check-input perm-check" type="checkbox" 
                           data-permissao="exportar" 
                           ${app.pode_exportar == 1 ? 'checked' : ''} ${disabled}>
                </td>
                <td class="text-center">
                    <input class="form-check-input perm-check" type="checkbox" 
                           data-permissao="importar" 
                           ${app.pode_importar == 1 ? 'checked' : ''} ${disabled}>
                </td>
            </tr>
        `);
        
        count++;
    });
    
    if (count === 0) {
        tbody.html(`
            <tr>
                <td colspan="8" class="text-center text-muted">
                    <i class="fas fa-search"></i> Nenhuma aplicação encontrada
                </td>
            </tr>
        `);
    }
}

function marcarTodos(permissao, checked) {
    if (!podeEditar) return;
    
    $(`.perm-check[data-permissao="${permissao}"]`).prop('checked', checked);
}

function salvarTodasPermissoes() {
    if (!grupoAtual || !podeEditar) return;
    
    if (!confirm('Deseja salvar todas as alterações de permissões?')) {
        return;
    }
    
    const permissoes = [];
    
    $('#permissoesTableBody tr[data-app-id]').each(function() {
        const appId = $(this).data('app-id');
        const perm = {
            aplicacao_id: appId,
            pode_acessar: $(this).find('[data-permissao="acessar"]').is(':checked'),
            pode_criar: $(this).find('[data-permissao="criar"]').is(':checked'),
            pode_visualizar: $(this).find('[data-permissao="visualizar"]').is(':checked'),
            pode_editar: $(this).find('[data-permissao="editar"]').is(':checked'),
            pode_excluir: $(this).find('[data-permissao="excluir"]').is(':checked'),
            pode_exportar: $(this).find('[data-permissao="exportar"]').is(':checked'),
            pode_importar: $(this).find('[data-permissao="importar"]').is(':checked')
        };
        permissoes.push(perm);
    });
    
    $.ajax({
        url: '../api/permissoes.php',
        method: 'POST',
        data: {
            action: 'update_grupo_bulk',
            grupo_id: grupoAtual,
            permissoes: JSON.stringify(permissoes)
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                alert(data.message);
                carregarPermissoes();
            } else {
                alert('Erro ao salvar: ' + (data.message || 'Erro desconhecido'));
            }
        },
        error: function() {
            alert('Erro na comunicação com o servidor');
        }
    });
}

function copiarPermissoes() {
    if (!grupoAtual) return;
    
    // Atualizar select de cópia removendo o grupo atual
    const selectCopia = $('#grupoOrigemCopia');
    selectCopia.find('option').each(function() {
        if ($(this).val() == grupoAtual) {
            $(this).prop('disabled', true);
        } else {
            $(this).prop('disabled', false);
        }
    });
    
    $('#modalCopiar').modal('show');
}

function confirmarCopia() {
    const grupoOrigem = $('#grupoOrigemCopia').val();
    
    if (!grupoOrigem) {
        alert('Selecione o grupo de origem');
        return;
    }
    
    if (!confirm('Tem certeza que deseja copiar as permissões? Todas as permissões atuais serão substituídas.')) {
        return;
    }
    
    $.ajax({
        url: '../api/permissoes.php',
        method: 'POST',
        data: {
            action: 'copy_permissions',
            grupo_origem_id: grupoOrigem,
            grupo_destino_id: grupoAtual
        },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                alert(data.message);
                $('#modalCopiar').modal('hide');
                carregarPermissoes();
            } else {
                alert('Erro ao copiar: ' + (data.message || 'Erro desconhecido'));
            }
        },
        error: function() {
            alert('Erro na comunicação com o servidor');
        }
    });
}

// Filtros e pesquisa
$('#searchAplicacao').on('keyup', function() {
    renderizarTabela();
});

$('#filtroModulo').on('change', function() {
    renderizarTabela();
});
