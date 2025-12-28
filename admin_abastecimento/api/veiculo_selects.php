<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';

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

$db = new Database();
$db->connect();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'marcas':
        $sql = "SELECT id_marca_veiculo as id, descricao as nome FROM marca_veiculo ORDER BY descricao";
        $result = $db->query($sql);
        $marcas = [];
        while ($row = $result->fetch_assoc()) {
            $marcas[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $marcas;
        break;

    case 'cores':
        $sql = "SELECT id_cor_veiculo as id, descricao as nome FROM cor_veiculo ORDER BY descricao";
        $result = $db->query($sql);
        $cores = [];
        while ($row = $result->fetch_assoc()) {
            $cores[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $cores;
        break;

    case 'tipos':
        $sql = "SELECT id_tp_veiculo as id, descricao as nome FROM tp_veiculo ORDER BY descricao";
        $result = $db->query($sql);
        $tipos = [];
        while ($row = $result->fetch_assoc()) {
            $tipos[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $tipos;
        break;

    case 'categorias':
        $sql = "SELECT id_cat_cnh as id, codigo as nome FROM cat_cnh ORDER BY codigo";
        $result = $db->query($sql);
        $categorias = [];
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $categorias;
        break;

    case 'setores':
        $clienteId = $_SESSION['cliente_id'] ?? null;
        $where = $clienteId ? "WHERE id_cliente = $clienteId" : "1=1";
        $sql = "SELECT id_setor as id, descricao as nome FROM setor $where ORDER BY descricao";
        $result = $db->query($sql);
        $setores = [];
        while ($row = $result->fetch_assoc()) {
            $setores[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $setores;
        break;

    case 'situacoes':
        $sql = "SELECT id_situacao as id, descricao as nome FROM situacao ORDER BY descricao";
        $result = $db->query($sql);
        $situacoes = [];
        while ($row = $result->fetch_assoc()) {
            $situacoes[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $situacoes;
        break;

    case 'formas_trabalho':
        $sql = "SELECT id_forma_trabalho as id, descricao as nome FROM forma_trabalho ORDER BY descricao";
        $result = $db->query($sql);
        $formas = [];
        while ($row = $result->fetch_assoc()) {
            $formas[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $formas;
        break;

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
