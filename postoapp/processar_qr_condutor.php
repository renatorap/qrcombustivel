<?php
if(session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

// Incluir o arquivo para validar token
include_once 'validar_token.php';

// Validar token
if(!validarToken()){
    echo json_encode([
        'success' => false,
        'message' => 'Sessão expirada. Faça login novamente.'
    ]);
    exit();
}

// Receber o QR Code
$qr_code = filter_input(INPUT_POST, 'qr_code', FILTER_SANITIZE_STRING);

if(empty($qr_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'QR Code inválido.'
    ]);
    exit();
}

try {
    $condutor = null;
    $metodo_identificacao = '';
    
    // Tentar identificar pelo QR Code ativo primeiro
    $query = "SELECT 
                c.id_condutor,
                c.nome,
                c.cpf,
                c.cnh
              FROM condutor c
              INNER JOIN condutor_qrcode cq ON cq.id_condutor = c.id_condutor 
                                           AND cq.id_cliente = :id_cliente
                                           AND cq.codigo_unico = :codigo_unico
                                           AND cq.id_situacao = 1
                                           AND (cq.fim_vigencia IS NULL OR cq.fim_vigencia >= NOW())
              WHERE c.id_cliente = :id_cliente2 
              AND c.id_situacao = 1
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt->bindParam(':id_cliente2', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt->bindParam(':codigo_unico', $qr_code, PDO::PARAM_STR);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $condutor = $stmt->fetch(PDO::FETCH_ASSOC);
        $metodo_identificacao = 'QR Code';
    }
    
    // Se não encontrou pelo QR Code, tentar por CPF ou CNH
    if(!$condutor) {
        // Limpar o código recebido (remover caracteres especiais)
        $codigo_limpo = preg_replace('/[^0-9]/', '', $qr_code);
        
        // Buscar por CPF ou CNH - condutor ativo mesmo sem QR Code
        $query = "SELECT 
                    c.id_condutor,
                    c.nome,
                    c.cpf,
                    c.cnh
                  FROM condutor c
                  WHERE c.id_cliente = :id_cliente 
                  AND c.id_situacao = 1
                  AND (c.cpf = :codigo OR c.cnh = :codigo2)
                  LIMIT 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
        $stmt->bindParam(':codigo', $codigo_limpo, PDO::PARAM_STR);
        $stmt->bindParam(':codigo2', $codigo_limpo, PDO::PARAM_STR);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $condutor = $stmt->fetch(PDO::FETCH_ASSOC);
            $metodo_identificacao = ($condutor['cpf'] == $codigo_limpo) ? 'CPF' : 'CNH';
        }
    }
    
    if($condutor) {
        echo json_encode([
            'success' => true,
            'id_condutor' => $condutor['id_condutor'],
            'nome_condutor' => $condutor['nome'],
            'metodo' => $metodo_identificacao
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Condutor não encontrado ou inativo. Verifique o QR Code, CPF ou CNH.'
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar condutor: ' . $e->getMessage()
    ]);
}
?>
