<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

header('Content-Type: application/json');

try {
    if (empty($_SESSION['token'])) {
        throw new Exception('Não autenticado');
    }

    $token = Security::validateToken($_SESSION['token']);
    if (!$token) {
        throw new Exception('Token inválido');
    }

    $db = new Database();
    $db->connect();
    $accessControl = new AccessControl($_SESSION['userId']);
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_setores':
            if (!$accessControl->verificarPermissao('relatorio_veiculos', 'acessar')) {
                throw new Exception('Sem permissão');
            }
            
            $clienteId = $_SESSION['cliente_id'];
            
            $sql = "SELECT DISTINCT s.id_setor, s.descricao 
                    FROM setor s
                    INNER JOIN veiculo v ON s.id_setor = v.id_setor
                    WHERE s.id_cliente = ?
                    ORDER BY s.descricao";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('i', $clienteId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $setores = [];
            while ($row = $result->fetch_assoc()) {
                $setores[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $setores
            ]);
            break;
            
        case 'get_situacoes':
            if (!$accessControl->verificarPermissao('relatorio_veiculos', 'acessar')) {
                throw new Exception('Sem permissão');
            }
            
            $sql = "SELECT id_situacao, descricao 
                    FROM situacao 
                    ORDER BY descricao";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $situacoes = [];
            while ($row = $result->fetch_assoc()) {
                $situacoes[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $situacoes
            ]);
            break;
            
        case 'autocomplete_placa':
            if (!$accessControl->verificarPermissao('relatorio_veiculos', 'acessar')) {
                throw new Exception('Sem permissão');
            }
            
            $clienteId = $_SESSION['cliente_id'];
            $termo = $_GET['termo'] ?? '';
            
            if (strlen($termo) < 2) {
                echo json_encode(['success' => true, 'data' => []]);
                break;
            }
            
            $sql = "SELECT v.placa, v.modelo, m.descricao as marca
                    FROM veiculo v
                    LEFT JOIN marca_veiculo m ON v.id_marca_veiculo = m.id_marca_veiculo
                    WHERE v.id_cliente = ? AND UPPER(v.placa) LIKE UPPER(?)
                    ORDER BY v.placa
                    LIMIT 10";
            $stmt = $db->prepare($sql);
            $termoLike = "%$termo%";
            $stmt->bind_param('is', $clienteId, $termoLike);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $veiculos = [];
            while ($row = $result->fetch_assoc()) {
                $veiculos[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $veiculos
            ]);
            break;
            
        case 'get_veiculos':
            if (!$accessControl->verificarPermissao('relatorio_veiculos', 'acessar')) {
                throw new Exception('Sem permissão');
            }
            
            $clienteId = $_SESSION['cliente_id'];
            $placa = $_GET['placa'] ?? '';
            $idSetores = $_GET['id_setor'] ?? [];
            $idSituacao = $_GET['id_situacao'] ?? '';
            
            $whereConditions = ["v.id_cliente = ?"];
            $params = [$clienteId];
            $types = 'i';
            
            if ($placa) {
                $whereConditions[] = "v.placa LIKE ?";
                $params[] = "%$placa%";
                $types .= 's';
            }
            
            if (!empty($idSetores) && is_array($idSetores)) {
                $placeholders = implode(',', array_fill(0, count($idSetores), '?'));
                $whereConditions[] = "v.id_setor IN ($placeholders)";
                foreach ($idSetores as $setor) {
                    $params[] = (int)$setor;
                    $types .= 'i';
                }
            }
            
            if ($idSituacao) {
                $whereConditions[] = "v.id_situacao = ?";
                $params[] = (int)$idSituacao;
                $types .= 'i';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $sql = "SELECT v.id_veiculo, v.placa, v.modelo, 
                           m.descricao as marca,
                           s.descricao as setor, 
                           sit.descricao as situacao,
                           v.id_setor, v.id_situacao
                    FROM veiculo v
                    LEFT JOIN setor s ON v.id_setor = s.id_setor
                    LEFT JOIN marca_veiculo m ON v.id_marca_veiculo = m.id_marca_veiculo
                    INNER JOIN situacao sit ON v.id_situacao = sit.id_situacao
                    WHERE $whereClause
                    ORDER BY v.placa";
            
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta');
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $veiculos = [];
            while ($row = $result->fetch_assoc()) {
                $veiculos[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $veiculos,
                'total' => count($veiculos)
            ]);
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
