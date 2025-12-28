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
        case 'autocomplete_nome':
            if (!$accessControl->verificarPermissao('condutor', 'acessar')) {
                throw new Exception('Sem permissão');
            }
            
            $clienteId = $_SESSION['cliente_id'];
            $termo = $_GET['termo'] ?? '';
            
            if (strlen($termo) < 2) {
                echo json_encode(['success' => true, 'data' => []]);
                break;
            }
            
            $sql = "SELECT nome, cnh
                    FROM condutor
                    WHERE id_cliente = ? AND UPPER(nome) LIKE UPPER(?)
                    ORDER BY nome
                    LIMIT 10";
            $stmt = $db->prepare($sql);
            $termoLike = "%$termo%";
            $stmt->bind_param('is', $clienteId, $termoLike);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $condutores = [];
            while ($row = $result->fetch_assoc()) {
                $condutores[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $condutores
            ]);
            break;
            
        case 'autocomplete_cnh':
            if (!$accessControl->verificarPermissao('condutor', 'acessar')) {
                throw new Exception('Sem permissão');
            }
            
            $clienteId = $_SESSION['cliente_id'];
            $termo = $_GET['termo'] ?? '';
            
            if (strlen($termo) < 2) {
                echo json_encode(['success' => true, 'data' => []]);
                break;
            }
            
            $sql = "SELECT nome, cnh
                    FROM condutor
                    WHERE id_cliente = ? AND UPPER(cnh) LIKE UPPER(?)
                    ORDER BY nome
                    LIMIT 10";
            $stmt = $db->prepare($sql);
            $termoLike = "%$termo%";
            $stmt->bind_param('is', $clienteId, $termoLike);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $condutores = [];
            while ($row = $result->fetch_assoc()) {
                $condutores[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $condutores
            ]);
            break;
            
        case 'get_condutores':
            if (!$accessControl->verificarPermissao('condutor', 'acessar')) {
                throw new Exception('Sem permissão');
            }
            
            $clienteId = $_SESSION['cliente_id'];
            $nome = $_GET['nome'] ?? '';
            $cnh = $_GET['cnh'] ?? '';
            $validadeCNH = $_GET['validade_cnh'] ?? '';
            $situacao = $_GET['situacao'] ?? '';
            
            $whereConditions = ["c.id_cliente = ?"];
            $params = [$clienteId];
            $types = 'i';
            
            if ($nome) {
                $whereConditions[] = "c.nome LIKE ?";
                $params[] = "%$nome%";
                $types .= 's';
            }
            
            if ($cnh) {
                $whereConditions[] = "c.cnh LIKE ?";
                $params[] = "%$cnh%";
                $types .= 's';
            }
            
            if ($situacao !== '') {
                $whereConditions[] = "c.id_situacao = ?";
                $params[] = (int)$situacao;
                $types .= 'i';
            }
            
            // Aplicar filtro de validade CNH
            if ($validadeCNH === 'valida') {
                $whereConditions[] = "c.validade_cnh IS NOT NULL";
                $whereConditions[] = "c.validade_cnh > DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            } elseif ($validadeCNH === 'a_expirar') {
                $whereConditions[] = "c.validade_cnh IS NOT NULL";
                $whereConditions[] = "c.validade_cnh >= CURDATE()";
                $whereConditions[] = "c.validade_cnh <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            } elseif ($validadeCNH === 'expirado') {
                $whereConditions[] = "c.validade_cnh IS NOT NULL";
                $whereConditions[] = "c.validade_cnh < CURDATE()";
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $sql = "SELECT c.id_condutor, c.nome, c.cnh, c.validade_cnh, c.id_situacao,
                           s.descricao as situacao,
                           DATE_FORMAT(c.validade_cnh, '%d/%m/%Y') as validade_cnh_formatada,
                           CASE 
                               WHEN c.validade_cnh IS NULL THEN NULL
                               WHEN c.validade_cnh < CURDATE() THEN 'Expirada'
                               WHEN c.validade_cnh <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'A Expirar'
                               ELSE 'Válida'
                           END as status_cnh
                    FROM condutor c
                    LEFT JOIN situacao s ON c.id_situacao = s.id_situacao
                    WHERE $whereClause
                    ORDER BY c.nome";
            
            $stmt = $db->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta: ' . $db->getError());
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $condutores = [];
            while ($row = $result->fetch_assoc()) {
                $condutores[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $condutores,
                'total' => count($condutores)
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
