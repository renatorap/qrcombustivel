<?php
/**
 * API para gerenciamento de clientes do usuário
 * Permite listar clientes acessíveis e trocar contexto
 */

require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userId = $_SESSION['userId'] ?? null;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    switch ($action) {
        case 'list':
            listClientesUsuario($conn, $userId);
            break;
            
        case 'switch':
            switchCliente($conn, $userId);
            break;
            
        case 'current':
            getCurrentCliente($conn);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}

/**
 * Lista clientes acessíveis pelo usuário
 */
function listClientesUsuario($conn, $userId) {
    try {
        // Verificar se tabela usuario_cliente existe
        $sqlCheck = "SHOW TABLES LIKE 'usuario_cliente'";
        $resultCheck = $conn->query($sqlCheck);
        
        if (!$resultCheck || $resultCheck->num_rows === 0) {
            // Tabela não existe, retornar todos os clientes
            $sql = "SELECT id, razao_social, nome_fantasia, cnpj, logo_path as logo, ativo
                    FROM clientes 
                    WHERE ativo = 1 
                    ORDER BY nome_fantasia, razao_social";
            $result = $conn->query($sql);
            
            if (!$result) {
                throw new Exception("Erro ao consultar clientes: " . $conn->error);
            }
            
            $clientes = [];
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $clientes[] = [
                    'id' => (int)$row['id'],
                    'razao_social' => $row['razao_social'],
                    'nome_fantasia' => $row['nome_fantasia'],
                    'nome_exibicao' => $row['nome_fantasia'] ?: $row['razao_social'],
                    'cnpj' => $row['cnpj'],
                    'logo' => $row['logo'],
                    'ativo' => (int)$row['ativo']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'clientes' => $clientes,
                'is_admin' => true,
                'total' => count($clientes)
            ]);
            return;
        }
        
        // Verificar se usuário é Administrador
        $sqlAdmin = "SELECT COUNT(*) as count 
                     FROM usuarios u
                     INNER JOIN grupos g ON u.grupo_id = g.id 
                     WHERE u.id = ? AND g.nome = 'Administradores'";
        
        $stmt = $conn->prepare($sqlAdmin);
        if (!$stmt) {
            throw new Exception("Erro ao preparar query admin: " . $conn->error);
        }
        
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $isAdmin = $result->fetch_assoc()['count'] > 0;
        
        if ($isAdmin) {
            // Administrador vê todos os clientes ativos
            $sql = "SELECT id, razao_social, nome_fantasia, cnpj, logo_path as logo, ativo
                    FROM clientes 
                    WHERE ativo = 1 
                    ORDER BY nome_fantasia, razao_social";
            $stmt = $conn->prepare($sql);
        } else {
            // Usuário comum vê apenas seus clientes vinculados
            $sql = "SELECT c.id, c.razao_social, c.nome_fantasia, c.cnpj, c.logo_path as logo, c.ativo
                    FROM clientes c
                    INNER JOIN usuario_cliente uc ON c.id = uc.cliente_id
                    WHERE uc.usuario_id = ? AND uc.ativo = 1 AND c.ativo = 1
                    ORDER BY c.nome_fantasia, c.razao_social";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $userId);
        }
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar query de clientes: " . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $clientes[] = [
                'id' => (int)$row['id'],
                'razao_social' => $row['razao_social'],
                'nome_fantasia' => $row['nome_fantasia'],
                'nome_exibicao' => $row['nome_fantasia'] ?: $row['razao_social'],
                'cnpj' => $row['cnpj'],
                'logo' => $row['logo'],
                'ativo' => (int)$row['ativo']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'clientes' => $clientes,
            'is_admin' => $isAdmin,
            'total' => count($clientes)
        ]);
    } catch (Exception $e) {
        error_log("Erro em listClientesUsuario: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'clientes' => [],
            'message' => 'Erro ao listar clientes: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Troca o cliente ativo na sessão
 */
function switchCliente($conn, $userId) {
    $clienteId = (int)($_POST['cliente_id'] ?? 0);
    
    if (!$clienteId) {
        echo json_encode(['success' => false, 'message' => 'Cliente não informado']);
        return;
    }
    
    // Verificar se usuário tem acesso ao cliente
    $sqlAdmin = "SELECT COUNT(*) as count 
                 FROM usuarios u
                 INNER JOIN grupos g ON u.grupo_id = g.id 
                 WHERE u.id = ? AND g.nome = 'Administradores'";
    
    $stmt = $conn->prepare($sqlAdmin);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $isAdmin = $result->fetch_assoc()['count'] > 0;
    
    if ($isAdmin) {
        // Administrador pode acessar qualquer cliente ativo
        $sql = "SELECT id, razao_social, nome_fantasia, logo_path as logo FROM clientes WHERE id = ? AND ativo = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $clienteId);
    } else {
        // Usuário comum só pode acessar clientes vinculados
        $sql = "SELECT c.id, c.razao_social, c.nome_fantasia, c.logo_path as logo
                FROM clientes c
                INNER JOIN usuario_cliente uc ON c.id = uc.cliente_id
                WHERE c.id = ? AND uc.usuario_id = ? AND uc.ativo = 1 AND c.ativo = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $clienteId, $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Acesso negado ao cliente']);
        return;
    }
    
    $cliente = $result->fetch_assoc();
    
    // Armazenar na sessão
    $_SESSION['cliente_id'] = (int)$cliente['id'];
    $_SESSION['cliente_nome'] = $cliente['nome_fantasia'] ?: $cliente['razao_social'];
    $_SESSION['cliente_logo'] = $cliente['logo'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Cliente alterado com sucesso',
        'cliente' => [
            'id' => (int)$cliente['id'],
            'nome' => $cliente['nome_fantasia'] ?: $cliente['razao_social'],
            'logo' => $cliente['logo']
        ]
    ]);
}

/**
 * Retorna o cliente atual da sessão
 */
function getCurrentCliente($conn) {
    $clienteId = $_SESSION['cliente_id'] ?? null;
    
    if (!$clienteId) {
        echo json_encode([
            'success' => true,
            'cliente' => null,
            'message' => 'Nenhum cliente selecionado'
        ]);
        return;
    }
    
    $sql = "SELECT id, razao_social, nome_fantasia, logo_path as logo FROM clientes WHERE id = ? AND ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $clienteId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        unset($_SESSION['cliente_id'], $_SESSION['cliente_nome'], $_SESSION['cliente_logo']);
        echo json_encode([
            'success' => true,
            'cliente' => null,
            'message' => 'Cliente não encontrado'
        ]);
        return;
    }
    
    $cliente = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'cliente' => [
            'id' => (int)$cliente['id'],
            'nome' => $cliente['nome_fantasia'] ?: $cliente['razao_social'],
            'logo' => $cliente['logo']
        ]
    ]);
}
?>
