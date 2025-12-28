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

    case 'sexos':
        $sql = "SELECT id_sexo as id, descricao as nome FROM sexo ORDER BY descricao";
        $result = $db->query($sql);
        $sexos = [];
        while ($row = $result->fetch_assoc()) {
            $sexos[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $sexos;
        break;

    case 'tipos_sanguineos':
        $sql = "SELECT id_tp_sanguineo as id, codigo as nome FROM tp_sanguineo ORDER BY codigo";
        $result = $db->query($sql);
        $tipos = [];
        while ($row = $result->fetch_assoc()) {
            $tipos[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $tipos;
        break;

    case 'cargos':
        $clienteId = $_SESSION['cliente_id'] ?? null;
        $where = $clienteId ? "WHERE id_cliente = $clienteId" : "1=1";
        $sql = "SELECT id_cargo as id, descricao as nome FROM cargo $where ORDER BY descricao";
        $result = $db->query($sql);
        $cargos = [];
        while ($row = $result->fetch_assoc()) {
            $cargos[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $cargos;
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

    default:
        $response['message'] = 'Ação inválida';
}

$db->close();
echo json_encode($response);
?>
