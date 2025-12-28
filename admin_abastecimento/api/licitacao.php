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
    $conn = $db->getConnection();
    $accessControl = new AccessControl($_SESSION['userId']);
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            if (!$accessControl->verificarPermissao('licitacao', 'acessar')) {
                throw new Exception('Sem permissão para acessar licitações');
            }
            
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
            $offset = ($page - 1) * $perPage;
            $search = $_GET['search'] ?? '';
            $clienteId = $_SESSION['cliente_id'] ?? null;
            
            if (!$clienteId) {
                throw new Exception('Cliente não identificado na sessão');
            }
            
            $whereConditions = ["l.id_cliente = ?"];
            $params = [$clienteId];
            $types = 'i';
            
            // Filtro para usuários do grupo 4 (fornecedores) - mostrar apenas licitações com contratos do fornecedor
            $fornecedorId = $accessControl->getFornecedorId();
            $fornecedorJoin = '';
            if ($fornecedorId) {
                $fornecedorJoin = " INNER JOIN contrato c ON l.id_licitacao = c.id_licitacao AND c.id_fornecedor = ?";
                $params[] = $fornecedorId;
                $types .= 'i';
            }
            
            if ($search) {
                $whereConditions[] = "(l.codigo LIKE ? OR l.objeto LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'ss';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Count total
            $countSql = "SELECT COUNT(DISTINCT l.id_licitacao) as total FROM licitacao l $fornecedorJoin WHERE $whereClause";
            $countStmt = $conn->prepare($countSql);
            if ($countStmt === false) {
                throw new Exception('Erro ao preparar consulta de contagem');
            }
            $countStmt->bind_param($types, ...$params);
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            $countStmt->close();
            
            // Get records with contrato count
            $contratoCountSql = $fornecedorId 
                ? "(SELECT COUNT(*) FROM contrato WHERE id_licitacao = l.id_licitacao AND id_fornecedor = $fornecedorId)"
                : "(SELECT COUNT(*) FROM contrato WHERE id_licitacao = l.id_licitacao)";
            
            $sql = "SELECT DISTINCT l.*, 
                    $contratoCountSql as total_contratos
                    FROM licitacao l 
                    $fornecedorJoin
                    WHERE $whereClause 
                    ORDER BY l.data DESC, l.codigo DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta');
            }
            
            $params[] = $perPage;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $licitacoes = [];
            while ($row = $result->fetch_assoc()) {
                $licitacoes[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $licitacoes,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'perPage' => $perPage,
                    'totalPages' => ceil($total / $perPage)
                ]
            ]);
            break;
            
        case 'get':
            if (!$accessControl->verificarPermissao('licitacao', 'acessar')) {
                throw new Exception('Sem permissão para acessar licitações');
            }
            
            $id = $_GET['id'] ?? 0;
            $clienteId = $_SESSION['cliente_id'];
            
            $sql = "SELECT * FROM licitacao WHERE id_licitacao = ? AND id_cliente = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta');
            }
            
            $stmt->bind_param('ii', $id, $clienteId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Licitação não encontrada');
            }
            
            $licitacao = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $licitacao
            ]);
            break;
            
        case 'create':
            if (!$accessControl->verificarPermissao('licitacao', 'criar')) {
                throw new Exception('Sem permissão para criar licitações');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['codigo']) || empty($data['data']) || empty($data['objeto'])) {
                throw new Exception('Preencha todos os campos obrigatórios');
            }
            
            $codigo = trim($data['codigo']);
            $dataLicitacao = trim($data['data']);
            $objeto = trim($data['objeto']);
            $empresaId = $_SESSION['empresa_id'];
            $clienteId = $_SESSION['cliente_id'];
            
            // Verificar se código já existe para este cliente
            $checkSql = "SELECT id_licitacao FROM licitacao WHERE codigo = ? AND id_cliente = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('si', $codigo, $clienteId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Já existe uma licitação com este código');
            }
            $checkStmt->close();
            
            $sql = "INSERT INTO licitacao (codigo, data, objeto, id_cliente) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar inserção');
            }
            
            $stmt->bind_param('sssi', $codigo, $dataLicitacao, $objeto, $clienteId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao criar licitação');
            }
            
            $novoId = $conn->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Licitação criada com sucesso',
                'id' => $novoId
            ]);
            break;
            
        case 'update':
            if (!$accessControl->verificarPermissao('licitacao', 'editar')) {
                throw new Exception('Sem permissão para editar licitações');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || empty($data['codigo']) || empty($data['data']) || empty($data['objeto'])) {
                throw new Exception('Preencha todos os campos obrigatórios');
            }
            
            $id = (int)$data['id'];
            $codigo = trim($data['codigo']);
            $dataLicitacao = trim($data['data']);
            $objeto = trim($data['objeto']);
            $clienteId = $_SESSION['cliente_id'];
            
            // Verificar se código já existe em outra licitação
            $checkSql = "SELECT id_licitacao FROM licitacao WHERE codigo = ? AND id_cliente = ? AND id_licitacao != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('sii', $codigo, $clienteId, $id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Já existe outra licitação com este código');
            }
            $checkStmt->close();
            
            $sql = "UPDATE licitacao SET codigo = ?, data = ?, objeto = ? 
                    WHERE id_licitacao = ? AND id_cliente = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar atualização');
            }
            
            $stmt->bind_param('sssii', $codigo, $dataLicitacao, $objeto, $id, $clienteId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar licitação');
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Licitação atualizada com sucesso'
            ]);
            break;
            
        case 'delete':
            if (!$accessControl->verificarPermissao('licitacao', 'excluir')) {
                throw new Exception('Sem permissão para excluir licitações');
            }
            
            $id = $_GET['id'] ?? 0;
            $clienteId = $_SESSION['cliente_id'];
            
            // Verificar se existem contratos vinculados
            $checkSql = "SELECT COUNT(*) as total FROM contrato WHERE id_licitacao = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            $totalContratos = $checkStmt->get_result()->fetch_assoc()['total'];
            $checkStmt->close();
            
            if ($totalContratos > 0) {
                throw new Exception("Não é possível excluir esta licitação pois existem $totalContratos contrato(s) vinculado(s)");
            }
            
            $sql = "DELETE FROM licitacao WHERE id_licitacao = ? AND id_cliente = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar exclusão');
            }
            
            $stmt->bind_param('ii', $id, $clienteId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao excluir licitação');
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Licitação não encontrada');
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Licitação excluída com sucesso'
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
