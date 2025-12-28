<?php

if(session_status() === PHP_SESSION_NONE) session_start();

// Limpar o buffer de redirecionamento
ob_start();

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

// Incluir o arquivo para validar token
include_once 'validar_token.php';

// Validar token
if(!validarToken()){
    $_SESSION['msg'] = "<div class=\"alert alert-danger\" role=\"alert\">Sessão expirada. Faça login novamente.</div>";
    @header("Location: index.php");
    exit();
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['msg'] = "<div class=\"alert alert-danger\" role=\"alert\">Método não permitido.</div>";
    @header("Location: captura_abastecimento.php");
    exit();
}

// Receber e validar dados do formulário
$id_condutor = filter_input(INPUT_POST, 'id_condutor', FILTER_SANITIZE_NUMBER_INT);
$id_veiculo = filter_input(INPUT_POST, 'id_veiculo', FILTER_SANITIZE_NUMBER_INT);
$id_produto = filter_input(INPUT_POST, 'produto', FILTER_SANITIZE_NUMBER_INT);
$data = filter_input(INPUT_POST, 'data', FILTER_SANITIZE_STRING);
$hora = filter_input(INPUT_POST, 'hora', FILTER_SANITIZE_STRING);
$km_atual = filter_input(INPUT_POST, 'km_atual', FILTER_SANITIZE_NUMBER_INT);
$litragem = filter_input(INPUT_POST, 'litragem', FILTER_SANITIZE_STRING);
$valor_unitario = filter_input(INPUT_POST, 'valor_unitario', FILTER_SANITIZE_STRING);
$valor_total = filter_input(INPUT_POST, 'valor_total', FILTER_SANITIZE_STRING);

// Validar campos obrigatórios - usando verificação que aceita 0 como valor válido
if($id_condutor === null || $id_condutor === '' || $id_condutor === false ||
   $id_veiculo === null || $id_veiculo === '' || $id_veiculo === false ||
   $id_produto === null || $id_produto === '' || $id_produto === false ||
   empty($data) || empty($hora) || 
   $km_atual === null || $km_atual === '' || $km_atual === false ||
   empty($litragem)) {
    
    // Debug para identificar qual campo está vazio
    $campos_vazios = [];
    if($id_condutor === null || $id_condutor === '' || $id_condutor === false) $campos_vazios[] = 'Condutor';
    if($id_veiculo === null || $id_veiculo === '' || $id_veiculo === false) $campos_vazios[] = 'Veículo';
    if($id_produto === null || $id_produto === '' || $id_produto === false) $campos_vazios[] = 'Combustível';
    if(empty($data)) $campos_vazios[] = 'Data';
    if(empty($hora)) $campos_vazios[] = 'Hora';
    if($km_atual === null || $km_atual === '' || $km_atual === false) $campos_vazios[] = 'Km Atual';
    if(empty($litragem)) $campos_vazios[] = 'Litragem';
    
    $msg_campos = !empty($campos_vazios) ? ' Campos faltando: ' . implode(', ', $campos_vazios) : '';
    
    // Salvar dados na sessão para recuperar após erro
    $_SESSION['form_data'] = $_POST;
    
    $_SESSION['msg'] = "<div class=\"alert alert-danger\" role=\"alert\">Todos os campos são obrigatórios.{$msg_campos}</div>";
    @header("Location: captura_abastecimento.php");
    exit();
}

// Converter valores para float
$litragem = floatval(str_replace(',', '.', $litragem));
$valor_unitario = floatval(str_replace(',', '.', $valor_unitario));
$valor_total = floatval(str_replace(',', '.', $valor_total));

try {
    // Validar capacidade do tanque
    $query_cap = "SELECT capacidade_combustivel FROM veiculo 
                  WHERE id_cliente = :id_cliente AND id_veiculo = :id_veiculo";
    
    $stmt_cap = $conn->prepare($query_cap);
    $stmt_cap->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt_cap->bindParam(':id_veiculo', $id_veiculo, PDO::PARAM_INT);
    $stmt_cap->execute();
    
    $row_cap = $stmt_cap->fetch(PDO::FETCH_ASSOC);
    $capacidade_combustivel = $row_cap['capacidade_combustivel'];
    
    if ($capacidade_combustivel < $litragem) {
        $_SESSION['form_data'] = $_POST;
        $_SESSION['msg'] = "<div class=\"alert alert-danger\" role=\"alert\">Litragem informada (" . $litragem . "L) excede a capacidade do tanque (" . $capacidade_combustivel . "L).</div>";
        @header("Location: captura_abastecimento.php");
        exit();
    }
    
    // Validar se não há abastecimento recente (menos de 3 horas)
    $query_recent = "SELECT COUNT(CONCAT(data, ' ', hora)) as qtde_data_hora
                     FROM consumo_combustivel
                     WHERE DATE_ADD(CONCAT(data, ' ', hora), INTERVAL 3 HOUR) > CURRENT_TIMESTAMP()
                     AND id_cliente = :id_cliente 
                     AND id_veiculo = :id_veiculo";
    
    $stmt_recent = $conn->prepare($query_recent);
    $stmt_recent->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt_recent->bindParam(':id_veiculo', $id_veiculo, PDO::PARAM_INT);
    $stmt_recent->execute();
    
    $row_recent = $stmt_recent->fetch(PDO::FETCH_ASSOC);
    
    if ($row_recent['qtde_data_hora'] >= 1) {
        $_SESSION['form_data'] = $_POST;
        $_SESSION['msg'] = "<div class=\"alert alert-warning\" role=\"alert\">Este veículo já foi abastecido há menos de 3 horas.</div>";
        @header("Location: captura_abastecimento.php");
        exit();
    }
    
    // Validar KM atual maior que último abastecimento
    $query_km = "SELECT MAX(km_veiculo) as ultimo_km
                 FROM consumo_combustivel
                 WHERE id_cliente = :id_cliente 
                 AND id_veiculo = :id_veiculo";
    
    $stmt_km = $conn->prepare($query_km);
    $stmt_km->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt_km->bindParam(':id_veiculo', $id_veiculo, PDO::PARAM_INT);
    $stmt_km->execute();
    
    $row_km = $stmt_km->fetch(PDO::FETCH_ASSOC);
    
    if ($row_km['ultimo_km'] && $km_atual <= $row_km['ultimo_km']) {
        $_SESSION['form_data'] = $_POST;
        $_SESSION['msg'] = "<div class=\"alert alert-danger\" role=\"alert\">KM atual (" . $km_atual . ") deve ser maior que o último registrado (" . $row_km['ultimo_km'] . ").</div>";
        @header("Location: captura_abastecimento.php");
        exit();
    }
    
    // Buscar ID do preço do combustível via combustivel_veiculo
    // Regra: usuário só pode abastecer veículos com contrato do seu fornecedor
    $query_preco = "SELECT pc.id_preco_combustivel
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
    
    $stmt_preco = $conn->prepare($query_preco);
    $stmt_preco->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt_preco->bindParam(':id_veiculo', $id_veiculo, PDO::PARAM_INT);
    $stmt_preco->bindParam(':id_fornecedor', $_SESSION['id_forn'], PDO::PARAM_INT);
    $stmt_preco->bindParam(':id_produto', $id_produto, PDO::PARAM_INT);
    $stmt_preco->execute();
    
    if($stmt_preco->rowCount() == 0) {
        $_SESSION['form_data'] = $_POST;
        $_SESSION['msg'] = "<div class=\"alert alert-danger\" role=\"alert\">Preço do combustível não encontrado no contrato vigente.</div>";
        @header("Location: captura_abastecimento.php");
        exit();
    }
    
    $row_preco = $stmt_preco->fetch(PDO::FETCH_ASSOC);
    $id_preco_combustivel = $row_preco['id_preco_combustivel'];
    
    // Iniciar transação
    $conn->beginTransaction();
    
    // Inserir registro de abastecimento
    $query_insert = "INSERT INTO consumo_combustivel (
                        id_cliente,
                        id_fornecedor,
                        id_condutor,
                        id_veiculo,
                        id_preco_combustivel,
                        id_produto,
                        data,
                        hora,
                        km_veiculo,
                        litragem,
                        valor_unitario,
                        valor_total,
                        id_user
                    ) VALUES (
                        :id_cliente,
                        :id_fornecedor,
                        :id_condutor,
                        :id_veiculo,
                        :id_preco_combustivel,
                        :id_produto,
                        :data,
                        :hora,
                        :km_veiculo,
                        :litragem,
                        :valor_unitario,
                        :valor_total,
                        :id_user
                    )";
    
    $stmt_insert = $conn->prepare($query_insert);
    $stmt_insert->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt_insert->bindParam(':id_fornecedor', $_SESSION['id_forn'], PDO::PARAM_INT);
    $stmt_insert->bindParam(':id_condutor', $id_condutor, PDO::PARAM_INT);
    $stmt_insert->bindParam(':id_veiculo', $id_veiculo, PDO::PARAM_INT);
    $stmt_insert->bindParam(':id_preco_combustivel', $id_preco_combustivel, PDO::PARAM_INT);
    $stmt_insert->bindParam(':id_produto', $id_produto, PDO::PARAM_INT);
    $stmt_insert->bindParam(':data', $data, PDO::PARAM_STR);
    $stmt_insert->bindParam(':hora', $hora, PDO::PARAM_STR);
    $stmt_insert->bindParam(':km_veiculo', $km_atual, PDO::PARAM_INT);
    $stmt_insert->bindParam(':litragem', $litragem, PDO::PARAM_STR);
    $stmt_insert->bindParam(':valor_unitario', $valor_unitario, PDO::PARAM_STR);
    $stmt_insert->bindParam(':valor_total', $valor_total, PDO::PARAM_STR);
    $stmt_insert->bindParam(':id_user', $_SESSION['login_user'], PDO::PARAM_STR);
    
    $stmt_insert->execute();
    
    // Confirmar transação
    $conn->commit();
    
    // Limpar dados do formulário após sucesso
    unset($_SESSION['form_data']);
    
    $_SESSION['msg'] = "<div class=\"alert alert-success\" role=\"alert\">Abastecimento registrado com sucesso! Total: R$ " . number_format($valor_total, 2, ',', '.') . "</div>";
    @header("Location: captura_abastecimento.php");
    exit();
    
} catch(PDOException $e) {
    // Reverter transação em caso de erro
    if($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $_SESSION['form_data'] = $_POST;
    $_SESSION['msg'] = "<div class=\"alert alert-danger\" role=\"alert\">Erro ao registrar abastecimento: " . $e->getMessage() . "</div>";
    @header("Location: captura_abastecimento.php");
    exit();
}
?>
