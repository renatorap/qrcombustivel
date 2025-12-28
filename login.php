<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_abastecimento/css/login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-logo">
            <img src="admin_abastecimento/assets/QR_Combustivel.png" alt="QR Combustível" onerror="this.style.display='none'">
        </div>

        <div class="login-container">
            <div class="login-header">
                <h1>Sistema de Abastecimento</h1>
                <p></p>
            </div>

            <div class="login-body">
                <div id="alertMessage" style="display: none;"></div>

                <?php
                // Exibir alerta de licença expirada
                if (isset($_GET['erro']) && $_GET['erro'] == 'licenca_expirada') {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <strong>Acesso Negado!</strong><br>
                            A licença do sistema expirou. Entre em contato com o administrador para renovar a licença.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                          </div>';
                }
                ?>

                <form id="loginForm">
                    <div class="form-group">
                        <label for="login">
                            <i class="fas fa-user"></i> Login
                        </label>
                        <input type="text" class="form-control" id="login" name="login" required placeholder="Seu login">
                    </div>

                    <div class="form-group">
                        <label for="senha">
                            <i class="fas fa-lock"></i> Senha
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="senha" name="senha" required placeholder="Sua senha">
                            <button class="input-group-text" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <span><i class="fas fa-sign-in-alt"></i> ENTRAR</span>
                    </button>
                </form>
                <div class="text-center mt-3">
                    <a href="#" id="forgotPasswordLink" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Esqueci a senha</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Esqueci a senha -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Recuperar senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Informe seu usuário ou e-mail cadastrado. Enviaremos um link para redefinir a senha.</p>
                    <div id="forgotAlert" style="display:none;"></div>
                    <div class="mb-3">
                        <label for="forgotInput" class="form-label">Usuário ou E-mail</label>
                        <input type="text" class="form-control" id="forgotInput" placeholder="Usuário ou E-mail">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="forgotSubmit">Enviar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('senha');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const login = document.getElementById('login').value;
            const senha = document.getElementById('senha').value;
            const alertDiv = document.getElementById('alertMessage');
            
            // Fazer requisição para API de login
            fetch('admin_abastecimento/api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `login=${encodeURIComponent(login)}&senha=${encodeURIComponent(senha)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertDiv.className = 'alert alert-success show';
                    alertDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    alertDiv.style.display = 'block';
                    
                    // Redirecionar será feito pelo backend via header Location
                    setTimeout(() => {
                        window.location.href = data.redirect || 'admin_abastecimento/pages/dashboard.php';
                    }, 500);
                } else {
                    alertDiv.className = 'alert alert-danger show';
                    alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                    alertDiv.style.display = 'block';
                    
                    // Focar no campo com erro
                    if (data.field) {
                        document.getElementById(data.field).focus();
                    }
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alertDiv.className = 'alert alert-danger show';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erro ao conectar com o servidor';
                alertDiv.style.display = 'block';
            });
        });

        // Forgot password functionality
        document.getElementById('forgotSubmit').addEventListener('click', function() {
            const input = document.getElementById('forgotInput').value;
            const alertDiv = document.getElementById('forgotAlert');
            
            if (!input) {
                alertDiv.className = 'alert alert-danger';
                alertDiv.innerHTML = 'Por favor, informe seu usuário ou e-mail';
                alertDiv.style.display = 'block';
                return;
            }
            
            fetch('admin_abastecimento/api/password_reset_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `login=${encodeURIComponent(input)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alertDiv.className = 'alert alert-success';
                    alertDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    alertDiv.style.display = 'block';
                    
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal')).hide();
                        alertDiv.style.display = 'none';
                        document.getElementById('forgotInput').value = '';
                    }, 3000);
                } else {
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                    alertDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alertDiv.className = 'alert alert-danger';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erro ao processar solicitação';
                alertDiv.style.display = 'block';
            });
        });
    </script>
</body>
</html>
