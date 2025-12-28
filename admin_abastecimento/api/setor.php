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
        
        $whereClause = "WHERE s.id_cliente = ?";
        $params = [$clienteId];
        $types = 'i';
        
        if (!empty($search)) {
            $whereClause .= " AND (s.codigo LIKE ? OR s.descricao LIKE ? OR u.descricao LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= 'sss';
        }
        
        // Contar total
        $sqlCount = "SELECT COUNT(*) as total 
                     FROM setor s
                     LEFT JOIN un_orcamentaria u ON s.id_un_orcam = u.id_un_orcam
                     $whereClause";
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $total = $stmtCount->get_result()->fetch_assoc()['total'];
        $totalPages = ceil($total / $limit);
        
        // Buscar registros
        $sql = "SELECT s.id_setor as id, s.codigo, s.descricao as nome, s.descricao, s.id_un_orcam as unidade_id, 
                       s.ativo, s.id_cliente, s.created_at, s.updated_at, u.descricao as unidade_nome 
                FROM setor s
                LEFT JOIN un_orcamentaria u ON s.id_un_orcam = u.id_un_orcam
                $whereClause 
                ORDER BY s.descricao 
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $setores = [];
        while ($row = $result->fetch_assoc()) {
            $setores[] = $row;
        }
        
        $response['success'] = true;
        $response['setores'] = $setores;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;
        
    case 'get':
        $id = (int)$_GET['id'];
        $sql = "SELECT s.id_setor as id, s.codigo, s.descricao as nome, s.descricao, s.id_un_orcam as unidade_id, 
                       s.ativo, s.id_cliente, s.created_at, s.updated_at, u.descricao as unidade_nome 
                FROM setor s
                LEFT JOIN un_orcamentaria u ON s.id_un_orcam = u.id_un_orcam
                WHERE s.id_setor = ? AND s.id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $clienteId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['setor'] = $row;
        } else {
            $response['message'] = 'Setor não encontrado';
        }
        break;
        
    case 'create':
        $codigo = $_POST['codigo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $unidade_id = !empty($_POST['unidade_id']) ? (int)$_POST['unidade_id'] : null;
        $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
        $empresaId = $_SESSION['empresa_id'] ?? 1;
        
        if (empty($codigo) || empty($descricao)) {
            $response['message'] = 'Código e descrição são obrigatórios';
            break;
        }
        
        // Verificar se código já existe
        $sqlCheck = "SELECT id_setor FROM setor WHERE codigo = ? AND id_cliente = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param('si', $codigo, $clienteId);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $response['message'] = 'Código já cadastrado';
            break;
        }
        
        $sql = "INSERT INTO setor (codigo, descricao, id_un_orcam, ativo, id_cliente) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssiii', $codigo, $descricao, $unidade_id, $ativo, $clienteId);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Setor criado com sucesso';
            $response['id'] = $stmt->insert_id;
        } else {
            $response['message'] = 'Erro ao criar setor';
        }
        break;
        
    case 'update':
        $id = (int)$_POST['id'];
        $codigo = $_POST['codigo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $unidade_id = !empty($_POST['unidade_id']) ? (int)$_POST['unidade_id'] : null;
        $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;
        
        if (empty($codigo) || empty($descricao)) {
            $response['message'] = 'Código e descrição são obrigatórios';
            break;
        }
        
        // Verificar se código já existe em outro registro
        $sqlCheck = "SELECT id_setor FROM setor WHERE codigo = ? AND id_cliente = ? AND id_setor != ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param('sii', $codigo, $clienteId, $id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            $response['message'] = 'Código já cadastrado';
            break;
        }
        
        $sql = "UPDATE setor 
                SET codigo = ?, descricao = ?, id_un_orcam = ?, ativo = ? 
                WHERE id_setor = ? AND id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssiiii', $codigo, $descricao, $unidade_id, $ativo, $id, $clienteId);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Setor atualizado com sucesso';
        } else {
            $response['message'] = 'Erro ao atualizar setor';
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        
        $sql = "DELETE FROM setor WHERE id_setor = ? AND id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $clienteId);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Setor excluído com sucesso';
        } else {
            $response['message'] = 'Erro ao excluir setor';
        }
        break;
        
    default:
        $response['message'] = 'Ação não reconhecida';
}

header('Content-Type: application/json');
echo json_encode($response);
