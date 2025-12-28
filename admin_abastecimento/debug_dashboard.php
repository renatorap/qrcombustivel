<?php
session_start();
echo "<h3>Debug Dashboard - Sessão</h3>";
echo "<pre>";
echo "cliente_id: " . ($_SESSION['cliente_id'] ?? 'NULL') . "\n";
echo "grupoId: " . ($_SESSION['grupoId'] ?? 'NULL') . "\n";
echo "userId: " . ($_SESSION['userId'] ?? 'NULL') . "\n";
echo "\nToda a sessão:\n";
print_r($_SESSION);
echo "</pre>";

require_once 'config/database.php';
$db = new Database();
$db->connect();

$clienteId = $_SESSION['cliente_id'] ?? null;

if ($clienteId) {
    echo "<h3>Query de Veículos</h3>";
    $sql = "SELECT v.placa, v.modelo, m.nome as marca, tc.nome as tipo_combustivel, v.ano
            FROM veiculo v
            LEFT JOIN marca m ON v.id_marca = m.id_marca
            LEFT JOIN tipo_combustivel tc ON v.id_tp_combustivel = tc.id_tp_combustivel
            WHERE v.id_cliente = ? AND v.id_situacao = 1
            ORDER BY v.placa
            LIMIT 10";
    
    echo "<pre>SQL: $sql\ncliente_id: $clienteId</pre>";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $clienteId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<h4>Resultado: " . $result->num_rows . " veículos encontrados</h4>";
    
    if ($result->num_rows > 0) {
        echo "<table border='1'><tr><th>Placa</th><th>Modelo</th><th>Marca</th><th>Combustível</th><th>Ano</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['placa']}</td>";
            echo "<td>{$row['modelo']}</td>";
            echo "<td>{$row['marca']}</td>";
            echo "<td>{$row['tipo_combustivel']}</td>";
            echo "<td>{$row['ano']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p style='color: red;'>cliente_id não está definido na sessão!</p>";
}
?>
