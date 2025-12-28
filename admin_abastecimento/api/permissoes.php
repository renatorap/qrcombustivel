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

if (!$accessControl->verificarPermissao('permissoes', 'acessar')) {
    $response['message'] = 'Você não possui permissão para gerenciar permissões';
    echo json_encode($response);
    exit;
}

$db = new Database();
$db->connect();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_grupo':
        // Buscar permissões de um grupo
        $grupoId = intval($_GET['grupo_id']);
        
        // Buscar todas as aplicações com as permissões do grupo
        $sql = "SELECT 
                    a.id,
                    a.codigo,
                    a.nome,
                    a.descricao,
                    a.modulo,
                    COALESCE(pg.pode_acessar, 0) AS pode_acessar,
                    COALESCE(pg.pode_criar, 0) AS pode_criar,
                    COALESCE(pg.pode_visualizar, 0) AS pode_visualizar,
                    COALESCE(pg.pode_editar, 0) AS pode_editar,
                    COALESCE(pg.pode_excluir, 0) AS pode_excluir,
                    COALESCE(pg.pode_exportar, 0) AS pode_exportar,
                    COALESCE(pg.pode_importar, 0) AS pode_importar
                FROM aplicacoes a
                LEFT JOIN permissoes_grupo pg ON a.id = pg.aplicacao_id AND pg.grupo_id = $grupoId
                WHERE a.ativo = 1
                ORDER BY a.modulo, a.ordem, a.nome";
        
        $result = $db->query($sql);
        $aplicacoes = [];
        
        while ($row = $result->fetch_assoc()) {
            $aplicacoes[] = $row;
        }
        
        $response['success'] = true;
        $response['aplicacoes'] = $aplicacoes;
        $accessControl->registrarAcao('permissoes', 'visualizar', "Visualizou permissões do grupo ID: $grupoId");
        break;

    case 'update_grupo':
        // Atualizar permissões de um grupo
        if (!$accessControl->verificarPermissao('permissoes', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar permissões';
            break;
        }

        $grupoId = intval($_POST['grupo_id']);
        $aplicacaoId = intval($_POST['aplicacao_id']);
        $permissoes = $_POST['permissoes'] ?? [];

        $podeAcessar = isset($permissoes['acessar']) ? 1 : 0;
        $podeCriar = isset($permissoes['criar']) ? 1 : 0;
        $podeVisualizar = isset($permissoes['visualizar']) ? 1 : 0;
        $podeEditar = isset($permissoes['editar']) ? 1 : 0;
        $podeExcluir = isset($permissoes['excluir']) ? 1 : 0;
        $podeExportar = isset($permissoes['exportar']) ? 1 : 0;
        $podeImportar = isset($permissoes['importar']) ? 1 : 0;

        // Verificar se já existe permissão
        $sqlCheck = "SELECT id FROM permissoes_grupo WHERE grupo_id = $grupoId AND aplicacao_id = $aplicacaoId";
        $resultCheck = $db->query($sqlCheck);

        if ($resultCheck->num_rows > 0) {
            // Atualizar
            $sql = "UPDATE permissoes_grupo SET 
                        pode_acessar = $podeAcessar,
                        pode_criar = $podeCriar,
                        pode_visualizar = $podeVisualizar,
                        pode_editar = $podeEditar,
                        pode_excluir = $podeExcluir,
                        pode_exportar = $podeExportar,
                        pode_importar = $podeImportar
                    WHERE grupo_id = $grupoId AND aplicacao_id = $aplicacaoId";
        } else {
            // Inserir
            $sql = "INSERT INTO permissoes_grupo 
                        (grupo_id, aplicacao_id, pode_acessar, pode_criar, pode_visualizar, pode_editar, pode_excluir, pode_exportar, pode_importar)
                    VALUES 
                        ($grupoId, $aplicacaoId, $podeAcessar, $podeCriar, $podeVisualizar, $podeEditar, $podeExcluir, $podeExportar, $podeImportar)";
        }

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Permissões atualizadas com sucesso';
            $accessControl->registrarAcao('permissoes', 'editar', "Atualizou permissões do grupo ID: $grupoId, aplicação ID: $aplicacaoId");
        } else {
            $response['message'] = 'Erro ao atualizar permissões';
        }
        break;

    case 'update_grupo_bulk':
        // Atualizar várias permissões de uma vez
        if (!$accessControl->verificarPermissao('permissoes', 'editar')) {
            $response['message'] = 'Você não possui permissão para editar permissões';
            break;
        }

        $grupoId = intval($_POST['grupo_id']);
        $permissoes = json_decode($_POST['permissoes'], true);

        if (!$permissoes) {
            $response['message'] = 'Dados de permissões inválidos';
            break;
        }

        $success = true;
        $count = 0;

        foreach ($permissoes as $perm) {
            $aplicacaoId = intval($perm['aplicacao_id']);
            $podeAcessar = $perm['pode_acessar'] ? 1 : 0;
            $podeCriar = $perm['pode_criar'] ? 1 : 0;
            $podeVisualizar = $perm['pode_visualizar'] ? 1 : 0;
            $podeEditar = $perm['pode_editar'] ? 1 : 0;
            $podeExcluir = $perm['pode_excluir'] ? 1 : 0;
            $podeExportar = $perm['pode_exportar'] ? 1 : 0;
            $podeImportar = $perm['pode_importar'] ? 1 : 0;

            // INSERT ON DUPLICATE KEY UPDATE
            $sql = "INSERT INTO permissoes_grupo 
                        (grupo_id, aplicacao_id, pode_acessar, pode_criar, pode_visualizar, pode_editar, pode_excluir, pode_exportar, pode_importar)
                    VALUES 
                        ($grupoId, $aplicacaoId, $podeAcessar, $podeCriar, $podeVisualizar, $podeEditar, $podeExcluir, $podeExportar, $podeImportar)
                    ON DUPLICATE KEY UPDATE
                        pode_acessar = $podeAcessar,
                        pode_criar = $podeCriar,
                        pode_visualizar = $podeVisualizar,
                        pode_editar = $podeEditar,
                        pode_excluir = $podeExcluir,
                        pode_exportar = $podeExportar,
                        pode_importar = $podeImportar";

            if ($db->query($sql)) {
                $count++;
            } else {
                $success = false;
            }
        }

        if ($success) {
            $response['success'] = true;
            $response['message'] = "Permissões atualizadas com sucesso ($count aplicações)";
            $accessControl->registrarAcao('permissoes', 'editar', "Atualizou permissões em lote do grupo ID: $grupoId");
        } else {
            $response['message'] = 'Erro ao atualizar algumas permissões';
        }
        break;

    case 'get_usuario':
        // Buscar permissões especiais de um usuário
        $usuarioId = intval($_GET['usuario_id']);
        
        $sql = "SELECT 
                    a.id,
                    a.codigo,
                    a.nome,
                    a.descricao,
                    a.modulo,
                    pu.pode_acessar,
                    pu.pode_criar,
                    pu.pode_visualizar,
                    pu.pode_editar,
                    pu.pode_excluir,
                    pu.pode_exportar,
                    pu.pode_importar,
                    pu.observacao
                FROM aplicacoes a
                INNER JOIN permissoes_usuario pu ON a.id = pu.aplicacao_id
                WHERE pu.usuario_id = $usuarioId
                ORDER BY a.modulo, a.ordem, a.nome";
        
        $result = $db->query($sql);
        $permissoes = [];
        
        while ($row = $result->fetch_assoc()) {
            $permissoes[] = $row;
        }
        
        $response['success'] = true;
        $response['permissoes'] = $permissoes;
        break;

    case 'copy_permissions':
        // Copiar permissões de um grupo para outro
        if (!$accessControl->verificarPermissao('permissoes', 'criar')) {
            $response['message'] = 'Você não possui permissão para copiar permissões';
            break;
        }

        $grupoOrigemId = intval($_POST['grupo_origem_id']);
        $grupoDestinoId = intval($_POST['grupo_destino_id']);

        // Copiar permissões
        $sql = "INSERT INTO permissoes_grupo 
                    (grupo_id, aplicacao_id, pode_acessar, pode_criar, pode_visualizar, pode_editar, pode_excluir, pode_exportar, pode_importar)
                SELECT 
                    $grupoDestinoId,
                    aplicacao_id,
                    pode_acessar,
                    pode_criar,
                    pode_visualizar,
                    pode_editar,
                    pode_excluir,
                    pode_exportar,
                    pode_importar
                FROM permissoes_grupo
                WHERE grupo_id = $grupoOrigemId
                ON DUPLICATE KEY UPDATE
                    pode_acessar = VALUES(pode_acessar),
                    pode_criar = VALUES(pode_criar),
                    pode_visualizar = VALUES(pode_visualizar),
                    pode_editar = VALUES(pode_editar),
                    pode_excluir = VALUES(pode_excluir),
                    pode_exportar = VALUES(pode_exportar),
                    pode_importar = VALUES(pode_importar)";

        if ($db->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'Permissões copiadas com sucesso';
            $accessControl->registrarAcao('permissoes', 'criar', "Copiou permissões do grupo $grupoOrigemId para $grupoDestinoId");
        } else {
            $response['message'] = 'Erro ao copiar permissões';
        }
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
