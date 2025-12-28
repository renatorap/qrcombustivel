<?php
if(session_status() === PHP_SESSION_NONE) session_start();

// Incluir o arquivo para validar token
include_once 'validar_token.php';

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

// Validar token
if(!validarToken()){
    die("Sessão expirada. Faça login novamente.");
}

echo "<h2>Debug - Dados da Sessão</h2>";

echo "<h3>Variáveis de Sessão:</h3>";
echo "id_empr (id_cliente): " . (isset($_SESSION['id_empr']) ? $_SESSION['id_empr'] : 'NOT SET') . "<br>";
echo "nome_empr: " . (isset($_SESSION['nome_empr']) ? $_SESSION['nome_empr'] : 'NOT SET') . "<br>";
echo "id_forn: " . (isset($_SESSION['id_forn']) ? $_SESSION['id_forn'] : 'NOT SET') . "<br>";
echo "nome_forn: " . (isset($_SESSION['nome_forn']) ? $_SESSION['nome_forn'] : 'NOT SET') . "<br>";
echo "login_user: " . (isset($_SESSION['login_user']) ? $_SESSION['login_user'] : 'NOT SET') . "<br>";
echo "nome_user: " . (isset($_SESSION['nome_user']) ? $_SESSION['nome_user'] : 'NOT SET') . "<br>";
echo "logo: " . (isset($_SESSION['logo']) ? $_SESSION['logo'] : 'NOT SET') . "<br>";

echo "<h3>Teste de Busca do Veículo SWV3G56:</h3>";
$placa = 'SWV3G56';
$placa_limpa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $placa));

echo "Placa original: " . $placa . "<br>";
echo "Placa limpa: " . $placa_limpa . "<br>";
echo "id_cliente da sessão: " . $_SESSION['id_empr'] . "<br><br>";

// Buscar veículo com a query atual
$query = "SELECT 
            v.id_veiculo,
            v.placa,
            v.id_cliente,
            v.id_situacao
          FROM veiculo v
          WHERE v.id_cliente = :id_cliente 
          AND v.id_situacao = 1
          AND REPLACE(REPLACE(UPPER(v.placa), '-', ''), ' ', '') = :placa
          LIMIT 1";

echo "<strong>Query utilizada:</strong><br><pre>" . $query . "</pre><br>";

$stmt = $conn->prepare($query);
$stmt->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
$stmt->bindParam(':placa', $placa_limpa, PDO::PARAM_STR);

echo "Parâmetros:<br>";
echo "  id_cliente: " . $_SESSION['id_empr'] . "<br>";
echo "  placa: " . $placa_limpa . "<br><br>";

$stmt->execute();

if($stmt->rowCount() > 0) {
    $veiculo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<span style='color: green; font-weight: bold;'>✓ VEÍCULO ENCONTRADO!</span><br>";
    echo "id_veiculo: " . $veiculo['id_veiculo'] . "<br>";
    echo "placa: " . $veiculo['placa'] . "<br>";
    echo "id_cliente: " . $veiculo['id_cliente'] . "<br>";
    echo "id_situacao: " . $veiculo['id_situacao'] . "<br>";
} else {
    echo "<span style='color: red; font-weight: bold;'>✗ VEÍCULO NÃO ENCONTRADO</span><br><br>";
    
    // Buscar sem filtro de cliente para ver se existe
    echo "<h4>Verificando se o veículo existe (sem filtro de cliente):</h4>";
    $query2 = "SELECT 
                v.id_veiculo,
                v.placa,
                v.id_cliente,
                v.id_situacao
              FROM veiculo v
              WHERE v.id_situacao = 1
              AND REPLACE(REPLACE(UPPER(v.placa), '-', ''), ' ', '') = :placa
              LIMIT 1";
    
    $stmt2 = $conn->prepare($query2);
    $stmt2->bindParam(':placa', $placa_limpa, PDO::PARAM_STR);
    $stmt2->execute();
    
    if($stmt2->rowCount() > 0) {
        $veiculo2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        echo "<span style='color: orange;'>⚠ Veículo existe, mas com id_cliente diferente:</span><br>";
        echo "id_veiculo: " . $veiculo2['id_veiculo'] . "<br>";
        echo "placa: " . $veiculo2['placa'] . "<br>";
        echo "id_cliente do veículo: " . $veiculo2['id_cliente'] . "<br>";
        echo "id_cliente da sessão: " . $_SESSION['id_empr'] . "<br>";
        echo "<strong style='color: red;'>PROBLEMA: Os IDs de cliente não correspondem!</strong><br>";
    } else {
        echo "Veículo não encontrado nem sem filtro de cliente.<br>";
    }
}

echo "<h3>Fornecedores do Cliente " . $_SESSION['id_empr'] . ":</h3>";
$query_forn = "SELECT id_fornecedor, nome_fantasia, id_cliente 
               FROM fornecedor 
               WHERE id_cliente = :id_cliente";
$stmt_forn = $conn->prepare($query_forn);
$stmt_forn->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
$stmt_forn->execute();

while($forn = $stmt_forn->fetch(PDO::FETCH_ASSOC)) {
    echo "  - ID: " . $forn['id_fornecedor'] . " - " . $forn['nome_fantasia'] . " (id_cliente: " . $forn['id_cliente'] . ")<br>";
    if($forn['id_fornecedor'] == $_SESSION['id_forn']) {
        echo "    <strong style='color: green;'>✓ Este é o fornecedor da sessão</strong><br>";
    }
}
?>
