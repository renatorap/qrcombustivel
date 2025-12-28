<?php

session_start(); // Iniciar a sessão

// Limpara o buffer de redirecionamento
ob_start();

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

//Verifica abastecimento anterior
$query = "
            SELECT 
            	concat(data, ' ', hora) data_hora
              FROM consumo_combustivel
             WHERE date_add(concat(data, ' ', hora), interval 3 hour) > current_timestamp()
               AND id_cliente = 1 AND id_veiculo = 7";
echo $query;

            $stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
            $stmt->execute();
            echo "<br>".$stmt->rowCount();
            //exit();
            // Captura os dados da consulta e inseri na tabela HTML
            if ($stmt->rowCount() > 0) {
                
                $msg = "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">Erro no abastecimento!</div>";
                
                echo $msg;
                exit();
                // Criar a mensagem de sucesso e atribuir para variável global
                $msg = base64_encode($msg);
                
                // Redireciona o o usuário para o arquivo index.php
                header("Location: pump_capt.php?msg=".$msg);
            }

?>
