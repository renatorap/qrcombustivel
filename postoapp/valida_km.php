<?php

if(session_status() === PHP_SESSION_NONE) session_start();

// Limpara o buffer de redirecionamento
//ob_start();

// Verifica se existe a variável litragem_agora
if (isset($_GET["litragem"])) {
    $litragem_agora = $_GET["litragem"];
}

// Verifica se existe a variável km_agora
if (isset($_GET["km"])) {
    $km_agora = $_GET["km"];
}

include_once('conexao.php');

    // QUERY para recuperar o MÉDIA GERAL DE KM/LITRO do banco de dados
    $query_media_km =   "
                        SELECT
                            AVG(km_litro_ant) AS avg_km_litro
                        FROM
                                (
                                SELECT 
                                    `data`,
                                    hora,
                                    km_litro,
                                    LAG(km_litro) OVER (ORDER BY `data`, hora) AS km_litro_ant
                                FROM
                                        (
                                        SELECT
                                            `data`,
                                            hora,
                                            litragem,
                                            km_veiculo AS km_atual,
                                            LAG(km_veiculo) OVER (ORDER BY `data`, hora) AS km_ultimo_abastecimento,
                                            km_veiculo - LAG(km_veiculo) OVER (ORDER BY `data`, hora) AS km_percorrido,
                                            (km_veiculo - LAG(km_veiculo) OVER (ORDER BY `data`, hora)) / litragem AS km_litro
                                        FROM consumo_combustivel
                                        WHERE id_cliente = 4
                                        AND id_veiculo = 2412
                                        AND DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                        ORDER BY `data` DESC, hora DESC
                                        ) r
                                ) f"
                    ;
                    //echo "<br>".$query_media_km."<br>";
    $stmt_media_km = $conn->prepare($query_media_km, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
    $stmt_media_km->execute();

    // Captura os dados da consulta e inseri na tabela HTML
    while ($row_media_km = $stmt_media_km->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {

        $media_km = $row_media_km[0];

    }


        // QUERY para recuperar o MÉDIA DE KM/LITRO do banco de dados
    $query_km =   "
                    SELECT 
                        ((km_agora - km_atual) / litragem_agora) AS km_litro_agora
                    FROM
                            (
                            SELECT
                                `data`,
                                hora,
                                " . $litragem_agora . " AS litragem_agora,
                                " . $km_agora . " AS km_agora,
                                km_veiculo AS km_atual
                            FROM consumo_combustivel
                            WHERE id_cliente = 4
                            AND id_veiculo = 2412
                            ORDER BY `data` DESC, hora DESC
                            LIMIT 1
                            ) r
                    ";
                    //echo "<br>".$query_km."<br>";
    $stmt_km = $conn->prepare($query_km, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
    $stmt_km->execute();

    // Captura os dados da consulta e inseri na tabela HTML
    while ($row_km = $stmt_km->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT)) {

        $km = $row_km[0];

    }

//echo "Média Geral de KM/LITRO: " . number_format($media_km, 2, ',', '.') . " | ";
//echo "KM/LITRO do Abastecimento Atual: " . number_format($km, 2, ',', '.') . " | ";

// Valida o KM/LITRO do abastecimento atual com a MÉDIA GERAL DE KM/LITRO
if (($km < ($media_km * 1.5)) and ($km > ($media_km * 0.5))) {
    print "OK";
} else {
    print "NOK";
}

?>