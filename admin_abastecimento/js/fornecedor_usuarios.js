// Gerenciamento de Usuários Vinculados ao Fornecedor

let fornecedorIdAtual = null;

// Carrega usuários vinculados ao fornecedor
function carregarUsuariosVinculados(fornecedorId) {
    fornecedorIdAtual = fornecedorId;
    
    if (!fornecedorId) {
        $('#infoSalvarFornecedor').show();
        $('#usuariosContent').hide();
        return;
    }
    
    $('#infoSalvarFornecedor').hide();
    $('#usuariosContent').show();
    
    $.ajax({
        url: '../api/fornecedor_usuarios.php?fornecedor_id=' + fornecedorId,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderizarTabelaUsuarios(response.data);
            } else {
                showNotification('Erro ao carregar usuários: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro AJAX:', status, error);
            console.error('Response:', xhr.responseText);
            showNotification('Erro ao carregar usuários vinculados: ' + error, 'error');
        }
    });
}

// Renderiza a tabela de usuários vinculados
function renderizarTabelaUsuarios(usuarios) {
    const tbody = $('#usuariosVinculadosTable');
    tbody.empty();
    
    if (usuarios.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-info-circle"></i> Nenhum usuário vinculado
                </td>
            </tr>
        `);
        return;
    }
    
    usuarios.forEach(function(usuario) {
        const statusClass = usuario.ativo == 1 ? 'success' : 'danger';
        const statusText = usuario.ativo == 1 ? 'Ativo' : 'Inativo';
        const dataVinculo = new Date(usuario.data_vinculo).toLocaleDateString('pt-BR');
        const grupoNome = usuario.grupo_nome || 'N/A';
        
        tbody.append(`
            <tr>
                <td>${usuario.login}</td>
                <td>${usuario.nome}</td>
                <td>${usuario.email}</td>
                <td><span class="badge bg-info">${grupoNome}</span></td>
                <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                <td>${dataVinculo}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="toggleStatusUsuario(${usuario.id}, ${usuario.ativo})" title="Alterar Status">
                        <i class="fas fa-toggle-${usuario.ativo == 1 ? 'on' : 'off'}"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="removerVinculoUsuario(${usuario.id}, '${usuario.nome}')" title="Remover Vínculo">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

// Abre modal para adicionar usuário
function adicionarUsuario() {
    if (!fornecedorIdAtual) {
        showNotification('Salve o fornecedor primeiro', 'warning');
        return;
    }
    
    // Carrega usuários disponíveis dos grupos 4 e 10 (que não estão vinculados ao fornecedor)
    $.ajax({
        url: '../api/fornecedor_usuarios_disponiveis.php?excluir_fornecedor_id=' + fornecedorIdAtual,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#selectUsuario');
                select.empty();
                
                if (response.data.length === 0) {
                    select.append('<option value="">Nenhum usuário disponível (Grupos 4 e 10)</option>');
                } else {
                    select.append('<option value="">Selecione um usuário</option>');
                    response.data.forEach(function(usuario) {
                        const grupoInfo = usuario.grupo_nome ? ` - ${usuario.grupo_nome}` : '';
                        select.append(`<option value="${usuario.id}">${usuario.nome} (${usuario.login})${grupoInfo}</option>`);
                    });
                }
                
                $('#modalAdicionarUsuario').modal('show');
            } else {
                showNotification('Erro ao carregar usuários: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar usuários disponíveis:', xhr.responseText);
            showNotification('Erro ao carregar usuários disponíveis: ' + error, 'error');
        }
    });
}

// Salva vínculo de usuário
function salvarVinculoUsuario() {
    const usuarioId = $('#selectUsuario').val();
    const ativo = $('#usuarioAtivo').is(':checked') ? 1 : 0;
    
    if (!usuarioId) {
        showNotification('Selecione um usuário', 'warning');
        return;
    }
    
    const data = {
        fornecedor_id: fornecedorIdAtual,
        usuario_id: usuarioId,
        ativo: ativo
    };
    
    $.ajax({
        url: '../api/fornecedor_usuarios.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                showNotification(response.message, 'success');
                $('#modalAdicionarUsuario').modal('hide');
                carregarUsuariosVinculados(fornecedorIdAtual);
            } else {
                showNotification('Erro: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao vincular:', xhr.responseText);
            const response = xhr.responseJSON;
            showNotification('Erro ao vincular usuário: ' + (response?.message || error), 'error');
        }
    });
}

// Alterna status do vínculo (ativo/inativo)
function toggleStatusUsuario(vinculoId, statusAtual) {
    const novoStatus = statusAtual == 1 ? 0 : 1;
    
    const data = {
        id: vinculoId,
        ativo: novoStatus
    };
    
    $.ajax({
        url: '../api/fornecedor_usuarios.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                showNotification(response.message, 'success');
                carregarUsuariosVinculados(fornecedorIdAtual);
            } else {
                showNotification('Erro: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao atualizar status:', xhr.responseText);
            showNotification('Erro ao atualizar status: ' + error, 'error');
        }
    });
}

// Remove vínculo de usuário
function removerVinculoUsuario(vinculoId, nomeUsuario) {
    if (!confirm(`Deseja realmente remover o vínculo com o usuário "${nomeUsuario}"?`)) {
        return;
    }
    
    $.ajax({
        url: '../api/fornecedor_usuarios.php?id=' + vinculoId,
        method: 'DELETE',
        success: function(response) {
            if (response.success) {
                showNotification(response.message, 'success');
                carregarUsuariosVinculados(fornecedorIdAtual);
            } else {
                showNotification('Erro: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao remover vínculo:', xhr.responseText);
            showNotification('Erro ao remover vínculo: ' + error, 'error');
        }
    });
}

// Evento ao trocar de aba - carrega usuários quando abrir a aba
$(document).ready(function() {
    console.log('fornecedor_usuarios.js carregado');
    $('#usuarios-tab').on('shown.bs.tab', function() {
        const fornecedorId = $('#fornecedorId').val();
        console.log('Aba usuários aberta, fornecedorId:', fornecedorId);
        carregarUsuariosVinculados(fornecedorId);
    });
});
