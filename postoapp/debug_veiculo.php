<?php
if(session_status() === PHP_SESSION_NONE) session_start();

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

// Incluir o arquivo para validar token
include_once 'validar_token.php';

// Validar token
if(!validarToken()){
    die("Sessão expirada. Faça login novamente.");
}

echo "<h2>Debug - Veículo SWV3G56</h2>";

echo "<h3>Dados da Sessão:</h3>";
echo "id_empr: " . (isset($_SESSION['id_empr']) ? $_SESSION['id_empr'] : 'NOT SET') . "<br>";
echo "nome_empr: " . (isset($_SESSION['nome_empr']) ? $_SESSION['nome_empr'] : 'NOT SET') . "<br>";
echo "id_forn: " . (isset($_SESSION['id_forn']) ? $_SESSION['id_forn'] : 'NOT SET') . "<br>";
echo "nome_forn: " . (isset($_SESSION['nome_forn']) ? $_SESSION['nome_forn'] : 'NOT SET') . "<br>";
echo "login_user: " . (isset($_SESSION['login_user']) ? $_SESSION['login_user'] : 'NOT SET') . "<br>";
echo "nome_user: " . (isset($_SESSION['nome_user']) ? $_SESSION['nome_user'] : 'NOT SET') . "<br>";

echo "<h3>Dados do Veículo SWV3G56:</h3>";
$placa = 'SWV3G56';
$query = "SELECT v.id_veiculo, v.placa, v.id_situacao, v.id_cliente, c.nome_fantasia as cliente
          FROM veiculo v
          INNER JOIN clientes c ON c.id = v.id_cliente
          WHERE REPLACE(REPLACE(UPPER(v.placa), '-', ''), ' ', '') = :placa 
          LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':placa', $placa, PDO::PARAM_STR);
$stmt->execute();
$veiculo = $stmt->fetch(PDO::FETCH_ASSOC);

if($veiculo) {
    echo "id_veiculo: " . $veiculo['id_veiculo'] . "<br>";
    echo "placa: " . $veiculo['placa'] . "<br>";
    echo "id_situacao: " . $veiculo['id_situacao'] . "<br>";
    echo "id_cliente: " . $veiculo['id_cliente'] . "<br>";
    echo "cliente: " . $veiculo['cliente'] . "<br>";
} else {
    echo "Veículo não encontrado!<br>";
}

echo "<h3>Verificação de Filtro:</h3>";
if($veiculo && isset($_SESSION['id_empr'])) {
    if($veiculo['id_cliente'] == $_SESSION['id_empr']) {
        echo "<span style='color: green;'>✓ O veículo pertence ao cliente da sessão</span><br>";
    } else {
        echo "<span style='color: red;'>✗ PROBLEMA: O veículo pertence ao cliente " . $veiculo['id_cliente'] . " (" . $veiculo['cliente'] . "), mas a sessão está configurada para cliente " . $_SESSION['id_empr'] . " (" . $_SESSION['nome_empr'] . ")</span><br>";
    }
}

echo "<h3>Verificação de QR Code:</h3>";
if(isset($_SESSION['base_veiculo'])) {
    echo "base_veiculo: " . $_SESSION['base_veiculo'] . "<br>";
    
    if($veiculo) {
        // Verificar se existe QR Code emitido
        $query_qr = "SELECT vq.id, vq.id_veiculo, vq.id_cliente, vq.id_situacao, vq.fim_vigencia
                     FROM veiculo_qrcode vq
                     WHERE vq.id_veiculo = :id_veiculo
                     AND vq.id_cliente = :id_cliente";
        
        $stmt_qr = $conn->prepare($query_qr);
        $stmt_qr->bindParam(':id_veiculo', $veiculo['id_veiculo'], PDO::PARAM_INT);
        $stmt_qr->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
        $stmt_qr->execute();
        
        if($stmt_qr->rowCount() > 0) {
            echo "<span style='color: green;'>✓ QR Code encontrado para este veículo</span><br>";
            while($qr = $stmt_qr->fetch(PDO::FETCH_ASSOC)) {
                echo "  - ID: " . $qr['id'] . ", Situação: " . $qr['id_situacao'] . ", Vigência: " . ($qr['fim_vigencia'] ?? 'Sem prazo') . "<br>";
            }
        } else {
            echo "<span style='color: orange;'>⚠ Nenhum QR Code emitido para este veículo na empresa da sessão</span><br>";
        }
    }
} else {
    echo "<span style='color: orange;'>⚠ base_veiculo não configurada na sessão</span><br>";
}

echo "<h3>Fornecedores Vinculados ao Cliente do Veículo:</h3>";
if($veiculo) {
    $query_forn = "SELECT f.id_fornecedor, f.nome_fantasia, f.id_cliente
                   FROM fornecedor f
                   WHERE f.id_cliente = :id_cliente";
    $stmt_forn = $conn->prepare($query_forn);
    $stmt_forn->bindParam(':id_cliente', $veiculo['id_cliente'], PDO::PARAM_INT);
    $stmt_forn->execute();
    
    while($forn = $stmt_forn->fetch(PDO::FETCH_ASSOC)) {
        echo "  - ID: " . $forn['id_fornecedor'] . " - " . $forn['nome_fantasia'] . "<br>";
    }
}
?>
