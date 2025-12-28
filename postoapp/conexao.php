<?php

// Credencias do banco de dados
$host = "localhost";
$user = "renatorap";
$pass = "J@melancia01";
$dbname = "conceit1_combustivel";
$port = 3306;

try{
    // Conexão com a porta
    //$conn = new PDO("mysql:host=$host;port=$port;dbname=" . $dbname, $user, $pass);

    // Conexão sem a porta
    $conn = new PDO("mysql:host=$host;dbname=" . $dbname, $user, $pass);
    // echo "Conexão com banco de dados realizado com sucesso!";
}catch(PDOException $err){
    echo "Erro: Conexão com banco de dados não realizado com sucesso. Erro gerado " . $err->getMessage();
}