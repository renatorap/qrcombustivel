// Gerenciamento de Licenças (Administradores)

let modalGerarLicenca, modalAdiarExpiracao, modalDetalhesLicenca;

$(document).ready(function() {
    // Inicializar modais
    modalGerarLicenca = new bootstrap.Modal(document.getElementById('modalGerarLicenca'));
    modalAdiarExpiracao = new bootstrap.Modal(document.getElementById('modalAdiarExpiracao'));
    modalDetalhesLicenca = new bootstrap.Modal(document.getElementById('modalDetalhesLicenca'));
    
    // Carregar clientes para o select
    carregarClientes();
    
    // Carregar licenças
    carregarLicencas();
    
    // Definir data mínima como hoje
    const hoje = new Date().toISOString().split('T')[0];
    $('#dataExpiracao').attr('min', hoje);
    $('#novaDataExpiracao').attr('min', hoje);
    
    // Filtro de status
    $('#filtroStatus').on('change', function() {
        carregarLicencas();
    });
    
    // Filtro de cliente
    $('#filtroCliente').on('change', function() {
        carregarLicencas();
    });
    
    // Carregar clientes para o filtro
    carregarClientesFiltro();
    
    // Evento de limpar formulário ao fechar modal
    $('#modalGerarLicenca').on('hidden.bs.modal', function() {
        $('#formGerarLicenca')[0].reset();
    });
});

// Carregar lista de clientes
function carregarClientes() {
    $.ajax({
        url: '../api/licenca.php',
        method: 'GET',
        data: { action: 'get_clientes' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#clienteSelect');
                select.empty().append('<option value="">Selecione o cliente...</option>');
                
                response.clientes.forEach(function(cliente) {
                    const email = cliente.email ? ` (${cliente.email})` : ' (sem e-mail)';
                    select.append(`<option value="${cliente.id_cliente}">${cliente.nome_fantasia}${email}</option>`);
                });
            }
        }
    });
}

// Carregar clientes para o filtro
function carregarClientesFiltro() {
    const filtro = $('#filtroCliente');
    if (filtro.length === 0) return; // Só carrega se o filtro existir (admin)
    
    $.ajax({
        url: '../api/licenca.php',
        method: 'GET',
        data: { action: 'get_clientes' },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.clientes) {
                filtro.empty().append('<option value="">Todos os Clientes</option>');
                
                response.clientes.forEach(function(cliente) {
                    filtro.append(`<option value="${cliente.id_cliente}">${cliente.nome_fantasia}</option>`);
                });
            } else {
                console.error('Erro ao carregar clientes:', response.message || 'Resposta inválida');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição de clientes:', error);
            console.log('Response:', xhr.responseText);
        }
    });
}

// Carregar licenças
function carregarLicencas() {
    const status = $('#filtroStatus').val();
    const cliente = $('#filtroCliente').val();
    
    $.ajax({
        url: '../api/licenca.php',
        method: 'GET',
        data: { 
            action: 'list',
            status: status,
            cliente_id: cliente
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderLicencas(response.licencas);
            } else {
                $('#licencasTable').html(`<tr><td colspan="8" class="text-center text-danger">${response.message}</td></tr>`);
            }
        },
        error: function() {
            $('#licencasTable').html('<tr><td colspan="8" class="text-center text-danger">Erro ao carregar licenças</td></tr>');
        }
    });
}

// Renderizar tabela de licenças
function renderLicencas(licencas) {
    const tbody = $('#licencasTable');
    tbody.empty();
    
    if (licencas.length === 0) {
        tbody.append('<tr><td colspan="8" class="text-center text-muted">Nenhuma licença encontrada</td></tr>');
        return;
    }
    
    licencas.forEach(function(lic) {
        const statusBadge = getStatusBadge(lic.status);
        const dataAtivacao = lic.data_ativacao ? formatarDataHora(lic.data_ativacao) : '-';
        const acoes = getAcoesButtons(lic);
        
        const row = `
            <tr>
                <td>${lic.cliente_nome || 'N/A'}</td>
                <td><code>${lic.codigo_licenca}</code></td>
                <td>${formatarDataHora(lic.data_geracao)}</td>
                <td>${dataAtivacao}</td>
                <td>${formatarData(lic.data_expiracao)}</td>
                <td>${statusBadge}</td>
                <td><small>${lic.gerado_por_nome || 'N/A'}</small></td>
                <td>${acoes}</td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Gerar badge de status
function getStatusBadge(status) {
    const badges = {
        'pendente': '<span class="badge bg-warning">Pendente</span>',
        'ativa': '<span class="badge bg-success">Ativa</span>',
        'expirada': '<span class="badge bg-danger">Expirada</span>',
        'cancelada': '<span class="badge bg-secondary">Cancelada</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">?</span>';
}

// Gerar botões de ação
function getAcoesButtons(lic) {
    let buttons = `
        <button class="btn btn-sm btn-info" onclick='verDetalhes(${JSON.stringify(lic)})' title="Ver Detalhes">
            <i class="fas fa-eye"></i>
        </button>
    `;
    
    if (lic.status === 'pendente' || lic.status === 'ativa') {
        buttons += `
            <button class="btn btn-sm btn-primary" onclick="enviarEmail(${lic.id_licenca})" title="Enviar por E-mail">
                <i class="fas fa-envelope"></i>
            </button>
        `;
        
        buttons += `
            <button class="btn btn-sm btn-warning" onclick='abrirModalAdiar(${JSON.stringify(lic)})' title="Adiar Expiração">
                <i class="fas fa-calendar-plus"></i>
            </button>
        `;
    }
    
    if (lic.status === 'pendente' || lic.status === 'ativa') {
        buttons += `
            <button class="btn btn-sm btn-danger" onclick="cancelarLicenca(${lic.id_licenca})" title="Cancelar Licença">
                <i class="fas fa-times"></i>
            </button>
        `;
    }
    
    return buttons;
}

// Gerar nova licença
function gerarLicenca() {
    const idCliente = $('#clienteSelect').val();
    const dataExpiracao = $('#dataExpiracao').val();
    const observacao = $('#observacao').val();
    const enviarEmail = $('#enviarEmail').is(':checked');
    
    if (!idCliente || !dataExpiracao) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }
    
    $.ajax({
        url: '../api/licenca.php',
        method: 'POST',
        data: {
            action: 'generate',
            id_cliente: idCliente,
            data_expiracao: dataExpiracao,
            observacao: observacao
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(`Licença gerada com sucesso!\n\nCódigo: ${response.codigo}`);
                modalGerarLicenca.hide();
                carregarLicencas();
                
                // Enviar e-mail se marcado
                if (enviarEmail) {
                    enviarEmail(response.id_licenca);
                }
            } else {
                alert('Erro: ' + response.message);
            }
        },
        error: function() {
            alert('Erro ao gerar licença');
        }
    });
}

// Enviar licença por e-mail
function enviarEmail(idLicenca) {
    if (!confirm('Deseja enviar a licença por e-mail para o cliente?')) {
        return;
    }
    
    $.ajax({
        url: '../api/licenca.php',
        method: 'POST',
        data: {
            action: 'send_email',
            id_licenca: idLicenca
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('E-mail enviado com sucesso!');
            } else {
                alert('Erro ao enviar e-mail: ' + response.message);
            }
        },
        error: function() {
            alert('Erro ao enviar e-mail');
        }
    });
}

// Abrir modal para adiar expiração
function abrirModalAdiar(licenca) {
    $('#idLicencaAdiar').val(licenca.id_licenca);
    $('#clienteAdiar').text(licenca.cliente_nome);
    $('#dataAtualAdiar').text(formatarData(licenca.data_expiracao));
    $('#novaDataExpiracao').val('');
    modalAdiarExpiracao.show();
}

// Adiar data de expiração
function adiarExpiracao() {
    const idLicenca = $('#idLicencaAdiar').val();
    const novaData = $('#novaDataExpiracao').val();
    
    if (!novaData) {
        alert('Selecione a nova data de expiração');
        return;
    }
    
    $.ajax({
        url: '../api/licenca.php',
        method: 'POST',
        data: {
            action: 'extend',
            id_licenca: idLicenca,
            nova_data_expiracao: novaData
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Data de expiração atualizada com sucesso!');
                modalAdiarExpiracao.hide();
                carregarLicencas();
            } else {
                alert('Erro: ' + response.message);
            }
        },
        error: function() {
            alert('Erro ao atualizar data');
        }
    });
}

// Cancelar licença
function cancelarLicenca(idLicenca) {
    if (!confirm('Tem certeza que deseja cancelar esta licença?\n\nEsta ação não pode ser desfeita.')) {
        return;
    }
    
    $.ajax({
        url: '../api/licenca.php',
        method: 'POST',
        data: {
            action: 'cancel',
            id_licenca: idLicenca
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Licença cancelada com sucesso');
                carregarLicencas();
            } else {
                alert('Erro: ' + response.message);
            }
        },
        error: function() {
            alert('Erro ao cancelar licença');
        }
    });
}

// Ver detalhes da licença
function verDetalhes(licenca) {
    $('#codigoLicencaDetalhe').text(licenca.codigo_licenca);
    $('#clienteDetalhe').text(licenca.cliente_nome || 'N/A');
    $('#statusDetalhe').html(getStatusBadge(licenca.status));
    $('#dataGeracaoDetalhe').text(formatarDataHora(licenca.data_geracao));
    $('#dataAtivacaoDetalhe').text(licenca.data_ativacao ? formatarDataHora(licenca.data_ativacao) : 'Não ativada');
    $('#dataExpiracaoDetalhe').text(formatarData(licenca.data_expiracao));
    $('#geradoPorDetalhe').text(licenca.gerado_por_nome || 'N/A');
    $('#observacaoDetalhe').text(licenca.observacao || 'Sem observações');
    
    modalDetalhesLicenca.show();
}

// Funções auxiliares
function formatarData(data) {
    if (!data) return '-';
    const d = new Date(data + 'T00:00:00');
    return d.toLocaleDateString('pt-BR');
}

function formatarDataHora(dataHora) {
    if (!dataHora) return '-';
    const d = new Date(dataHora);
    return d.toLocaleString('pt-BR');
}
