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

// Receber o ID do veículo
$id_veiculo = filter_input(INPUT_POST, 'id_veiculo', FILTER_SANITIZE_NUMBER_INT);

if(empty($id_veiculo)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID do veículo não informado.'
    ]);
    exit();
}

try {
    // Buscar combustíveis do veículo que estejam disponíveis no contrato vigente do fornecedor
    $query = "SELECT DISTINCT
                p.id_produto,
                p.descricao
              FROM veiculo v
              INNER JOIN combustivel_veiculo cv ON cv.id_veiculo = v.id_veiculo
                                              AND cv.id_cliente = :id_cliente
              INNER JOIN produto p ON p.id_produto = cv.id_produto
              INNER JOIN preco_combustivel pc ON pc.id_produto = p.id_produto
                                              AND pc.inicio_vigencia <= CURDATE()
                                              AND (pc.fim_vigencia IS NULL OR pc.fim_vigencia >= CURDATE())
              INNER JOIN aditamento_combustivel ac ON ac.id_aditamento_combustivel = pc.id_aditamento_combustivel
              INNER JOIN contrato c ON c.id_contrato = ac.id_contrato
                                   AND c.id_fornecedor = :id_fornecedor
                                   AND c.id_cliente = :id_cliente2
              WHERE v.id_cliente = :id_cliente3
              AND v.id_veiculo = :id_veiculo
              AND v.id_situacao = 1
              ORDER BY p.descricao";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt->bindParam(':id_cliente2', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt->bindParam(':id_cliente3', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt->bindParam(':id_fornecedor', $_SESSION['id_forn'], PDO::PARAM_INT);
    $stmt->bindParam(':id_veiculo', $id_veiculo, PDO::PARAM_INT);
    $stmt->execute();
    
    $combustiveis = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $combustiveis[] = [
            'id_produto' => $row['id_produto'],
            'descricao' => $row['descricao']
        ];
    }
    
    if(count($combustiveis) > 0) {
        echo json_encode([
            'success' => true,
            'combustiveis' => $combustiveis
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Nenhum combustível cadastrado para este veículo.'
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar combustíveis: ' . $e->getMessage()
    ]);
}
?>
