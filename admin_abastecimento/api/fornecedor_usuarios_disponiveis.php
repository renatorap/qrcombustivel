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

$database = new Database();
$conn = $database->connect();

try {
    // Parâmetro opcional para excluir usuários já vinculados a um fornecedor
    $fornecedor_id = isset($_GET['excluir_fornecedor_id']) ? (int)$_GET['excluir_fornecedor_id'] : null;
    
    if ($fornecedor_id) {
        // Listar apenas usuários dos grupos 4 e 10 que NÃO estão vinculados ao fornecedor
        $sql = "SELECT 
                    u.id,
                    u.login,
                    u.nome,
                    u.email,
                    u.perfil,
                    u.ativo,
                    u.grupo_id,
                    g.nome as grupo_nome
                FROM usuarios u
                LEFT JOIN grupos g ON u.grupo_id = g.id
                WHERE u.grupo_id IN (4, 10)
                AND u.id NOT IN (
                    SELECT usuario_id 
                    FROM usuario_fornecedor 
                    WHERE fornecedor_id = ?
                )
                AND u.ativo = 1
                ORDER BY u.nome";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $fornecedor_id);
    } else {
        // Listar todos os usuários ativos dos grupos 4 e 10
        $sql = "SELECT 
                    u.id,
                    u.login,
                    u.nome,
                    u.email,
                    u.perfil,
                    u.ativo,
                    u.grupo_id,
                    g.nome as grupo_nome
                FROM usuarios u
                LEFT JOIN grupos g ON u.grupo_id = g.id
                WHERE u.grupo_id IN (4, 10)
                AND u.ativo = 1
                ORDER BY u.nome";
        
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $usuarios]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
