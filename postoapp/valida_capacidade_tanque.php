<?php

// Verifica se existe a variável selprod
if (isset($_GET["ltrg"])) {
    $ltrg = $_GET["ltrg"];

}

session_start(); // Iniciar a sessão

// Limpara o buffer de redirecionamento
ob_start();

include_once('conexao.php');

$query = "SELECT capacidade_combustivel FROM veiculo WHERE id_cliente = " . $_SESSION['id_empr'] . " AND id_veiculo = " . $_SESSION['id_veic'];

            $stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
            $stmt->execute();

        // Captura os dados da consulta e inseri na tabela HTML
            while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {

                print $row[0];

            }


?>