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

// Controle de acesso para clientes
$accessControl = new AccessControl($_SESSION['userId']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Necessário ao menos acessar para qualquer ação
if (!$accessControl->verificarPermissao('clientes', 'acessar')) {
    $response['message'] = 'Você não possui permissão para acessar clientes';
    $accessControl->registrarAcao('clientes', 'acesso_negado', 'Tentativa de acesso sem permissão', 'negado');
    echo json_encode($response);
    exit;
}

$db = new Database();
$db->connect();

// Função para fazer upload de logo
function uploadLogo() {
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $file = $_FILES['logo'];
    $uploadDir = __DIR__ . '/../storage/cliente/logo';
    
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
    
    // Gerar nome único
    $filename = 'logo_' . time() . '_' . uniqid() . '.' . $ext;
    $filepath = $uploadDir . '/' . $filename;
    
    // Mover arquivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'storage/cliente/logo/' . $filename;
    }
    
    return null;
}

switch ($action) {
    case 'list':
    // listar clientes: já validado acessar
        $search = Security::sanitize($_GET['search'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $limit = PAGINATION_LIMIT;
        $offset = ($page - 1) * $limit;

        $where = "1=1";
        
        // Filtro para usuários do grupo 3 (clientes) - mostrar apenas o próprio cliente
        if ($accessControl->isCliente()) {
            $clienteId = $accessControl->getClienteId();
            if ($clienteId) {
                $where .= " AND id = $clienteId";
            }
        }
        
        if ($search) {
            $search = strtolower($search);
            $where .= " AND (LOWER(razao_social) LIKE '%$search%' OR LOWER(nome_fantasia) LIKE '%$search%' OR LOWER(cnpj) LIKE '%$search%')";
        }

        $sql = "SELECT COUNT(*) as total FROM clientes WHERE $where";
        $result = $db->query($sql);
        $row = $result->fetch_assoc();
        $totalPages = ceil($row['total'] / $limit);

        $sql = "SELECT * FROM clientes WHERE $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $result = $db->query($sql);

        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }

        $response['success'] = true;
        $response['clientes'] = $clientes;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;

    case 'get':
        $id = intval($_GET['id']);
        
        // Filtro para usuários do grupo 3 (clientes)
        if ($accessControl->isCliente()) {
            $clienteIdUsuario = $accessControl->getClienteId();
            if ($clienteIdUsuario && $id != $clienteIdUsuario) {
                $response['message'] = 'Você não tem permissão para visualizar este cliente';
                break;
            }
        }
        
        $sql = "SELECT * FROM clientes WHERE id = $id";
        $result = $db->query($sql);

        if ($result->num_rows === 1) {
            $response['success'] = true;
            $response['cliente'] = $result->fetch_assoc();
        } else {
            $response['message'] = 'Cliente não encontrado';
        }
        break;

    case 'create':
        if (!$accessControl->verificarPermissao('clientes', 'criar')) {
            $response['message'] = 'Você não possui permissão para criar clientes';
            $accessControl->registrarAcao('clientes', 'criar', 'Tentativa de criar sem permissão', 'negado');
            break;
        }
        $razao_social = Security::sanitize($_POST['razao_social']);
        $nome_fantasia = Security::sanitize($_POST['nome_fantasia'] ?? '');
        $cnpj = Security::sanitize($_POST['cnpj']);
        $inscricao_estadual = Security::sanitize($_POST['inscricao_estadual'] ?? '');
        $inscricao_municipal = Security::sanitize($_POST['inscricao_municipal'] ?? '');
        $cep = Security::sanitize($_POST['cep'] ?? '');
        $logradouro = Security::sanitize($_POST['logradouro'] ?? '');
        $numero = Security::sanitize($_POST['numero'] ?? '');
        $complemento = Security::sanitize($_POST['complemento'] ?? '');
        $bairro = Security::sanitize($_POST['bairro'] ?? '');
        $cidade = Security::sanitize($_POST['cidade'] ?? '');
        $uf = Security::sanitize($_POST['uf'] ?? '');
        $telefone = Security::sanitize($_POST['telefone'] ?? '');
        $celular = Security::sanitize($_POST['celular'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $site = Security::sanitize($_POST['site'] ?? '');
        $ativo = intval($_POST['ativo'] ?? 1);

        if (!$razao_social || !$cnpj) {
            $response['message'] = 'Razão Social e CNPJ são obrigatórios';
            break;
        }

        // Upload de logo (ou uso de path já fornecido); se nenhum, gravar NULL
        $logo_path = uploadLogo();
        if (!$logo_path && !empty($_POST['logo_path'])) {
            $logo_path = Security::sanitize($_POST['logo_path']);
        }
        if (!$logo_path) {
            $logo_path = null; // persistir NULL
        }
        $logo_path_sql = $logo_path === null ? 'NULL' : "'$logo_path'";

        $sql = "INSERT INTO clientes (razao_social, nome_fantasia, cnpj, inscricao_estadual, inscricao_municipal, 
                cep, logradouro, numero, complemento, bairro, cidade, uf, telefone, celular, email, site, logo_path, ativo) 
                VALUES ('$razao_social', '$nome_fantasia', '$cnpj', '$inscricao_estadual', '$inscricao_municipal',
                '$cep', '$logradouro', '$numero', '$complemento', '$bairro', '$cidade', '$uf', 
                '$telefone', '$celular', '$email', '$site', $logo_path_sql, $ativo)";
        
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Cliente criado com sucesso';
            $accessControl->registrarAcao('clientes', 'criar', "Criou cliente: $razao_social ($cnpj)");
        } else {
            $response['message'] = 'Erro ao criar cliente';
        }
        break;

    case 'update':
        if (!$accessControl->verificarPermissao('clientes', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar clientes';
            $accessControl->registrarAcao('clientes', 'editar', 'Tentativa de editar sem permissão', 'negado');
            break;
        }
        $id = intval($_POST['id']);
        
        // Filtro para usuários do grupo 3 (clientes)
        if ($accessControl->isCliente()) {
            $clienteIdUsuario = $accessControl->getClienteId();
            if ($clienteIdUsuario && $id != $clienteIdUsuario) {
                $response['message'] = 'Você não tem permissão para editar este cliente';
                break;
            }
        }
        $razao_social = Security::sanitize($_POST['razao_social']);
        $nome_fantasia = Security::sanitize($_POST['nome_fantasia'] ?? '');
        $cnpj = Security::sanitize($_POST['cnpj']);
        $inscricao_estadual = Security::sanitize($_POST['inscricao_estadual'] ?? '');
        $inscricao_municipal = Security::sanitize($_POST['inscricao_municipal'] ?? '');
        $cep = Security::sanitize($_POST['cep'] ?? '');
        $logradouro = Security::sanitize($_POST['logradouro'] ?? '');
        $numero = Security::sanitize($_POST['numero'] ?? '');
        $complemento = Security::sanitize($_POST['complemento'] ?? '');
        $bairro = Security::sanitize($_POST['bairro'] ?? '');
        $cidade = Security::sanitize($_POST['cidade'] ?? '');
        $uf = Security::sanitize($_POST['uf'] ?? '');
        $telefone = Security::sanitize($_POST['telefone'] ?? '');
        $celular = Security::sanitize($_POST['celular'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $site = Security::sanitize($_POST['site'] ?? '');
        $ativo = intval($_POST['ativo'] ?? 1);

        if (!$razao_social || !$cnpj) {
            $response['message'] = 'Razão Social e CNPJ são obrigatórios';
            break;
        }

        // Flag de remoção de logo
        $remove_logo = isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1';

        // Tenta novo upload
        $novo_logo = uploadLogo();
        $logo_path = null; // default será NULL se nada for mantido

        if ($novo_logo) {
            // Delete logo anterior se existir
            $result = $db->query("SELECT logo_path FROM clientes WHERE id=$id");
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['logo_path'] && file_exists(__DIR__ . '/../' . $row['logo_path'])) {
                    @unlink(__DIR__ . '/../' . $row['logo_path']);
                }
            }
            $logo_path = $novo_logo; // novo caminho (string)
        } elseif ($remove_logo) {
            // Remover logo existente
            $result = $db->query("SELECT logo_path FROM clientes WHERE id=$id");
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                if ($row['logo_path'] && file_exists(__DIR__ . '/../' . $row['logo_path'])) {
                    @unlink(__DIR__ . '/../' . $row['logo_path']);
                }
            }
            $logo_path = null; // campo fica NULL
        } else {
            // Manter existente
            if (!empty($_POST['logo_path'])) {
                $logo_path = Security::sanitize($_POST['logo_path']);
            } else {
                $result = $db->query("SELECT logo_path FROM clientes WHERE id=$id");
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    // Pode vir NULL do banco
                    $logo_path = $row['logo_path'];
                }
            }
        }
        $logo_path_sql = ($logo_path === null ? 'NULL' : "'$logo_path'");
        $sql = "UPDATE clientes SET 
                razao_social='$razao_social',
                nome_fantasia='$nome_fantasia',
                cnpj='$cnpj',
                inscricao_estadual='$inscricao_estadual',
                inscricao_municipal='$inscricao_municipal',
                cep='$cep',
                logradouro='$logradouro',
                numero='$numero',
                complemento='$complemento',
                bairro='$bairro',
                cidade='$cidade',
                uf='$uf',
                telefone='$telefone',
                celular='$celular',
                email='$email',
                site='$site',
                logo_path=$logo_path_sql,
                ativo=$ativo
                WHERE id=$id";
        
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Cliente atualizado com sucesso';
            $accessControl->registrarAcao('clientes', 'editar', "Atualizou cliente #$id: $razao_social ($cnpj)");
        } else {
            $response['message'] = 'Erro ao atualizar cliente';
        }
        break;

    case 'delete':
        if (!$accessControl->verificarPermissao('clientes', 'excluir')) {
            $response['message'] = 'Você não possui permissão para excluir clientes';
            $accessControl->registrarAcao('clientes', 'excluir', 'Tentativa de excluir sem permissão', 'negado');
            break;
        }
        
        // Usuários do grupo 3 (clientes) não podem excluir
        if ($accessControl->isCliente()) {
            $response['message'] = 'Usuários de cliente não podem excluir registros';
            break;
        }
        
        $id = intval($_POST['id']);
        $sql = "DELETE FROM clientes WHERE id=$id";
        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Cliente excluído com sucesso';
            $accessControl->registrarAcao('clientes', 'excluir', "Excluiu cliente #$id");
        } else {
            $response['message'] = 'Erro ao excluir cliente';
        }
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
