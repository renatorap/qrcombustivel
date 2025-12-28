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

if (!$accessControl->verificarPermissao('usuarios', 'acessar')) {
    $response['message'] = 'Você não possui permissão para gerenciar usuários';
    echo json_encode($response);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = new Database();
$db->connect();

switch ($action) {
    case 'list':
        // Listar usuários
        $search = Security::sanitize($_GET['search'] ?? '');
        $grupo_id = intval($_GET['grupo_id'] ?? 0);
        $ativo = isset($_GET['ativo']) ? intval($_GET['ativo']) : null;
        $clienteId = $_SESSION['cliente_id'] ?? null;
        $usuarioGrupoId = $_SESSION['grupoId'] ?? null;
        $usuarioId = $_SESSION['userId'] ?? null;
        $page = intval($_GET['page'] ?? 1);
        $limit = PAGINATION_LIMIT;
        $offset = ($page - 1) * $limit;

        $where = "1=1";
        $join = "";
        
        // Filtro por grupo:
        // Grupos 1, 2 e 3: podem visualizar todos os usuários do cliente
        // Outros grupos: somente seus próprios usuários
        if (in_array($usuarioGrupoId, [1, 2, 3])) {
            // Grupos 1, 2 e 3: filtrar por cliente através da tabela usuario_cliente
            if ($clienteId) {
                $join = " INNER JOIN usuario_cliente uc ON u.id = uc.usuario_id AND uc.cliente_id = $clienteId";
            }
        } else {
            // Outros grupos: mostrar apenas o próprio usuário
            $where .= " AND u.id = $usuarioId";
        }
        
        if ($search) {
            $search = strtolower($search);
            $where .= " AND (LOWER(u.login) LIKE '%$search%' OR LOWER(u.nome) LIKE '%$search%' OR LOWER(u.email) LIKE '%$search%')";
        }
        
        if ($grupo_id) {
            $where .= " AND u.grupo_id = $grupo_id";
        }
        
        if ($ativo !== null) {
            $where .= " AND u.ativo = $ativo";
        }

        // Contar total
        $sql = "SELECT COUNT(DISTINCT u.id) as total FROM usuarios u $join WHERE $where";
        $result = $db->query($sql);
        
        if (!$result) {
            $response['success'] = false;
            $response['message'] = 'Erro ao contar usuários: ' . $db->getConnection()->error;
            break;
        }
        
        $row = $result->fetch_assoc();
        $totalPages = ceil($row['total'] / $limit);

        // Buscar usuários
        $sql = "SELECT DISTINCT u.id, u.login, u.nome, u.email, u.grupo_id, u.ativo, u.created_at, u.updated_at,
                       g.nome as grupo_nome,
                       g.descricao as grupo_descricao
                FROM usuarios u
                $join
                LEFT JOIN grupos g ON u.grupo_id = g.id
                WHERE $where
                ORDER BY u.login
                LIMIT $limit OFFSET $offset";
        
        $result = $db->query($sql);
        
        if (!$result) {
            $response['success'] = false;
            $response['message'] = 'Erro ao buscar usuários: ' . $db->getConnection()->error;
            break;
        }

        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            // Remover senha do resultado
            unset($row['senha']);
            $usuarios[] = $row;
        }

        $response['success'] = true;
        $response['usuarios'] = $usuarios;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;

    case 'get':
        // Obter usuário específico
        $id = intval($_GET['id'] ?? 0);

        if (!$id) {
            $response['message'] = 'ID inválido';
            break;
        }

        $sql = "SELECT u.id, u.login, u.nome, u.email, u.grupo_id, u.ativo, u.created_at, u.updated_at,
                       g.nome as grupo_nome,
                       g.descricao as grupo_descricao
                FROM usuarios u
                LEFT JOIN grupos g ON u.grupo_id = g.id
                WHERE u.id = $id";
        
        $result = $db->query($sql);

        if ($result->num_rows == 0) {
            $response['message'] = 'Usuário não encontrado';
            break;
        }

        $usuario = $result->fetch_assoc();
        unset($usuario['senha']); // Remover senha

        // Permissões especiais não são mais usadas (tabela permissoes_usuario não existe)
        // As permissões agora vêm apenas do grupo via permissoes_grupo
        $usuario['permissoes_especiais'] = [];

        $response['success'] = true;
        $response['usuario'] = $usuario;
        break;

    case 'create':
        // Criar novo usuário
        if (!$accessControl->verificarPermissao('usuarios', 'criar')) {
            $response['message'] = 'Você não possui permissão para criar usuários';
            break;
        }

        $login = Security::sanitize($_POST['login'] ?? '');
        $nome = Security::sanitize($_POST['nome'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $grupo_id = intval($_POST['grupo_id'] ?? 0);
        $ativo = isset($_POST['ativo']) ? intval($_POST['ativo']) : 1;

        if (empty($login) || empty($email) || empty($senha)) {
            $response['message'] = 'Login, e-mail e senha são obrigatórios';
            break;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'E-mail inválido';
            break;
        }

        if (strlen($senha) < 6) {
            $response['message'] = 'A senha deve ter no mínimo 6 caracteres';
            break;
        }

        // Verificar se e-mail já existe
        $sql = "SELECT id FROM usuarios WHERE email = '$email'";
        $result = $db->query($sql);
        
        if ($result->num_rows > 0) {
            $response['message'] = 'Já existe um usuário com este e-mail';
            break;
        }

        // Criptografar senha
        $senha_hash = Security::hashPassword($senha);
        $clienteId = $_SESSION['cliente_id'] ?? null;

        $nome_sql = $nome ? "'$nome'" : "NULL";
        $cliente_sql = $clienteId ? "$clienteId" : "NULL";
        
        if ($grupo_id) {
            $sql = "INSERT INTO usuarios (login, nome, email, senha, ativo, grupo_id, cliente_id) 
                    VALUES ('$login', $nome_sql, '$email', '$senha_hash', $ativo, $grupo_id, $cliente_sql)";
        } else {
            $sql = "INSERT INTO usuarios (login, nome, email, senha, ativo, cliente_id) 
                    VALUES ('$login', $nome_sql, '$email', '$senha_hash', $ativo, $cliente_sql)";
        }

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Usuário criado com sucesso';
            $response['id'] = $db->lastInsertId();
            
            $accessControl->registrarAcao('usuarios', 'criar', "Criou usuário: $login ($email)");
        } else {
            $response['message'] = 'Erro ao criar usuário: ' . $db->getError();
        }
        break;

    case 'update':
        // Atualizar usuário
        if (!$accessControl->verificarPermissao('usuarios', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar usuários';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $login = Security::sanitize($_POST['login'] ?? '');
        $nome = Security::sanitize($_POST['nome'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $grupo_id = isset($_POST['grupo_id']) ? intval($_POST['grupo_id']) : null;
        $ativo = isset($_POST['ativo']) ? intval($_POST['ativo']) : 1;

        if (!$id || empty($login) || empty($email)) {
            $response['message'] = 'ID, login e e-mail são obrigatórios';
            break;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'E-mail inválido';
            break;
        }

        // Verificar se e-mail já existe em outro usuário
        $sql = "SELECT id FROM usuarios WHERE email = '$email' AND id != $id";
        $result = $db->query($sql);
        
        if ($result->num_rows > 0) {
            $response['message'] = 'Já existe outro usuário com este e-mail';
            break;
        }

        $nome_sql = $nome ? "'$nome'" : "NULL";
        
        $set_parts = [
            "login = '$login'",
            "nome = $nome_sql",
            "email = '$email'",
            "ativo = $ativo"
        ];

        if ($grupo_id !== null) {
            if ($grupo_id > 0) {
                $set_parts[] = "grupo_id = $grupo_id";
            } else {
                $set_parts[] = "grupo_id = NULL";
            }
        }

        if (!empty($senha)) {
            if (strlen($senha) < 6) {
                $response['message'] = 'A senha deve ter no mínimo 6 caracteres';
                break;
            }
            $senha_hash = Security::hashPassword($senha);
            $set_parts[] = "senha = '$senha_hash'";
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $set_parts) . " WHERE id = $id";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Usuário atualizado com sucesso';
            
            $accessControl->registrarAcao('usuarios', 'editar', "Atualizou usuário #$id: $login");
        } else {
            $response['message'] = 'Erro ao atualizar usuário: ' . $db->getError();
        }
        break;

    case 'delete':
        // Excluir usuário
        if (!$accessControl->verificarPermissao('usuarios', 'excluir')) {
            $response['message'] = 'Você não possui permissão para excluir usuários';
            break;
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            $response['message'] = 'ID inválido';
            break;
        }

        // Não permitir excluir a si mesmo
        if ($id == $_SESSION['userId']) {
            $response['message'] = 'Você não pode excluir seu próprio usuário';
            break;
        }

        // Buscar informações antes de excluir
        $sql = "SELECT login, nome, email FROM usuarios WHERE id = $id";
        $result = $db->query($sql);
        
        if ($result->num_rows == 0) {
            $response['message'] = 'Usuário não encontrado';
            break;
        }
        
        $user = $result->fetch_assoc();

        $sql = "DELETE FROM usuarios WHERE id = $id";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Usuário excluído com sucesso';
            
            $nome_display = $user['nome'] ? $user['nome'] : $user['login'];
            $accessControl->registrarAcao('usuarios', 'excluir', "Excluiu usuário #$id: $nome_display ({$user['email']})");
        } else {
            $response['message'] = 'Erro ao excluir usuário: ' . $db->getError();
        }
        break;

    case 'toggle':
        // Alternar status ativo/inativo
        if (!$accessControl->verificarPermissao('usuarios', 'editar')) {
            $response['message'] = 'Você não possui permissão para alterar usuários';
            break;
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            $response['message'] = 'ID inválido';
            break;
        }

        // Não permitir desativar a si mesmo
        if ($id == $_SESSION['userId']) {
            $response['message'] = 'Você não pode desativar seu próprio usuário';
            break;
        }

        $sql = "UPDATE usuarios SET ativo = 1 - ativo WHERE id = $id";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Status alterado com sucesso';
            
            $accessControl->registrarAcao('usuarios', 'editar', "Alterou status do usuário #$id");
        } else {
            $response['message'] = 'Erro ao alterar status: ' . $db->getError();
        }
        break;

    case 'reset_password':
        // Resetar senha do usuário
        if (!$accessControl->verificarPermissao('usuarios', 'editar')) {
            $response['message'] = 'Você não possui permissão para resetar senhas';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $nova_senha = $_POST['nova_senha'] ?? '';

        if (!$id || empty($nova_senha)) {
            $response['message'] = 'ID e nova senha são obrigatórios';
            break;
        }

        if (strlen($nova_senha) < 6) {
            $response['message'] = 'A senha deve ter no mínimo 6 caracteres';
            break;
        }

        $senha_hash = Security::hashPassword($nova_senha);
        
        $sql = "UPDATE usuarios SET senha = '$senha_hash' WHERE id = $id";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Senha resetada com sucesso';
            
            $accessControl->registrarAcao('usuarios', 'editar', "Resetou senha do usuário #$id");
        } else {
            $response['message'] = 'Erro ao resetar senha: ' . $db->getError();
        }
        break;

    case 'permissoes':
        // Gerenciar permissões especiais do usuário
        if (!$accessControl->verificarPermissao('usuarios', 'editar')) {
            $response['message'] = 'Você não possui permissão para gerenciar permissões';
            break;
        }

        $usuario_id = intval($_POST['usuario_id'] ?? 0);
        $aplicacao_id = intval($_POST['aplicacao_id'] ?? 0);
        $permissoes = $_POST['permissoes'] ?? [];

        if (!$usuario_id || !$aplicacao_id) {
            $response['message'] = 'ID do usuário e da aplicação são obrigatórios';
            break;
        }

        // Verificar se já existe permissão especial
        $sql = "SELECT id FROM permissoes_usuario WHERE usuario_id = $usuario_id AND aplicacao_id = $aplicacao_id";
        $result = $db->query($sql);

        $acessar = isset($permissoes['acessar']) ? intval($permissoes['acessar']) : 0;
        $criar = isset($permissoes['criar']) ? intval($permissoes['criar']) : 0;
        $visualizar = isset($permissoes['visualizar']) ? intval($permissoes['visualizar']) : 0;
        $editar = isset($permissoes['editar']) ? intval($permissoes['editar']) : 0;
        $excluir = isset($permissoes['excluir']) ? intval($permissoes['excluir']) : 0;
        $exportar = isset($permissoes['exportar']) ? intval($permissoes['exportar']) : 0;
        $importar = isset($permissoes['importar']) ? intval($permissoes['importar']) : 0;

        if ($result->num_rows > 0) {
            // Atualizar existente
            $sql = "UPDATE permissoes_usuario SET 
                    acessar = $acessar,
                    criar = $criar,
                    visualizar = $visualizar,
                    editar = $editar,
                    excluir = $excluir,
                    exportar = $exportar,
                    importar = $importar
                    WHERE usuario_id = $usuario_id AND aplicacao_id = $aplicacao_id";
        } else {
            // Criar novo
            $sql = "INSERT INTO permissoes_usuario 
                    (usuario_id, aplicacao_id, acessar, criar, visualizar, editar, excluir, exportar, importar) 
                    VALUES ($usuario_id, $aplicacao_id, $acessar, $criar, $visualizar, $editar, $excluir, $exportar, $importar)";
        }

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Permissões especiais atualizadas com sucesso';
            
            $accessControl->registrarAcao('usuarios', 'editar', "Atualizou permissões especiais do usuário #$usuario_id");
        } else {
            $response['message'] = 'Erro ao atualizar permissões: ' . $db->getError();
        }
        break;

    case 'remove_permissao':
        // Remover permissão especial
        if (!$accessControl->verificarPermissao('usuarios', 'editar')) {
            $response['message'] = 'Você não possui permissão para gerenciar permissões';
            break;
        }

        $usuario_id = intval($_POST['usuario_id'] ?? 0);
        $aplicacao_id = intval($_POST['aplicacao_id'] ?? 0);

        if (!$usuario_id || !$aplicacao_id) {
            $response['message'] = 'ID do usuário e da aplicação são obrigatórios';
            break;
        }

        $sql = "DELETE FROM permissoes_usuario WHERE usuario_id = $usuario_id AND aplicacao_id = $aplicacao_id";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Permissão especial removida com sucesso';
            
            $accessControl->registrarAcao('usuarios', 'editar', "Removeu permissão especial do usuário #$usuario_id");
        } else {
            $response['message'] = 'Erro ao remover permissão: ' . $db->getError();
        }
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
