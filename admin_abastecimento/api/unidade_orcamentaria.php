<?php
require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

// Validar token
$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$response = ['success' => false];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$clienteId = $_SESSION['cliente_id'] ?? null;

switch ($action) {
    case 'list':
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = PAGINATION_LIMIT;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        
        $whereClause = "WHERE u.id_cliente = ?";
        $params = [$clienteId];
        $types = 'i';
        
        if (!empty($search)) {
            $whereClause .= " AND (u.codigo LIKE ? OR u.descricao LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= 'ss';
        }
        
        // Contar total
        $sqlCount = "SELECT COUNT(*) as total FROM un_orcamentaria u $whereClause";
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $total = $stmtCount->get_result()->fetch_assoc()['total'];
        $totalPages = ceil($total / $limit);
        
        // Buscar registros
        $sql = "SELECT u.id_un_orcam as id, u.codigo, u.descricao as nome, u.descricao, u.ativo, u.id_cliente, u.created_at, u.updated_at 
                FROM un_orcamentaria u 
                $whereClause 
                ORDER BY u.descricao 
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $unidades = [];
        while ($row = $result->fetch_assoc()) {
            $unidades[] = $row;
        }
        
        $response['success'] = true;
        $response['unidades'] = $unidades;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;
        
    case 'list_all':
        $sql = "SELECT id_un_orcam as id, codigo, descricao as nome FROM un_orcamentaria 
                WHERE id_cliente = ? AND ativo = 1 
                ORDER BY descricao";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $clienteId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $unidades = [];
        while ($row = $result->fetch_assoc()) {
            $unidades[] = $row;
        }
        
        $response['success'] = true;
        $response['unidades'] = $unidades;
        break;
        
    case 'get':
        $id = (int)$_GET['id'];
        $sql = "SELECT id_un_orcam as id, codigo, descricao as nome, descricao, ativo, id_cliente, created_at, updated_at 
                FROM un_orcamentaria WHERE id_un_orcam = ? AND id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $clienteId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['unidade'] = $row;
        } else {
            $response['message'] = 'Unidade não encontrada';
        }
        break;
        
    case 'create':
        $codigo = $_POST['codigo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
        $empresaId = $_SESSION['empresa_id'] ?? 1;
        
        if (empty($codigo) || empty($descricao)) {
            $response['message'] = 'Código e descrição são obrigatórios';
            break;
        }
        
        // Verificar se código já existe
        $sqlCheck = "SELECT id_un_orcam FROM un_orcamentaria WHERE codigo = ? AND id_cliente = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param('si', $codigo, $clienteId);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $response['message'] = 'Código já cadastrado';
            break;
        }
        
        $sql = "INSERT INTO un_orcamentaria (codigo, descricao, ativo, id_cliente) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssii', $codigo, $descricao, $ativo, $clienteId);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Unidade criada com sucesso';
            $response['id'] = $stmt->insert_id;
        } else {
            $response['message'] = 'Erro ao criar unidade';
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $codigo = $_POST['codigo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
        
        if (empty($codigo) || empty($descricao)) {
            $response['message'] = 'Código e descrição são obrigatórios';
            break;
        }
        
        // Verificar se código já existe em outro registro
        $sqlCheck = "SELECT id_un_orcam FROM un_orcamentaria WHERE codigo = ? AND id_cliente = ? AND id_un_orcam != ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param('sii', $codigo, $clienteId, $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $response['message'] = 'Código já cadastrado';
            break;
        }
        
        $sql = "UPDATE un_orcamentaria 
                SET codigo = ?, descricao = ?, ativo = ? 
                WHERE id_un_orcam = ? AND id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssiii', $codigo, $descricao, $ativo, $id, $clienteId);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Unidade atualizada com sucesso';
        } else {
            $response['message'] = 'Erro ao atualizar unidade';
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        // Verificar se há setores vinculados
        $sqlCheck = "SELECT COUNT(*) as total FROM setor WHERE id_un_orcam = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param('i', $id);
        $stmtCheck->execute();
        $result = $stmtCheck->get_result()->fetch_assoc();
        
        if ($result['total'] > 0) {
            $response['message'] = 'Não é possível excluir. Existem setores vinculados a esta unidade.';
            break;
        }
        
        $sql = "DELETE FROM un_orcamentaria WHERE id_un_orcam = ? AND id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $clienteId);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Unidade excluída com sucesso';
        } else {
            $response['message'] = 'Erro ao excluir unidade';
        }
        break;
        
    default:
        $response['message'] = 'Ação não reconhecida';
}

header('Content-Type: application/json');
echo json_encode($response);
