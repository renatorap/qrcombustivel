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

if (!$accessControl->verificarPermissao('sincronizacao', 'acessar')) {
    $response['message'] = 'Você não possui permissão para sincronizar aplicações';
    echo json_encode($response);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'scan':
        // Escanear diretório de páginas
        if (!$accessControl->verificarPermissao('sincronizacao', 'criar')) {
            $response['message'] = 'Você não possui permissão para executar sincronização';
            break;
        }

        $resultado = $accessControl->sincronizarAplicacoes(__DIR__ . '/../pages/');
        
        $response['success'] = true;
        $response['message'] = 'Sincronização concluída';
        $response['data'] = $resultado;
        $response['summary'] = [
            'novas' => $resultado['novas'],
            'atualizadas' => $resultado['atualizadas'],
            'erros' => count($resultado['erros'])
        ];
        
        $accessControl->registrarAcao('sincronizacao', 'criar', 
            "Sincronizou aplicações: {$resultado['novas']} novas, {$resultado['atualizadas']} existentes"
        );
        break;

    case 'list':
        // Listar aplicações cadastradas
        $db = new Database();
        $db->connect();

        $search = Security::sanitize($_GET['search'] ?? '');
        $modulo = Security::sanitize($_GET['modulo'] ?? '');
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

        $db->close();
        break;

    case 'modulos':
        // Listar módulos únicos
        $db = new Database();
        $db->connect();

        $sql = "SELECT DISTINCT modulo FROM aplicacoes ORDER BY modulo";
        $result = $db->query($sql);

        $modulos = [];
        while ($row = $result->fetch_assoc()) {
            $modulos[] = $row['modulo'];
        }

        $response['success'] = true;
        $response['modulos'] = $modulos;

        $db->close();
        break;

    default:
        $response['message'] = 'Ação inválida';
}

echo json_encode($response);
?>
