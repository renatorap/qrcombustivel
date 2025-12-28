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
$clienteId = $_SESSION['cliente_id'] ?? null;
$action = $_GET['action'] ?? '';

$db = new Database();
$db->connect();

switch ($action) {
    case 'listar_extrato':
        // Filtros
        $tipoData = $_GET['tipoData'] ?? 'intervalo';
        $dataInicio = $_GET['dataInicio'] ?? '';
        $dataFim = $_GET['dataFim'] ?? '';
        $dataUnica = $_GET['dataUnica'] ?? '';
        
        $tipoHora = $_GET['tipoHora'] ?? 'intervalo';
        $horaInicio = $_GET['horaInicio'] ?? '';
        $horaFim = $_GET['horaFim'] ?? '';
        
        $idsFornecedor = isset($_GET['fornecedor']) && is_array($_GET['fornecedor']) ? array_map('intval', $_GET['fornecedor']) : [];
        $idCondutor = isset($_GET['condutor']) && $_GET['condutor'] !== '' ? intval($_GET['condutor']) : null;
        $idsSetor = isset($_GET['setor']) && is_array($_GET['setor']) ? array_map('intval', $_GET['setor']) : [];
        $idsProduto = isset($_GET['produto']) && is_array($_GET['produto']) ? array_map('intval', $_GET['produto']) : [];
        $placa = isset($_GET['placa']) && $_GET['placa'] !== '' ? $db->escape($_GET['placa']) : null;
        
        $ordenacao = $_GET['ordenacao'] ?? 'data_asc';
        $quebras = isset($_GET['quebra']) && is_array($_GET['quebra']) ? $_GET['quebra'] : [];
        
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 100;
        $offset = ($page - 1) * $perPage;
        
        // Construir WHERE
        $where = [];
        
        // Filtro de empresa
        if ($clienteId) {
            $where[] = "cc.id_cliente = " . intval($clienteId);
        }
        
        // Filtros de data
        switch ($tipoData) {
            case 'intervalo':
                if (!empty($dataInicio) && !empty($dataFim)) {
                    $where[] = "((cc.`data` >= '" . $db->escape($dataInicio) . "') AND (cc.`data` <= '" . $db->escape($dataFim) . "'))";
                }
                break;
            case 'unica':
                if (!empty($dataUnica)) {
                    $where[] = "cc.`data` = '" . $db->escape($dataUnica) . "'";
                }
                break;
            case 'maior':
                if (!empty($dataInicio)) {
                    $where[] = "cc.`data` >= '" . $db->escape($dataInicio) . "'";
                }
                break;
            case 'menor':
                if (!empty($dataFim)) {
                    $where[] = "cc.`data` <= '" . $db->escape($dataFim) . "'";
                }
                break;
        }
        
        // Filtros de horário
        switch ($tipoHora) {
            case 'intervalo':
                if (!empty($horaInicio) && !empty($horaFim)) {
                    $where[] = "cc.hora BETWEEN '" . $db->escape($horaInicio) . "' AND '" . $db->escape($horaFim) . "'";
                }
                break;
            case 'maior':
                if (!empty($horaInicio)) {
                    $where[] = "cc.hora >= '" . $db->escape($horaInicio) . "'";
                }
                break;
            case 'menor':
                if (!empty($horaFim)) {
                    $where[] = "cc.hora <= '" . $db->escape($horaFim) . "'";
                }
                break;
        }
        
        // Filtro de fornecedor (múltiplo)
        if (!empty($idsFornecedor)) {
            $where[] = "cc.id_fornecedor IN (" . implode(',', $idsFornecedor) . ")";
        }
        
        // Filtro de condutor
        if ($idCondutor) {
            $where[] = "cc.id_condutor = " . intval($idCondutor);
        }
        
        // Filtro de setor (múltiplo)
        if (!empty($idsSetor)) {
            $where[] = "v.id_setor IN (" . implode(',', $idsSetor) . ")";
        }
        
        // Filtro de produto (múltiplo)
        if (!empty($idsProduto)) {
            $where[] = "cc.id_produto IN (" . implode(',', $idsProduto) . ")";
        }
        
        // Filtro de placa
        if ($placa) {
            $where[] = "UPPER(v.placa) LIKE UPPER('%" . $placa . "%')";
        }
        
        // Se não houver filtros, adicionar condição sempre verdadeira
        $whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';
        
        // Construir ordenação baseada nas quebras selecionadas
        $orderBy = '';
        if (!empty($quebras)) {
            $orderFields = [];
            foreach ($quebras as $quebra) {
                switch ($quebra) {
                    case 'fornecedor':
                        $orderFields[] = 'nome_fant';
                        break;
                    case 'setor':
                        $orderFields[] = 'setor';
                        break;
                    case 'produto':
                        $orderFields[] = 'produto';
                        break;
                    case 'placa':
                        $orderFields[] = 'placa';
                        break;
                }
            }
            // Adicionar data e hora ao final
            $orderFields[] = 'data';
            $orderFields[] = 'hora';
            $orderBy = implode(' ASC, ', $orderFields) . ' ASC';
        } else {
            // Ordenação padrão ou por seleção manual
            switch ($ordenacao) {
                case 'data_asc':
                    $orderBy = 'data ASC, hora ASC';
                    break;
                case 'data_desc':
                    $orderBy = 'data DESC, hora DESC';
                    break;
                case 'placa_asc':
                    $orderBy = 'placa ASC, data ASC, hora ASC';
                    break;
                case 'placa_desc':
                    $orderBy = 'placa DESC, data DESC, hora DESC';
                    break;
                case 'fornecedor_asc':
                    $orderBy = 'nome_fant ASC, data ASC, hora ASC';
                    break;
                case 'fornecedor_desc':
                    $orderBy = 'nome_fant DESC, data DESC, hora DESC';
                    break;
                case 'setor_asc':
                    $orderBy = 'setor ASC, placa ASC, data ASC, hora ASC';
                    break;
                case 'setor_desc':
                    $orderBy = 'setor DESC, placa DESC, data DESC, hora DESC';
                    break;
                case 'produto_asc':
                    $orderBy = 'produto ASC, data ASC, hora ASC';
                    break;
                case 'produto_desc':
                    $orderBy = 'produto DESC, data DESC, hora DESC';
                    break;
                default:
                    $orderBy = 'data ASC, hora ASC';
                    break;
            }
        }
        
        // Query principal baseada na query do usuário
        $sql = "WITH res AS (
                    SELECT 
                        cc.id_cliente,
                        cc.id_veiculo,
                        cc.id_fornecedor,
                        v.placa AS placa, 
                        cc.`data` AS data, 
                        cc.hora AS hora, 
                        cc.km_veiculo AS km_veic_atu, 
                        CASE
                            WHEN km_veiculo_ant IS NULL THEN LAG(km_veiculo) OVER (ORDER BY cc.id_cliente, cc.id_veiculo, cc.`data`, cc.hora)
                            ELSE km_veiculo_ant
                        END AS km_veic_ant,
                        cc.litragem AS litragem, 
                        f.nome_fantasia AS nome_fant, 
                        c.nome AS condutor, 
                        s.descricao AS setor, 
                        p.descricao AS produto, 
                        cc.valor_unitario AS vl_unit, 
                        cc.valor_total AS vl_total 
                    FROM consumo_combustivel AS cc 
                    INNER JOIN veiculo v ON v.id_veiculo = cc.id_veiculo 
                    INNER JOIN setor s ON s.id_setor = v.id_setor 
                    INNER JOIN condutor c ON c.id_condutor = cc.id_condutor 
                    INNER JOIN produto p ON cc.id_produto = p.id_produto 
                    INNER JOIN fornecedor f ON f.id_fornecedor = cc.id_fornecedor 
                    WHERE $whereClause
                )
                SELECT
                    res.placa, 
                    res.data, 
                    res.hora, 
                    res.km_veic_atu, 
                    res.km_veic_ant, 
                    (res.km_veic_atu - res.km_veic_ant) AS km_rodado, 
                    ROUND(((res.km_veic_atu - res.km_veic_ant) / litragem), 2) AS km_litro, 
                    res.litragem, 
                    res.nome_fant, 
                    res.condutor, 
                    res.setor, 
                    res.produto, 
                    res.vl_unit, 
                    res.vl_total 
                FROM res
                ORDER BY $orderBy
                LIMIT $perPage OFFSET $offset";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao buscar dados: ' . $db->getError();
            break;
        }
        
        $extratos = [];
        while ($row = $result->fetch_assoc()) {
            $extratos[] = $row;
        }
        
        // Contar total de registros (sem limit)
        $sqlCount = "WITH res AS (
                        SELECT 
                            cc.id_cliente,
                            cc.id_veiculo,
                            v.placa AS placa, 
                            cc.`data` AS data, 
                            cc.hora AS hora, 
                            cc.km_veiculo AS km_veic_atu, 
                            CASE
                                WHEN km_veiculo_ant IS NULL THEN LAG(km_veiculo) OVER (ORDER BY cc.id_cliente, cc.id_veiculo, cc.`data`, cc.hora)
                                ELSE km_veiculo_ant
                            END AS km_veic_ant,
                            cc.litragem AS litragem, 
                            cc.valor_total AS vl_total 
                        FROM consumo_combustivel AS cc 
                        INNER JOIN veiculo v ON v.id_veiculo = cc.id_veiculo 
                        INNER JOIN setor s ON s.id_setor = v.id_setor 
                        INNER JOIN condutor c ON c.id_condutor = cc.id_condutor 
                        INNER JOIN produto p ON cc.id_produto = p.id_produto 
                        INNER JOIN fornecedor f ON f.id_fornecedor = cc.id_fornecedor 
                        WHERE $whereClause
                    )
                    SELECT COUNT(*) AS total FROM res";
        $resultCount = $db->query($sqlCount);
        $totalRegistros = $resultCount->fetch_assoc()['total'];
        $totalPaginas = ceil($totalRegistros / $perPage);
        
        // Estatísticas totais
        $sqlStats = "WITH res AS (
                        SELECT 
                            cc.id_cliente,
                            cc.id_veiculo,
                            v.placa AS placa, 
                            cc.`data` AS data, 
                            cc.hora AS hora, 
                            cc.km_veiculo AS km_veic_atu, 
                            CASE
                                WHEN km_veiculo_ant IS NULL THEN LAG(km_veiculo) OVER (ORDER BY cc.id_cliente, cc.id_veiculo, cc.`data`, cc.hora)
                                ELSE km_veiculo_ant
                            END AS km_veic_ant,
                            cc.litragem AS litragem, 
                            cc.valor_total AS vl_total 
                        FROM consumo_combustivel AS cc 
                        INNER JOIN veiculo v ON v.id_veiculo = cc.id_veiculo 
                        INNER JOIN setor s ON s.id_setor = v.id_setor 
                        INNER JOIN condutor c ON c.id_condutor = cc.id_condutor 
                        INNER JOIN produto p ON cc.id_produto = p.id_produto 
                        INNER JOIN fornecedor f ON f.id_fornecedor = cc.id_fornecedor 
                        WHERE $whereClause
                    )
                    SELECT 
                        COUNT(*) AS total_abastecimentos,
                        SUM(res.litragem) AS total_litros,
                        SUM(res.vl_total) AS total_valor,
                        AVG(res.vl_total) AS media_valor,
                        SUM(res.km_veic_atu - res.km_veic_ant) AS total_km_rodado,
                        AVG((res.km_veic_atu - res.km_veic_ant) / res.litragem) AS media_km_litro
                    FROM res";
        $resultStats = $db->query($sqlStats);
        $stats = $resultStats->fetch_assoc();
        
        $response['success'] = true;
        $response['data'] = $extratos;
        $response['stats'] = $stats;
        $response['totalPaginas'] = $totalPaginas;
        $response['paginaAtual'] = $page;
        $response['totalRegistros'] = $totalRegistros;
        break;
    
    case 'listar_fornecedores':
        $sql = "SELECT DISTINCT f.id_fornecedor, f.nome_fantasia, f.razao_social
                FROM fornecedor f
                INNER JOIN consumo_combustivel cc ON f.id_fornecedor = cc.id_fornecedor
                WHERE cc.id_cliente = " . intval($clienteId) . "
                ORDER BY f.nome_fantasia ASC";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao buscar fornecedores';
            break;
        }
        
        $fornecedores = [];
        while ($row = $result->fetch_assoc()) {
            $fornecedores[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $fornecedores;
        break;
    
    case 'buscar_condutores':
        $termo = $_GET['termo'] ?? '';
        
        $sql = "SELECT DISTINCT c.id_condutor, c.nome, c.cnh
                FROM condutor c
                INNER JOIN consumo_combustivel cc ON c.id_condutor = cc.id_condutor
                WHERE cc.id_cliente = " . intval($clienteId) . "
                AND (UPPER(c.nome) LIKE UPPER('%" . $db->escape($termo) . "%') 
                     OR UPPER(c.cnh) LIKE UPPER('%" . $db->escape($termo) . "%'))
                ORDER BY c.nome ASC
                LIMIT 20";
        
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
    
    case 'listar_setores':
        $sql = "SELECT DISTINCT s.id_setor, s.descricao
                FROM setor s
                INNER JOIN veiculo v ON s.id_setor = v.id_setor
                INNER JOIN consumo_combustivel cc ON v.id_veiculo = cc.id_veiculo
                WHERE cc.id_cliente = " . intval($clienteId) . "
                ORDER BY s.descricao ASC";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao buscar setores';
            break;
        }
        
        $setores = [];
        while ($row = $result->fetch_assoc()) {
            $setores[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $setores;
        break;
    
    case 'listar_produtos':
        $sql = "SELECT DISTINCT p.id_produto, p.descricao
                FROM produto p
                INNER JOIN consumo_combustivel cc ON p.id_produto = cc.id_produto
                WHERE cc.id_cliente = " . intval($clienteId) . "
                ORDER BY p.descricao ASC";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao buscar produtos';
            break;
        }
        
        $produtos = [];
        while ($row = $result->fetch_assoc()) {
            $produtos[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $produtos;
        break;
    
    case 'buscar_placas':
        $termo = $_GET['termo'] ?? '';
        
        $sql = "SELECT DISTINCT v.id_veiculo, v.placa, v.modelo
                FROM veiculo v
                INNER JOIN consumo_combustivel cc ON v.id_veiculo = cc.id_veiculo
                WHERE cc.id_cliente = " . intval($clienteId) . "
                AND (UPPER(v.placa) LIKE UPPER('%" . $db->escape($termo) . "%') 
                     OR UPPER(v.modelo) LIKE UPPER('%" . $db->escape($termo) . "%'))
                ORDER BY v.placa ASC
                LIMIT 20";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao buscar placas';
            break;
        }
        
        $placas = [];
        while ($row = $result->fetch_assoc()) {
            $placas[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $placas;
        break;
    
    default:
        $response['message'] = 'Ação inválida';
        break;
}

echo json_encode($response);
?>
