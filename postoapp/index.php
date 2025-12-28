<?php

$msg = $_GET['msg'];
//echo $msg;
//exit();

if (isset($conn)){
    mysqli_close($conn);
}
//echo "chegou aqui"; exit();

if(session_status() === PHP_SESSION_NONE) session_start();

ob_start();
//echo "chegou aqui 1"; exit();
// Verificar se a variável de sessão 'id_cond' existe
if (isset($_SESSION['id_cond'])) {
    unset($_SESSION['id_cond']);
}
//echo "chegou aqui 2"; exit();
// Verificar se a variável de sessão 'nome_cond' existe
if (isset($_SESSION['nome_cond'])) {
    unset($_SESSION['nome_cond']);
}
//echo "chegou aqui 3"; exit();
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

//Define timezone
date_default_timezone_set("America/Sao_Paulo");

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

// if (isset($conn)){
//     echo "Conexão OK";
// }
// exit();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="refresh" content="600; URL=index.php">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PostoApp | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.js">
    </script>
</head>
<body class="login-page">

    <?php
    // Receber os dados do formulário
$dados = filter_input_array(INPUT_POST, FILTER_DEFAULT);

// Acessa o IF quando o usuário clicou no botão "Acessar" do formulário
if (isset($dados['SendLogin'])) {
    // var_dump($dados);
    // exit();
    
    $login = $dados['usuario'];
    $passwd = hash("sha256", $dados['senha']);
    
    // echo "<br>" . $login . "<br>";
    // echo "<br>" . $passwd . "<br>";
    // exit();

    // QUERY para recuperar o usuário do banco de dados
    $query_usuario = "SELECT name, login, email, pswd FROM sec_users WHERE login = :usuario LIMIT 1";

    // Preparar a QUERY
    $result_usuario = $conn->prepare($query_usuario);

    // Substitui o link ":usuario" pelo valor que vem do formulário
    $result_usuario->bindParam(':usuario', $dados['usuario']);

    // Executar a QUERY
    $result_usuario->execute();
    // var_dump($result_usuario->rowCount());
    // exit();

    // Acessa o IF quando encontrou usuário no banco de dados
    if (($result_usuario) and ($result_usuario->rowCount() != 0)) {
        // Ler o resultado retornado do banco de dados
        $row_usuario = $result_usuario->fetch(PDO::FETCH_ASSOC);
        // var_dump($row_usuario);
        // exit();
        /*
            echo "<br>" . $passwd . "<br>";
            echo "<br>" .  $row_usuario['pswd'] . "<br>";
            echo "<br>" .  $row_usuario['name'] . "<br>";
            echo "<br>" .  $row_usuario['login'] . "<br>";
            echo "<br>" .  $row_usuario['email'] . "<br>";
        if ($passwd === $row_usuario['pswd']) {
            echo "OK";
        } else {
            echo "ERRADO";
        }
        
        */

        // echo $passwd."<br>";
        // echo $row_usuario['pswd'];
        // exit();

        // Verificar se a senha digitada pelo usuário no formulário é igual a senha salva no banco de dados
        if ($passwd === $row_usuario['pswd']) {
            // O JWT é divido em três partes separadas por ponto ".": um header, um payload e uma signature
            // Header indica o tipo do token "JWT", e o algoritmo utilizado "HS256"
            $header = [
                'alg' => 'HS256',
                'typ' => 'JWT'
                ];
            // var_dump($header);
            // exit();
            // Converter o array em objeto
            $header = json_encode($header);
            //var_dump($header);

            // Codificar dados em base64
            $header = base64_encode($header);

            // Imprimir o header
            // var_dump($header);
            // exit();

            // O payload é o corpo do JWT, recebe as informações que precisa armazenar
            // iss - O domínio da aplicação que gera o token
            // aud - Define o domínio que pode usar o token
            // exp - Data de vencimento do token
            // 7 days; 24 hours; 60 mins; 60secs
            $duracao = time() + (12 * 60 * 60);
            // 5 segundos
            // $duracao = time() + (5);

            $payload = [
                /*'iss' => 'localhost',
                'aud' => 'localhost',*/
                'exp' => $duracao,
                'id' => $row_usuario['login'],
                'nome' => $row_usuario['name'],
                'email' => $row_usuario['email']
            ];

            // Converter o array em objeto
            $payload = json_encode($payload);
            //var_dump($payload);

            // Codificar dados em base64
            $payload = base64_encode($payload);

            // Imprimir o payload
            //var_dump($payload);

            // O signature é a assinatura. 
            // Chave secreta e única
            $chave = "AMORA24915651000170J";
            
            // Pegar o header e o payload e codificar com o algoritmo sha256, junto com a chave
            // Gera um valor de hash com chave usando o método HMAC
            $signature = hash_hmac('sha256', "$header.$payload", $chave, true);

            // Codificar dados em base64
            $signature = base64_encode($signature);

            // Imprimir o signature
            //var_dump($signature);

            // Imprimir o token
            // echo "Token: $header.$payload.$signature <br>";
            // exit();
            // Salvar o token em cookies
            // Cria o cookie com duração 12 horas
            setcookie('token', "$header.$payload.$signature", (time() + (12 * 60 * 60)));

            // Redirecionar o usuário para página dashboard
            @header("Location: pump_capt.php");

        } else {
            // Criar a mensagem de erro e atribuir para variável global "msg"
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Erro: Usuário ou senha inválida!</div>";
        }
    } else {
        // Criar a mensagem de erro e atribuir para variável global "msg"
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Erro: Usuário ou senha inválida!</div>";
    }
}


?>
    <div class="login-container">
        <div class="text-center mb-4">
            <img src="img/QR_Combustivel.png" alt="Logo" class="img-fluid login-logo">
        </div>
        
        <div class="form-container">
            <div class="card">
                <div class="card-header">
                    <h5 class="text-center mb-0">LOGIN</h5>
                </div>
                <div class="card-body">
                    <div class="form-floating" id="alerta">
                            <?php
                            // Verificar se existe a variável global "msg" e acessa o IF
                            if (isset($_SESSION['msg'])) {
                                // Imprimir o valor da variável global "msg"
                                echo $_SESSION['msg'];

                                // Limpar a variável global "msg"
                                unset($_SESSION['msg']);
                            }
                            else if (isset($msg))
                            {
                                echo "<div class=\"alert alert-success alert-dismissible fade show\" role=\"alert\">".$msg."</div>";
                            }
                            
                            ?>
                    </div>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="usuario" class="form-label">USUÁRIO</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="usuario" name="usuario" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="senha" class="form-label">SENHA</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" name="SendLogin">ENTRAR</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>

    window.setTimeout(function() {
        $("#alerta").fadeTo(1000, 0).slideUp(1000, function(){
            $(this).remove();
        });
    }, 5000);
    
</script>
</body>
</html>