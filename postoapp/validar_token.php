<?php

// Função para validar o token do sistema unificado
function validarToken(){
    // Verifica se há token na sessão (sistema novo)
    if(isset($_SESSION['token']) && !empty($_SESSION['token'])) {
        // Sistema novo - validar token da sessão
        require_once __DIR__ . '/../admin_abastecimento/config/config.php';
        require_once __DIR__ . '/../admin_abastecimento/config/security.php';
        
        $payload = Security::validateToken($_SESSION['token']);
        
        if($payload !== false) {
            return true;
        }
        
        return false;
    }
    
    // Sistema antigo - validar token do cookie
    if(!isset($_COOKIE['token']) || empty($_COOKIE['token'])) {
        return false;
    }
    
    // Recuperar o token do cookie
    $token = $_COOKIE['token'];

    // Converter o token em array
    $token_array = explode('.', $token);
    
    if(count($token_array) != 3) {
        return false;
    }
    
    $header = $token_array[0];
    $payload = $token_array[1];
    $signature = $token_array[2];

    // Chave secreta e única (sistema antigo)
    $chave = "AMORA24915651000170J";

    // Usar o header e o payload e codificar com o algoritmo sha256
    $validar_assinatura = hash_hmac('sha256', "$header.$payload", $chave, true);

    // Codificar dados em base64
    $validar_assinatura = base64_encode($validar_assinatura);

    // Comparar a assinatura do token recebido com a assinatura gerada.
    // Acessa o IF quando o token é válido
    if($signature == $validar_assinatura){

        // decodificar dados de base64
        $dados_token = base64_decode($payload);

        // Converter objeto em array
        $dados_token = json_decode($dados_token);

        // Comparar a data de vencimento do token com a data atual
        // Acessa o IF quando a data do token é maior do que a data atual
        if($dados_token->exp > time()){
            // Retorna TRUE indicando que o token é válido
            return true;
        }else{
            // Acessa o ELSE quando a data do token é menor ou igual a data atual
            // Retorna FALSE indicando que o token é inválido
            return false;
        }        
    }else{ 
        // Acessa o ELSE quando o token é inválido
        // Retorna FALSE indicando que o token é inválido
        return false;
    }    
}

// Recuperar o nome salvo no token
function recuperarNomeToken(){
    // Sistema novo - da sessão
    if(isset($_SESSION['nome']) && !empty($_SESSION['nome'])) {
        return $_SESSION['nome'];
    }
    
    // Sistema antigo - do cookie
    if(!isset($_COOKIE['token'])) {
        return '';
    }
    
    // Recuperar o token do cookie
    $token = $_COOKIE['token'];

    // Converter o token em array
    $token_array = explode('.', $token);
    
    if(count($token_array) < 2) {
        return '';
    }
    
    $payload = $token_array[1];

    // decodificar dados de base64
    $dados_token = base64_decode($payload);

    // Converter objeto em array
    $dados_token = json_decode($dados_token);

    // Retorna o nome do usuário salvo no token
    return $dados_token->nome ?? '';
}

// Recuperar o email salvo no token
function recuperarEmailToken(){
    // Sistema novo - da sessão
    if(isset($_SESSION['login']) && !empty($_SESSION['login'])) {
        return $_SESSION['login'];
    }
    
    // Sistema antigo - do cookie
    if(!isset($_COOKIE['token'])) {
        return '';
    }
    
    // Recuperar o token do cookie
    $token = $_COOKIE['token'];

    // Converter o token em array
    $token_array = explode('.', $token);
    
    if(count($token_array) < 2) {
        return '';
    }
    
    $payload = $token_array[1];

    // decodificar dados de base64
    $dados_token = base64_decode($payload);

    // Converter objeto em array
    $dados_token = json_decode($dados_token);

    // Retorna o nome do usuário salvo no token
    return $dados_token->email ?? '';
}