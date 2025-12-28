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
    // Parâmetro opcional para excluir usuários já vinculados a um cliente
    $cliente_id = isset($_GET['excluir_cliente_id']) ? (int)$_GET['excluir_cliente_id'] : null;
    
    if ($cliente_id) {
        // Listar apenas usuários que NÃO estão vinculados ao cliente
        $sql = "SELECT 
                    u.id,
                    u.login,
                    u.nome,
                    u.email,
                    u.perfil,
                    u.ativo
                FROM usuarios u
                WHERE u.id NOT IN (
                    SELECT usuario_id 
                    FROM usuario_cliente 
                    WHERE cliente_id = ?
                )
                AND u.ativo = 1
                ORDER BY u.nome";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $cliente_id);
    } else {
        // Listar todos os usuários ativos
        $sql = "SELECT 
                    id,
                    login,
                    nome,
                    email,
                    perfil,
                    ativo
                FROM usuarios
                WHERE ativo = 1
                ORDER BY nome";
        
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
