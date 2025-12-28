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

function uploadFoto() {
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $file = $_FILES['foto'];
    $uploadDir = __DIR__ . '/../storage/condutor/foto';
    
    // Criar diretório se não existir
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    
    // Validar extensão
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowedExtensions)) {
        return null;
    }
    
    // Validar tamanho (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }
    
    // Gerar nome único
    $filename = 'foto_' . time() . '_' . uniqid() . '.' . $ext;
    $filepath = $uploadDir . '/' . $filename;
    
    // Mover arquivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'storage/condutor/foto/' . $filename;
    }
    
    return null;
}

// Controle de acesso para condutores
$accessControl = new AccessControl($_SESSION['userId']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Necessário ao menos acessar para qualquer ação
if (!$accessControl->verificarPermissao('condutores', 'acessar')) {
    $response['message'] = 'Você não possui permissão para acessar condutores';
    $accessControl->registrarAcao('condutores', 'acesso_negado', 'Tentativa de acesso sem permissão', 'negado');
    echo json_encode($response);
    exit;
}

$db = new Database();
$db->connect();

switch ($action) {
    case 'list_for_report':
        // Lista condutores para relatório de crachá (sem paginação)
        $clienteId = $_SESSION['cliente_id'] ?? null;
        
        if (!$clienteId) {
            $response['success'] = true;
            $response['message'] = 'Selecione um cliente no menu superior';
            $response['data'] = [];
            break;
        }
        
        $where = "c.id_cliente = $clienteId";
        
        // Aplicar filtros
        if (!empty($_GET['nome'])) {
            $nome = Security::sanitize($_GET['nome']);
            $where .= " AND LOWER(c.nome) LIKE '%" . strtolower($nome) . "%'";
        }
        
        if (!empty($_GET['cpf'])) {
            $cpf = Security::sanitize($_GET['cpf']);
            $cpf = preg_replace('/\D/', '', $cpf); // Remove não numéricos
            $where .= " AND c.cpf LIKE '%$cpf%'";
        }
        
        if (!empty($_GET['cnh'])) {
            $cnh = Security::sanitize($_GET['cnh']);
            $where .= " AND c.cnh LIKE '%$cnh%'";
        }
        
        if (!empty($_GET['id_situacao'])) {
            $id_situacao = intval($_GET['id_situacao']);
            $where .= " AND c.id_situacao = $id_situacao";
        } else {
            // Por padrão, apenas condutores ativos (situação 1)
            $where .= " AND c.id_situacao = 1";
        }
        
        if (isset($_GET['e_condutor']) && $_GET['e_condutor'] !== '') {
            $e_condutor = intval($_GET['e_condutor']);
            $where .= " AND c.e_condutor = $e_condutor";
        }
        
        $sql = "SELECT c.id_condutor, c.nome, c.cpf, c.cnh, c.matricula, c.e_condutor,
                       cg.descricao as cargo_nome,
                       sit.descricao as situacao_nome
                FROM condutor c
                LEFT JOIN cargo cg ON c.id_cargo = cg.id_cargo
                LEFT JOIN situacao sit ON c.id_situacao = sit.id_situacao
                WHERE $where
                ORDER BY c.nome ASC";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao consultar condutores: ' . $db->getError();
            break;
        }
        
        $condutores = [];
        while ($row = $result->fetch_assoc()) {
            $condutores[] = $row;
        }
        
        $response['success'] = true;
        $response['data'] = $condutores;
        break;

    case 'list':
        $search = Security::sanitize($_GET['search'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $limit = PAGINATION_LIMIT;
        $offset = ($page - 1) * $limit;
        $clienteId = $_SESSION['cliente_id'] ?? null;

        $where = "1=1";
        
        // Filtrar por cliente se estiver definido
        if ($clienteId) {
            $where .= " AND c.id_cliente = $clienteId";
        } else {
            $response['success'] = true;
            $response['message'] = 'Selecione um cliente no menu superior para visualizar os condutores';
            $response['data'] = [];
            $response['totalPages'] = 0;
            $response['currentPage'] = 1;
            break;
        }
        
        if ($search) {
            $search = strtolower($search);
            $where .= " AND (LOWER(c.nome) LIKE '%$search%' OR LOWER(c.cpf) LIKE '%$search%' OR LOWER(c.cnh) LIKE '%$search%' OR LOWER(c.matricula) LIKE '%$search%')";
        }

        $sql = "SELECT COUNT(*) as total FROM condutor c WHERE $where";
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao consultar condutores: ' . $db->getError();
            break;
        }
        
        $row = $result->fetch_assoc();
        $totalPages = ceil($row['total'] / $limit);

        $sql = "SELECT c.*, 
                       cat.codigo as categoria_nome,
                       s.descricao as sexo_nome,
                       ts.codigo as tipo_sanguineo_nome,
                       cg.descricao as cargo_nome,
                       sit.descricao as situacao_nome
                FROM condutor c
                LEFT JOIN cat_cnh cat ON c.id_cat_cnh = cat.id_cat_cnh
                LEFT JOIN sexo s ON c.id_sexo = s.id_sexo
                LEFT JOIN tp_sanguineo ts ON c.id_tp_sanguineo = ts.id_tp_sanguineo
                LEFT JOIN cargo cg ON c.id_cargo = cg.id_cargo
                LEFT JOIN situacao sit ON c.id_situacao = sit.id_situacao
                WHERE $where 
                ORDER BY c.id_condutor DESC 
                LIMIT $limit OFFSET $offset";
        $result = $db->query($sql);
        
        if (!$result) {
            $response['message'] = 'Erro ao listar condutores: ' . $db->getError();
            break;
        }

        $condutores = [];
        while ($row = $result->fetch_assoc()) {
            $condutores[] = $row;
        }

        $response['success'] = true;
        $response['data'] = $condutores;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;

    case 'get':
        $id = intval($_GET['id']);
        $clienteId = $_SESSION['cliente_id'] ?? null;
        
        if (!$clienteId) {
            $response['message'] = 'Selecione um cliente no menu superior';
            break;
        }
        
        $where = "c.id_condutor = $id AND c.id_cliente = $clienteId";
        
        $sql = "SELECT c.*, 
                       cat.codigo as categoria_nome,
                       s.descricao as sexo_nome,
                       ts.codigo as tipo_sanguineo_nome,
                       cg.descricao as cargo_nome,
                       sit.descricao as situacao_nome
                FROM condutor c
                LEFT JOIN cat_cnh cat ON c.id_cat_cnh = cat.id_cat_cnh
                LEFT JOIN sexo s ON c.id_sexo = s.id_sexo
                LEFT JOIN tp_sanguineo ts ON c.id_tp_sanguineo = ts.id_tp_sanguineo
                LEFT JOIN cargo cg ON c.id_cargo = cg.id_cargo
                LEFT JOIN situacao sit ON c.id_situacao = sit.id_situacao
                WHERE $where";
        $result = $db->query($sql);

        if (!$result) {
            $response['message'] = 'Erro ao consultar condutor: ' . $db->getError();
            break;
        }

        if ($result->num_rows === 1) {
            $response['success'] = true;
            $response['data'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Condutor não encontrado ou você não tem acesso a ele';
        }
        break;

    case 'create':
        if (!$accessControl->verificarPermissao('condutores', 'criar')) {
            $response['message'] = 'Você não possui permissão para criar condutores';
            $accessControl->registrarAcao('condutores', 'criar', 'Tentativa de criar sem permissão', 'negado');
            break;
        }

        $clienteId = $_SESSION['cliente_id'] ?? null;
        if (!$clienteId) {
            $response['message'] = 'Selecione um cliente no menu superior antes de criar um condutor';
            break;
        }
        
        // Campos obrigatórios
        $nome = Security::sanitize($_POST['nome']);
        $cnh = Security::sanitize($_POST['cnh']);
        $validade_cnh = Security::sanitize($_POST['validade_cnh']);
        $e_condutor = isset($_POST['e_condutor']) ? 1 : 0;
        
        // Campos opcionais
        $matricula = isset($_POST['matricula']) ? Security::sanitize($_POST['matricula']) : null;
        $data_nascimento = isset($_POST['data_nascimento']) ? Security::sanitize($_POST['data_nascimento']) : null;
        $rg = isset($_POST['rg']) ? Security::sanitize($_POST['rg']) : null;
        $cpf = isset($_POST['cpf']) ? Security::sanitize($_POST['cpf']) : null;
        $telefone = isset($_POST['telefone']) ? Security::sanitize($_POST['telefone']) : null;
        $email = isset($_POST['email']) ? Security::sanitize($_POST['email']) : null;
        $id_cat_cnh = isset($_POST['id_cat_cnh']) ? intval($_POST['id_cat_cnh']) : null;
        $id_sexo = isset($_POST['id_sexo']) ? intval($_POST['id_sexo']) : null;
        $id_tp_sanguineo = isset($_POST['id_tp_sanguineo']) ? intval($_POST['id_tp_sanguineo']) : null;
        $id_cargo = isset($_POST['id_cargo']) ? intval($_POST['id_cargo']) : null;
        $id_situacao = isset($_POST['id_situacao']) ? intval($_POST['id_situacao']) : null;

        if (!$nome || !$cnh || !$validade_cnh) {
            $response['message'] = 'Campos obrigatórios não preenchidos (Nome, CNH, Validade CNH)';
            break;
        }

        // Upload de foto
        $foto = uploadFoto();
        if (!$foto && !empty($_POST['foto_path'])) {
            $foto = Security::sanitize($_POST['foto_path']);
        }
        if (!$foto) {
            $foto = null;
        }
        $foto_sql = $foto === null ? 'NULL' : "'" . $db->escape($foto) . "'";

        $sql = "INSERT INTO condutor (
            id_cliente, nome, matricula, data_nascimento, rg, cpf, cnh, validade_cnh,
            telefone, email, id_cat_cnh, id_sexo, id_tp_sanguineo, id_cargo, id_situacao, e_condutor, foto
        ) VALUES (
            $clienteId, '" . $db->escape($nome) . "', 
            " . ($matricula ? "'" . $db->escape($matricula) . "'" : "NULL") . ", 
            " . ($data_nascimento ? "'" . $db->escape($data_nascimento) . "'" : "NULL") . ", 
            " . ($rg ? "'" . $db->escape($rg) . "'" : "NULL") . ", 
            " . ($cpf ? "'" . $db->escape($cpf) . "'" : "NULL") . ", 
            '" . $db->escape($cnh) . "', 
            '" . $db->escape($validade_cnh) . "',
            " . ($telefone ? "'" . $db->escape($telefone) . "'" : "NULL") . ", 
            " . ($email ? "'" . $db->escape($email) . "'" : "NULL") . ",
            " . ($id_cat_cnh ? $id_cat_cnh : "NULL") . ", 
            " . ($id_sexo ? $id_sexo : "NULL") . ",
            " . ($id_tp_sanguineo ? $id_tp_sanguineo : "NULL") . ", 
            " . ($id_cargo ? $id_cargo : "NULL") . ",
            " . ($id_situacao ? $id_situacao : "NULL") . ", 
            $e_condutor,
            $foto_sql
        )";
        
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Condutor criado com sucesso';
            $accessControl->registrarAcao('condutores', 'criar', "Criou condutor: $nome");
        } else {
            $response['message'] = 'Erro ao criar condutor: ' . $db->getError();
        }
        break;

    case 'update':
        if (!$accessControl->verificarPermissao('condutores', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar condutores';
            $accessControl->registrarAcao('condutores', 'editar', 'Tentativa de editar sem permissão', 'negado');
            break;
        }

        $id = intval($_POST['id']);
        $clienteId = $_SESSION['cliente_id'] ?? null;
        if (!$clienteId) {
            $response['message'] = 'Selecione um cliente no menu superior';
            break;
        }
        
        // Campos obrigatórios
        $nome = Security::sanitize($_POST['nome']);
        $cnh = Security::sanitize($_POST['cnh']);
        $validade_cnh = Security::sanitize($_POST['validade_cnh']);
        $e_condutor = isset($_POST['e_condutor']) ? intval($_POST['e_condutor']) : 0;
        
        // Campos opcionais
        $matricula = !empty($_POST['matricula']) ? Security::sanitize($_POST['matricula']) : null;
        $data_nascimento = !empty($_POST['data_nascimento']) ? Security::sanitize($_POST['data_nascimento']) : null;
        $rg = !empty($_POST['rg']) ? Security::sanitize($_POST['rg']) : null;
        $cpf = !empty($_POST['cpf']) ? Security::sanitize($_POST['cpf']) : null;
        $telefone = !empty($_POST['telefone']) ? Security::sanitize($_POST['telefone']) : null;
        $email = !empty($_POST['email']) ? Security::sanitize($_POST['email']) : null;
        $id_cat_cnh = !empty($_POST['id_cat_cnh']) ? intval($_POST['id_cat_cnh']) : null;
        $id_sexo = !empty($_POST['id_sexo']) ? intval($_POST['id_sexo']) : null;
        $id_tp_sanguineo = !empty($_POST['id_tp_sanguineo']) ? intval($_POST['id_tp_sanguineo']) : null;
        $id_cargo = !empty($_POST['id_cargo']) ? intval($_POST['id_cargo']) : null;
        $id_situacao = !empty($_POST['id_situacao']) ? intval($_POST['id_situacao']) : null;

        if (!$nome || !$cnh || !$validade_cnh) {
            $response['message'] = 'Campos obrigatórios não preenchidos';
            break;
        }

        // Upload de foto ou remoção
        $foto = uploadFoto();
        $remove_foto = isset($_POST['remove_foto']) && $_POST['remove_foto'] === '1';
        
        if ($remove_foto) {
            $foto_sql = "NULL";
        } elseif ($foto) {
            $foto_sql = "'" . $db->escape($foto) . "'";
        } elseif (!empty($_POST['foto_path'])) {
            $foto_sql = "'" . $db->escape($_POST['foto_path']) . "'";
        } else {
            $foto_sql = null; // Manter valor atual
        }
        
        $where = "id_condutor=$id AND id_cliente=$clienteId";

        $sql = "UPDATE condutor SET 
            nome='$nome', 
            matricula=" . (is_null($matricula) ? "NULL" : "'$matricula'") . ",
            data_nascimento=" . (is_null($data_nascimento) ? "NULL" : "'$data_nascimento'") . ",
            rg=" . (is_null($rg) ? "NULL" : "'$rg'") . ",
            cpf=" . (is_null($cpf) ? "NULL" : "'$cpf'") . ",
            cnh='$cnh',
            validade_cnh='$validade_cnh',
            telefone=" . (is_null($telefone) ? "NULL" : "'$telefone'") . ",
            email=" . (is_null($email) ? "NULL" : "'$email'") . ",
            id_cat_cnh=" . (is_null($id_cat_cnh) ? "NULL" : $id_cat_cnh) . ",
            id_sexo=" . (is_null($id_sexo) ? "NULL" : $id_sexo) . ",
            id_tp_sanguineo=" . (is_null($id_tp_sanguineo) ? "NULL" : $id_tp_sanguineo) . ",
            id_cargo=" . (is_null($id_cargo) ? "NULL" : $id_cargo) . ",
            id_situacao=" . (is_null($id_situacao) ? "NULL" : $id_situacao) . ",
            e_condutor=$e_condutor" . 
            ($foto_sql !== null ? ", foto=$foto_sql" : "") . "
        WHERE $where";
        
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Condutor atualizado com sucesso';
            $accessControl->registrarAcao('condutores', 'editar', "Editou condutor ID $id");
        } else {
            $response['message'] = 'Erro ao atualizar condutor: ' . $db->getError();
        }
        break;

    case 'delete':
        if (!$accessControl->verificarPermissao('condutores', 'excluir')) {
            $response['message'] = 'Você não possui permissão para excluir condutores';
            $accessControl->registrarAcao('condutores', 'excluir', 'Tentativa de excluir sem permissão', 'negado');
            break;
        }

        $id = intval($_POST['id']);
        $clienteId = $_SESSION['cliente_id'] ?? null;
        if (!$clienteId) {
            $response['message'] = 'Selecione um cliente no menu superior';
            break;
        }
        
        $where = "id_condutor = $id AND id_cliente=$clienteId";
        
        $sql = "DELETE FROM condutor WHERE $where";
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Condutor excluído com sucesso';
            $accessControl->registrarAcao('condutores', 'excluir', "Excluiu condutor #$id");
        } else {
            $response['message'] = 'Erro ao excluir condutor';
        }
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
