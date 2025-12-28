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

$method = $_SERVER['REQUEST_METHOD'];
$database = new Database();
$conn = $database->connect();

try {
    switch ($method) {
        case 'GET':
            // Listar usuários vinculados a um fornecedor
            if (!isset($_GET['fornecedor_id'])) {
                throw new Exception('ID do fornecedor não informado');
            }
            
            $fornecedor_id = (int)$_GET['fornecedor_id'];
            
            $sql = "SELECT 
                        uf.id,
                        uf.usuario_id,
                        uf.fornecedor_id,
                        uf.ativo,
                        uf.data_vinculo,
                        u.login,
                        u.nome,
                        u.email,
                        u.perfil,
                        u.grupo_id,
                        g.nome as grupo_nome
                    FROM usuario_fornecedor uf
                    INNER JOIN usuarios u ON uf.usuario_id = u.id
                    LEFT JOIN grupos g ON u.grupo_id = g.id
                    WHERE uf.fornecedor_id = ?
                    ORDER BY u.nome";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $fornecedor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $usuarios = [];
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
            
            echo json_encode(['success' => true, 'data' => $usuarios]);
            break;
            
        case 'POST':
            // Vincular usuário a fornecedor
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['fornecedor_id']) || !isset($data['usuario_id'])) {
                throw new Exception('Dados incompletos');
            }
            
            $fornecedor_id = (int)$data['fornecedor_id'];
            $usuario_id = (int)$data['usuario_id'];
            $ativo = isset($data['ativo']) ? (int)$data['ativo'] : 1;
            
            // Verificar se o usuário pertence aos grupos 4 ou 10
            $sql = "SELECT grupo_id FROM usuarios WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();
            
            if (!$usuario || !in_array($usuario['grupo_id'], [4, 10])) {
                throw new Exception('Apenas usuários dos grupos 4 e 10 podem ser vinculados a fornecedores');
            }
            
            // Verifica se o vínculo já existe
            $sql = "SELECT id FROM usuario_fornecedor WHERE fornecedor_id = ? AND usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $fornecedor_id, $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception('Este usuário já está vinculado ao fornecedor');
            }
            
            // Insere o vínculo
            $sql = "INSERT INTO usuario_fornecedor (usuario_id, fornecedor_id, ativo, data_vinculo) 
                    VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $usuario_id, $fornecedor_id, $ativo);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao vincular usuário');
            }
            
            $vinculo_id = $conn->insert_id;
            
            // Retorna os dados do vínculo criado
            $sql = "SELECT 
                        uf.id,
                        uf.usuario_id,
                        uf.fornecedor_id,
                        uf.ativo,
                        uf.data_vinculo,
                        u.login,
                        u.nome,
                        u.email,
                        u.perfil,
                        u.grupo_id,
                        g.nome as grupo_nome
                    FROM usuario_fornecedor uf
                    INNER JOIN usuarios u ON uf.usuario_id = u.id
                    LEFT JOIN grupos g ON u.grupo_id = g.id
                    WHERE uf.id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $vinculo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $vinculo = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Usuário vinculado com sucesso',
                'data' => $vinculo
            ]);
            break;
            
        case 'PUT':
            // Atualizar status do vínculo
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id'])) {
                throw new Exception('ID do vínculo não informado');
            }
            
            $vinculo_id = (int)$data['id'];
            $ativo = isset($data['ativo']) ? (int)$data['ativo'] : 1;
            
            $sql = "UPDATE usuario_fornecedor SET ativo = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $ativo, $vinculo_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar vínculo');
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Vínculo atualizado com sucesso'
            ]);
            break;
            
        case 'DELETE':
            // Remover vínculo
            if (!isset($_GET['id'])) {
                throw new Exception('ID do vínculo não informado');
            }
            
            $vinculo_id = (int)$_GET['id'];
            
            $sql = "DELETE FROM usuario_fornecedor WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $vinculo_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao remover vínculo');
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Vínculo removido com sucesso'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
