$(document).ready(function() {
    // Toggle password visibility
    $('#togglePassword').click(function() {
        const senhaInput = $('#senha');
        const tipo = senhaInput.attr('type') === 'password' ? 'text' : 'password';
        senhaInput.attr('type', tipo);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    // Helper: clear validation classes
    function clearValidation() {
        $('#login, #senha').removeClass('is-invalid');
    }

    // Clear invalid state when user types
    $('#login, #senha').on('input', function() {
        $(this).removeClass('is-invalid');
    });

    // Form submission
    $('#loginForm').submit(function(e) {
        e.preventDefault();

        clearValidation();

        const login = $('#login').val().trim();
        const senha = $('#senha').val().trim();        // Send credentials to server; server returns structured JSON with 'code' and optional 'field'
        $.ajax({
            url: 'api/login.php',
            method: 'POST',
            data: {
                login: login,
                senha: senha
            },
            dataType: 'json',
            success: function(data) {
                if (data && data.success) {
                    showAlert(data.message || 'Login realizado com sucesso! Redirecionando...', 'success');
                    setTimeout(() => {
                        window.location.href = 'pages/dashboard.php';
                    }, 1200);
                    return;
                }

                // Not successful: handle specific reasons if provided by server
                if (data && data.code) {
                    switch (data.code) {
                        case 'missing_fields':
                            if (data.field === 'login') {
                                $('#login').addClass('is-invalid').focus();
                            } else if (data.field === 'senha') {
                                $('#senha').addClass('is-invalid').focus();
                            }
                            showAlert(data.message || 'Campo obrigatório', 'danger');
                            break;
                        case 'user_not_found':
                            $('#login').addClass('is-invalid').focus();
                            showAlert(data.message || 'Usuário não encontrado', 'danger');
                            break;
                        case 'user_inactive':
                            $('#login').addClass('is-invalid').focus();
                            showAlert(data.message || 'Usuário inativo', 'warning');
                            break;
                        case 'wrong_password':
                            $('#senha').addClass('is-invalid').focus();
                            showAlert(data.message || 'Senha incorreta', 'danger');
                            break;
                        default:
                            showAlert(data.message || 'Erro ao autenticar', 'danger');
                    }
                } else {
                    showAlert((data && data.message) || 'Erro ao fazer login', 'danger');
                }
            },
            error: function(xhr, status, err) {
                // Try to parse JSON error response if any
                let msg = 'Erro na comunicação com o servidor';
                try {
                    const json = JSON.parse(xhr.responseText || '{}');
                    if (json && json.message) msg = json.message;
                } catch (e) {
                    // ignore parse errors
                }
                showAlert(msg, 'danger');
            }
        });
    });

    function showAlert(message, type) {
        const alertDiv = $('#alertMessage');

        // Clear previous timeout if exists
        const prevTimeout = alertDiv.data('timeout');
        if (prevTimeout) {
            clearTimeout(prevTimeout);
            alertDiv.removeData('timeout');
        }

        const icon = type === 'success' ? 'check-circle' : 'exclamation-circle';
        const markup = `
            <div class="alert alert-${type} alert-dismissible" role="alert">
                <i class="fas fa-${icon}" style="margin-right:8px;"></i>
                <span class="alert-text">${message}</span>
                <button type="button" class="btn-close" aria-label="Fechar"></button>
            </div>
        `;

        // Inject and animate
        alertDiv.stop(true, true).html(markup).slideDown(220);

        // Auto hide
        const t = setTimeout(() => {
            alertDiv.slideUp(220, function() { alertDiv.empty(); });
        }, 5000);
        alertDiv.data('timeout', t);
    }

    // Close button handler (delegated)
    $(document).on('click', '#alertMessage .btn-close', function() {
        const alertDiv = $('#alertMessage');
        const prevTimeout = alertDiv.data('timeout');
        if (prevTimeout) {
            clearTimeout(prevTimeout);
            alertDiv.removeData('timeout');
        }
        alertDiv.slideUp(180, function() { alertDiv.empty(); });
    });
});