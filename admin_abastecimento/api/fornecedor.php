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

// Controle de acesso para fornecedores
$accessControl = new AccessControl($_SESSION['userId']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Necessário ao menos acessar para qualquer ação
if (!$accessControl->verificarPermissao('fornecedores', 'acessar')) {
    $response['message'] = 'Você não possui permissão para acessar fornecedores';
    $accessControl->registrarAcao('fornecedores', 'acesso_negado', 'Tentativa de acesso sem permissão', 'negado');
    echo json_encode($response);
    exit;
}

$database = new Database();
$conn = $database->connect();
$clienteId = $_SESSION['cliente_id'] ?? null;

// Verificar se usuário é do grupo 4 (fornecedor)
$accessControl = new AccessControl($_SESSION['userId']);

switch ($action) {
    case 'list':
        $search = Security::sanitize($_GET['search'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $limit = PAGINATION_LIMIT;
        $offset = ($page - 1) * $limit;

        $where = "id_cliente = $clienteId";
        
        // Filtro para usuários do grupo 4 (fornecedores)
        $fornecedorId = $accessControl->getFornecedorId();
        if ($fornecedorId) {
            $where .= " AND id_fornecedor = $fornecedorId";
        }
        
        if ($search) {
            $search = $conn->real_escape_string($search);
            $where .= " AND (razao_social LIKE '%$search%' OR nome_fantasia LIKE '%$search%' OR cnpj LIKE '%$search%')";
        }

        $sql = "SELECT COUNT(*) as total FROM fornecedor WHERE $where";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $totalPages = ceil($row['total'] / $limit);

        $sql = "SELECT * FROM fornecedor WHERE $where ORDER BY id_fornecedor DESC LIMIT $limit OFFSET $offset";
        $result = $conn->query($sql);

        $fornecedores = [];
        while ($row = $result->fetch_assoc()) {
            $fornecedores[] = $row;
        }

        $response['success'] = true;
        $response['fornecedores'] = $fornecedores;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;

    case 'get':
        if (!isset($_GET['id'])) {
            $response['message'] = 'ID não informado';
            break;
        }

        $id = intval($_GET['id']);
        
        // Filtro para usuários do grupo 4 (fornecedores)
        $fornecedorId = $accessControl->getFornecedorId();
        if ($fornecedorId && $id != $fornecedorId) {
            $response['message'] = 'Acesso negado a este fornecedor';
            break;
        }
        
        $sql = "SELECT * FROM fornecedor WHERE id_fornecedor = ? AND id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $clienteId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['fornecedor'] = $row;
        } else {
            $response['message'] = 'Fornecedor não encontrado';
        }
        break;

    case 'create':
        if (!$accessControl->verificarPermissao('fornecedores', 'criar')) {
            $response['message'] = 'Você não possui permissão para criar fornecedores';
            break;
        }

        $razao_social = Security::sanitize($_POST['razao_social'] ?? '');
        $nome_fantasia = Security::sanitize($_POST['nome_fantasia'] ?? '');
        $cnpj = Security::sanitize($_POST['cnpj'] ?? '');
        $insc_estadual = Security::sanitize($_POST['insc_estadual'] ?? '');
        $insc_municipal = Security::sanitize($_POST['insc_municipal'] ?? '');
        $tel_principal = Security::sanitize($_POST['tel_principal'] ?? '');
        $tel_contato = Security::sanitize($_POST['tel_contato'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
        $logradouro = Security::sanitize($_POST['logradouro'] ?? '');
        $numero = Security::sanitize($_POST['numero'] ?? '');
        $bairro = Security::sanitize($_POST['bairro'] ?? '');
        $cidade = Security::sanitize($_POST['cidade'] ?? '');
        $uf = Security::sanitize($_POST['uf'] ?? '');
        $compl_endereco = Security::sanitize($_POST['compl_endereco'] ?? '');

        if (empty($razao_social) || empty($cnpj) || empty($tel_principal) || empty($cep) || empty($logradouro) || empty($numero) || empty($bairro)) {
            $response['message'] = 'Campos obrigatórios não preenchidos';
            break;
        }

        $id_situacao = 1; // Ativo por padrão

        $sql = "INSERT INTO fornecedor (id_cliente, id_situacao, razao_social, nome_fantasia, cnpj, 
                insc_estadual, insc_municipal, tel_principal, tel_contato, email, cep, logradouro, numero, 
                bairro, cidade, uf, compl_endereco) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iissssssssisssss', 
            $clienteId, $id_situacao, $razao_social, $nome_fantasia, $cnpj,
            $insc_estadual, $insc_municipal, $tel_principal, $tel_contato, $email, 
            $cep, $logradouro, $numero, $bairro, $cidade, $uf, $compl_endereco
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Fornecedor cadastrado com sucesso';
            $response['id'] = $conn->insert_id;
            $accessControl->registrarAcao('fornecedores', 'criar', "Fornecedor criado: $razao_social");
        } else {
            $response['message'] = 'Erro ao cadastrar fornecedor: ' . $conn->error;
        }
        break;

    case 'update':
        if (!$accessControl->verificarPermissao('fornecedores', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar fornecedores';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        
        // Filtro para usuários do grupo 4 (fornecedores)
        $fornecedorId = $accessControl->getFornecedorId();
        if ($fornecedorId && $id != $fornecedorId) {
            $response['message'] = 'Você só pode editar seu próprio fornecedor';
            break;
        }
        
        $razao_social = Security::sanitize($_POST['razao_social'] ?? '');
        $nome_fantasia = Security::sanitize($_POST['nome_fantasia'] ?? '');
        $cnpj = Security::sanitize($_POST['cnpj'] ?? '');
        $insc_estadual = Security::sanitize($_POST['insc_estadual'] ?? '');
        $insc_municipal = Security::sanitize($_POST['insc_municipal'] ?? '');
        $tel_principal = Security::sanitize($_POST['tel_principal'] ?? '');
        $tel_contato = Security::sanitize($_POST['tel_contato'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
        $logradouro = Security::sanitize($_POST['logradouro'] ?? '');
        $numero = Security::sanitize($_POST['numero'] ?? '');
        $bairro = Security::sanitize($_POST['bairro'] ?? '');
        $cidade = Security::sanitize($_POST['cidade'] ?? '');
        $uf = Security::sanitize($_POST['uf'] ?? '');
        $compl_endereco = Security::sanitize($_POST['compl_endereco'] ?? '');

        if (empty($id) || empty($razao_social) || empty($cnpj)) {
            $response['message'] = 'Dados inválidos';
            break;
        }

        $sql = "UPDATE fornecedor SET 
                razao_social = ?, nome_fantasia = ?, cnpj = ?, insc_estadual = ?, insc_municipal = ?,
                tel_principal = ?, tel_contato = ?, email = ?, cep = ?, logradouro = ?, numero = ?,
                bairro = ?, cidade = ?, uf = ?, compl_endereco = ?
                WHERE id_fornecedor = ? AND id_cliente = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssssisssssiii', 
            $razao_social, $nome_fantasia, $cnpj, $insc_estadual, $insc_municipal,
            $tel_principal, $tel_contato, $email, $cep, $logradouro, $numero,
            $bairro, $cidade, $uf, $compl_endereco, $id, $clienteId
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Fornecedor atualizado com sucesso';
            $accessControl->registrarAcao('fornecedores', 'editar', "Fornecedor editado ID: $id");
        } else {
            $response['message'] = 'Erro ao atualizar fornecedor: ' . $conn->error;
        }
        break;

    case 'delete':
        if (!$accessControl->verificarPermissao('fornecedores', 'excluir')) {
            $response['message'] = 'Você não possui permissão para excluir fornecedores';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        
        // Filtro para usuários do grupo 4 (fornecedores) - não podem excluir
        $fornecedorId = $accessControl->getFornecedorId();
        if ($fornecedorId) {
            $response['message'] = 'Fornecedores não podem excluir registros';
            break;
        }
        
        if (empty($id)) {
            $response['message'] = 'ID não informado';
            break;
        }

        $sql = "DELETE FROM fornecedor WHERE id_fornecedor = ? AND id_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $clienteId);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Fornecedor excluído com sucesso';
            $accessControl->registrarAcao('fornecedores', 'excluir', "Fornecedor excluído ID: $id");
        } else {
            $response['message'] = 'Erro ao excluir fornecedor: ' . $conn->error;
        }
        break;

    default:
        $response['message'] = 'Ação inválida';
        break;
}

echo json_encode($response);
