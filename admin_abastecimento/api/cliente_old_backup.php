<?php
header('Content-Type: application/json');
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

$response = ['success' => false, 'message' => '', 'cliente' => null];

if (empty($_SESSION['token'])) {
    $response['message'] = 'Não autenticado';
    echo json_encode($response);
    exit;
}

$accessControl = new AccessControl($_SESSION['userId']);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$db = new Database();
$db->connect();
$conn = $db->getConnection();

function ensureClienteTable($db) {
    // Tabela já criada via migration - não precisa criar aqui
    return true;
}

function ensureAplicacaoRegistered($db) {
    // Aplicação já registrada no banco
    return true;
}

function saveLogo($fieldName = 'logo') {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) return null;
    $file = $_FILES[$fieldName];
    $dir = realpath(__DIR__ . '/../storage');
    if ($dir === false) $dir = __DIR__ . '/../storage';
    $uploadDir = $dir . '/uploads/logos';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
    $name = 'cliente_logo_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . ($safeExt ? ('.' . strtolower($safeExt)) : '');
    $dest = $uploadDir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return null;
    // Return relative path from project root to be web-accessible if needed
    return 'storage/uploads/logos/' . $name;
}

try {
    ensureClienteTable($db);
    ensureAplicacaoRegistered($db);

    switch ($action) {
        case 'get':
            if (!$accessControl->verificarPermissao('cliente', 'acessar')) {
                $response['message'] = 'Sem permissão para acessar cliente';
                break;
            }
            $res = $db->query("SELECT * FROM clientes ORDER BY id ASC LIMIT 1");
            $response['success'] = true;
            $response['cliente'] = $res && $res->num_rows ? $res->fetch_assoc() : null;
            $accessControl->registrarAcao('cliente', 'visualizar', 'Visualizou dados do cliente');
            break;

        case 'save':
            // Check intent based on existence
            $existsRes = $db->query("SELECT id FROM clientes ORDER BY id ASC LIMIT 1");
            $exists = ($existsRes && $existsRes->num_rows);
            if ($exists) {
                if (!$accessControl->verificarPermissao('cliente', 'editar')) {
                    $response['message'] = 'Sem permissão para editar cliente';
                    break;
                }
            } else {
                if (!$accessControl->verificarPermissao('cliente', 'criar')) {
                    $response['message'] = 'Sem permissão para criar cliente';
                    break;
                }
            }

            $f = fn($k) => Security::sanitize($_POST[$k] ?? null);
            
            // Converter data_fundacao se vier no formato brasileiro (dd/mm/yyyy)
            $dataFundacao = $f('data_fundacao');
            if ($dataFundacao && preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dataFundacao, $m)) {
                $dataFundacao = $m[3] . '-' . $m[2] . '-' . $m[1]; // yyyy-mm-dd
            }
            
            $data = [
                'razao_social' => $f('razao_social'),
                'nome_fantasia' => $f('nome_fantasia'),
                'cnpj' => $f('cnpj'),
                'inscricao_estadual' => $f('inscricao_estadual'),
                'inscricao_municipal' => $f('inscricao_municipal'),
                'cnae' => $f('cnae'),
                'regime_tributario' => $f('regime_tributario'),
                'data_fundacao' => $dataFundacao,
                'cep' => $f('cep'),
                'logradouro' => $f('logradouro'),
                'numero' => $f('numero'),
                'complemento' => $f('complemento'),
                'bairro' => $f('bairro'),
                'cidade' => $f('cidade'),
                'uf' => $f('uf'),
                'telefone' => $f('telefone'),
                'celular' => $f('celular'),
                'email' => $f('email'),
                'site' => $f('site'),
                'ativo' => isset($_POST['ativo']) ? 1 : 0
            ];

            if (empty($data['razao_social']) || empty($data['cnpj'])) {
                $response['message'] = 'Razão Social e CNPJ são obrigatórios';
                break;
            }

            $logoPath = saveLogo('logo');
            if ($logoPath) $data['logo_path'] = $logoPath;

            if ($exists) {
                $pairs = [];
                foreach ($data as $k => $v) {
                    $val = $v === null ? 'NULL' : ("'" . $conn->real_escape_string($v) . "'");
                    $pairs[] = "$k = $val";
                }
                $sql = 'UPDATE clientes SET ' . implode(', ', $pairs) . ' WHERE id = (SELECT id FROM (SELECT id FROM clientes ORDER BY id ASC LIMIT 1) t)';
                if ($db->query($sql)) {
                    $response['success'] = true;
                    $response['message'] = 'Cliente atualizado com sucesso';
                    $accessControl->registrarAcao('cliente', 'editar', 'Atualizou dados do cliente');
                } else {
                    $response['message'] = 'Erro ao atualizar cliente: ' . $conn->error;
                }
            } else {
                $cols = [];$vals = [];
                foreach ($data as $k => $v) {
                    $cols[] = $k;
                    if ($v === null || $v === '') {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = "'" . $conn->real_escape_string($v) . "'";
                    }
                }
                $sql = 'INSERT INTO clientes (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')';
                if ($db->query($sql)) {
                    $response['success'] = true;
                    $response['message'] = 'Cliente criado com sucesso';
                    $accessControl->registrarAcao('cliente', 'criar', 'Criou dados do cliente');
                } else {
                    $response['message'] = 'Erro ao criar cliente: ' . $conn->error;
                    $response['debug_sql'] = $sql;
                }
            }
            break;

        default:
            $response['message'] = 'Ação inválida';
    }
} catch (Exception $e) {
    $response['message'] = 'Erro: ' . $e->getMessage();
}

$db->close();
echo json_encode($response);
