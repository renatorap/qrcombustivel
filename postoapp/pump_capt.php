<?php

if(session_status() === PHP_SESSION_NONE) session_start();

// Limpara o buffer de redirecionamento
//ob_start();

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
    //unset($_SESSION['id_cond'], $_SESSION['nome_cond'], $_SESSION['id_veic'], $_SESSION['placa_veic']);

}

// Incluir o arquivo para validar e recuperar dados do token
include_once 'validar_token.php';

$get_cod = filter_input_array(INPUT_GET, FILTER_DEFAULT);
// var_dump($get_cod);
// echo "<br>";
// var_dump($_SESSION);
//exit();

$qr_code = $get_cod["cod"];
//echo $qr_code;

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

    // QUERY para recuperar o usuário do banco de dados (sistema novo)
    $login_usuario = recuperarEmailToken();
    $nome_usuario = recuperarNomeToken();
    
    $query_header = "SELECT
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
                    WHERE u.grupo_id = 3
                    AND (u.login = :login OR u.nome = :nome)
                    ORDER BY uf.id DESC
                    LIMIT 1";

    // Preparar a QUERY
    $result_header = $conn->prepare($query_header);
    $result_header->bindParam(':login', $login_usuario, PDO::PARAM_STR);
    $result_header->bindParam(':nome', $nome_usuario, PDO::PARAM_STR);

    // Executar a QUERY
    $result_header->execute();

    // Acessa o IF quando encontrou usuário no banco de dados
    if (($result_header) and ($result_header->rowCount() != 0)) {
        // Ler o resultado retornado do banco de dados
        $row_header = $result_header->fetch(PDO::FETCH_ASSOC);
        //var_dump($row_header);
        
            $_SESSION['login_user'] = $row_header['login_user'];
            $_SESSION['nome_user'] = $row_header['nome_user'];
            $_SESSION['id_forn'] = $row_header['id_forn'];
            $_SESSION['nome_forn'] = $row_header['nome_forn'];
            $_SESSION['id_empr'] = $row_header['id_cliente'];
            $_SESSION['nome_empr'] = $row_header['nome_empr'];
            $_SESSION['logo'] = $row_header['logo'];
        //var_dump($_SESSION);
    }

// Chamar a função para recuperar o nome salvo no token
//echo "Bem vindo " . recuperarNomeToken() . ". <br>";

// Chamar a função para recuperar o e-mail salvo no token
//echo "E-mail do usuário logado " . recuperarEmailToken() . ". <br>";

// Link para sair e apagar cookie token
//echo "<a href='logout.php'>Sair</a><br>";

    //VARIAVEL BASE CONDUTOR/VEICULO
    $sql = "SELECT
                set_name as set_name,
                set_value as set_value
              FROM sec_settings
             WHERE set_name IN ('base_condutor', 'base_veiculo')";
    $stmt = $conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
        if ($row[0] == 'base_condutor') {
            $_SESSION['base_condutor'] = $row[1];
        }else{
            $_SESSION['base_veiculo'] = $row[1];
        }
        //$data = $row[0] . "\t" . $row[1] . "\r";
        //print $data;
    }

    if (($qr_code % $_SESSION['base_condutor'] == 0) && (!isset($_SESSION['msg']))) {
        $_SESSION['get_id_condutor'] = $qr_code / $_SESSION['base_condutor'];

    }
    elseif (($qr_code % $_SESSION['base_veiculo'] == 0) && (!isset($_SESSION['msg']))) {
        $_SESSION['get_id_veiculo'] = $qr_code / $_SESSION['base_veiculo'];

    }

    // echo "Cod: " . $qr_code . "<br><br>";

    // echo "Expressão Condutor: " . $qr_code . " / " . $_SESSION['base_condutor'] . "<br>";
    // echo "Divisão Condutor: " . ($qr_code / $_SESSION['base_condutor']) . "<br>";
    // echo "Resto Condutor: " . ($qr_code % $_SESSION['base_condutor']) . "<br>";
    // echo "ID Condutor: " . $_SESSION['base_condutor'] . "<br>";
    // echo "Sessão Condutor: " .$_SESSION['get_id_condutor'];

    if (isset($_SESSION['get_id_condutor']) && ($_SESSION['get_id_condutor'] != 0)) {
        // QUERY para recuperar o CONDUTOR do banco de dados
        $query_cond =   "
                        SELECT
                            c.id_condutor AS id_cond,
                            c.nome AS nome_cond
                            FROM condutor c
                            WHERE c.id_cliente = 
                        " . $_SESSION['id_empr'] . " AND c.id_condutor = ". $_SESSION['get_id_condutor'] . " AND c.id_situacao = 1 LIMIT 1";
        //echo $query_cond."<br>";
            $stmt_cond = $conn->prepare($query_cond, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
            $stmt_cond->execute();
            while ($row_cond = $stmt_cond->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
                $_SESSION['id_cond'] = $row_cond[0];
                $_SESSION['nome_cond'] = $row_cond[1];
                //print $data_cond;
            }
        }

        // echo "Expressão Veículo: " . $qr_code . " / " . $_SESSION['base_veiculo'] . "<br>";
        // echo "Divisão Veículo: " . ($qr_code / $_SESSION['base_veiculo']) . "<br>";
        // echo "Resto Veículo: " . ($qr_code % $_SESSION['base_veiculo']) . "<br>";
        // echo "ID Veículo: " . $_SESSION['base_veiculo'] . "<br>";
        // echo "Sessão Veículo: " .$_SESSION['get_id_veiculo'];
        if (isset($_SESSION['get_id_veiculo']) && ($_SESSION['get_id_veiculo'] != 0)) {

            // QUERY para recuperar o VEÍCULO do banco de dados
            $query_veic =   "
                            SELECT
                            	v.id_veiculo AS id_veic,
                            	v.placa AS placa_veic
                              FROM veiculo v
                             WHERE v.id_situacao = 1
                               AND v.id_cliente = 
                               " . $_SESSION['id_empr'] . "
                               AND v.id_situacao = 1
                               AND v.id_veiculo = ". $_SESSION['get_id_veiculo'] . "
                            LIMIT 1";
        //echo $query_cond."<br>";
            $stmt_veic = $conn->prepare($query_veic, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
            $stmt_veic->execute();
            while ($row_veic = $stmt_veic->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
                $_SESSION['id_veic'] = $row_veic[0];
                $_SESSION['placa_veic'] = $row_veic[1];
                //print $_SESSION['id_veic'] . "\t" . $_SESSION['placa_veic'];
            }
    }

    if (isset($_SESSION['id_veic'])) {

        // QUERY para recuperar o COMBUSTIVEL do banco de dados
        $query_comb =   "
                        SELECT DISTINCT
                            p.id_produto AS id_prod,
                            p.descricao AS desc_prod
                        FROM veiculo v
                        INNER JOIN combustivel_veiculo cv ON cv.id_veiculo = v.id_veiculo
                                                        AND cv.id_cliente = " . $_SESSION['id_empr'] . "
                        INNER JOIN produto p ON p.id_produto = cv.id_produto
                        WHERE v.id_cliente = 
                        " . $_SESSION['id_empr'] . " AND v.id_veiculo = " . $_SESSION['id_veic'];

        $stmt_comb = $conn->prepare($query_comb, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));

        $stmt_comb->execute();
    
    }


//echo date_default_timezone_get();
// TRATAMENTO DATA E HORA
$data = date('Y-m-d');
$hora = date('H:i');

// echo $data."<br>";
// echo $hora."<br>";
// exit();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="600; URL=pump_capt.php">
    <title>PostoApp - Abastecimento</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
	<script src="https://code.jquery.com/jquery-3.6.3.js"></script>
	<script type="text/javascript" src="js/ajax.js"></script>
</head>
<body>

<div class="container mt-1">
    <div class="row">
        <div class="col-12">
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
            <img src="<?php echo $logo_src; ?>" style="width: 60px; height: 60px; float: left; margin: 5px;" onerror="this.style.display='none'">
            <ul>
                <li style="list-style-type: none;"><?php echo "Município: ".$_SESSION['nome_empr']; ?></li>
                <li style="list-style-type: none;"><?php echo "Fornecedor: ".$_SESSION['nome_forn']; ?></li>
                <li style="list-style-type: none;"><?php echo "Usuário: ".$_SESSION['nome_user']; ?></li>
            </ul>
        </div>
    </div>
</div>
    <a href="read_qr_code.php" target="_self">
    <h3 class="h3 text-center mb-n5">Abastecimento</h3>
    </a>
<div class="container">
    <div class="row alig-items-center">
        <div class="col-md-9 mx-auto col-lg-5">
                    <div class="form-floating" id="alerta">
                        <?php
                        // Verificar se existe a variável global "msg" e acessa o IF
                        if (isset($_SESSION['msg'])) {
                            // Imprimir o valor da variável global "msg"
                            echo $_SESSION['msg'];
                            unset($_SESSION['msg']);
                        }
                        
                        ?>
                    </div>
            <form method="post" action="nao_sei.php" class="p-2 p-md-4 border rounded-3 bg-light">
                <!-- VEÍCULO -->
                <div class="form-floating mb-1">
                    <input type="text" class="form-control form-control-sm" name="placa_veiculo" value="<?php if(isset($_SESSION['get_id_veiculo'])) { echo $_SESSION['placa_veic']; } ?>" disabled required/>
                    <label for="placa_veiculo">Veículo</label>
                </div>
                <!-- CONDUTOR -->
                <div class="form-floating mb-1">
                    <input type="text" class="form-control form-control-sm" name="condutor" value="<?php if(isset($_SESSION['get_id_condutor'])) { echo $_SESSION['nome_cond']; } ?>" disabled required/>
                    <label for="condutor">Condutor</label>
                </div>
                <!-- PRODUTO/COMBUSTÍVEL -->
                <div class="form-floating mb-1">
                    <select class="form-select" name="produto" id="produto" required onchange="getDados();">
                        <option value="0">Selecione Combustível</option>
                        <?php
                            if (isset($_SESSION['id_veic'])) {
                                while ($row_comb = $stmt_comb->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
                                    //$data_comb = $row_comb[0] . "\t" . $row_comb[1] . "\t" . $row_comb[2] . "\t" . $row_comb[3] . "\t" . $row_comb[4] . "<br>";
                                    //print $data_comb;
                                    echo "<option value=\"" . $row_comb[0] . "\">" . $row_comb[1] . "</option>";
                                }
                            }
                        ?>
                    </select>
                    <label class="form-label" for="produto">Combustível</label>
                </div>
                
                <!-- DATA ATUAL -->
                <div class="form-floating mb-1">
                    <input type="date" class="form-control form-control-sm" name="data" min="2025-01-01" max="<?php echo $data; ?>" value="<?php echo $data; ?>" required/>
                    <label for="data">Data</label>
                </div>
                <!-- HORA ATUAL -->
                <div class="form-floating mb-1">
                    <input type="time" class="form-control form-control-sm" name="hora" value="<?php echo $hora; ?>" required/>
                    <label for="hora">Hora</label>
                </div>
                <!-- LITRAGEM -->
                <div class="form-floating mb-1">
                    <input type="number" class="form-control form-control-sm" name="litragem" id="litragem" step="0.001" min="0" inputmode="decimal" required/>
                    <label for="litragem">Litragem</label>
                </div>
                <!-- KM ATUAL -->
                <div class="form-floating mb-1">
                    <input type="number" class="form-control form-control-sm" name="km_atual" id="km_atual" required onblur="validaKManterior();" />
                    <label for="km_atual">Km Atual</label>
                </div>
                <!-- VALOR UNITÁRIO -->
                <div class="form-floating mb-1">
                    <input type="number" class="form-control form-control-sm" name="valor_unitario" id="valor_unitario" disabled required/>
                    <input type="hidden" name="valor_unitario_h" id="valor_unitario_h"/>
                    <label for="valor_unitario">Valor Unitário</label>
                </div>
                <!-- VALOR TOTAL -->
                <div class="form-floating mb-1">
                    <input type="text" class="form-control form-control-sm" name="valor_total" id="valor_total" disabled required/>
                    <input type="hidden" name="valor_unitario_h" id="valor_unitario_h"/>
                    <label for="valor_total">Valor Total</label>
                </div>
                <!-- SUBMIT -->
                <div>
                    <button class="btn btn-primary w-100" type="submit" name="SendPump">Abastecer</button>
                </div>
            </form>
            <br><br><a href='logout.php'>Sair</a>
        </div>
    </div>
</div>

<script>
    //FORMATAÇÃO DE VALORES
    $('input[type="number"]').keyup(function(){
        //Receber os 2 valores, substituir vírgulas por ponto (replace) e converter para float (decimal)
        var num1 = parseFloat($('#litragem').val().replace(',','.'));
        var num2 = parseFloat($('#valor_unitario').val().replace(',','.'));
        //Somar os valores digitados e exibir o resultado preservando 3 dígitos após o ponto
        parseFloat($('#valor_total').val((num1 * num2).toFixed(2)));
    });

    window.setTimeout(function() {
        $("#alerta").fadeTo(1500, 0).slideUp(1500, function(){
            $(this).remove(); 
        });
    }, 6000);


</script>

<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<?php
            /*
            echo $login_user . "<br>";
            echo $nome_user . "<br>";
            echo $id_forn . "<br>";
            echo $nome_forn . "<br>";
            echo $id_empresa . "<br>";
            echo $nome_empr . "<br>";
            echo $logo . "<br>";
            */
?>
</body>
</html>