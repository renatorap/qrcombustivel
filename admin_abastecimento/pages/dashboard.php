<?php
require_once '../config/cache_control.php'; // Controle de cache e sessão
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/license_checker.php';
require_once '../components/dashboard-components.php';

$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

// Verificar licença do cliente
$clienteId = $_SESSION['cliente_id'] ?? null;
$grupoId = $_SESSION['grupoId'] ?? null;
$statusLicenca = LicenseChecker::verificarEBloquear($clienteId, $grupoId);

// Buscar estatísticas do banco de dados
$db = new Database();
$db->connect();

// Verificar se usuário é do grupo 4 (fornecedor)
require_once '../config/access_control.php';
$accessControl = new AccessControl($_SESSION['userId']);
$fornecedorId = $accessControl->getFornecedorId();
$fornecedorFilter = $fornecedorId ? " AND c.id_fornecedor = $fornecedorId" : "";

// Total de veículos do cliente logado
$totalVeiculos = 0;
if ($clienteId) {
    $sql = "SELECT COUNT(*) as total FROM veiculo WHERE id_cliente = $clienteId AND id_situacao = 1";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $totalVeiculos = $row['total'];
    }
}

// Total de abastecimentos (este mês, filtrado por fornecedor se grupo 4)
$totalAbastecimentos = 0;
if ($clienteId) {
    $sql = "SELECT COUNT(*) as total FROM consumo_combustivel c
            WHERE c.id_cliente = $clienteId 
            AND MONTH(c.data) = MONTH(CURDATE()) 
            AND YEAR(c.data) = YEAR(CURDATE())
            $fornecedorFilter";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $totalAbastecimentos = $row['total'];
    }
}

// Gasto total (este mês, filtrado por fornecedor se grupo 4)
$gastoTotal = 0;
if ($clienteId) {
    $sql = "SELECT SUM(c.valor_total) as total FROM consumo_combustivel c
            WHERE c.id_cliente = $clienteId 
            AND MONTH(c.data) = MONTH(CURDATE()) 
            AND YEAR(c.data) = YEAR(CURDATE())
            $fornecedorFilter";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $gastoTotal = 'R$ ' . number_format($row['total'] ?? 0, 2, ',', '.');
    }
}

// Consumo médio (litros este mês, filtrado por fornecedor se grupo 4)
$consumoMedio = 0;
if ($clienteId) {
    $sql = "SELECT SUM(c.litragem) as total FROM consumo_combustivel c
            WHERE c.id_cliente = $clienteId 
            AND MONTH(c.data) = MONTH(CURDATE()) 
            AND YEAR(c.data) = YEAR(CURDATE())
            $fornecedorFilter";
    $result = $db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $consumoMedio = number_format($row['total'] ?? 0, 0, ',', '.') . 'L';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
            <div class="page-title">
                <div>
                    <h1>
                        <i class="fas fa-chart-line"></i>Dashboard
                    </h1>
                </div>
            </div>

            <?php 
            // Exibir aviso de expiração próxima se houver
            if ($statusLicenca['ativa'] && isset($statusLicenca['aviso_expiracao']) && $statusLicenca['aviso_expiracao']) {
                echo LicenseChecker::getAvisoExpiracao($statusLicenca['dias_restantes'], $statusLicenca['expira_em']);
            }
            ?>

            <!-- Grid de Estatísticas -->
            <div class="dashboard-grid">
                <?php 
                if ($fornecedorId) {
                    // Usuário do grupo 4 (fornecedor) - mostrar apenas abastecimentos
                    renderStatCard('Abastecimentos', $totalAbastecimentos, 'fa-gas-pump', 'Este mês do seu fornecedor', 'orange');
                } else {
                    // Outros usuários - mostrar todos os cards
                    renderStatCard('Veículos', $totalVeiculos, 'fa-car', 'Cadastrados no sistema', 'primary');
                    renderStatCard('Abastecimentos', $totalAbastecimentos, 'fa-gas-pump', 'Este mês', 'orange');
                    renderStatCard('Gasto Total', $gastoTotal, 'fa-dollar-sign', 'Este mês', 'danger');
                    renderStatCard('Qtde Litros', $consumoMedio, 'fa-tachometer-alt', 'Litragem esse mês', 'success');
                }
                ?>
            </div>

            <!-- Abastecimentos Recentes -->
            <div class="dashboard-section">
                <h4 class="section-title">
                    <i class="fas fa-history"></i> Abastecimentos Recentes
                </h4>
                
                <div class="table-container">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Veículo</th>
                                <th>Combustível</th>
                                <th>Litros</th>
                                <th>Valor</th>
                                <?php if (!$fornecedorId): ?>
                                <th>Fornecedor</th>
                                <?php endif; ?>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Buscar últimos 10 abastecimentos (filtrado por fornecedor se grupo 4)
                            $sql = "SELECT c.data, c.hora, c.litragem, c.valor_total,
                                    v.placa, p.descricao as produto_nome,
                                    f.razao_social as fornecedor_nome
                                    FROM consumo_combustivel c
                                    LEFT JOIN veiculo v ON c.id_veiculo = v.id_veiculo
                                    LEFT JOIN produto p ON c.id_produto = p.id_produto
                                    LEFT JOIN fornecedor f ON c.id_fornecedor = f.id_fornecedor
                                    WHERE c.id_cliente = " . ($clienteId ?? 'NULL') . "
                                    $fornecedorFilter
                                    ORDER BY c.data DESC, c.hora DESC
                                    LIMIT 10";
                            
                            $result = $db->query($sql);
                            
                            // Debug (remover após correção)
                            if (!$result) {
                                echo "<tr><td colspan='7' class='text-center text-danger'>Erro na query: " . htmlspecialchars($db->getError()) . "</td></tr>";
                                echo "<!-- SQL: " . htmlspecialchars($sql) . " -->";
                            } elseif ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $dataFormatada = date('d/m/Y', strtotime($row['data']));
                                    $litros = number_format($row['litragem'], 2, ',', '.');
                                    $valor = number_format($row['valor_total'], 2, ',', '.');
                                    echo "<tr>";
                                    echo "<td><strong>{$dataFormatada}</strong></td>";
                                    echo "<td>" . ($row['placa'] ?? '-') . "</td>";
                                    echo "<td>" . ($row['produto_nome'] ?? '-') . "</td>";
                                    echo "<td>{$litros}L</td>";
                                    echo "<td><strong>R$ {$valor}</strong></td>";
                                    if (!$fornecedorId) {
                                        echo "<td>" . ($row['fornecedor_nome'] ?? '-') . "</td>";
                                    }
                                    echo '<td><span class="badge-completed">Concluído</span></td>';
                                    echo "</tr>";
                                }
                            } else {
                                $colspan = $fornecedorId ? 6 : 7;
                                echo "<tr><td colspan='$colspan' class='text-center text-muted'>";
                                echo "Nenhum abastecimento encontrado";
                                echo "<br><small>Cliente ID: " . ($clienteId ?? 'NULL') . ($fornecedorId ? " | Fornecedor ID: $fornecedorId" : "") . "</small>";
                                echo "</td></tr>";
                                echo "<!-- SQL: " . htmlspecialchars($sql) . " -->";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!$fornecedorId): ?>
            <!-- Veículos Ativos (oculto para usuários do grupo 4) -->
            <div class="dashboard-section">
                <h4 class="section-title">
                    <i class="fas fa-car-side"></i> Veículos Ativos
                </h4>
                
                <div class="table-container">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Placa</th>
                                <th>Modelo</th>
                                <th>Marca</th>
                                <th>Combustível</th>
                                <th>Ano</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Buscar veículos ativos
                            $sql = "SELECT v.placa, v.modelo, mv.descricao as marca, p.descricao as tipo_combustivel, v.ano
                                    FROM veiculo v
                                    LEFT JOIN marca_veiculo mv ON v.id_marca_veiculo = mv.id_marca_veiculo
                                    LEFT JOIN combustivel_veiculo cv ON v.id_veiculo = cv.id_veiculo
                                    LEFT JOIN produto p ON cv.id_produto = p.id_produto
                                    WHERE v.id_cliente = $clienteId AND v.id_situacao = 1
                                    ORDER BY v.placa
                                    LIMIT 10";
                            
                            $result = $db->query($sql);
                            
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td><strong>" . htmlspecialchars($row['placa']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($row['modelo'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['marca'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['tipo_combustivel'] ?? '-') . "</td>";
                                    echo "<td>" . htmlspecialchars($row['ano'] ?? '-') . "</td>";
                                    echo '<td><span class="badge-completed">Ativo</span></td>';
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-muted'>Nenhum veículo ativo encontrado</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
</body>
</html>