<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

if (empty($_SESSION['token'])) {
    $response['message'] = 'Não autenticado';
    echo json_encode($response);
    exit;
}

$accessControl = new AccessControl($_SESSION['userId']);

// Permitir acesso apenas para grupo 4 (fornecedores)
if (!$accessControl->isFornecedor()) {
    $response['message'] = 'Acesso negado. Este relatório está disponível apenas para fornecedores.';
    echo json_encode($response);
    exit;
}

$fornecedorId = $accessControl->getFornecedorId();
$action = $_GET['action'] ?? '';

$db = new Database();
$db->connect();

switch ($action) {
    case 'listar_consumo':
        // Filtros
        $tipoData = $_GET['tipoData'] ?? 'intervalo';
        $dataInicio = $_GET['dataInicio'] ?? '';
        $dataFim = $_GET['dataFim'] ?? '';
        $dataUnica = $_GET['dataUnica'] ?? '';
        $horaInicio = $_GET['horaInicio'] ?? '';
        $horaFim = $_GET['horaFim'] ?? '';
        $idVeiculo = isset($_GET['veiculo']) && $_GET['veiculo'] !== '' ? intval($_GET['veiculo']) : null;
        $idCondutor = isset($_GET['condutor']) && $_GET['condutor'] !== '' ? intval($_GET['condutor']) : null;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;
        
        // Construir WHERE
        $where = [];
        
        // Filtro de fornecedor (apenas se for fornecedor)
        if ($fornecedorId) {
            $where[] = "cc.id_fornecedor = " . intval($fornecedorId);
        }
        
        // Filtros de data
        switch ($tipoData) {
            case 'intervalo':
                if (!empty($dataInicio) && !empty($dataFim)) {
                    $where[] = "cc.data BETWEEN '" . $db->escape($dataInicio) . "' AND '" . $db->escape($dataFim) . "'";
                }
                break;
            case 'unica':
                if (!empty($dataUnica)) {
                    $where[] = "cc.data = '" . $db->escape($dataUnica) . "'";
                }
                break;
            case 'maior':
                if (!empty($dataInicio)) {
                    $where[] = "cc.data >= '" . $db->escape($dataInicio) . "'";
                }
                break;
            case 'menor':
                if (!empty($dataFim)) {
                    $where[] = "cc.data <= '" . $db->escape($dataFim) . "'";
                }
                break;
        }
        
        // Filtros de horário
        if (!empty($horaInicio) && !empty($horaFim)) {
            $where[] = "cc.hora BETWEEN '" . $db->escape($horaInicio) . "' AND '" . $db->escape($horaFim) . "'";
        } elseif (!empty($horaInicio)) {
            $where[] = "cc.hora >= '" . $db->escape($horaInicio) . "'";
        } elseif (!empty($horaFim)) {
            $where[] = "cc.hora <= '" . $db->escape($horaFim) . "'";
        }
        
        // Filtro de veículo
        if ($idVeiculo) {
            $where[] = "cc.id_veiculo = " . intval($idVeiculo);
        }
        
        // Filtro de condutor
        if ($idCondutor) {
            $where[] = "cc.id_condutor = " . intval($idCondutor);
        }
        
        // Se não houver filtros, adicionar condição sempre verdadeira
        $whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';
        
        // Query principal
        $sql = "SELECT cc.*,
                       v.placa,
                       v.modelo as veiculo_modelo,
                       c.nome as condutor_nome,
                       p.descricao as produto_descricao,
                       f.razao_social as fornecedor_nome
                FROM consumo_combustivel cc
                LEFT JOIN veiculo v ON cc.id_veiculo = v.id_veiculo
                LEFT JOIN condutor c ON cc.id_condutor = c.id_condutor
                LEFT JOIN produto p ON cc.id_produto = p.id_produto
                LEFT JOIN fornecedor f ON cc.id_fornecedor = f.id_fornecedor
                WHERE $whereClause
                ORDER BY cc.data DESC, cc.hora DESC
                LIMIT $perPage OFFSET $offset";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao buscar dados: ' . $db->getError();
            break;
        }
        
        $consumos = [];
        while ($row = $result->fetch_assoc()) {
            $consumos[] = $row;
        }
        
        // Contar total de registros
        $sqlCount = "SELECT COUNT(*) as total
                     FROM consumo_combustivel cc
                     WHERE $whereClause";
        $resultCount = $db->query($sqlCount);
        $totalRegistros = $resultCount->fetch_assoc()['total'];
        $totalPaginas = ceil($totalRegistros / $perPage);
        
        // Estatísticas
        $sqlStats = "SELECT 
                        COUNT(*) as total_abastecimentos,
                        SUM(cc.litragem) as total_litros,
                        SUM(cc.valor_total) as total_valor,
                        AVG(cc.valor_total) as media_valor
                     FROM consumo_combustivel cc
                     WHERE $whereClause";
        $resultStats = $db->query($sqlStats);
        $stats = $resultStats->fetch_assoc();
        
        $response['success'] = true;
        $response['data'] = $consumos;
        $response['stats'] = $stats;
        $response['totalPaginas'] = $totalPaginas;
        $response['paginaAtual'] = $page;
        $response['totalRegistros'] = $totalRegistros;
        break;
    
    case 'listar_veiculos':
        // Listar veículos vinculados ao fornecedor via contratos
        $sql = "SELECT DISTINCT v.id_veiculo, v.placa, v.modelo
                FROM veiculo v
                INNER JOIN clientes cl ON v.id_cliente = cl.id
                INNER JOIN contrato ct ON cl.id = ct.id_cliente
                WHERE ct.id_fornecedor = $fornecedorId
                AND v.id_situacao = 1
                ORDER BY v.placa ASC";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao buscar veículos';
            break;
        }
        
        $veiculos = [];
        while ($row = $result->fetch_assoc()) {
            $veiculos[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $veiculos;
        break;
    
    case 'listar_condutores':
        // Listar condutores vinculados ao fornecedor via contratos
        $sql = "SELECT DISTINCT c.id_condutor, c.nome, c.cnh
                FROM condutor c
                INNER JOIN clientes cl ON c.id_cliente = cl.id
                INNER JOIN contrato ct ON cl.id = ct.id_cliente
                WHERE ct.id_fornecedor = $fornecedorId
                AND c.id_situacao = 1
                ORDER BY c.nome ASC";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao buscar condutores';
            break;
        }
        
        $condutores = [];
        while ($row = $result->fetch_assoc()) {
            $condutores[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $condutores;
        break;
    
    default:
        $response['message'] = 'Ação inválida';
        break;
}

echo json_encode($response);
?>
