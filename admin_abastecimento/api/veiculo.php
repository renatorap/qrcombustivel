<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

if (empty($_SESSION['token'])) {
    $response['message'] = 'Não autenticado';
    echo json_encode($response);
    exit;
}

// Controle de acesso para veículos
$accessControl = new AccessControl($_SESSION['userId']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Necessário ao menos acessar para qualquer ação
if (!$accessControl->verificarPermissao('veiculos', 'acessar')) {
    $response['message'] = 'Você não possui permissão para acessar veículos';
    $accessControl->registrarAcao('veiculos', 'acesso_negado', 'Tentativa de acesso sem permissão', 'negado');
    echo json_encode($response);
    exit;
}

$db = new Database();
$db->connect();

switch ($action) {
    case 'list_for_report':
        // Lista veículos para relatório de QR Code (sem paginação)
        $clienteId = $_SESSION['cliente_id'] ?? null;
        
        if (!$clienteId) {
            $response['success'] = true;
            $response['message'] = 'Selecione um cliente no menu superior';
            $response['data'] = [];
            break;
        }
        
        $where = "v.id_cliente = $clienteId";
        
        // Aplicar filtros
        if (!empty($_GET['placa'])) {
            $placa = Security::sanitize($_GET['placa']);
            $where .= " AND LOWER(v.placa) LIKE '%" . strtolower($placa) . "%'";
        }
        
        if (!empty($_GET['modelo'])) {
            $modelo = Security::sanitize($_GET['modelo']);
            $where .= " AND LOWER(v.modelo) LIKE '%" . strtolower($modelo) . "%'";
        }
        
        if (!empty($_GET['id_situacao'])) {
            $id_situacao = intval($_GET['id_situacao']);
            $where .= " AND v.id_situacao = $id_situacao";
        } else {
            // Por padrão, apenas veículos ativos (situação 1)
            $where .= " AND v.id_situacao = 1";
        }
        
        $sql = "SELECT v.id_veiculo, v.placa, v.modelo, v.ano,
                       m.descricao as marca_nome,
                       s.descricao as setor_nome,
                       sit.descricao as situacao_nome
                FROM veiculo v
                LEFT JOIN marca_veiculo m ON v.id_marca_veiculo = m.id_marca_veiculo
                LEFT JOIN setor s ON v.id_setor = s.id_setor
                LEFT JOIN situacao sit ON v.id_situacao = sit.id_situacao
                WHERE $where
                ORDER BY v.placa ASC";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao consultar veículos: ' . $db->getError();
            break;
        }
        
        $veiculos = [];
        while ($row = $result->fetch_assoc()) {
            $veiculos[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $veiculos;
        break;

    case 'list':
        $search = Security::sanitize($_GET['search'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $limit = PAGINATION_LIMIT;
        $offset = ($page - 1) * $limit;
        $clienteId = $_SESSION['cliente_id'] ?? null;

        $where = "1=1";
        
        // Filtrar por cliente se estiver definido
        if ($clienteId) {
            $where .= " AND v.id_cliente = $clienteId";
        } else {
            // Se não há cliente selecionado, retornar vazio com mensagem informativa
            $response['success'] = true;
            $response['message'] = 'Selecione um cliente no menu superior para visualizar os veículos';
            $response['veiculos'] = [];
            $response['totalPages'] = 0;
            $response['currentPage'] = 1;
            break;
        }
        
        if ($search) {
            $search = strtolower($search);
            $where .= " AND (LOWER(v.placa) LIKE '%$search%' OR LOWER(v.modelo) LIKE '%$search%' OR LOWER(m.descricao) LIKE '%$search%')";
        }

        $sql = "SELECT COUNT(*) as total FROM veiculo v 
                LEFT JOIN marca_veiculo m ON v.id_marca_veiculo = m.id_marca_veiculo
                WHERE $where";
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao consultar veículos: ' . $db->conn->error;
            break;
        }
        
        $row = $result->fetch_assoc();
        $totalPages = ceil($row['total'] / $limit);

        $sql = "SELECT v.*, 
                       m.descricao as marca_nome,
                       s.descricao as setor_nome
                FROM veiculo v
                LEFT JOIN marca_veiculo m ON v.id_marca_veiculo = m.id_marca_veiculo
                LEFT JOIN setor s ON v.id_setor = s.id_setor
                WHERE $where 
                ORDER BY v.id_veiculo DESC 
                LIMIT $limit OFFSET $offset";
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao listar veículos: ' . $db->conn->error;
            break;
        }

        $veiculos = [];
        while ($row = $result->fetch_assoc()) {
            $veiculos[] = $row;
        }

        $response['success'] = true;
        $response['veiculos'] = $veiculos;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;

    case 'get':
        $id = intval($_GET['id']);
        $clienteId = $_SESSION['cliente_id'] ?? null;
        
        if (!$clienteId) {
            $response['message'] = 'Selecione um cliente no menu superior';
            break;
        }
        
        $where = "v.id_veiculo = $id AND v.id_cliente = $clienteId";
        
        $sql = "SELECT v.*, 
                       m.descricao as marca_nome,
                       c.descricao as cor_nome,
                       t.descricao as tipo_nome,
                       cat.codigo as categoria_nome,
                       s.descricao as setor_nome,
                       sit.descricao as situacao_nome,
                       f.descricao as forma_trabalho_nome
                FROM veiculo v
                LEFT JOIN marca_veiculo m ON v.id_marca_veiculo = m.id_marca_veiculo
                LEFT JOIN cor_veiculo c ON v.id_cor_veiculo = c.id_cor_veiculo
                LEFT JOIN tp_veiculo t ON v.id_tp_veiculo = t.id_tp_veiculo
                LEFT JOIN cat_cnh cat ON v.id_cat_cnh = cat.id_cat_cnh
                LEFT JOIN setor s ON v.id_setor = s.id_setor
                LEFT JOIN situacao sit ON v.id_situacao = sit.id_situacao
                LEFT JOIN forma_trabalho f ON v.id_forma_trabalho = f.id_forma_trabalho
                WHERE $where";
        $result = $db->query($sql);

        if (!$result) {
            $response['message'] = 'Erro ao consultar veículo: ' . $db->conn->error;
            break;
        }

        if ($result->num_rows === 1) {
            $response['success'] = true;
            $response['veiculo'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Veículo não encontrado ou você não tem acesso a ele';
        }
        break;

    case 'create':
        if (!$accessControl->verificarPermissao('veiculos', 'criar')) {
            $response['message'] = 'Você não possui permissão para criar veículos';
            $accessControl->registrarAcao('veiculos', 'criar', 'Tentativa de criar sem permissão', 'negado');
            break;
        }

        $clienteId = $_SESSION['cliente_id'] ?? null;
        if (!$clienteId) {
            $response['message'] = 'Selecione um cliente no menu superior antes de criar um veículo';
            break;
        }
        
        // Campos obrigatórios
        $placa = Security::sanitize($_POST['placa']);
        $modelo = Security::sanitize($_POST['modelo']);
        $id_marca_veiculo = intval($_POST['id_marca_veiculo']);
        $ano = intval($_POST['ano']);
        $id_cor_veiculo = intval($_POST['id_cor_veiculo']);
        $id_tp_veiculo = intval($_POST['id_tp_veiculo']);
        $id_setor = intval($_POST['id_setor']);
        $id_situacao = intval($_POST['id_situacao']);
        $id_forma_trabalho = intval($_POST['id_forma_trabalho']);
        $capacidade_combustivel = intval($_POST['capacidade_combustivel'] ?? 9999);
        
        // Campos opcionais
        $placa_patrimonio = Security::sanitize($_POST['placa_patrimonio'] ?? '');
        $chassi = Security::sanitize($_POST['chassi'] ?? '');
        $renavam = Security::sanitize($_POST['renavam'] ?? '');
        $data_aquisicao = Security::sanitize($_POST['data_aquisicao'] ?? null);
        $id_cat_cnh = intval($_POST['id_cat_cnh'] ?? 0);
        $atividade = Security::sanitize($_POST['atividade'] ?? '');
        $tem_seguro = isset($_POST['tem_seguro']) ? 1 : 0;
        $tel_seguradora = Security::sanitize($_POST['tel_seguradora'] ?? '');
        
        // KM/Hora baseado na forma de trabalho - apenas um dos dois deve ser preenchido
        $km_inicial = null;
        $hora_inicial = null;
        
        if ($id_forma_trabalho == 1) {
            // Kilometragem
            $km_value = $_POST['km_inicial'] ?? '';
            $km_inicial = ($km_value !== '' && $km_value !== null) ? intval($km_value) : null;
            if ($km_inicial === null || $km_inicial < 0) {
                $response['message'] = 'KM inicial deve ser maior ou igual a 0 quando a forma de trabalho for Kilometragem';
                break;
            }
        } elseif ($id_forma_trabalho == 2) {
            // Hora
            $hora_value = $_POST['hora_inicial'] ?? '';
            $hora_inicial = ($hora_value !== '' && $hora_value !== null) ? intval($hora_value) : null;
            if ($hora_inicial === null || $hora_inicial < 0) {
                $response['message'] = 'Hora inicial deve ser maior ou igual a 0 quando a forma de trabalho for Hora';
                break;
            }
        }

        if (!$placa || !$modelo || !$id_marca_veiculo || !$ano || !$id_cor_veiculo || !$id_tp_veiculo || !$id_setor || !$id_situacao || !$id_forma_trabalho) {
            $response['message'] = 'Campos obrigatórios não preenchidos';
            break;
        }

        $sql = "INSERT INTO veiculo (
            id_cliente, placa, placa_patrimonio, modelo, id_marca_veiculo, ano, id_cor_veiculo,
            chassi, renavam, data_aquisicao, id_cat_cnh, capacidade_combustivel, km_inicial, hora_inicial,
            id_tp_veiculo, id_setor, id_situacao, atividade, id_forma_trabalho, tem_seguro, tel_seguradora
        ) VALUES (
            $clienteId, '$placa', " . ($placa_patrimonio ? "'$placa_patrimonio'" : "NULL") . ", '$modelo', $id_marca_veiculo, $ano, $id_cor_veiculo,
            " . ($chassi ? "'$chassi'" : "NULL") . ", " . ($renavam ? "'$renavam'" : "NULL") . ", " . ($data_aquisicao ? "'$data_aquisicao'" : "NULL") . ", 
            " . ($id_cat_cnh ? $id_cat_cnh : "NULL") . ", $capacidade_combustivel, " . ($km_inicial ?: "NULL") . ", " . ($hora_inicial ?: "NULL") . ",
            $id_tp_veiculo, $id_setor, $id_situacao, " . ($atividade ? "'$atividade'" : "NULL") . ", $id_forma_trabalho, $tem_seguro, " . ($tel_seguradora ? "'$tel_seguradora'" : "NULL") . "
        )";
        
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Veículo criado com sucesso';
            $accessControl->registrarAcao('veiculos', 'criar', "Criou veículo: $placa - $modelo");
        } else {
            $response['message'] = 'Erro ao criar veículo: ' . $db->conn->error;
        }
        break;

    case 'update':
        if (!$accessControl->verificarPermissao('veiculos', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar veículos';
            $accessControl->registrarAcao('veiculos', 'editar', 'Tentativa de editar sem permissão', 'negado');
            break;
        }

        $id = intval($_POST['id']);
        $clienteId = $_SESSION['cliente_id'] ?? null;
        if (!$clienteId) {
            $response['message'] = 'Selecione um cliente no menu superior';
            break;
        }
        
        // Campos obrigatórios
        $placa = Security::sanitize($_POST['placa']);
        $modelo = Security::sanitize($_POST['modelo']);
        $id_marca_veiculo = intval($_POST['id_marca_veiculo']);
        $ano = intval($_POST['ano']);
        $id_cor_veiculo = intval($_POST['id_cor_veiculo']);
        $id_tp_veiculo = intval($_POST['id_tp_veiculo']);
        $id_setor = intval($_POST['id_setor']);
        $id_situacao = intval($_POST['id_situacao']);
        $id_forma_trabalho = intval($_POST['id_forma_trabalho']);
        $capacidade_combustivel = intval($_POST['capacidade_combustivel'] ?? 9999);
        
        // Campos opcionais
        $placa_patrimonio = Security::sanitize($_POST['placa_patrimonio'] ?? '');
        $chassi = Security::sanitize($_POST['chassi'] ?? '');
        $renavam = Security::sanitize($_POST['renavam'] ?? '');
        $data_aquisicao = Security::sanitize($_POST['data_aquisicao'] ?? null);
        $id_cat_cnh = intval($_POST['id_cat_cnh'] ?? 0);
        $atividade = Security::sanitize($_POST['atividade'] ?? '');
        $tem_seguro = isset($_POST['tem_seguro']) ? 1 : 0;
        $tel_seguradora = Security::sanitize($_POST['tel_seguradora'] ?? '');
        
        // KM/Hora baseado na forma de trabalho - apenas um dos dois deve ser preenchido
        $km_inicial = null;
        $hora_inicial = null;
        
        if ($id_forma_trabalho == 1) {
            // Kilometragem
            $km_value = $_POST['km_inicial'] ?? '';
            $km_inicial = ($km_value !== '' && $km_value !== null) ? intval($km_value) : null;
            if ($km_inicial === null || $km_inicial < 0) {
                $response['message'] = 'KM inicial deve ser maior ou igual a 0 quando a forma de trabalho for Kilometragem';
                break;
            }
        } elseif ($id_forma_trabalho == 2) {
            // Hora
            $hora_value = $_POST['hora_inicial'] ?? '';
            $hora_inicial = ($hora_value !== '' && $hora_value !== null) ? intval($hora_value) : null;
            if ($hora_inicial === null || $hora_inicial < 0) {
                $response['message'] = 'Hora inicial deve ser maior ou igual a 0 quando a forma de trabalho for Hora';
                break;
            }
        }

        if (!$placa || !$modelo || !$id_marca_veiculo || !$ano || !$id_cor_veiculo || !$id_tp_veiculo || !$id_setor || !$id_situacao || !$id_forma_trabalho) {
            $response['message'] = 'Campos obrigatórios não preenchidos';
            break;
        }
        
        $where = "id_veiculo=$id";
        if ($clienteId) {
            $where .= " AND id_cliente=$clienteId";
        }

        $sql = "UPDATE veiculo SET 
            placa='$placa', 
            placa_patrimonio=" . ($placa_patrimonio ? "'$placa_patrimonio'" : "NULL") . ",
            modelo='$modelo', 
            id_marca_veiculo=$id_marca_veiculo, 
            ano=$ano, 
            id_cor_veiculo=$id_cor_veiculo,
            chassi=" . ($chassi ? "'$chassi'" : "NULL") . ", 
            renavam=" . ($renavam ? "'$renavam'" : "NULL") . ", 
            data_aquisicao=" . ($data_aquisicao ? "'$data_aquisicao'" : "NULL") . ",
            id_cat_cnh=" . ($id_cat_cnh ? $id_cat_cnh : "NULL") . ",
            capacidade_combustivel=$capacidade_combustivel,
            km_inicial=" . ($km_inicial ?: "NULL") . ",
            hora_inicial=" . ($hora_inicial ?: "NULL") . ",
            id_tp_veiculo=$id_tp_veiculo,
            id_setor=$id_setor,
            id_situacao=$id_situacao,
            atividade=" . ($atividade ? "'$atividade'" : "NULL") . ",
            id_forma_trabalho=$id_forma_trabalho,
            tem_seguro=$tem_seguro,
            tel_seguradora=" . ($tel_seguradora ? "'$tel_seguradora'" : "NULL") . "
        WHERE $where";
        
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Veículo atualizado com sucesso';
            $accessControl->registrarAcao('veiculos', 'editar', "Atualizou veículo #$id");
        } else {
            $response['message'] = 'Erro ao atualizar veículo: ' . $db->conn->error;
        }
        break;

    case 'delete':
        if (!$accessControl->verificarPermissao('veiculos', 'excluir')) {
            $response['message'] = 'Você não possui permissão para excluir veículos';
            $accessControl->registrarAcao('veiculos', 'excluir', 'Tentativa de excluir sem permissão', 'negado');
            break;
        }

        $id = intval($_POST['id']);
        $clienteId = $_SESSION['cliente_id'] ?? null;
        if (!$clienteId) {
            $response['message'] = 'Selecione um cliente no menu superior';
            break;
        }
        
        $where = "id_veiculo = $id";
        if ($clienteId) {
            $where .= " AND id_cliente=$clienteId";
        }
        
        $sql = "DELETE FROM veiculo WHERE $where";
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Veículo excluído com sucesso';
            $accessControl->registrarAcao('veiculos', 'excluir', "Excluiu veículo #$id");
        } else {
            $response['message'] = 'Erro ao excluir veículo';
        }
        break;

    case 'list_combustiveis':
        // Listar combustíveis disponíveis e os selecionados para o veículo
        $idVeiculo = intval($_GET['id'] ?? 0);
        $clienteId = $_SESSION['cliente_id'] ?? null;
        
        if (!$idVeiculo) {
            $response['message'] = 'ID do veículo não informado';
            break;
        }
        
        // Buscar todos os produtos do tipo combustível (ajustar conforme seu banco)
        $sqlProdutos = "SELECT p.id_produto, p.descricao 
                        FROM produto p
                        WHERE p.id_situacao = 1
                        ORDER BY p.descricao ASC";
        
        $resultProdutos = $db->query($sqlProdutos);
        $produtos = [];
        
        if ($resultProdutos) {
            while ($row = $resultProdutos->fetch_assoc()) {
                $produtos[] = $row;
            }
        }
        
        // Buscar combustíveis já associados ao veículo
        $sqlSelecionados = "SELECT id_produto 
                            FROM combustivel_veiculo 
                            WHERE id_veiculo = $idVeiculo";
        
        if ($clienteId) {
            $sqlSelecionados .= " AND id_cliente = $clienteId";
        }
        
        $resultSelecionados = $db->query($sqlSelecionados);
        $selecionados = [];
        
        if ($resultSelecionados) {
            while ($row = $resultSelecionados->fetch_assoc()) {
                $selecionados[] = intval($row['id_produto']);
            }
        }
        
        $response['success'] = true;
        $response['produtos'] = $produtos;
        $response['selecionados'] = $selecionados;
        break;

    case 'save_combustiveis':
        // Salvar combustíveis do veículo
        if (!$accessControl->verificarPermissao('veiculos', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar veículos';
            break;
        }
        
        $idVeiculo = intval($_POST['id_veiculo'] ?? 0);
        $combustiveis = $_POST['combustiveis'] ?? '';
        $clienteId = $_SESSION['cliente_id'] ?? null;
        $empresaId = $_SESSION['empresa_id'] ?? 1;
        
        if (!$idVeiculo) {
            $response['message'] = 'ID do veículo não informado';
            break;
        }
        
        if (!$clienteId) {
            $response['message'] = 'Cliente não identificado';
            break;
        }
        
        // Remover combustíveis anteriores
        $sqlDelete = "DELETE FROM combustivel_veiculo 
                      WHERE id_veiculo = $idVeiculo 
                      AND id_cliente = $clienteId";
        $resultDelete = $db->query($sqlDelete);
        
        // Inserir novos combustíveis
        $totalInseridos = 0;
        if (!empty($combustiveis)) {
            $combustiveisArray = explode(',', $combustiveis);
            
            foreach ($combustiveisArray as $idProduto) {
                $idProduto = intval($idProduto);
                
                if ($idProduto > 0) {
                    $sqlInsert = "INSERT INTO combustivel_veiculo 
                                  (id_veiculo, id_cliente, id_produto) 
                                  VALUES ($idVeiculo, $clienteId, $idProduto)";
                    
                    $resultInsert = $db->query($sqlInsert);
                    if ($resultInsert) {
                        $totalInseridos++;
                    }
                }
            }
        }
        
        error_log("Total inseridos: $totalInseridos");
        
        $response['success'] = true;
        $response['message'] = 'Combustíveis salvos com sucesso';
        $response['total_inseridos'] = $totalInseridos;
        $accessControl->registrarAcao('veiculos', 'editar', "Atualizou combustíveis do veículo #$idVeiculo");
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>