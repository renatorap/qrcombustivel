<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

header('Content-Type: application/json');

try {
    if (empty($_SESSION['token'])) {
        throw new Exception('Não autenticado');
    }

    $token = Security::validateToken($_SESSION['token']);
    if (!$token) {
        throw new Exception('Token inválido');
    }

    $db = new Database();
    $conn = $db->getConnection();
    $accessControl = new AccessControl($_SESSION['userId']);
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            if (!$accessControl->verificarPermissao('contrato', 'acessar')) {
                throw new Exception('Sem permissão para acessar produtos do contrato');
            }
            
            $idContrato = $_GET['id_contrato'] ?? 0;
            
            $sql = "SELECT cp.*, p.descricao as produto_nome, u.sigla as unidade
                    FROM contrato_produto cp
                    INNER JOIN produto p ON cp.id_produto = p.id_produto
                    INNER JOIN un_medida u ON p.id_un_medida = u.id_un_medida
                    WHERE cp.id_contrato = ? AND cp.ativo = 1
                    ORDER BY p.descricao";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta');
            }
            
            $stmt->bind_param('i', $idContrato);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $produtos = [];
            while ($row = $result->fetch_assoc()) {
                $produtos[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $produtos
            ]);
            break;
            
        case 'get_disponiveis':
            if (!$accessControl->verificarPermissao('contrato', 'acessar')) {
                throw new Exception('Sem permissão para acessar produtos');
            }
            
            $idContrato = $_GET['id_contrato'] ?? 0;
            
            // Buscar produtos ativos que ainda não estão vinculados ao contrato
            $sql = "SELECT p.id_produto, p.descricao as nome, u.sigla as unidade
                    FROM produto p
                    INNER JOIN un_medida u ON p.id_un_medida = u.id_un_medida
                    WHERE p.id_situacao = 1
                    AND p.id_produto NOT IN (
                        SELECT id_produto FROM contrato_produto 
                        WHERE id_contrato = ? AND ativo = 1
                    )
                    ORDER BY p.descricao";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta');
            }
            
            $stmt->bind_param('i', $idContrato);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $produtos = [];
            while ($row = $result->fetch_assoc()) {
                $produtos[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $produtos
            ]);
            break;
            
        case 'vincular':
            if (!$accessControl->verificarPermissao('contrato', 'editar')) {
                throw new Exception('Sem permissão para vincular produtos');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id_contrato']) || empty($data['id_produto'])) {
                throw new Exception('Contrato e produto são obrigatórios');
            }
            
            $idContrato = (int)$data['id_contrato'];
            $idProduto = (int)$data['id_produto'];
            
            // Verificar se já existe vínculo (ativo ou inativo)
            $checkSql = "SELECT id_contrato_produto, ativo FROM contrato_produto 
                        WHERE id_contrato = ? AND id_produto = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('ii', $idContrato, $idProduto);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $checkStmt->close();
                
                if ($row['ativo'] == 1) {
                    throw new Exception('Este produto já está vinculado ao contrato');
                }
                
                // Reativar vínculo existente
                $updateSql = "UPDATE contrato_produto SET ativo = 1, data_vinculo = CURRENT_TIMESTAMP 
                             WHERE id_contrato = ? AND id_produto = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param('ii', $idContrato, $idProduto);
                
                if (!$updateStmt->execute()) {
                    throw new Exception('Erro ao reativar vínculo');
                }
                $updateStmt->close();
            } else {
                $checkStmt->close();
                
                // Inserir novo vínculo
                $sql = "INSERT INTO contrato_produto (id_contrato, id_produto, ativo) 
                        VALUES (?, ?, 1)";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    throw new Exception('Erro ao preparar inserção');
                }
                
                $stmt->bind_param('ii', $idContrato, $idProduto);
                
                if (!$stmt->execute()) {
                    throw new Exception('Erro ao vincular produto');
                }
                
                $stmt->close();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Produto vinculado com sucesso'
            ]);
            break;
            
        case 'desvincular':
            if (!$accessControl->verificarPermissao('contrato', 'editar')) {
                throw new Exception('Sem permissão para desvincular produtos');
            }
            
            $id = $_GET['id'] ?? 0;
            
            // Atualizar para ativo = 0 ao invés de deletar
            $sql = "UPDATE contrato_produto SET ativo = 0 WHERE id_contrato_produto = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar atualização');
            }
            
            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao desvincular produto');
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Vínculo não encontrado');
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Produto desvinculado com sucesso'
            ]);
            break;
            
        default:
            throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
