<?php

if(session_status() === PHP_SESSION_NONE) session_start();

//Define timezone
date_default_timezone_set("America/Sao_Paulo");

$_SESSION['LAST_ACTIVITY'] = 0;

//Check the session start time is set or not
if(!isset($_SESSION['LAST_ACTIVITY']))
{
    //Set the session start time
    $_SESSION['LAST_ACTIVITY'] = time();
}

if (isset($_SESSION['LAST_ACTIVITY']) && ((time() - $_SESSION['LAST_ACTIVITY']) > 600)) {
    $_SESSION['LAST_ACTIVITY'] = time();
}

// Incluir o arquivo para validar e recuperar dados do token
include_once 'validar_token.php';

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

// Chamar a função validar o token, se a função retornar FALSE significa que o token é inválido e acessa o IF
if(!validarToken()){
    // Criar a mensagem de erro e atribuir para variável global
    $_SESSION['msg'] = "<p style='color: #f00;'>Erro: Necessário realizar o login para acessar a página!</p>";

    // Redireciona o usuário para o arquivo index.php
    @header("Location: index.php");

    // Pausar o processamento da página
    exit();
}

// Verificar se dados já estão na sessão (sistema novo)
if(!isset($_SESSION['id_forn']) || !isset($_SESSION['id_empr'])) {
    // Buscar dados do fornecedor e empresa usando o sistema unificado
    $login_usuario = isset($_SESSION['login']) ? $_SESSION['login'] : recuperarEmailToken();
    $nome_usuario = isset($_SESSION['nome']) ? $_SESSION['nome'] : recuperarNomeToken();
    
    // Tentar buscar pelo sistema novo (tabela usuarios + usuario_fornecedor)
    $query_header_new = "SELECT
                        u.login as login_user,
                        u.nome as nome_user,
                        f.id_fornecedor as id_forn,
                        f.nome_fantasia as nome_forn,
                        c.id as id_cliente,
                        c.nome_fantasia as nome_empr,
                        c.logo_path as logo
                    FROM usuarios u
                    INNER JOIN usuario_fornecedor uf ON uf.usuario_id = u.id AND uf.ativo = 1
                    INNER JOIN fornecedor f ON f.id_fornecedor = uf.fornecedor_id
                    INNER JOIN clientes c ON c.id = f.id_cliente
                    WHERE u.grupo_id = 10
                    AND (u.login = :login OR u.nome = :nome) 
                    ORDER BY uf.id DESC
                    LIMIT 1";
    
    $result_header = $conn->prepare($query_header_new);
    $result_header->bindParam(':login', $login_usuario, PDO::PARAM_STR);
    $result_header->bindParam(':nome', $nome_usuario, PDO::PARAM_STR);
    $result_header->execute();
    
    // Acessa o IF quando encontrou usuário no banco de dados
    if (($result_header) and ($result_header->rowCount() != 0)) {
        // Ler o resultado retornado do banco de dados
        $row_header = $result_header->fetch(PDO::FETCH_ASSOC);
        
        $_SESSION['login_user'] = $row_header['login_user'];
        $_SESSION['nome_user'] = $row_header['nome_user'];
        $_SESSION['id_forn'] = $row_header['id_forn'];
        $_SESSION['nome_forn'] = $row_header['nome_forn'];
        $_SESSION['id_empr'] = $row_header['id_cliente'];
        $_SESSION['nome_empr'] = $row_header['nome_empr'];
        $_SESSION['logo'] = $row_header['logo'];
    } else {
        $_SESSION['msg'] = "<p style='color: #f00;'>Erro: Fornecedor não encontrado para este usuário!</p>";
        @header("Location: index.php");
        exit();
    }
}

// TRATAMENTO DATA E HORA
$data = date('Y-m-d');
$hora = date('H:i');

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="600; URL=captura_abastecimento.php">
    <title>PostoApp - Captura de Abastecimento</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.3.js"></script>
    <style>
        body {
            background-color: #f5f5f5;
        }
        .main-container {
            max-width: 480px;
            margin: 0 auto;
        }
        #qr-reader {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        .qr-button {
            margin: 3px;
        }
        #qr-reader__dashboard_section_csr {
            display: none !important;
        }
        .hidden {
            display: none;
        }
        .alert-custom {
            padding: 8px;
            margin: 8px 0;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-qr {
            margin: 3px 0;
            padding: 8px 12px;
            font-size: 0.95rem;
        }
        .header-compact {
            background-color: #fff;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .header-compact img {
            width: 45px !important;
            height: 45px !important;
            margin: 0 8px 0 0 !important;
        }
        .header-compact ul {
            margin: 0;
            padding: 0;
            font-size: 0.85rem;
            line-height: 1.4;
        }
        .header-compact li {
            list-style-type: none;
        }
        h3.page-title {
            font-size: 1.3rem;
            margin: 10px 0;
        }
        .qr-section {
            margin-bottom: 10px;
            padding: 12px;
        }
        .qr-section h5 {
            font-size: 1rem;
            margin-bottom: 10px;
        }
        .form-section {
            padding: 12px 15px;
            margin-bottom: 10px;
        }
        .form-floating {
            margin-bottom: 8px !important;
        }
        .form-floating > label {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }
        .form-control, .form-select {
            font-size: 0.95rem;
            padding: 0.5rem 0.75rem;
            height: calc(2.5rem + 2px);
        }
        .btn-lg {
            padding: 10px;
            font-size: 1rem;
        }
        .btn-secondary {
            padding: 8px 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="header-compact">
        <div class="d-flex align-items-center">
            <?php 
            // Verificar se o logo é do novo sistema (caminho completo) ou antigo (apenas nome)
            $logo_path = $_SESSION['logo'];
            if (strpos($logo_path, '/') !== false || strpos($logo_path, 'storage') !== false) {
                // Novo sistema - caminho completo relativo ao admin_abastecimento
                $logo_src = "../admin_abastecimento/" . $logo_path;
            } else {
                // Sistema antigo - concatenar com img/id_empr/
                $logo_src = "img/" . $_SESSION['id_empr'] . "/" . $logo_path;
            }
            ?>
            <img src="<?php echo $logo_src; ?>" class="rounded" onerror="this.style.display='none'">
            <ul class="flex-grow-1">
                <li><strong>Município:</strong> <?php echo $_SESSION['nome_empr']; ?></li>
                <li><strong>Fornecedor:</strong> <?php echo $_SESSION['nome_forn']; ?></li>
                <li><strong>Usuário:</strong> <?php echo $_SESSION['nome_user']; ?></li>
            </ul>
        </div>
    </div>

    <div id="alerta">
        <?php
        if (isset($_SESSION['msg'])) {
            echo $_SESSION['msg'];
            unset($_SESSION['msg']);
        }
        
        // Recuperar dados do formulário se existirem
        $form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
        ?>
    </div>

    <!-- QR Code Reader Section -->
    <div class="qr-section border rounded bg-light">
        <h4 class="text-center">Abastecimento</h4>
        
        <!-- Botões de Leitura QR Code -->
        <div class="d-grid gap-1">
            <button type="button" class="btn btn-primary btn-qr" onclick="iniciarLeituraCondutor()">
                <i class="bi bi-qr-code-scan"></i> Ler QR Code do Crachá
            </button>
            <button type="button" class="btn btn-success btn-qr" onclick="iniciarLeituraVeiculo()">
                <i class="bi bi-qr-code-scan"></i> Ler QR Code do Veículo
            </button>
        </div>
        
        <div id="qr-reader" class="hidden mt-2"></div>
        <div id="qr-reader-results" class="mt-2"></div>
    </div>

    <!-- Form de Captura -->
    <form method="post" action="salvar_abastecimento.php" class="form-section border rounded bg-light">
        
        <!-- ID CONDUTOR (hidden) -->
        <input type="hidden" name="id_condutor" id="id_condutor" value="<?php echo isset($form_data['id_condutor']) ? htmlspecialchars($form_data['id_condutor']) : ''; ?>">
        
        <!-- ID VEÍCULO (hidden) -->
        <input type="hidden" name="id_veiculo" id="id_veiculo" value="<?php echo isset($form_data['id_veiculo']) ? htmlspecialchars($form_data['id_veiculo']) : ''; ?>">
        
        <!-- CONDUTOR -->
        <div class="form-floating mb-2">
            <input type="text" class="form-control form-control-sm" name="nome_condutor" id="nome_condutor" value="<?php echo isset($form_data['nome_condutor']) ? htmlspecialchars($form_data['nome_condutor']) : ''; ?>" readonly required/>
            <label for="nome_condutor"><i class="bi bi-person"></i> Condutor</label>
        </div>
        
        <!-- VEÍCULO -->
        <div class="form-floating mb-2">
            <input type="text" class="form-control form-control-sm" name="placa_veiculo" id="placa_veiculo" value="<?php echo isset($form_data['placa_veiculo']) ? htmlspecialchars($form_data['placa_veiculo']) : ''; ?>" readonly required/>
            <label for="placa_veiculo"><i class="bi bi-car-front"></i> Placa do Veículo</label>
        </div>
        
        <!-- PRODUTO/COMBUSTÍVEL -->
        <div class="form-floating mb-2">
            <select class="form-select" name="produto" id="produto" required onchange="buscarPrecoUnitario();">
                <option value="">Selecione o Combustível</option>
            </select>
            <label class="form-label" for="produto"><i class="bi bi-fuel-pump"></i> Combustível</label>
        </div>
        
        <!-- DATA ATUAL -->
        <div class="form-floating mb-2">
            <input type="date" class="form-control form-control-sm" name="data" id="data" min="2025-01-01" max="<?php echo $data; ?>" value="<?php echo isset($form_data['data']) ? htmlspecialchars($form_data['data']) : $data; ?>" required/>
            <label for="data"><i class="bi bi-calendar"></i> Data</label>
        </div>
        
        <!-- HORA ATUAL -->
        <div class="form-floating mb-2">
            <input type="time" class="form-control form-control-sm" name="hora" id="hora" value="<?php echo isset($form_data['hora']) ? htmlspecialchars($form_data['hora']) : $hora; ?>" required/>
            <label for="hora"><i class="bi bi-clock"></i> Hora</label>
        </div>
        
        <!-- KM ATUAL -->
        <div class="form-floating mb-2">
            <input type="number" class="form-control form-control-sm" name="km_atual" id="km_atual" step="1" min="0" value="<?php echo isset($form_data['km_atual']) ? htmlspecialchars($form_data['km_atual']) : ''; ?>" required />
            <label for="km_atual"><i class="bi bi-speedometer"></i> Km Atual</label>
        </div>
        
        <!-- LITRAGEM -->
        <div class="form-floating mb-2">
            <input type="number" class="form-control form-control-sm" name="litragem" id="litragem" step="0.001" min="0.001" inputmode="decimal" value="<?php echo isset($form_data['litragem']) ? htmlspecialchars($form_data['litragem']) : ''; ?>" required onkeyup="calcularValorTotal()" onchange="calcularValorTotal()"/>
            <label for="litragem"><i class="bi bi-droplet"></i> Litragem</label>
        </div>
        
        <!-- VALOR UNITÁRIO -->
        <div class="form-floating mb-2">
            <input type="number" class="form-control form-control-sm" name="valor_unitario" id="valor_unitario" step="0.001" readonly required/>
            <label for="valor_unitario"><i class="bi bi-currency-dollar"></i> Valor Unitário</label>
        </div>
        
        <!-- VALOR TOTAL -->
        <div class="form-floating mb-2">
            <input type="text" class="form-control form-control-sm" name="valor_total" id="valor_total" readonly required/>
            <label for="valor_total"><i class="bi bi-cash-stack"></i> Valor Total</label>
        </div>
        
        <!-- SUBMIT -->
        <div class="d-grid gap-2 mt-3">
            <button class="btn btn-primary btn-lg" type="submit" name="SendPump">
                <i class="bi bi-check-circle"></i> Registrar Abastecimento
            </button>
        </div>
    </form>
    
    <div class="text-center mt-2 mb-3">
        <a href='logout.php' class="btn btn-secondary"><i class="bi bi-box-arrow-right"></i> Sair</a>
    </div>
</div>

<script src="js/html5-qrcode.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<script>
let html5QrCode;
let tipoLeitura = ''; // 'condutor' ou 'veiculo'

// Função para iniciar leitura de QR Code do Condutor
function iniciarLeituraCondutor() {
    tipoLeitura = 'condutor';
    iniciarCamera();
}

// Função para iniciar leitura de QR Code do Veículo
function iniciarLeituraVeiculo() {
    tipoLeitura = 'veiculo';
    iniciarCamera();
}

// Função para iniciar a câmera
function iniciarCamera() {
    document.getElementById('qr-reader').classList.remove('hidden');
    
    html5QrCode = new Html5Qrcode("qr-reader");
    
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };
    
    html5QrCode.start(
        { facingMode: "environment" },
        config,
        onScanSuccess,
        onScanFailure
    ).catch((err) => {
        console.error("Erro ao iniciar câmera:", err);
        alert("Erro ao acessar a câmera: " + err);
    });
}

// Função chamada quando QR Code é lido com sucesso
function onScanSuccess(decodedText, decodedResult) {
    html5QrCode.stop().then(() => {
        document.getElementById('qr-reader').classList.add('hidden');
        processarQRCode(decodedText);
    }).catch((err) => {
        console.error("Erro ao parar câmera:", err);
    });
}

// Função chamada quando há falha na leitura
function onScanFailure(error) {
    // Não faz nada, apenas continua tentando
}

// Função para processar o QR Code lido
function processarQRCode(qrCode) {
    const url = tipoLeitura === 'condutor' ? 'processar_qr_condutor.php' : 'processar_qr_veiculo.php';
    
    $.ajax({
        url: url,
        type: 'POST',
        data: { qr_code: qrCode },
        dataType: 'json',
        success: function(response) {
            // Mostrar debug se existir
            if(response.debug) {
                console.log('=== DEBUG processar_qr_veiculo.php ===');
                response.debug.forEach(function(log) {
                    console.log(log);
                });
                console.log('====================================');
            }
            
            if(response.success) {
                if(tipoLeitura === 'condutor') {
                    $('#id_condutor').val(response.id_condutor);
                    $('#nome_condutor').val(response.nome_condutor);
                    const metodo = response.metodo || 'QR Code';
                    mostrarAlerta('Condutor identificado por ' + metodo + ': ' + response.nome_condutor, 'success');
                } else {
                    $('#id_veiculo').val(response.id_veiculo);
                    $('#placa_veiculo').val(response.placa_veiculo);
                    const metodo = response.metodo || 'QR Code';
                    mostrarAlerta('Veículo identificado por ' + metodo + ': ' + response.placa_veiculo, 'success');
                    
                    // Carregar combustíveis do veículo
                    carregarCombustiveis(response.id_veiculo);
                }
            } else {
                mostrarAlerta(response.message, 'danger');
            }
        },
        error: function() {
            mostrarAlerta('Erro ao processar QR Code. Tente novamente.', 'danger');
        }
    });
}

// Função para carregar combustíveis do veículo
function carregarCombustiveis(idVeiculo) {
    $.ajax({
        url: 'buscar_combustiveis_veiculo.php',
        type: 'POST',
        data: { id_veiculo: idVeiculo },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                const selectProduto = $('#produto');
                selectProduto.empty();
                selectProduto.prop('disabled', false);
                
                if(response.combustiveis.length === 1) {
                    // Apenas um combustível - preencher automaticamente
                    const comb = response.combustiveis[0];
                    selectProduto.append('<option value="' + comb.id_produto + '" selected>' + comb.descricao + '</option>');
                    // Não desabilitar para garantir que o valor seja enviado no form
                    selectProduto.css('pointer-events', 'none');
                    selectProduto.css('background-color', '#e9ecef');
                    
                    // Buscar preço automaticamente
                    buscarPrecoUnitario();
                } else {
                    // Múltiplos combustíveis - habilitar seleção
                    selectProduto.append('<option value="">Selecione o Combustível</option>');
                    response.combustiveis.forEach(function(comb) {
                        selectProduto.append('<option value="' + comb.id_produto + '">' + comb.descricao + '</option>');
                    });
                    selectProduto.css('pointer-events', 'auto');
                    selectProduto.css('background-color', '');
                }
            }
        },
        error: function() {
            mostrarAlerta('Erro ao carregar combustíveis.', 'danger');
        }
    });
}

// Função para buscar preço unitário
function buscarPrecoUnitario() {
    const idProduto = $('#produto').val();
    const idVeiculo = $('#id_veiculo').val();
    
    if(!idProduto || !idVeiculo) {
        return;
    }
    
    $.ajax({
        url: 'buscar_preco_combustivel.php',
        type: 'POST',
        data: { 
            id_produto: idProduto,
            id_veiculo: idVeiculo
        },
        dataType: 'json',
        success: function(response) {
            console.log('Resposta buscar_preco_combustivel.php:', response);
            if(response.success) {
                $('#valor_unitario').val(parseFloat(response.valor).toFixed(3));
                calcularValorTotal();
            } else {
                mostrarAlerta(response.message || 'Preço não encontrado para este combustível.', 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro AJAX buscar_preco_combustivel.php:', status, error);
            console.error('Resposta:', xhr.responseText);
            mostrarAlerta('Erro ao buscar preço do combustível.', 'danger');
        }
    });
}

// Função para calcular valor total
function calcularValorTotal() {
    const litragem = parseFloat($('#litragem').val()) || 0;
    const valorUnitario = parseFloat($('#valor_unitario').val()) || 0;
    const valorTotal = (litragem * valorUnitario).toFixed(2);
    $('#valor_total').val(valorTotal);
}

// Função para mostrar alertas
function mostrarAlerta(mensagem, tipo) {
    const alertaDiv = $('#alerta');
    const classeAlerta = tipo === 'success' ? 'alert-success' : 'alert-danger';
    alertaDiv.html('<div class="alert-custom ' + classeAlerta + '">' + mensagem + '</div>');
    
    setTimeout(function() {
        alertaDiv.fadeOut(2500, function() {
            $(this).html('').show();
        });
    }, 3000);
}

// Auto fade dos alertas PHP
window.setTimeout(function() {
    $("#alerta").fadeTo(2500, 0).slideUp(2500, function(){
        $(this).html('').show().css('opacity', '1');
    });
}, 6000);

// Restaurar dados do formulário após erro
$(document).ready(function() {
    <?php if(!empty($form_data)): ?>
        <?php if(isset($form_data['id_veiculo']) && !empty($form_data['id_veiculo'])): ?>
            // Recarregar combustíveis do veículo
            carregarCombustiveis(<?php echo $form_data['id_veiculo']; ?>);
            
            <?php if(isset($form_data['produto']) && !empty($form_data['produto'])): ?>
                // Aguardar carregamento dos combustíveis e selecionar o correto
                setTimeout(function() {
                    $('#produto').val('<?php echo $form_data['produto']; ?>');
                    buscarPrecoUnitario();
                }, 500);
            <?php endif; ?>
        <?php endif; ?>
        
        // Calcular valor total se tiver litragem
        <?php if(isset($form_data['litragem']) && !empty($form_data['litragem'])): ?>
            setTimeout(function() {
                calcularValorTotal();
            }, 1000);
        <?php endif; ?>
        
        // Limpar dados salvos da sessão após restaurar
        <?php unset($_SESSION['form_data']); ?>
    <?php endif; ?>
});

</script>

</body>
</html>
