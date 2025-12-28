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

// Verificar permissão
$accessControl = new AccessControl($_SESSION['userId']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Verificar se pode acessar grupos
if (!$accessControl->verificarPermissao('grupos', 'acessar')) {
    $response['message'] = 'Você não possui permissão para acessar grupos';
    $accessControl->registrarAcao('grupos', 'acesso_negado', 'Tentativa de acesso sem permissão', 'negado');
    echo json_encode($response);
    exit;
}

$db = new Database();
$db->connect();

switch ($action) {
    case 'list':
        // Listar grupos
        $search = Security::sanitize($_GET['search'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $where = "1=1";
        if ($search) {
            $search = strtolower($search);
            $where = "(LOWER(g.nome) LIKE '%$search%' OR LOWER(g.descricao) LIKE '%$search%')";
        }

        $sql = "SELECT COUNT(*) as total FROM grupos g WHERE $where";
        $result = $db->query($sql);
        $row = $result->fetch_assoc();
        $totalPages = ceil($row['total'] / $limit);

        $sql = "SELECT g.*, 
                       COUNT(u.id) as total_usuarios 
                FROM grupos g 
                LEFT JOIN usuarios u ON u.grupo_id = g.id AND u.ativo = 1
                WHERE $where 
                GROUP BY g.id 
                ORDER BY g.nome ASC 
                LIMIT $limit OFFSET $offset";
        $result = $db->query($sql);

        $grupos = [];
        while ($row = $result->fetch_assoc()) {
            $grupos[] = $row;
        }

        $response['success'] = true;
        $response['grupos'] = $grupos;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        
        $accessControl->registrarAcao('grupos', 'visualizar', 'Listou grupos');
        break;

    case 'get':
        // Buscar grupo específico
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM grupos WHERE id = $id";
        $result = $db->query($sql);

        if ($result->num_rows === 1) {
            $response['success'] = true;
            $response['grupo'] = $result->fetch_assoc();
            $accessControl->registrarAcao('grupos', 'visualizar', "Visualizou grupo ID: $id");
        } else {
            $response['message'] = 'Grupo não encontrado';
        }
        break;

    case 'create':
        // Verificar permissão de criar
        if (!$accessControl->verificarPermissao('grupos', 'criar')) {
            $response['message'] = 'Você não possui permissão para criar grupos';
            $accessControl->registrarAcao('grupos', 'criar', 'Tentativa de criar sem permissão', 'negado');
            break;
        }

        $nome = Security::sanitize($_POST['nome']);
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        $ativo = intval($_POST['ativo'] ?? 1);

        if (!$nome) {
            $response['message'] = 'Nome é obrigatório';
            break;
        }

        $sql = "INSERT INTO grupos (nome, descricao, ativo) VALUES ('$nome', '$descricao', $ativo)";
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Grupo criado com sucesso';
            $response['id'] = $db->connect()->insert_id;
            $accessControl->registrarAcao('grupos', 'criar', "Criou grupo: $nome");
        } else {
            $response['message'] = 'Erro ao criar grupo';
        }
        break;

    case 'update':
        // Verificar permissão de editar
        if (!$accessControl->verificarPermissao('grupos', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar grupos';
            $accessControl->registrarAcao('grupos', 'editar', 'Tentativa de editar sem permissão', 'negado');
            break;
        }

        $id = intval($_POST['id']);
        $nome = Security::sanitize($_POST['nome']);
        $descricao = Security::sanitize($_POST['descricao'] ?? '');
        $ativo = intval($_POST['ativo'] ?? 1);

        if (!$nome) {
            $response['message'] = 'Nome é obrigatório';
            break;
        }

        $sql = "UPDATE grupos SET nome='$nome', descricao='$descricao', ativo=$ativo WHERE id=$id";
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Grupo atualizado com sucesso';
            $accessControl->registrarAcao('grupos', 'editar', "Editou grupo ID: $id");
        } else {
            $response['message'] = 'Erro ao atualizar grupo';
        }
        break;

    case 'delete':
        // Verificar permissão de excluir
        if (!$accessControl->verificarPermissao('grupos', 'excluir')) {
            $response['message'] = 'Você não possui permissão para excluir grupos';
            $accessControl->registrarAcao('grupos', 'excluir', 'Tentativa de excluir sem permissão', 'negado');
            break;
        }

        $id = intval($_POST['id']);
        
        // Verificar se há usuários vinculados
        $sqlCheck = "SELECT COUNT(*) as total FROM usuarios WHERE grupo_id = $id";
        $resultCheck = $db->query($sqlCheck);
        $rowCheck = $resultCheck->fetch_assoc();
        
        if ($rowCheck['total'] > 0) {
            $response['message'] = 'Não é possível excluir. Existem ' . $rowCheck['total'] . ' usuário(s) vinculado(s) a este grupo.';
            break;
        }

        $sql = "DELETE FROM grupos WHERE id=$id";
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Grupo excluído com sucesso';
            $accessControl->registrarAcao('grupos', 'excluir', "Excluiu grupo ID: $id");
        } else {
            $response['message'] = 'Erro ao excluir grupo';
        }
        break;

    case 'usuarios':
        // Listar usuários do grupo
        $grupoId = intval($_GET['grupo_id']);
        $sql = "SELECT id, login, nome, email, ativo FROM usuarios WHERE grupo_id = $grupoId ORDER BY login";
        $result = $db->query($sql);

        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }

        $response['success'] = true;
        $response['usuarios'] = $usuarios;
        $accessControl->registrarAcao('grupos', 'visualizar', "Listou usuários do grupo ID: $grupoId");
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
