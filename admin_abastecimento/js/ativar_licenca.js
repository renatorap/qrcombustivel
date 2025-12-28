// Ativação de Licença

let modalSucesso;

$(document).ready(function() {
    modalSucesso = new bootstrap.Modal(document.getElementById('modalSucesso'));
    
    // Verificar status da licença atual
    verificarStatusLicenca();
    
    // Auto-focus no campo de código
    $('#codigoLicenca').focus();
    
    // Formatar código enquanto digita (converter para maiúsculas, manter hífens)
    $('#codigoLicenca').on('input', function() {
        let valor = $(this).val().toUpperCase().replace(/[^A-Z0-9-]/g, '');
        $(this).val(valor);
    });
});

// Ativar licença
function ativarLicenca(event) {
    event.preventDefault();
    
    const codigoLicenca = $('#codigoLicenca').val().trim();
    
    if (!codigoLicenca) {
        alert('Digite o código da licença');
        return;
    }
    
    // Desabilitar botão durante processamento
    const btnSubmit = $('button[type="submit"]');
    const textoOriginal = btnSubmit.html();
    btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Ativando...');
    
    $.ajax({
        url: '../api/licenca.php',
        method: 'POST',
        data: {
            action: 'activate',
            codigo_licenca: codigoLicenca
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const mensagem = `
                    Cliente: ${response.cliente}<br>
                    Válida até: ${response.expiracao}
                `;
                $('#mensagemSucesso').html(mensagem);
                modalSucesso.show();
                
                // Limpar formulário
                $('#codigoLicenca').val('');
                
                // Atualizar status
                setTimeout(function() {
                    verificarStatusLicenca();
                }, 1000);
                
            } else if (response.warning) {
                alert('⚠️ ' + response.message);
            } else {
                alert('❌ ' + response.message);
            }
        },
        error: function() {
            alert('Erro ao ativar licença. Tente novamente.');
        },
        complete: function() {
            btnSubmit.prop('disabled', false).html(textoOriginal);
        }
    });
}

// Verificar status da licença atual
function verificarStatusLicenca() {
    $.ajax({
        url: '../api/licenca.php',
        method: 'GET',
        data: { action: 'check_status' },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.ativa) {
                $('#statusLicencaAtual').show();
                $('#statusAtual').html('<span class="badge bg-success">Ativa</span>');
                $('#validadeAtual').text(formatarData(response.expiracao));
                
                // Mostrar aviso se estiver próximo da expiração
                if (response.dias_restantes <= 7) {
                    const dias = Math.floor(response.dias_restantes);
                    let mensagem = '';
                    
                    if (dias === 0) {
                        mensagem = 'Sua licença <strong>expira hoje</strong>!';
                    } else if (dias === 1) {
                        mensagem = 'Sua licença expira <strong>amanhã</strong>!';
                    } else {
                        mensagem = `Sua licença expira em <strong>${dias} dias</strong>!`;
                    }
                    
                    $('#mensagemExpiracao').html(mensagem);
                    $('#avisoExpiracao').show();
                } else {
                    $('#avisoExpiracao').hide();
                }
            } else {
                $('#statusLicencaAtual').show();
                $('#statusAtual').html('<span class="badge bg-danger">Inativa</span>');
                $('#validadeAtual').text('-');
                $('#avisoExpiracao').hide();
            }
        }
    });
}

// Formatar data
function formatarData(data) {
    if (!data) return '-';
    const d = new Date(data + 'T00:00:00');
    return d.toLocaleDateString('pt-BR');
}

// Verificar status a cada 5 minutos
setInterval(verificarStatusLicenca, 300000);
