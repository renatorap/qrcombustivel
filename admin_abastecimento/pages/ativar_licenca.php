<?php
require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    $_SESSION = array();
    session_destroy();
    header('Location: ../index.php');
    exit;
}

// Verificar permissão (administradores e operadores)
$grupoId = $_SESSION['grupoId'] ?? null;
if (!in_array($grupoId, [1, 2, 3])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativar Licença - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="ativar-licenca-container">
            <div class="license-card">
                <div class="license-header">
                    <i class="fas fa-key"></i>
                    <h2>Ativação de Licença</h2>
                    <p>Insira o código para liberar o acesso ao sistema</p>
                </div>
                
                <div class="license-body">
                    <?php
                    // Exibir alerta de acordo com o motivo
                    $motivo = $_GET['motivo'] ?? '';
                    if ($motivo == 'expirada') {
                        echo '<div class="alert alert-danger alert-custom" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                                    <div>
                                        <strong>Licença Expirada!</strong><br>
                                        <span>A licença do sistema expirou. Ative uma nova licença para continuar.</span>
                                    </div>
                                </div>
                              </div>';
                    } elseif ($motivo == 'pendente') {
                        echo '<div class="alert alert-warning alert-custom" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                    <div>
                                        <strong>Licença Pendente!</strong><br>
                                        <span>Você possui uma licença pendente. Insira o código abaixo para ativar.</span>
                                    </div>
                                </div>
                              </div>';
                    }
                    ?>

                    <form id="formAtivarLicenca" onsubmit="ativarLicenca(event)">
                        <div class="mb-4">
                            <label for="codigoLicenca" class="form-label fw-bold mb-3">
                                <i class="fas fa-barcode me-2"></i>Código da Licença
                            </label>
                            <input type="text" 
                                   class="form-control form-control-custom" 
                                   id="codigoLicenca" 
                                   placeholder="LIC-202412-XXXXXXXX"
                                   required
                                   autocomplete="off">
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Digite exatamente como recebeu por e-mail
                                </small>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-activate">
                                <i class="fas fa-unlock-alt me-2"></i>Ativar Licença Agora
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="sidebar-info">
                <div id="statusLicencaAtual" class="status-box d-none">
                    <h6 class="status-box-title">
                        <i class="fas fa-info-circle me-2"></i>Status da Licença Atual
                    </h6>
                    <div class="status-item">
                        <span class="status-label">
                            <i class="fas fa-signal"></i> Status
                        </span>
                        <span class="status-value" id="statusAtual"></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">
                            <i class="fas fa-calendar-alt"></i> Validade
                        </span>
                        <span class="status-value" id="validadeAtual"></span>
                    </div>
                    <div id="avisoExpiracao" class="alert alert-warning alert-expiracao">
                        <i class="fas fa-exclamation-triangle me-2"></i><span id="mensagemExpiracao"></span>
                    </div>
                </div>
                
                <div class="info-box">
                    <h6><i class="fas fa-lightbulb me-2"></i>Como Funciona?</h6>
                    <ul class="list-unstyled mb-0">
                        <li><i class="fas fa-check-circle text-success me-2"></i>A licença é gerada pelo administrador</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>O código é enviado por e-mail</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Insira o código no campo ao lado</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Sistema liberado até a data de expiração</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Sucesso -->
    <div class="modal fade" id="modalSucesso" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body success-modal-body">
                    <div class="success-animation mb-4">
                        <i class="fas fa-check-circle success-icon"></i>
                    </div>
                    <h3 class="success-title">
                        Licença Ativada!
                    </h3>
                    <p class="success-message" id="mensagemSucesso"></p>
                    <button type="button" class="btn btn-success btn-lg btn-success-modal" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-right me-2"></i>Continuar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/ativar_licenca.js"></script>
</body>
</html>
