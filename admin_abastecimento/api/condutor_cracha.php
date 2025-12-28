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

if (!$accessControl->verificarPermissao('condutores', 'acessar')) {
    $response['message'] = 'Você não possui permissão para acessar crachás';
    echo json_encode($response);
    exit;
}

$db = new Database();
$db->connect();
$clienteId = $_SESSION['cliente_id'] ?? null;

switch ($action) {
    case 'verificar_via':
        // Verificar se existe via válida do crachá
        $idCondutor = intval($_GET['id_condutor'] ?? 0);
        
        if (!$idCondutor) {
            $response['message'] = 'ID do condutor não informado';
            break;
        }
        
        $sql = "SELECT COUNT(*) as total
                FROM condutor_qrcode cq
                WHERE cq.id_condutor = $idCondutor 
                AND cq.id_cliente = $clienteId
                AND cq.id_situacao = 1
                AND cq.fim_vigencia IS NULL";
        
        $result = $db->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            $response['success'] = true;
            $response['tem_via'] = $row['total'] > 0;
            if (!$response['tem_via']) {
                $response['message'] = 'Condutor não possui via de crachá válida';
            }
        } else {
            $response['message'] = 'Erro ao verificar via: ' . $db->getError();
        }
        break;
    
    case 'get_via_ativa':
        // Buscar a via ativa do crachá do condutor
        $idCondutor = intval($_GET['id_condutor'] ?? 0);
        
        if (!$idCondutor) {
            $response['message'] = 'ID do condutor não informado';
            break;
        }
        
        $sql = "SELECT cq.*, c.nome, c.cpf, c.cnh, c.foto, c.id_cargo,
                       car.descricao as cargo_nome,
                       s.descricao as situacao_nome
                FROM condutor_qrcode cq
                INNER JOIN condutor c ON cq.id_condutor = c.id_condutor
                LEFT JOIN cargo car ON c.id_cargo = car.id_cargo
                LEFT JOIN situacao s ON cq.id_situacao = s.id_situacao
                WHERE cq.id_condutor = $idCondutor 
                AND cq.id_cliente = $clienteId
                AND cq.id_situacao = 1
                AND cq.fim_vigencia IS NULL
                ORDER BY cq.inicio_vigencia DESC
                LIMIT 1";
        
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $response['success'] = true;
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Nenhuma via ativa encontrada para este condutor';
        }
        break;
    
    case 'gerar_nova_via':
        // Gerar manualmente uma nova via do crachá (inativa as anteriores)
        if (!$accessControl->verificarPermissao('condutores', 'editar')) {
            $response['message'] = 'Você não possui permissão para gerar novas vias';
            break;
        }
        
        $idCondutor = intval($_POST['id_condutor'] ?? 0);
        
        if (!$idCondutor) {
            $response['message'] = 'ID do condutor não informado';
            break;
        }
        
        // Verificar se o condutor existe e está ativo
        $sql = "SELECT id_condutor, nome, id_situacao 
                FROM condutor 
                WHERE id_condutor = $idCondutor 
                AND id_cliente = $clienteId";
        $result = $db->query($sql);
        
        if (!$result || $result->num_rows === 0) {
            $response['message'] = 'Condutor não encontrado';
            break;
        }
        
        $condutor = $result->fetch_assoc();
        
        if ($condutor['id_situacao'] != 1) {
            $response['message'] = 'Não é possível gerar crachá para condutor inativo';
            break;
        }
        
        // Inativar todas as vias anteriores
        $sql = "UPDATE condutor_qrcode 
                SET id_situacao = 2, 
                    fim_vigencia = CURRENT_TIMESTAMP()
                WHERE id_condutor = $idCondutor 
                AND id_situacao = 1";
        $db->query($sql);
        
        // Criar nova via
        $sql = "INSERT INTO condutor_qrcode (id_condutor, id_cliente, id_situacao, inicio_vigencia, fim_vigencia, dt_insert, dt_update)
                VALUES ($idCondutor, $clienteId, 1, CURRENT_TIMESTAMP(), NULL, CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP())";
        
        if ($db->query($sql)) {
            $idVia = $db->lastInsertId();
            
            // Gerar código único para a via
            $codigoUnico = 'CRC' . str_pad($idVia, 10, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($idVia . $idCondutor . time()), 0, 8));
            
            // Atualizar com o código único
            $sqlUpdate = "UPDATE condutor_qrcode SET codigo_unico = '$codigoUnico' WHERE id = $idVia";
            $db->query($sqlUpdate);
            
            $response['success'] = true;
            $response['message'] = 'Nova via do crachá gerada com sucesso';
            $response['data'] = ['id_via' => $idVia, 'codigo_unico' => $codigoUnico];
            $accessControl->registrarAcao('condutores', 'criar', "Gerou nova via do crachá para: " . $condutor['nome']);
        } else {
            $response['message'] = 'Erro ao gerar nova via: ' . $db->getError();
        }
        break;
    
    case 'listar_historico':
        // Listar histórico de vias do crachá do condutor
        $idCondutor = intval($_GET['id_condutor'] ?? 0);
        
        if (!$idCondutor) {
            $response['message'] = 'ID do condutor não informado';
            break;
        }
        
        $sql = "SELECT cq.*, s.descricao as situacao_nome
                FROM condutor_qrcode cq
                LEFT JOIN situacao s ON cq.id_situacao = s.id_situacao
                WHERE cq.id_condutor = $idCondutor 
                AND cq.id_cliente = $clienteId
                ORDER BY cq.inicio_vigencia DESC";
        
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
