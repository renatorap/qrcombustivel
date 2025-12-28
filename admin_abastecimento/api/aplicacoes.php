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

if (!$accessControl->verificarPermissao('aplicacoes', 'acessar')) {
    $response['message'] = 'Você não possui permissão para gerenciar aplicações';
    echo json_encode($response);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = new Database();
$db->connect();

switch ($action) {
    case 'list':
        // Listar aplicações
        $search = Security::sanitize($_GET['search'] ?? '');
        $modulo = Security::sanitize($_GET['modulo'] ?? '');
        $ativo = isset($_GET['ativo']) ? intval($_GET['ativo']) : null;
        $page = intval($_GET['page'] ?? 1);
        $limit = PAGINATION_LIMIT;
        $offset = ($page - 1) * $limit;

        $where = "1=1";
        if ($search) {
            $search = strtolower($search);
            $where .= " AND (LOWER(codigo) LIKE '%$search%' OR LOWER(nome) LIKE '%$search%')";
        }
        if ($modulo) {
            $where .= " AND modulo = '$modulo'";
        }
        if ($ativo !== null) {
            $where .= " AND ativo = $ativo";
        }

        $sql = "SELECT COUNT(*) as total FROM aplicacoes WHERE $where";
        $result = $db->query($sql);
        $row = $result->fetch_assoc();
        $totalPages = ceil($row['total'] / $limit);

        $sql = "SELECT * FROM aplicacoes WHERE $where ORDER BY modulo, ordem, nome LIMIT $limit OFFSET $offset";
        $result = $db->query($sql);

        $aplicacoes = [];
        while ($row = $result->fetch_assoc()) {
            $aplicacoes[] = $row;
        }

        $response['success'] = true;
        $response['aplicacoes'] = $aplicacoes;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;

    case 'get':
        // Obter aplicação específica
        $id = intval($_GET['id'] ?? 0);

        if (!$id) {
            $response['message'] = 'ID inválido';
            break;
        }

        $sql = "SELECT * FROM aplicacoes WHERE id = $id";
        $result = $db->query($sql);

        if ($result->num_rows == 0) {
            $response['message'] = 'Aplicação não encontrada';
            break;
        }

        $response['success'] = true;
        $response['aplicacao'] = $result->fetch_assoc();
        break;

    case 'create':
        // Criar nova aplicação
        if (!$accessControl->verificarPermissao('aplicacoes', 'criar')) {
            $response['message'] = 'Você não possui permissão para criar aplicações';
            break;
        }

        $codigo = Security::sanitize($_POST['codigo'] ?? '');
        $nome = Security::sanitize($_POST['nome'] ?? '');
        $modulo = Security::sanitize($_POST['modulo'] ?? 'Sistema');
        $icone = Security::sanitize($_POST['icone'] ?? 'fa-file');
        $ordem = intval($_POST['ordem'] ?? 999);
        $ativo = isset($_POST['ativo']) ? intval($_POST['ativo']) : 1;

        if (empty($codigo) || empty($nome)) {
            $response['message'] = 'Código e nome são obrigatórios';
            break;
        }

        // Verificar duplicação
        $sql = "SELECT id FROM aplicacoes WHERE codigo = '$codigo'";
        $result = $db->query($sql);
        
        if ($result->num_rows > 0) {
            $response['message'] = 'Já existe uma aplicação com este código';
            break;
        }

        $sql = "INSERT INTO aplicacoes (codigo, nome, modulo, icone, ordem, ativo) 
                VALUES ('$codigo', '$nome', '$modulo', '$icone', $ordem, $ativo)";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Aplicação criada com sucesso';
            $response['id'] = $db->lastInsertId();
            
            $accessControl->registrarAcao('aplicacoes', 'criar', "Criou aplicação: $codigo - $nome");
        } else {
            $response['message'] = 'Erro ao criar aplicação: ' . $db->getError();
        }
        break;

    case 'update':
        // Atualizar aplicação
        if (!$accessControl->verificarPermissao('aplicacoes', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar aplicações';
            break;
        }

        $id = intval($_POST['id'] ?? 0);
        $codigo = Security::sanitize($_POST['codigo'] ?? '');
        $nome = Security::sanitize($_POST['nome'] ?? '');
        $modulo = Security::sanitize($_POST['modulo'] ?? 'Sistema');
        $icone = Security::sanitize($_POST['icone'] ?? 'fa-file');
        $ordem = intval($_POST['ordem'] ?? 999);
        $ativo = isset($_POST['ativo']) ? intval($_POST['ativo']) : 1;

        if (!$id || empty($codigo) || empty($nome)) {
            $response['message'] = 'ID, código e nome são obrigatórios';
            break;
        }

        // Verificar duplicação
        $sql = "SELECT id FROM aplicacoes WHERE codigo = '$codigo' AND id != $id";
        $result = $db->query($sql);
        
        if ($result->num_rows > 0) {
            $response['message'] = 'Já existe outra aplicação com este código';
            break;
        }

        $sql = "UPDATE aplicacoes SET 
                codigo = '$codigo',
                nome = '$nome',
                modulo = '$modulo',
                icone = '$icone',
                ordem = $ordem,
                ativo = $ativo
                WHERE id = $id";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Aplicação atualizada com sucesso';
            
            $accessControl->registrarAcao('aplicacoes', 'editar', "Atualizou aplicação #$id: $codigo - $nome");
        } else {
            $response['message'] = 'Erro ao atualizar aplicação: ' . $db->getError();
        }
        break;

    case 'delete':
        // Excluir aplicação
        if (!$accessControl->verificarPermissao('aplicacoes', 'excluir')) {
            $response['message'] = 'Você não possui permissão para excluir aplicações';
            break;
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            $response['message'] = 'ID inválido';
            break;
        }

        // Buscar informações antes de excluir
        $sql = "SELECT codigo, nome FROM aplicacoes WHERE id = $id";
        $result = $db->query($sql);
        
        if ($result->num_rows == 0) {
            $response['message'] = 'Aplicação não encontrada';
            break;
        }
        
        $app = $result->fetch_assoc();

        // Verificar se há permissões vinculadas
        $sql = "SELECT COUNT(*) as total FROM permissoes_grupo WHERE aplicacao_id = $id";
        $result = $db->query($sql);
        $row = $result->fetch_assoc();
        
        if ($row['total'] > 0) {
            $response['message'] = 'Não é possível excluir esta aplicação pois existem permissões vinculadas a ela';
            break;
        }

        $sql = "DELETE FROM aplicacoes WHERE id = $id";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Aplicação excluída com sucesso';
            
            $accessControl->registrarAcao('aplicacoes', 'excluir', "Excluiu aplicação #$id: {$app['codigo']} - {$app['nome']}");
        } else {
            $response['message'] = 'Erro ao excluir aplicação: ' . $db->getError();
        }
        break;

    case 'toggle':
        // Alternar status ativo/inativo
        if (!$accessControl->verificarPermissao('aplicacoes', 'editar')) {
            $response['message'] = 'Você não possui permissão para alterar aplicações';
            break;
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            $response['message'] = 'ID inválido';
            break;
        }

        $sql = "UPDATE aplicacoes SET ativo = 1 - ativo WHERE id = $id";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Status alterado com sucesso';
            
            $accessControl->registrarAcao('aplicacoes', 'editar', "Alterou status da aplicação #$id");
        } else {
            $response['message'] = 'Erro ao alterar status: ' . $db->getError();
        }
        break;

    case 'modulos':
        // Listar módulos únicos
        $sql = "SELECT DISTINCT modulo FROM aplicacoes ORDER BY modulo";
        $result = $db->query($sql);

        $modulos = [];
        while ($row = $result->fetch_assoc()) {
            $modulos[] = $row['modulo'];
        }

        $response['success'] = true;
        $response['modulos'] = $modulos;
        break;

    case 'grupos':
        // Listar grupos que têm permissão para uma aplicação
        $id = intval($_GET['id'] ?? 0);

        if (!$id) {
            $response['message'] = 'ID inválido';
            break;
        }

        $sql = "SELECT g.*, 
                       p.pode_acessar, p.pode_criar, p.pode_visualizar, p.pode_editar, p.pode_excluir, p.pode_exportar, p.pode_importar
                FROM grupos g
                LEFT JOIN permissoes_grupo p ON g.id = p.grupo_id AND p.aplicacao_id = $id
                WHERE g.ativo = 1
                ORDER BY g.nome";
        
        $result = $db->query($sql);

        $grupos = [];
        while ($row = $result->fetch_assoc()) {
            $grupos[] = $row;
        }

        $response['success'] = true;
        $response['grupos'] = $grupos;
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
