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
    <style>
        .ativar-licenca-container {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 20px;
            padding: 40px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .license-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 700px;
            width: 100%;
            flex: 1;
            transition: all 0.3s ease;
        }
        
        .license-card:hover {
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
            transform: translateY(-5px);
        }
        
        .license-header {
            background: linear-gradient(135deg, #2f6b8f 0%, #255a7a 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .license-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .license-header i {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .license-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .license-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
        }
        
        .license-body {
            padding: 40px 30px;
        }
        
        .alert-custom {
            border-radius: 15px;
            border: none;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .alert-danger.alert-custom {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .alert-warning.alert-custom {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #333;
        }
        
        .form-control-custom {
            border: 3px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            font-size: 1.3rem;
            font-family: 'Courier New', monospace;
            letter-spacing: 3px;
            text-align: center;
            text-transform: uppercase;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control-custom:focus {
            border-color: #2f6b8f;
            box-shadow: 0 0 0 5px rgba(47, 107, 143, 0.1);
            background: white;
            transform: scale(1.02);
        }
        
        .btn-activate {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            border: none;
            border-radius: 15px;
            padding: 18px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .btn-activate::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }
        
        .btn-activate:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-activate:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .btn-activate:active {
            transform: translateY(-1px);
        }
        
        .sidebar-info {
            width: 350px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .info-box {
            background: linear-gradient(135deg, #e8f4f8 0%, #d1e7f0 100%);
            border-radius: 15px;
            padding: 20px;
            border-left: 5px solid #2f6b8f;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .info-box h6 {
            color: #2f6b8f;
            font-weight: 700;
            margin-bottom: 12px;
            font-size: 1rem;
        }
        
        .info-box ul {
            margin-bottom: 0;
        }
        
        .info-box li {
            padding: 6px 0;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .status-box {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .status-box h6 {
            font-size: 0.95rem;
            margin-bottom: 15px;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 8px;
        }
        
        .status-label {
            font-weight: 600;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .status-value {
            font-weight: 700;
            color: #2f6b8f;
            font-size: 1rem;
        }
        
        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #2f6b8f, transparent);
            margin: 30px 0;
        }
        
        .btn-activate .spinner-border {
            width: 1.2rem;
            height: 1.2rem;
            border-width: 2px;
        }
        
        .form-label {
            color: #2f6b8f;
        }
        
        /* Efeito de digitação no input */
        .form-control-custom::placeholder {
            opacity: 0.5;
            font-size: 1.1rem;
        }
        
        /* Animação de entrada */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .license-card {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Efeito hover nos itens da lista */
        .info-box li {
            transition: all 0.3s ease;
            padding-left: 5px;
        }
        
        .info-box li:hover {
            padding-left: 15px;
            color: #2f6b8f;
        }
        
        /* Loading spinner no botão */
        .btn-activate:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        @media (max-width: 1200px) {
            .ativar-licenca-container {
                flex-direction: column;
                align-items: center;
            }
            
            .sidebar-info {
                width: 100%;
                max-width: 700px;
                order: 2;
            }
            
            .license-card {
                order: 1;
            }
        }
        
        @media (max-width: 768px) {
            .license-header {
                padding: 30px 20px;
            }
            
            .license-header i {
                font-size: 3rem;
            }
            
            .license-header h2 {
                font-size: 1.5rem;
            }
            
            .license-header p {
                font-size: 0.9rem;
            }
            
            .license-body {
                padding: 30px 20px;
            }
            
            .form-control-custom {
                font-size: 1rem;
                letter-spacing: 2px;
                padding: 15px;
            }
            
            .btn-activate {
                font-size: 1rem;
                padding: 15px 30px;
            }
            
            .alert-custom {
                padding: 15px;
            }
            
            .alert-custom i {
                font-size: 1.5rem;
            }
            
            .status-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .ativar-licenca-container {
                padding: 20px 10px;
            }
            
            .license-card {
                border-radius: 15px;
            }
            
            .form-control-custom {
                font-size: 0.9rem;
                letter-spacing: 1px;
            }
        }
    </style>
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
                                        <strong style="font-size: 1.2rem;">Licença Expirada!</strong><br>
                                        <span>A licença do sistema expirou. Ative uma nova licença para continuar.</span>
                                    </div>
                                </div>
                              </div>';
                    } elseif ($motivo == 'pendente') {
                        echo '<div class="alert alert-warning alert-custom" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                    <div>
                                        <strong style="font-size: 1.2rem;">Licença Pendente!</strong><br>
                                        <span>Você possui uma licença pendente. Insira o código abaixo para ativar.</span>
                                    </div>
                                </div>
                              </div>';
                    }
                    ?>

                    <form id="formAtivarLicenca" onsubmit="ativarLicenca(event)">
                        <div class="mb-4">
                            <label for="codigoLicenca" class="form-label fw-bold mb-3" style="font-size: 1.1rem;">
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
                <div id="statusLicencaAtual" class="status-box" style="display: none;">
                    <h6 class="text-center" style="color: #2f6b8f; font-weight: 700;">
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
                    <div id="avisoExpiracao" class="alert alert-warning mt-3" style="display: none; border-radius: 10px;">
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
            <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden;">
                <div class="modal-body text-center p-5" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);">
                    <div class="success-animation mb-4">
                        <i class="fas fa-check-circle" style="font-size: 5rem; color: #28a745; animation: scaleIn 0.5s ease-out;"></i>
                    </div>
                    <h3 style="color: #28a745; font-weight: 700; margin-bottom: 20px;">
                        Licença Ativada!
                    </h3>
                    <p class="mb-4" id="mensagemSucesso" style="font-size: 1.1rem; color: #495057;"></p>
                    <button type="button" class="btn btn-success btn-lg px-5" data-bs-dismiss="modal" style="border-radius: 50px; font-weight: 600;">
                        <i class="fas fa-arrow-right me-2"></i>Continuar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .modal-backdrop.show {
            opacity: 0.7;
        }
        
        #modalSucesso .modal-content {
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
    </style>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/ativar_licenca.js"></script>
</body>
</html>
