<?php

session_start(); // Iniciar a sessão

// Limpara o buffer de redirecionamento
ob_start();

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

// Verifica se existe a variável selprod
if (isset($_POST["placa_veiculo"])) {
    $placa_veiculo = $_POST["placa_veiculo"];
echo $placa_veiculo."<br>";

}

if(isset($_POST["condutor"])) {
    $condutor = $_POST["condutor"];
echo $condutor."<br>";

}

if(isset($_POST["produto"])) {
    $produto = $_POST["produto"];
echo $produto."<br>";

}

if(isset($_POST["data"])) {
    $data = "'" . $_POST["data"] . "'";
echo $data."<br>";

}

if(isset($_POST["hora"])) {
    $hora = "'" . $_POST["hora"] . "'";
echo $hora."<br>";

}

if(isset($_POST["km_atual"])) {
    $km_atual = $_POST["km_atual"];
echo $km_atual."<br>";

}

if(isset($_POST["litragem"])) {
    $litragem = $_POST["litragem"];
echo $litragem."<br>";

}

//if(isset($_POST["valor_unitario"])) {
//    $valor_unitario = $_POST["valor_unitario"];
//echo $valor_unitario."<br>";

//}


            // QUERY para recuperar o VEÍCULO/COMBUSTIVEL do banco de dados
            $query_cval =   "
                            SELECT
                            	pc.id_preco_combustivel,
                            	pc.valor
                              FROM combustivel_veiculo cv
                             INNER JOIN produto p ON p.id_produto = cv.id_produto
                             INNER JOIN preco_combustivel pc ON pc.id_produto = cv.id_produto
                             INNER JOIN aditamento_combustivel ac ON ac.id_aditamento_combustivel = pc.id_aditamento_combustivel
                             INNER JOIN contrato c ON c.id_contrato = ac.id_contrato
                             WHERE pc.fim_vigencia IS NULL"
                             . " AND cv.id_cliente = " . $_SESSION['id_empr'] . " AND cv.id_veiculo = " . $_SESSION['id_veic'] . " AND c.id_fornecedor = " . $_SESSION['id_forn'] . 
                              " AND p.id_produto = ". $produto ;
                            //echo "<br>".$query_cval."<br>";
            $stmt_cval = $conn->prepare($query_cval, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
            $stmt_cval->execute();

        // Captura os dados da consulta e inseri na tabela HTML
            while ($row_cval = $stmt_cval->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {

                $id_preco_combustivel = $row_cval[0];
                $valor_unitario = $row_cval[1];

            }

$valor_total = $valor_unitario * $litragem;

$msg = $_SESSION['id_forn']."<br>";

$msg .= $_SESSION['id_veic']."<br>";

$msg .= $placa_veiculo."<br>";

$msg .= $_SESSION['id_cond']."<br>";

$msg .= $condutor."<br>";

$msg .= $produto."<br>";

$msg .= $id_preco_combustivel."<br>";

$msg .= $data."<br>";

$msg .= $hora."<br>";

$msg .= $km_atual."<br>";

$msg .= $litragem."<br>";

$msg .= $valor_unitario."<br>";

$msg .= $valor_total."<br>";

//echo "<br>".$msg."<br>";

$insert_cc = "
INSERT INTO
    `consumo_combustivel` (
        `id_veiculo`,
        `id_condutor`,
        `id_fornecedor`,
        `id_preco_combustivel`,
        `data`,
        `hora`,
        `litragem`,
        `km_veiculo`,
        `id_produto`,
        `valor_unitario`,
        `valor_total`,
        `data_hora_ins`
    )
VALUES
    (
        " . $_SESSION['id_veic'] . ",
        " . $_SESSION['id_cond'] . ",
        " . $_SESSION['id_forn'] . ",
        " . $id_preco_combustivel . ",
        " . $data . ",
        " . $hora . ",
        " . $litragem . ",
        " . $km_atual . ",
        " . $produto . ",
        " . $valor_unitario . ",
        " . $valor_total . ",
        current_timestamp()
    )
";
echo $insert_cc;

if ($conn->query($insert_cc) === TRUE) {
    $_SESSION['msg'] = "Abastecimento realizado!";
    //$_SESSION['msg'] = "<div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">Abastecimento realizado!</div>";
} else {
    $_SESSION['msg'] = "Erro no abastecimento!";
    //$_SESSION['msg'] = "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">Erro no abastecimento!</div>";
}

$conn->close();

echo "<br>".$_SESSION['msg'];

session_unset($_SESSION['id_cond'], $_SESSION['nome_cond'], $_SESSION['id_veic'], $_SESSION['placa_veic']);


header("Location: pump_capt.php");

?>