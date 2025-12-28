<?php

if(session_status() === PHP_SESSION_NONE) { session_start(); };

// Limpara o buffer de redirecionamento
ob_start();

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

//VERIFICA PÁGINA ORIGEM
$file_autorizado = 'pump_capt.php';
$http_referer = substr( basename($_SERVER["HTTP_REFERER"]), 0, 13 );
if ($http_referer == $file_autorizado and $_SERVER['REQUEST_METHOD'] == 'POST')
{

    // Verifica se existe a variável selprod
    if (isset($_POST["placa_veiculo"])) {
        $placa_veiculo = htmlspecialchars($_POST["placa_veiculo"], ENT_QUOTES, 'UTF-8');
    //echo $placa_veiculo."<br>";
    
    }
    
    if(isset($_POST["condutor"])) {
        $condutor = htmlspecialchars($_POST["condutor"], ENT_QUOTES, 'UTF-8');
    //echo $condutor."<br>";
    
    }
    
    if(isset($_POST["produto"])) {
        $produto = htmlspecialchars($_POST["produto"], ENT_QUOTES, 'UTF-8');
    //echo $produto."<br>";
    
    }
    
    if(isset($_POST["data"])) {
        $data = "'" . htmlspecialchars($_POST["data"], ENT_QUOTES, 'UTF-8') . "'";
    //echo $data."<br>";
    
    }
    
    if(isset($_POST["hora"])) {
        $hora = "'" . htmlspecialchars($_POST["hora"], ENT_QUOTES, 'UTF-8') . "'";
    //echo $hora."<br>";
    
    }
    
    if(isset($_POST["km_atual"])) {
        $km_atual = htmlspecialchars($_POST["km_atual"], ENT_QUOTES, 'UTF-8');
    //echo $km_atual."<br>";
    
    }
    
    if(isset($_POST["litragem"])) {
        $litragem = htmlspecialchars($_POST["litragem"], ENT_QUOTES, 'UTF-8');

        // Query busca capacidade tanque combustível
        $sql = "SELECT capacidade_combustivel FROM veiculo WHERE id_cliente = " . $_SESSION['id_empr'] . " AND id_veiculo = " . $_SESSION['id_veic'];
        
        $st = $conn->prepare($sql);
        $st->execute();
        $row_cap = $st->fetch(PDO::FETCH_ASSOC);
        $capacidade_combustivel = $row_cap['capacidade_combustivel'];
        //echo $capacidade_combustivel."<br>";
        //echo $litragem;
        //exit();

        // Valida capacidade tanque
        if ($capacidade_combustivel < $litragem) {
            
            $msg = "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">Verifique a Litragem!</div>";
            
            //echo $msg;
            
            // Criar a mensagem de sucesso e atribuir para variável global
            $_SESSION['msg'] = $msg;

            // Redireciona o o usuário para o arquivo index.php
            @header("Location: pump_capt.php");
            
            exit();
        }
    }
    
    //Verifica abastecimento anterior
    $query = "
                SELECT 
                	count(concat(data, ' ', hora)) as qtde_data_hora
                  FROM consumo_combustivel
                 WHERE date_add(concat(data, ' ', hora), interval 5 hour) > current_timestamp()
                   AND id_cliente = " . $_SESSION['id_empr'] . " AND id_veiculo = " . $_SESSION['id_veic'];
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $qtde_data_hora = $row['qtde_data_hora'];
    // echo $qtde_data_hora;
    // exit();
    
    // Valida abastecidas anteriores
    if ($qtde_data_hora >= 1) {
        
        $msg = "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">Veículo já abastecido!</div>";
        
        //echo $msg;
        
        // Criar a mensagem de sucesso e atribuir para variável global
        $_SESSION['msg'] = $msg;


        // Verificar se a variável de sessão 'id_cond' existe
        if (isset($_SESSION['id_cond'])) {
            unset($_SESSION['id_cond']);
        }

        // Verificar se a variável de sessão 'nome_cond' existe
        if (isset($_SESSION['nome_cond'])) {
            unset($_SESSION['nome_cond']);
        }

        // Verificar se a variável de sessão 'id_veic' existe
        if (isset($_SESSION['id_veic'])) {
            unset($_SESSION['id_veic']);
        }

        // Verificar se a variável de sessão 'placa_veic' existe
        if (isset($_SESSION['placa_veic'])) {
            unset($_SESSION['placa_veic']);
        }
        
        // Verificar se a variável de sessão 'get_id_condutor' existe
        if (isset($_SESSION['get_id_condutor'])) {
            unset($_SESSION['get_id_condutor']);
        }

        // Verificar se a variável de sessão 'get_id_veiculo' existe
        if (isset($_SESSION['get_id_veiculo'])) {
            unset($_SESSION['get_id_veiculo']);
        }

        // Redireciona o o usuário para o arquivo index.php
        @header("Location: pump_capt.php");
        
        exit();
    }
    
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
                     WHERE pc.fim_vigencia IS NULL
                       AND cv.id_cliente = " . $_SESSION['id_empr'] . " 
                       AND cv.id_veiculo = " . $_SESSION['id_veic'] . " 
                       AND c.id_fornecedor = " . $_SESSION['id_forn'] . "
                       AND p.id_produto = ". $produto ;
                    //echo "<br>".$query_cval."<br>";
    $stmt_cval = $conn->prepare($query_cval, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
    $stmt_cval->execute();
    
    // Captura os dados da consulta e inseri na tabela HTML
    while ($row_cval = $stmt_cval->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {
    
        $id_preco_combustivel = $row_cval[0];
        $valor_unitario = $row_cval[1];
    
    }
    
    $valor_total = $valor_unitario * $litragem;
    
    //$msg = $_SESSION['id_forn']."<br>";
    
    //$msg .= $_SESSION['id_veic']."<br>";
    
    //$msg .= $placa_veiculo."<br>";
    
    //$msg .= $_SESSION['id_cond']."<br>";
    
    //$msg .= $condutor."<br>";
    
    //$msg .= $produto."<br>";
    
    //$msg .= $id_preco_combustivel."<br>";
    
    //$msg .= $data."<br>";
    
    //$msg .= $hora."<br>";
    
    //$msg .= $km_atual."<br>";
    
    //$msg .= $litragem."<br>";
    
    //$msg .= $valor_unitario."<br>";
    
    //$msg .= $valor_total."<br>";
    
    //echo "<br>".$msg."<br>";

        //Verifica abastecimento anterior
    $qr_km_ant = "
                SELECT 
                	km_veiculo as km_veiculo_ant
                  FROM consumo_combustivel
                 WHERE id_cliente = " . $_SESSION['id_empr'] . "
                 AND id_veiculo = " . $_SESSION['id_veic'] . "
                 ORDER BY data DESC, hora DESC LIMIT 1";
    
    $stmt_km_ant = $conn->prepare($qr_km_ant);
    $stmt_km_ant->execute();
    $row_km_ant = $stmt_km_ant->fetch(PDO::FETCH_ASSOC);
    $km_ant = $row_km_ant['km_veiculo_ant'];
    // echo $km_ant;
    // exit();

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
            `km_veiculo_ant`,
            `id_produto`,
            `valor_unitario`,
            `valor_total`,
            `data_hora_ins`,
            `id_cliente`,
            `id_user`
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
            " . $km_ant . ",
            " . $produto . ",
            " . $valor_unitario . ",
            " . $valor_total . ",
            current_timestamp(),
            " . $_SESSION['id_empr'] . ",
            '" . $_SESSION['login_user'] . "'
        )
    ";
    //echo $insert_cc;
    //exit();
    if ($conn->query($insert_cc)) {
        //$msg = "Abastecimento realizado!";
        $msg = "<div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">Abastecimento realizado!</div>";
    } else {
        //$msg = "Erro no abastecimento!";
        $msg = "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">Erro no abastecimento!</div>";
    }

    // Verificar se a variável de sessão 'id_cond' existe
    if (isset($_SESSION['id_cond'])) {
        unset($_SESSION['id_cond']);
    }

    // Verificar se a variável de sessão 'nome_cond' existe
    if (isset($_SESSION['nome_cond'])) {
        unset($_SESSION['nome_cond']);
    }

    // Verificar se a variável de sessão 'id_veic' existe
    if (isset($_SESSION['id_veic'])) {
        unset($_SESSION['id_veic']);
    }

    // Verificar se a variável de sessão 'placa_veic' existe
    if (isset($_SESSION['placa_veic'])) {
        unset($_SESSION['placa_veic']);
    }
    
    // Verificar se a variável de sessão 'get_id_condutor' existe
    if (isset($_SESSION['get_id_condutor'])) {
        unset($_SESSION['get_id_condutor']);
    }

    // Verificar se a variável de sessão 'get_id_veiculo' existe
    if (isset($_SESSION['get_id_veiculo'])) {
        unset($_SESSION['get_id_veiculo']);
    }

    // Criar a mensagem de sucesso e atribuir para variável global
    $_SESSION['msg'] = $msg;
    
    // Redireciona o o usuário para o arquivo index.php
    @header("Location: pump_capt.php");

    }
    else
    {
    $msg = "<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">Tente novamente</div>";

    // Verificar se a variável de sessão 'id_cond' existe
    if (isset($_SESSION['id_cond'])) {
        unset($_SESSION['id_cond']);
    }

    // Verificar se a variável de sessão 'nome_cond' existe
    if (isset($_SESSION['nome_cond'])) {
        unset($_SESSION['nome_cond']);
    }

    // Verificar se a variável de sessão 'id_veic' existe
    if (isset($_SESSION['id_veic'])) {
        unset($_SESSION['id_veic']);
    }

    // Verificar se a variável de sessão 'placa_veic' existe
    if (isset($_SESSION['placa_veic'])) {
        unset($_SESSION['placa_veic']);
    }

    // Verificar se a variável de sessão 'get_id_condutor' existe
    if (isset($_SESSION['get_id_condutor'])) {
        unset($_SESSION['get_id_condutor']);
    }

    // Verificar se a variável de sessão 'get_id_veiculo' existe
    if (isset($_SESSION['get_id_veiculo'])) {
        unset($_SESSION['get_id_veiculo']);
    }

    $_SESSION['msg'] = $msg;
    @header("Location: pump_capt.php");
    }
?>