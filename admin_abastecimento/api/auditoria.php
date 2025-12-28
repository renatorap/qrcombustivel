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

if (!$accessControl->verificarPermissao('auditoria', 'acessar')) {
    $response['message'] = 'Você não possui permissão para visualizar auditoria';
    echo json_encode($response);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = new Database();
$db->connect();

switch ($action) {
    case 'list':
        // Listar logs de auditoria
        $usuario_id = intval($_GET['usuario_id'] ?? 0);
        $aplicacao_id = intval($_GET['aplicacao_id'] ?? 0);
        $acao = Security::sanitize($_GET['acao'] ?? '');
        $data_inicio = Security::sanitize($_GET['data_inicio'] ?? '');
        $data_fim = Security::sanitize($_GET['data_fim'] ?? '');
        $search = Security::sanitize($_GET['search'] ?? '');
        $page = intval($_GET['page'] ?? 1);
        $limit = PAGINATION_LIMIT;
        $offset = ($page - 1) * $limit;

        $where = "1=1";
        
        if ($usuario_id) {
            $where .= " AND a.usuario_id = $usuario_id";
        }
        
        if ($aplicacao_id) {
            $where .= " AND a.aplicacao_id = $aplicacao_id";
        }
        
        if ($acao) {
            $where .= " AND a.acao = '$acao'";
        }
        
        if ($data_inicio) {
            $where .= " AND DATE(a.data_hora) >= '$data_inicio'";
        }
        
        if ($data_fim) {
            $where .= " AND DATE(a.data_hora) <= '$data_fim'";
        }
        
        if ($search) {
            $search = strtolower($search);
            $where .= " AND (LOWER(u.nome) LIKE '%$search%' OR LOWER(ap.nome) LIKE '%$search%' OR LOWER(a.descricao) LIKE '%$search%' OR LOWER(a.ip_address) LIKE '%$search%')";
        }

        // Contar total
        $sql = "SELECT COUNT(*) as total 
                FROM acoes_usuario a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                LEFT JOIN aplicacoes ap ON a.aplicacao_id = ap.id
                WHERE $where";
        $result = $db->query($sql);
        $row = $result->fetch_assoc();
        $totalPages = ceil($row['total'] / $limit);

        // Buscar logs
        $sql = "SELECT a.*, 
                       u.nome as usuario_nome,
                       u.email as usuario_email,
                       ap.nome as aplicacao_nome,
                       ap.codigo as aplicacao_codigo
                FROM acoes_usuario a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                LEFT JOIN aplicacoes ap ON a.aplicacao_id = ap.id
                WHERE $where
                ORDER BY a.data_hora DESC
                LIMIT $limit OFFSET $offset";
        
        $result = $db->query($sql);

        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }

        $response['success'] = true;
        $response['logs'] = $logs;
        $response['totalPages'] = $totalPages;
        $response['currentPage'] = $page;
        break;

    case 'get':
        // Obter log específico
        $id = intval($_GET['id'] ?? 0);

        if (!$id) {
            $response['message'] = 'ID inválido';
            break;
        }

        $sql = "SELECT a.*, 
                       u.nome as usuario_nome,
                       u.email as usuario_email,
                       ap.nome as aplicacao_nome,
                       ap.codigo as aplicacao_codigo
                FROM acoes_usuario a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                LEFT JOIN aplicacoes ap ON a.aplicacao_id = ap.id
                WHERE a.id = $id";
        
        $result = $db->query($sql);

        if ($result->num_rows == 0) {
            $response['message'] = 'Log não encontrado';
            break;
        }

        $response['success'] = true;
        $response['log'] = $result->fetch_assoc();
        break;

    case 'stats':
        // Estatísticas de auditoria
        $data_inicio = Security::sanitize($_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days')));
        $data_fim = Security::sanitize($_GET['data_fim'] ?? date('Y-m-d'));

        $where = "DATE(created_at) BETWEEN '$data_inicio' AND '$data_fim'";

        // Total de ações
        $sql = "SELECT COUNT(*) as total FROM acoes_usuario WHERE $where";
        $result = $db->query($sql);
        $stats['total_acoes'] = $result->fetch_assoc()['total'];

        // Ações por tipo
        $sql = "SELECT acao, COUNT(*) as total 
                FROM acoes_usuario 
                WHERE $where
                GROUP BY acao
                ORDER BY total DESC";
        $result = $db->query($sql);
        
        $stats['por_tipo'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['por_tipo'][] = $row;
        }

        // Usuários mais ativos
        $sql = "SELECT u.id, u.nome, u.email, COUNT(a.id) as total 
                FROM acoes_usuario a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                WHERE $where
                GROUP BY u.id, u.nome, u.email
                ORDER BY total DESC
                LIMIT 10";
        $result = $db->query($sql);
        
        $stats['usuarios_ativos'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['usuarios_ativos'][] = $row;
        }

        // Aplicações mais acessadas
        $sql = "SELECT ap.id, ap.codigo, ap.nome, COUNT(a.id) as total 
                FROM acoes_usuario a
                LEFT JOIN aplicacoes ap ON a.aplicacao_id = ap.id
                WHERE $where AND ap.id IS NOT NULL
                GROUP BY ap.id, ap.codigo, ap.nome
                ORDER BY total DESC
                LIMIT 10";
        $result = $db->query($sql);
        
        $stats['aplicacoes_acessadas'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['aplicacoes_acessadas'][] = $row;
        }

        // Ações por dia
        $sql = "SELECT DATE(created_at) as dia, COUNT(*) as total 
                FROM acoes_usuario 
                WHERE $where
                GROUP BY DATE(created_at)
                ORDER BY dia ASC";
        $result = $db->query($sql);
        
        $stats['por_dia'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['por_dia'][] = $row;
        }

        // Ações por hora do dia
        $sql = "SELECT HOUR(created_at) as hora, COUNT(*) as total 
                FROM acoes_usuario 
                WHERE $where
                GROUP BY HOUR(created_at)
                ORDER BY hora ASC";
        $result = $db->query($sql);
        
        $stats['por_hora'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['por_hora'][] = $row;
        }

        $response['success'] = true;
        $response['stats'] = $stats;
        $response['periodo'] = [
            'inicio' => $data_inicio,
            'fim' => $data_fim
        ];
        break;

    case 'usuarios':
        // Listar usuários que têm logs
        $sql = "SELECT DISTINCT u.id, u.nome, u.email
                FROM acoes_usuario a
                INNER JOIN usuarios u ON a.usuario_id = u.id
                ORDER BY u.nome";
        
        $result = $db->query($sql);

        $usuarios = [];
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }

        $response['success'] = true;
        $response['usuarios'] = $usuarios;
        break;

    case 'aplicacoes':
        // Listar aplicações que têm logs
        $sql = "SELECT DISTINCT ap.id, ap.codigo, ap.nome
                FROM acoes_usuario a
                INNER JOIN aplicacoes ap ON a.aplicacao_id = ap.id
                ORDER BY ap.nome";
        
        $result = $db->query($sql);

        $aplicacoes = [];
        while ($row = $result->fetch_assoc()) {
            $aplicacoes[] = $row;
        }

        $response['success'] = true;
        $response['aplicacoes'] = $aplicacoes;
        break;

    case 'acoes':
        // Listar tipos de ações distintas
        $sql = "SELECT DISTINCT acao FROM acoes_usuario ORDER BY acao";
        
        $result = $db->query($sql);

        $acoes = [];
        while ($row = $result->fetch_assoc()) {
            $acoes[] = $row['acao'];
        }

        $response['success'] = true;
        $response['acoes'] = $acoes;
        break;

    case 'export':
        // Exportar logs (apenas usuários com permissão de exportar)
        if (!$accessControl->verificarPermissao('auditoria', 'exportar')) {
            $response['message'] = 'Você não possui permissão para exportar logs';
            break;
        }

        $usuario_id = intval($_GET['usuario_id'] ?? 0);
        $aplicacao_id = intval($_GET['aplicacao_id'] ?? 0);
        $acao = Security::sanitize($_GET['acao'] ?? '');
        $data_inicio = Security::sanitize($_GET['data_inicio'] ?? '');
        $data_fim = Security::sanitize($_GET['data_fim'] ?? '');
        $formato = Security::sanitize($_GET['formato'] ?? 'csv');

        $where = "1=1";
        
        if ($usuario_id) {
            $where .= " AND a.usuario_id = $usuario_id";
        }
        
        if ($aplicacao_id) {
            $where .= " AND a.aplicacao_id = $aplicacao_id";
        }
        
        if ($acao) {
            $where .= " AND a.acao = '$acao'";
        }
        
        if ($data_inicio) {
            $where .= " AND DATE(a.created_at) >= '$data_inicio'";
        }
        
        if ($data_fim) {
            $where .= " AND DATE(a.created_at) <= '$data_fim'";
        }

        $sql = "SELECT a.id, a.created_at, 
                       u.nome as usuario, u.email,
                       ap.codigo as aplicacao, ap.nome as aplicacao_nome,
                       a.acao, a.descricao, a.ip_address
                FROM acoes_usuario a
                LEFT JOIN usuarios u ON a.usuario_id = u.id
                LEFT JOIN aplicacoes ap ON a.aplicacao_id = ap.id
                WHERE $where
                ORDER BY a.created_at DESC
                LIMIT 10000";
        
        $result = $db->query($sql);

        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }

        $response['success'] = true;
        $response['dados'] = $dados;
        $response['total'] = count($dados);
        
        $accessControl->registrarAcao('auditoria', 'exportar', 
            "Exportou " . count($dados) . " logs em formato $formato"
        );
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
