$(document).ready(function() {
    function showAlert(message, type = 'danger') {
        const div = $('#resetAlert');
        const cls = type === 'success' ? 'alert-success' : (type === 'info' ? 'alert-info' : 'alert-danger');
        const markup = `<div class="alert ${cls} alert-dismissible" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>`;
        div.html(markup).slideDown(180);
    }

    const token = $('#tokenInput').val().trim();
    if (!token) {
        showAlert('Token inválido ou ausente. Verifique o link enviado por e-mail.', 'danger');
    }

    $('#resetForm').submit(function(e) {
        e.preventDefault();
        $('#resetAlert').hide().empty();

        const pwd = $('#password').val();
        const conf = $('#confirmPassword').val();

        // basic client validation
        if (!pwd || pwd.length < 6) {
            $('#password').addClass('is-invalid').focus();
            showAlert('A senha deve ter pelo menos 6 caracteres.', 'danger');
            return;
        }
        if (pwd !== conf) {
            $('#confirmPassword').addClass('is-invalid').focus();
            showAlert('As senhas não coincidem.', 'danger');
            return;
        }

        $('#resetBtn').prop('disabled', true).text('Enviando...');

        $.ajax({
            url: 'api/password_reset_confirm.php',
            method: 'POST',
            data: { token: token, password: pwd },
            dataType: 'json',
            success: function(data) {
                if (data && data.success) {
                    showAlert(data.message || 'Senha redefinida com sucesso.', 'success');
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 1600);
                } else {
                    const msg = (data && data.message) ? data.message : 'Erro ao redefinir a senha.';
                    showAlert(msg, 'danger');
                }
            },
            error: function(xhr) {
                let msg = 'Erro na comunicação com o servidor.';
                try { const j = JSON.parse(xhr.responseText || '{}'); if (j.message) msg = j.message; } catch(e) {}
                showAlert(msg, 'danger');
            },
            complete: function() {
                $('#resetBtn').prop('disabled', false).text('Redefinir senha');
            }
        });
    });

    // clear invalid on input
    $('#password, #confirmPassword').on('input', function() { $(this).removeClass('is-invalid'); $('#resetAlert').hide().empty(); });
});
