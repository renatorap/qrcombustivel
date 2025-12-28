<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';

$response = [
    'success' => false,
    'message' => '',
    'token' => null,
    'code' => null,
    'field' => null
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = Security::sanitize($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($login)) {
        $response['message'] = 'Login é obrigatório';
        $response['code'] = 'missing_fields';
        $response['field'] = 'login';
        echo json_encode($response);
        exit;
    }

    if (empty($senha)) {
        $response['message'] = 'Senha é obrigatória';
        $response['code'] = 'missing_fields';
        $response['field'] = 'senha';
        echo json_encode($response);
        exit;
    }

    $db = new Database();
    $db->connect();

    // Primeiro buscar pelo usuário (independente do campo ativo) para diferenciar motivos
    $sqlUser = "SELECT u.id, u.login, u.nome, u.senha, u.perfil, u.grupo_id, u.cliente_id, u.ativo, g.nome as grupo_nome 
                FROM usuarios u 
                LEFT JOIN grupos g ON u.grupo_id = g.id 
                WHERE u.login = ?";
    $stmtUser = $db->prepare($sqlUser);
    $stmtUser->bind_param("s", $login);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser->num_rows === 0) {
        $response['message'] = 'Usuário não encontrado';
        $response['code'] = 'user_not_found';
        $response['field'] = 'login';
    } else {
        $user = $resultUser->fetch_assoc();

        if ((int)$user['ativo'] !== 1) {
            $response['message'] = 'Usuário inativo. Contate o administrador.';
            $response['code'] = 'user_inactive';
            $response['field'] = 'login';
        } else {
            if (Security::verifyPassword($senha, $user['senha'])) {
                $token = Security::generateToken($user['id'], $user['grupo_id'] ?? 0);

                $_SESSION['token'] = $token;
                $_SESSION['userId'] = $user['id'];
                $_SESSION['grupoId'] = $user['grupo_id'];
                $_SESSION['grupoNome'] = $user['grupo_nome'];
                $_SESSION['userRole'] = $user['perfil'];
                $_SESSION['login'] = $user['login'];
                $_SESSION['nome'] = $user['nome'];
                
                // Buscar cliente_id e dados do cliente
                $clienteId = null;
                
                // Primeiro verifica se está direto na tabela usuarios
                if (!empty($user['cliente_id'])) {
                    $clienteId = $user['cliente_id'];
                } else {
                    // Se não, busca na tabela usuario_cliente (relacionamento muitos-para-muitos)
                    $sqlClienteId = "SELECT cliente_id FROM usuario_cliente 
                                     WHERE usuario_id = ? AND ativo = 1 
                                     ORDER BY data_vinculo DESC LIMIT 1";
                    $stmtClienteId = $db->prepare($sqlClienteId);
                    $stmtClienteId->bind_param("i", $user['id']);
                    $stmtClienteId->execute();
                    $resultClienteId = $stmtClienteId->get_result();
                    
                    if ($resultClienteId->num_rows > 0) {
                        $clienteIdData = $resultClienteId->fetch_assoc();
                        $clienteId = $clienteIdData['cliente_id'];
                    }
                    
                    $stmtClienteId->close();
                }
                
                // Armazenar cliente_id na sessão
                if ($clienteId) {
                    $_SESSION['cliente_id'] = $clienteId;
                    
                    // Buscar dados completos do cliente
                    $sqlCliente = "SELECT id, nome_fantasia, razao_social, logo_path 
                                   FROM clientes 
                                   WHERE id = ? AND ativo = 1";
                    $stmtCliente = $db->prepare($sqlCliente);
                    $stmtCliente->bind_param("i", $clienteId);
                    $stmtCliente->execute();
                    $resultCliente = $stmtCliente->get_result();
                    
                    if ($resultCliente->num_rows > 0) {
                        $clienteData = $resultCliente->fetch_assoc();
                        $_SESSION['cliente_nome'] = $clienteData['nome_fantasia'] ?: $clienteData['razao_social'];
                        $_SESSION['cliente_logo'] = $clienteData['logo_path'];
                    }
                    
                    $stmtCliente->close();
                }

                // Determinar redirecionamento baseado no grupo
                $grupoId = (int)$user['grupo_id'];
                $redirectPath = '';
                
                if (in_array($grupoId, [1, 2, 3, 4])) {
                    // Grupos 1, 2, 3, 4 vão para admin_abastecimento
                    $redirectPath = '/admin_abastecimento/pages/dashboard.php';
                    $_SESSION['projeto'] = 'admin';
                } elseif ($grupoId === 10) {
                    // Grupo 10 vai para postoapp - captura de abastecimento
                    $redirectPath = '/postoapp/captura_abastecimento.php';
                    $_SESSION['projeto'] = 'posto';
                } else {
                    // Grupo não autorizado
                    $response['message'] = 'Seu grupo não tem permissão de acesso ao sistema';
                    $response['code'] = 'unauthorized_group';
                    $response['field'] = 'login';
                    echo json_encode($response);
                    exit;
                }

                $response['success'] = true;
                $response['message'] = 'Login realizado com sucesso';
                $response['token'] = $token;
                $response['code'] = 'success';
                $response['redirect'] = $redirectPath;
            } else {
                $response['message'] = 'Senha incorreta';
                $response['code'] = 'wrong_password';
                $response['field'] = 'senha';
            }
        }
    }

    $stmtUser->close();
    $db->close();
}

echo json_encode($response);
?>