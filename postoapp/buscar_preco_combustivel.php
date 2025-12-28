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

// Receber parâmetros
$id_produto = filter_input(INPUT_POST, 'id_produto', FILTER_SANITIZE_NUMBER_INT);
$id_veiculo = filter_input(INPUT_POST, 'id_veiculo', FILTER_SANITIZE_NUMBER_INT);

// DEBUG
error_log("buscar_preco_combustivel.php - POST: " . json_encode($_POST));
error_log("buscar_preco_combustivel.php - Params: id_produto=$id_produto, id_veiculo=$id_veiculo, id_empr=" . ($_SESSION['id_empr'] ?? 'NULL') . ", id_forn=" . ($_SESSION['id_forn'] ?? 'NULL'));

if(empty($id_produto) || empty($id_veiculo)) {
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetros inválidos.'
    ]);
    exit();
}

try {
    // Buscar preço do combustível via combustivel_veiculo
    // Regra: usuário só pode abastecer veículos com contrato do seu fornecedor
    $query = "SELECT pc.valor
              FROM combustivel_veiculo cv
              INNER JOIN produto p ON p.id_produto = cv.id_produto
              INNER JOIN preco_combustivel pc ON pc.id_produto = cv.id_produto
              INNER JOIN aditamento_combustivel ac ON ac.id_aditamento_combustivel = pc.id_aditamento_combustivel
              INNER JOIN contrato c ON c.id_contrato = ac.id_contrato
              WHERE ((pc.inicio_vigencia <= CAST(CURDATE() AS DATETIME)) 
                AND (pc.fim_vigencia IS NULL OR pc.fim_vigencia >= CAST(CURDATE() AS DATETIME)))
              AND cv.id_cliente = :id_cliente
              AND cv.id_veiculo = :id_veiculo
              AND c.id_fornecedor = :id_fornecedor
              AND p.id_produto = :id_produto
              ORDER BY pc.inicio_vigencia DESC
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt->bindParam(':id_veiculo', $id_veiculo, PDO::PARAM_INT);
    $stmt->bindParam(':id_fornecedor', $_SESSION['id_forn'], PDO::PARAM_INT);
    $stmt->bindParam(':id_produto', $id_produto, PDO::PARAM_INT);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("buscar_preco_combustivel.php - Preço encontrado: " . $row['valor']);
        
        echo json_encode([
            'success' => true,
            'valor' => $row['valor']
        ]);
    } else {
        error_log("buscar_preco_combustivel.php - NENHUM PREÇO ENCONTRADO");
        echo json_encode([
            'success' => false,
            'message' => 'Preço não encontrado. Verifique se existe contrato vigente para este combustível.'
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar preço: ' . $e->getMessage()
    ]);
}
?>
