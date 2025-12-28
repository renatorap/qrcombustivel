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
            if (!$accessControl->verificarPermissao('contrato', 'acessar')) {
                throw new Exception('Sem permissão para acessar contratos');
            }
            
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            $search = $_GET['search'] ?? '';
            $clienteId = $_SESSION['cliente_id'];
            
            $whereConditions = ["c.id_cliente = ?"];
            $params = [$clienteId];
            $types = 'i';
            
            // Filtro para usuários do grupo 4 (fornecedores)
            $fornecedorId = $accessControl->getFornecedorId();
            if ($fornecedorId) {
                $whereConditions[] = "c.id_fornecedor = ?";
                $params[] = $fornecedorId;
                $types .= 'i';
            }
            
            if ($search) {
                $whereConditions[] = "(c.codigo LIKE ? OR c.descricao LIKE ? OR l.codigo LIKE ? OR f.razao_social LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'ssss';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Count total
            $countSql = "SELECT COUNT(*) as total 
                        FROM contrato c
                        INNER JOIN licitacao l ON c.id_licitacao = l.id_licitacao
                        INNER JOIN fornecedor f ON c.id_fornecedor = f.id_fornecedor
                        WHERE $whereClause";
            $countStmt = $conn->prepare($countSql);
            if ($countStmt === false) {
                throw new Exception('Erro ao preparar consulta de contagem');
            }
            $countStmt->bind_param($types, ...$params);
            $countStmt->execute();
            $total = $countStmt->get_result()->fetch_assoc()['total'];
            $countStmt->close();
            
            // Get records with joins
            $sql = "SELECT c.*, 
                    l.codigo as licitacao_codigo, 
                    l.objeto as licitacao_objeto,
                    f.razao_social as fornecedor_nome,
                    (SELECT COUNT(*) FROM aditamento_combustivel WHERE id_contrato = c.id_contrato) as total_aditamentos
                    FROM contrato c
                    INNER JOIN licitacao l ON c.id_licitacao = l.id_licitacao
                    INNER JOIN fornecedor f ON c.id_fornecedor = f.id_fornecedor
                    WHERE $whereClause 
                    ORDER BY c.data DESC, c.codigo DESC 
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
            
            $contratos = [];
            while ($row = $result->fetch_assoc()) {
                $contratos[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $contratos,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'perPage' => $perPage,
                    'totalPages' => ceil($total / $perPage)
                ]
            ]);
            break;
            
        case 'get':
            if (!$accessControl->verificarPermissao('contrato', 'acessar')) {
                throw new Exception('Sem permissão para acessar contratos');
            }
            
            $id = $_GET['id'] ?? 0;
            $clienteId = $_SESSION['cliente_id'];
            
            $whereConditions = ["c.id_contrato = ?", "c.id_cliente = ?"];
            $params = [$id, $clienteId];
            $types = 'ii';
            
            // Filtro para usuários do grupo 4 (fornecedores)
            $fornecedorId = $accessControl->getFornecedorId();
            if ($fornecedorId) {
                $whereConditions[] = "c.id_fornecedor = ?";
                $params[] = $fornecedorId;
                $types .= 'i';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $sql = "SELECT c.*, 
                    l.codigo as licitacao_codigo, 
                    f.razao_social as fornecedor_nome
                    FROM contrato c
                    INNER JOIN licitacao l ON c.id_licitacao = l.id_licitacao
                    INNER JOIN fornecedor f ON c.id_fornecedor = f.id_fornecedor
                    WHERE $whereClause";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta');
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Contrato não encontrado');
            }
            
            $contrato = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $contrato
            ]);
            break;
            
        case 'get_licitacoes':
            if (!$accessControl->verificarPermissao('contrato', 'acessar')) {
                throw new Exception('Sem permissão para acessar licitações');
            }
            
            $clienteId = $_SESSION['cliente_id'];
            
            $sql = "SELECT id_licitacao, codigo, objeto, data 
                    FROM licitacao 
                    WHERE id_cliente = ? 
                    ORDER BY data DESC, codigo DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $clienteId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $licitacoes = [];
            while ($row = $result->fetch_assoc()) {
                $licitacoes[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $licitacoes
            ]);
            break;
            
        case 'get_fornecedores':
            if (!$accessControl->verificarPermissao('contrato', 'acessar')) {
                throw new Exception('Sem permissão para acessar fornecedores');
            }
            
            $clienteId = $_SESSION['cliente_id'];
            
            $sql = "SELECT id_fornecedor, razao_social, nome_fantasia, cnpj 
                    FROM fornecedor 
                    WHERE id_cliente = ? AND id_situacao = 1
                    ORDER BY razao_social";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $clienteId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $fornecedores = [];
            while ($row = $result->fetch_assoc()) {
                $fornecedores[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $fornecedores
            ]);
            break;
            
        case 'create':
            if (!$accessControl->verificarPermissao('contrato', 'criar')) {
                throw new Exception('Sem permissão para criar contratos');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['codigo']) || empty($data['data']) || empty($data['descricao']) || 
                empty($data['id_licitacao']) || empty($data['id_fornecedor'])) {
                throw new Exception('Preencha todos os campos obrigatórios');
            }
            
            $codigo = trim($data['codigo']);
            $dataContrato = trim($data['data']);
            $descricao = trim($data['descricao']);
            $idLicitacao = (int)$data['id_licitacao'];
            $idFornecedor = (int)$data['id_fornecedor'];
            $empresaId = $_SESSION['empresa_id'];
            $clienteId = $_SESSION['cliente_id'];
            
            // Verificar se licitação existe e pertence ao cliente
            $checkLicitacao = "SELECT id_licitacao FROM licitacao WHERE id_licitacao = ? AND id_cliente = ?";
            $stmtCheck = $conn->prepare($checkLicitacao);
            $stmtCheck->bind_param('ii', $idLicitacao, $clienteId);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) {
                throw new Exception('Licitação não encontrada');
            }
            $stmtCheck->close();
            
            // Verificar se fornecedor existe e pertence ao cliente
            $checkFornecedor = "SELECT id_fornecedor FROM fornecedor WHERE id_fornecedor = ? AND id_cliente = ?";
            $stmtCheck = $conn->prepare($checkFornecedor);
            $stmtCheck->bind_param('ii', $idFornecedor, $clienteId);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) {
                throw new Exception('Fornecedor não encontrado');
            }
            $stmtCheck->close();
            
            // Verificar se código já existe para este cliente
            $checkSql = "SELECT id_contrato FROM contrato WHERE codigo = ? AND id_cliente = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('si', $codigo, $clienteId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Já existe um contrato com este código');
            }
            $checkStmt->close();
            
            $sql = "INSERT INTO contrato (codigo, data, descricao, id_licitacao, id_fornecedor, id_cliente) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar inserção');
            }
            
            $stmt->bind_param('sssiiii', $codigo, $dataContrato, $descricao, $idLicitacao, $idFornecedor, $clienteId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao criar contrato');
            }
            
            $novoId = $conn->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Contrato criado com sucesso',
                'id' => $novoId
            ]);
            break;
            
        case 'update':
            if (!$accessControl->verificarPermissao('contrato', 'editar')) {
                throw new Exception('Sem permissão para editar contratos');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || empty($data['codigo']) || empty($data['data']) || 
                empty($data['descricao']) || empty($data['id_licitacao']) || empty($data['id_fornecedor'])) {
                throw new Exception('Preencha todos os campos obrigatórios');
            }
            
            $id = (int)$data['id'];
            $codigo = trim($data['codigo']);
            $dataContrato = trim($data['data']);
            $descricao = trim($data['descricao']);
            $idLicitacao = (int)$data['id_licitacao'];
            $idFornecedor = (int)$data['id_fornecedor'];
            $clienteId = $_SESSION['cliente_id'];
            
            // Verificar se licitação existe e pertence ao cliente
            $checkLicitacao = "SELECT id_licitacao FROM licitacao WHERE id_licitacao = ? AND id_cliente = ?";
            $stmtCheck = $conn->prepare($checkLicitacao);
            $stmtCheck->bind_param('ii', $idLicitacao, $clienteId);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) {
                throw new Exception('Licitação não encontrada');
            }
            $stmtCheck->close();
            
            // Verificar se fornecedor existe e pertence ao cliente
            $checkFornecedor = "SELECT id_fornecedor FROM fornecedor WHERE id_fornecedor = ? AND id_cliente = ?";
            $stmtCheck = $conn->prepare($checkFornecedor);
            $stmtCheck->bind_param('ii', $idFornecedor, $clienteId);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) {
                throw new Exception('Fornecedor não encontrado');
            }
            $stmtCheck->close();
            
            // Verificar se código já existe em outro contrato
            $checkSql = "SELECT id_contrato FROM contrato WHERE codigo = ? AND id_cliente = ? AND id_contrato != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('sii', $codigo, $clienteId, $id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Já existe outro contrato com este código');
            }
            $checkStmt->close();
            
            $sql = "UPDATE contrato SET codigo = ?, data = ?, descricao = ?, id_licitacao = ?, id_fornecedor = ? 
                    WHERE id_contrato = ? AND id_cliente = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar atualização');
            }
            
            $stmt->bind_param('sssiiii', $codigo, $dataContrato, $descricao, $idLicitacao, $idFornecedor, $id, $clienteId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar contrato');
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Contrato atualizado com sucesso'
            ]);
            break;
            
        case 'delete':
            if (!$accessControl->verificarPermissao('contrato', 'excluir')) {
                throw new Exception('Sem permissão para excluir contratos');
            }
            
            $id = $_GET['id'] ?? 0;
            $clienteId = $_SESSION['cliente_id'];
            
            // Verificar se existem aditamentos vinculados
            $checkSql = "SELECT COUNT(*) as total FROM aditamento_combustivel WHERE id_contrato = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            $totalAditamentos = $checkStmt->get_result()->fetch_assoc()['total'];
            $checkStmt->close();
            
            if ($totalAditamentos > 0) {
                throw new Exception("Não é possível excluir este contrato pois existem $totalAditamentos aditamento(s) vinculado(s)");
            }
            
            $sql = "DELETE FROM contrato WHERE id_contrato = ? AND id_cliente = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar exclusão');
            }
            
            $stmt->bind_param('ii', $id, $clienteId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao excluir contrato');
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Contrato não encontrado');
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Contrato excluído com sucesso'
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
