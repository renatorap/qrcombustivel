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

$accessControl = new AccessControl($_SESSION['userId']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!$accessControl->verificarPermissao('veiculos', 'acessar')) {
    $response['message'] = 'Você não possui permissão para acessar QR Codes de veículos';
    echo json_encode($response);
    exit;
}

$db = new Database();
$db->connect();
$clienteId = $_SESSION['cliente_id'] ?? null;

switch ($action) {
    case 'verificar_via':
        // Verificar se existe via válida do QR Code
        $idVeiculo = intval($_GET['id_veiculo'] ?? 0);
        
        if (!$idVeiculo) {
            $response['message'] = 'ID do veículo não informado';
            break;
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM veiculo_qrcode vq
                WHERE vq.id_veiculo = $idVeiculo 
                AND vq.id_cliente = $clienteId
                AND vq.id_situacao = 1
                AND vq.fim_vigencia IS NULL";
        
        $result = $db->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            $response['success'] = true;
            $response['tem_via'] = $row['total'] > 0;
            if (!$response['tem_via']) {
                $response['message'] = 'Veículo não possui via de QR Code válida';
            }
        } else {
            $response['message'] = 'Erro ao verificar via: ' . $db->getError();
        }
        break;
    
    case 'get_via_ativa':
        // Buscar a via ativa do QR Code do veículo
        $idVeiculo = intval($_GET['id_veiculo'] ?? 0);
        
        if (!$idVeiculo) {
            $response['message'] = 'ID do veículo não informado';
            break;
        }
        
        $sql = "SELECT vq.*, v.placa, v.modelo,
                       s.descricao as situacao_nome
                FROM veiculo_qrcode vq
                INNER JOIN veiculo v ON vq.id_veiculo = v.id_veiculo
                LEFT JOIN situacao s ON vq.id_situacao = s.id_situacao
                WHERE vq.id_veiculo = $idVeiculo 
                AND vq.id_cliente = $clienteId
                AND vq.id_situacao = 1
                AND vq.fim_vigencia IS NULL
                ORDER BY vq.inicio_vigencia DESC
                LIMIT 1";
        
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $response['success'] = true;
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Nenhuma via ativa encontrada para este veículo';
        }
        break;
    
    case 'gerar_nova_via':
        // Gerar manualmente uma nova via do QR Code (inativa as anteriores)
        if (!$accessControl->verificarPermissao('veiculos', 'editar')) {
            $response['message'] = 'Você não possui permissão para gerar novas vias';
            break;
        }
        
        $idVeiculo = intval($_POST['id_veiculo'] ?? 0);
        
        if (!$idVeiculo) {
            $response['message'] = 'ID do veículo não informado';
            break;
        }
        
        // Verificar se o veículo existe e está ativo
        $sql = "SELECT id_veiculo, placa, modelo, id_situacao 
                FROM veiculo 
                WHERE id_veiculo = $idVeiculo 
                AND id_cliente = $clienteId";
        $result = $db->query($sql);
        
        if (!$result || $result->num_rows === 0) {
            $response['message'] = 'Veículo não encontrado';
            break;
        }
        
        $veiculo = $result->fetch_assoc();
        
        if ($veiculo['id_situacao'] != 1) {
            $response['message'] = 'Não é possível gerar QR Code para veículo inativo';
            break;
        }
        
        // Inativar todas as vias anteriores
        $sql = "UPDATE veiculo_qrcode 
                SET id_situacao = 2, 
                    fim_vigencia = CURRENT_TIMESTAMP()
                WHERE id_veiculo = $idVeiculo 
                AND id_situacao = 1";
        $db->query($sql);
        
        // Criar nova via
        $sql = "INSERT INTO veiculo_qrcode (id_veiculo, id_cliente, id_situacao, inicio_vigencia, fim_vigencia, dt_insert, dt_update)
                VALUES ($idVeiculo, $clienteId, 1, CURRENT_TIMESTAMP(), NULL, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP())";
        
        if ($db->query($sql)) {
            $idVia = $db->lastInsertId();
            
            // Gerar código único para a via
            $codigoUnico = 'VEI' . str_pad($idVia, 10, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($idVia . $idVeiculo . time()), 0, 8));
            
            // Atualizar com o código único
            $sqlUpdate = "UPDATE veiculo_qrcode SET codigo_unico = '$codigoUnico' WHERE id = $idVia";
            $db->query($sqlUpdate);
            
            $response['success'] = true;
            $response['message'] = 'Nova via do QR Code gerada com sucesso';
            $response['data'] = ['id_via' => $idVia, 'codigo_unico' => $codigoUnico];
            $accessControl->registrarAcao('veiculos', 'criar', "Gerou nova via do QR Code para: " . $veiculo['placa']);
        } else {
            $response['message'] = 'Erro ao gerar nova via: ' . $db->getError();
        }
        break;
    
    case 'listar_historico':
        // Listar histórico de vias do QR Code do veículo
        $idVeiculo = intval($_GET['id_veiculo'] ?? 0);
        
        if (!$idVeiculo) {
            $response['message'] = 'ID do veículo não informado';
            break;
        }
        
        $sql = "SELECT vq.*, s.descricao as situacao_nome
                FROM veiculo_qrcode vq
                LEFT JOIN situacao s ON vq.id_situacao = s.id_situacao
                WHERE vq.id_veiculo = $idVeiculo 
                AND vq.id_cliente = $clienteId
                ORDER BY vq.inicio_vigencia DESC";
        
        $result = $db->query($sql);
        
        if ($result) {
            $vias = [];
            while ($row = $result->fetch_assoc()) {
                $vias[] = $row;
            }
            $response['success'] = true;
            $response['data'] = $vias;
        } else {
            $response['message'] = 'Erro ao buscar histórico: ' . $db->getError();
        }
        break;
    
    default:
        $response['message'] = 'Ação inválida';
        break;
}

echo json_encode($response);
?>
