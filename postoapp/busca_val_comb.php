<?php
// ARQUIVO LEGADO - NÃO É MAIS USADO NO SISTEMA NOVO
// Substituído por buscar_preco_combustivel.php
// Mantido apenas para compatibilidade com código antigo

if(session_status() === PHP_SESSION_NONE) session_start();

// Receber parâmetros (suporta GET e POST para compatibilidade)
$produto = $_GET["produto"] ?? $_POST["produto"] ?? null;
$id_veiculo = $_GET["id_veiculo"] ?? $_POST["id_veiculo"] ?? $_SESSION['id_veic'] ?? null;

if(empty($produto)) {
    print "0.00";
    exit();
}

include_once('conexao.php');

try {
    // QUERY para recuperar o preço do combustível
    $query_cval = "SELECT pc.valor
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
                        
    $stmt_cval = $conn->prepare($query_cval);
    $stmt_cval->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt_cval->bindParam(':id_veiculo', $id_veiculo, PDO::PARAM_INT);
    $stmt_cval->bindParam(':id_fornecedor', $_SESSION['id_forn'], PDO::PARAM_INT);
    $stmt_cval->bindParam(':id_produto', $produto, PDO::PARAM_INT);
    $stmt_cval->execute();

    if($row_cval = $stmt_cval->fetch(PDO::FETCH_NUM)) {
        print $row_cval[0];
    } else {
        print "0.00";
    }
} catch(PDOException $e) {
    error_log("Erro busca_val_comb.php: " . $e->getMessage());
    print "0.00";
}
?>