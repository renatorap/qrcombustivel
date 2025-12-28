$(document).ready(function() {
    function showForgotAlert(message, type = 'info') {
        const div = $('#forgotAlert');
        const cls = type === 'success' ? 'alert-success' : (type === 'danger' ? 'alert-danger' : 'alert-info');
        const markup = `<div class="alert ${cls} alert-dismissible" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>`;
        div.html(markup).slideDown(180);
    }

    $('#forgotSubmit').click(function() {
        const val = $('#forgotInput').val().trim();
        $('#forgotAlert').hide().empty();
        if (!val) {
            showForgotAlert('Informe usuário ou e-mail', 'danger');
            $('#forgotInput').focus();
            return;
        }

        $(this).prop('disabled', true).text('Enviando...');

        $.ajax({
            url: '../api/password_reset_request.php',
            method: 'POST',
            data: { login: val },
            dataType: 'json',
            success: function(data) {
                if (data && data.success) {
                    showForgotAlert(data.message || 'Se o usuário/e-mail existir, você receberá instruções por e-mail.', 'success');
                    // Optionally auto-close modal after a short delay
                    setTimeout(function() {
                        const modalEl = document.getElementById('forgotPasswordModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        $('#forgotInput').val('');
                    }, 2500);
                } else {
                    showForgotAlert(data.message || 'Erro ao processar pedido', 'danger');
                }
            },
            error: function(xhr) {
                let msg = 'Erro na comunicação com o servidor';
                try { const json = JSON.parse(xhr.responseText || '{}'); if (json.message) msg = json.message; } catch(e) {}
                showForgotAlert(msg, 'danger');
            },
            complete: function() {
                $('#forgotSubmit').prop('disabled', false).text('Enviar');
            }
        });
    });

    // Clear alert when modal opened
    $('#forgotPasswordModal').on('shown.bs.modal', function() {
        $('#forgotAlert').hide().empty();
        $('#forgotInput').val('').focus();
    });
});
