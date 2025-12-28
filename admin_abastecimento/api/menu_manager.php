<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/access_control.php';

header('Content-Type: application/json');

// Verificar autenticação
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$userId = $_SESSION['userId'];
$accessControl = new AccessControl($userId);

// Verificar permissão
if (!$accessControl->verificarPermissao('menu_manager', 'acessar')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sem permissão']);
    exit;
}

// Criar conexão
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar ao banco de dados']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            handleList($conn, $accessControl);
            break;
        case 'create':
            handleCreate($conn, $accessControl);
            break;
        case 'update':
            handleUpdate($conn, $accessControl);
            break;
        case 'delete':
            handleDelete($conn, $accessControl);
            break;
        case 'reorder':
            handleReorder($conn, $accessControl);
            break;
        case 'aplicacoes':
            handleAplicacoes($conn);
            break;
        default:
            throw new Exception('Ação inválida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleList($conn, $accessControl) {
    $status = $_GET['status'] ?? '';
    
    // Buscar módulos
    $sqlModulos = "SELECT * FROM modulo WHERE 1=1";
    if ($status !== '') {
        $sqlModulos .= " AND ativo = " . intval($status);
    }
    $sqlModulos .= " ORDER BY ordem, id";
    
    $resultModulos = $conn->query($sqlModulos);
    $modulos = [];
    
    while ($modulo = $resultModulos->fetch_assoc()) {
        $modulo['submenus'] = [];
        
        // Buscar submenus do módulo
        $sqlSubmenus = "SELECT * FROM submenu WHERE modulo_id = ? ";
        if ($status !== '') {
            $sqlSubmenus .= " AND ativo = " . intval($status);
        }
        $sqlSubmenus .= " ORDER BY ordem, id";
        
        $stmtSubmenus = $conn->prepare($sqlSubmenus);
        $stmtSubmenus->bind_param("i", $modulo['id']);
        $stmtSubmenus->execute();
        $resultSubmenus = $stmtSubmenus->get_result();
        
        while ($submenu = $resultSubmenus->fetch_assoc()) {
            $submenu['subsubmenus'] = [];
            
            // Buscar sub-submenus do submenu
            $sqlSubsubmenus = "SELECT * FROM subsubmenu WHERE submenu_id = ? ";
            if ($status !== '') {
                $sqlSubsubmenus .= " AND ativo = " . intval($status);
            }
            $sqlSubsubmenus .= " ORDER BY ordem, id";
            
            $stmtSubsubmenus = $conn->prepare($sqlSubsubmenus);
            $stmtSubsubmenus->bind_param("i", $submenu['id']);
            $stmtSubsubmenus->execute();
            $resultSubsubmenus = $stmtSubsubmenus->get_result();
            
            while ($subsubmenu = $resultSubsubmenus->fetch_assoc()) {
                $submenu['subsubmenus'][] = $subsubmenu;
            }
            
            $modulo['submenus'][] = $submenu;
        }
        
        $modulos[] = $modulo;
    }
    
    echo json_encode(['success' => true, 'data' => $modulos]);
}

function handleCreate($conn, $accessControl) {
    if (!$accessControl->verificarPermissao('menu_manager', 'criar')) {
        throw new Exception('Sem permissão para criar');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $tipo = $data['tipo'] ?? '';
    
    switch ($tipo) {
        case 'modulo':
            createModulo($conn, $data);
            break;
        case 'submenu':
            createSubmenu($conn, $data);
            break;
        case 'subsubmenu':
            createSubsubmenu($conn, $data);
            break;
        default:
            throw new Exception('Tipo inválido');
    }
}

function createModulo($conn, $data) {
    $codigo = $data['codigo'] ?? '';
    $nome = $data['nome'] ?? '';
    $icone = $data['icone'] ?? 'fa-folder';
    $expandido = intval($data['expandido'] ?? 0);
    $aplicacaoId = $expandido ? null : ($data['aplicacao_id'] ?? null);
    $ordem = intval($data['ordem'] ?? 0);
    $ativo = intval($data['ativo'] ?? 1);
    
    if (empty($codigo) || empty($nome)) {
        throw new Exception('Código e nome são obrigatórios');
    }
    
    if (!$expandido && empty($aplicacaoId)) {
        throw new Exception('Aplicação é obrigatória para itens não expansíveis');
    }
    
    $sql = "INSERT INTO modulo (codigo, nome, icone, expandido, aplicacao_id, ordem, ativo) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisii", $codigo, $nome, $icone, $expandido, $aplicacaoId, $ordem, $ativo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Módulo criado com sucesso', 'id' => $conn->insert_id]);
    } else {
        throw new Exception('Erro ao criar módulo: ' . $stmt->error);
    }
}

function createSubmenu($conn, $data) {
    $moduloId = intval($data['modulo_id'] ?? 0);
    $codigo = $data['codigo'] ?? '';
    $nome = $data['nome'] ?? '';
    $icone = $data['icone'] ?? 'fa-file';
    $expandido = intval($data['expandido'] ?? 0);
    $aplicacaoId = $expandido ? null : ($data['aplicacao_id'] ?? null);
    $ordem = intval($data['ordem'] ?? 0);
    $ativo = intval($data['ativo'] ?? 1);
    
    if (empty($moduloId) || empty($codigo) || empty($nome)) {
        throw new Exception('Módulo, código e nome são obrigatórios');
    }
    
    if (!$expandido && empty($aplicacaoId)) {
        throw new Exception('Aplicação é obrigatória para itens não expansíveis');
    }
    
    $sql = "INSERT INTO submenu (modulo_id, codigo, nome, icone, expandido, aplicacao_id, ordem, ativo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssisii", $moduloId, $codigo, $nome, $icone, $expandido, $aplicacaoId, $ordem, $ativo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Submenu criado com sucesso', 'id' => $conn->insert_id]);
    } else {
        throw new Exception('Erro ao criar submenu: ' . $stmt->error);
    }
}

function createSubsubmenu($conn, $data) {
    $submenuId = intval($data['submenu_id'] ?? 0);
    $codigo = $data['codigo'] ?? '';
    $nome = $data['nome'] ?? '';
    $icone = $data['icone'] ?? 'fa-circle';
    $aplicacaoId = intval($data['aplicacao_id'] ?? 0);
    $ordem = intval($data['ordem'] ?? 0);
    $ativo = intval($data['ativo'] ?? 1);
    
    if (empty($submenuId) || empty($codigo) || empty($nome) || empty($aplicacaoId)) {
        throw new Exception('Submenu, código, nome e aplicação são obrigatórios');
    }
    
    $sql = "INSERT INTO subsubmenu (submenu_id, codigo, nome, icone, aplicacao_id, ordem, ativo) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssiis", $submenuId, $codigo, $nome, $icone, $aplicacaoId, $ordem, $ativo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sub-submenu criado com sucesso', 'id' => $conn->insert_id]);
    } else {
        throw new Exception('Erro ao criar sub-submenu: ' . $stmt->error);
    }
}

function handleUpdate($conn, $accessControl) {
    if (!$accessControl->verificarPermissao('menu_manager', 'editar')) {
        throw new Exception('Sem permissão para editar');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $tipo = $data['tipo'] ?? '';
    $id = intval($data['id'] ?? 0);
    
    if (empty($id)) {
        throw new Exception('ID inválido');
    }
    
    switch ($tipo) {
        case 'modulo':
            updateModulo($conn, $id, $data);
            break;
        case 'submenu':
            updateSubmenu($conn, $id, $data);
            break;
        case 'subsubmenu':
            updateSubsubmenu($conn, $id, $data);
            break;
        default:
            throw new Exception('Tipo inválido');
    }
}

function updateModulo($conn, $id, $data) {
    $codigo = $data['codigo'] ?? '';
    $nome = $data['nome'] ?? '';
    $icone = $data['icone'] ?? 'fa-folder';
    $expandido = intval($data['expandido'] ?? 0);
    $aplicacaoId = $expandido ? null : ($data['aplicacao_id'] ?? null);
    $ordem = intval($data['ordem'] ?? 0);
    $ativo = intval($data['ativo'] ?? 1);
    
    if (empty($codigo) || empty($nome)) {
        throw new Exception('Código e nome são obrigatórios');
    }
    
    if (!$expandido && empty($aplicacaoId)) {
        throw new Exception('Aplicação é obrigatória para itens não expansíveis');
    }
    
    $sql = "UPDATE modulo SET codigo = ?, nome = ?, icone = ?, expandido = ?, aplicacao_id = ?, ordem = ?, ativo = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisiii", $codigo, $nome, $icone, $expandido, $aplicacaoId, $ordem, $ativo, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Módulo atualizado com sucesso']);
    } else {
        throw new Exception('Erro ao atualizar módulo: ' . $stmt->error);
    }
}

function updateSubmenu($conn, $id, $data) {
    $codigo = $data['codigo'] ?? '';
    $nome = $data['nome'] ?? '';
    $icone = $data['icone'] ?? 'fa-file';
    $expandido = intval($data['expandido'] ?? 0);
    $aplicacaoId = $expandido ? null : ($data['aplicacao_id'] ?? null);
    $ordem = intval($data['ordem'] ?? 0);
    $ativo = intval($data['ativo'] ?? 1);
    
    if (empty($codigo) || empty($nome)) {
        throw new Exception('Código e nome são obrigatórios');
    }
    
    if (!$expandido && empty($aplicacaoId)) {
        throw new Exception('Aplicação é obrigatória para itens não expansíveis');
    }
    
    $sql = "UPDATE submenu SET codigo = ?, nome = ?, icone = ?, expandido = ?, aplicacao_id = ?, ordem = ?, ativo = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisiii", $codigo, $nome, $icone, $expandido, $aplicacaoId, $ordem, $ativo, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Submenu atualizado com sucesso']);
    } else {
        throw new Exception('Erro ao atualizar submenu: ' . $stmt->error);
    }
}

function updateSubsubmenu($conn, $id, $data) {
    $codigo = $data['codigo'] ?? '';
    $nome = $data['nome'] ?? '';
    $icone = $data['icone'] ?? 'fa-circle';
    $aplicacaoId = intval($data['aplicacao_id'] ?? 0);
    $ordem = intval($data['ordem'] ?? 0);
    $ativo = intval($data['ativo'] ?? 1);
    
    if (empty($codigo) || empty($nome) || empty($aplicacaoId)) {
        throw new Exception('Código, nome e aplicação são obrigatórios');
    }
    
    $sql = "UPDATE subsubmenu SET codigo = ?, nome = ?, icone = ?, aplicacao_id = ?, ordem = ?, ativo = ? 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiiis", $codigo, $nome, $icone, $aplicacaoId, $ordem, $ativo, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Sub-submenu atualizado com sucesso']);
    } else {
        throw new Exception('Erro ao atualizar sub-submenu: ' . $stmt->error);
    }
}

function handleDelete($conn, $accessControl) {
    if (!$accessControl->verificarPermissao('menu_manager', 'excluir')) {
        throw new Exception('Sem permissão para excluir');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $tipo = $data['tipo'] ?? '';
    $id = intval($data['id'] ?? 0);
    
    if (empty($id)) {
        throw new Exception('ID inválido');
    }
    
    $table = '';
    switch ($tipo) {
        case 'modulo':
            $table = 'modulo';
            break;
        case 'submenu':
            $table = 'submenu';
            break;
        case 'subsubmenu':
            $table = 'subsubmenu';
            break;
        default:
            throw new Exception('Tipo inválido');
    }
    
    $sql = "DELETE FROM $table WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item excluído com sucesso']);
    } else {
        throw new Exception('Erro ao excluir item: ' . $stmt->error);
    }
}

function handleReorder($conn, $accessControl) {
    if (!$accessControl->verificarPermissao('menu_manager', 'editar')) {
        throw new Exception('Sem permissão para reordenar');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    $tipo = $data['tipo'] ?? '';
    $items = $data['items'] ?? [];
    
    if (empty($items)) {
        throw new Exception('Nenhum item para reordenar');
    }
    
    $table = '';
    switch ($tipo) {
        case 'modulo':
            $table = 'modulo';
            break;
        case 'submenu':
            $table = 'submenu';
            break;
        case 'subsubmenu':
            $table = 'subsubmenu';
            break;
        default:
            throw new Exception('Tipo inválido');
    }
    
    $conn->begin_transaction();
    
    try {
        foreach ($items as $index => $itemId) {
            $ordem = ($index + 1) * 10;
            $sql = "UPDATE $table SET ordem = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $ordem, $itemId);
            $stmt->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Ordem atualizada com sucesso']);
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception('Erro ao reordenar: ' . $e->getMessage());
    }
}

function handleAplicacoes($conn) {
    $sql = "SELECT id, nome, url FROM aplicacoes WHERE ativo = 1 ORDER BY nome";
    $result = $conn->query($sql);
    
    $aplicacoes = [];
    while ($row = $result->fetch_assoc()) {
        $aplicacoes[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $aplicacoes]);
}
