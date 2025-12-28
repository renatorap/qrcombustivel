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
            if (!$accessControl->verificarPermissao('aditamento', 'acessar')) {
                throw new Exception('Sem permissão para acessar aditamentos');
            }
            
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            $search = $_GET['search'] ?? '';
            $statusFilter = $_GET['status'] ?? '';
            $clienteId = $_SESSION['cliente_id'];
            
            $whereConditions = ["a.id_cliente = ?"];
            $params = [$clienteId];
            $types = 'i';
            
            // Filtro para usuários do grupo 4 (fornecedores)
            $fornecedorId = $accessControl->getFornecedorId();
            if ($fornecedorId) {
                $whereConditions[] = "c.id_fornecedor = ?";
                $params[] = $fornecedorId;
                $types .= 'i';
            }
            
            if ($search) {
                $whereConditions[] = "(a.codigo LIKE ? OR a.descricao LIKE ? OR c.codigo LIKE ? OR l.codigo LIKE ?)";
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'ssss';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get records with joins and vigência status
            $sql = "SELECT a.*, 
                    c.codigo as contrato_codigo,
                    l.codigo as licitacao_codigo,
                    f.razao_social as fornecedor_nome,
                    (SELECT MIN(inicio_vigencia) FROM preco_combustivel WHERE id_aditamento_combustivel = a.id_aditamento_combustivel) as inicio_vigencia,
                    (SELECT MAX(fim_vigencia) FROM preco_combustivel WHERE id_aditamento_combustivel = a.id_aditamento_combustivel) as fim_vigencia,
                    (SELECT COUNT(*) FROM preco_combustivel WHERE id_aditamento_combustivel = a.id_aditamento_combustivel) as total_precos
                    FROM aditamento_combustivel a
                    INNER JOIN contrato c ON a.id_contrato = c.id_contrato
                    INNER JOIN licitacao l ON a.id_licitacao = l.id_licitacao
                    LEFT JOIN fornecedor f ON c.id_fornecedor = f.id_fornecedor
                    WHERE $whereClause 
                    ORDER BY a.data DESC, a.codigo DESC";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta');
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $aditamentos = [];
            $now = date('Y-m-d H:i:s');
            
            while ($row = $result->fetch_assoc()) {
                // Determinar status de vigência
                if (empty($row['inicio_vigencia'])) {
                    $row['status_vigencia'] = 'sem_precos';
                } elseif ($row['inicio_vigencia'] > $now) {
                    $row['status_vigencia'] = 'futuro';
                } elseif (empty($row['fim_vigencia']) || $row['fim_vigencia'] > $now) {
                    $row['status_vigencia'] = 'ativo';
                } else {
                    $row['status_vigencia'] = 'encerrado';
                }
                
                // Aplicar filtro de status
                if ($statusFilter && $row['status_vigencia'] !== $statusFilter) {
                    continue;
                }
                
                $aditamentos[] = $row;
            }
            $stmt->close();
            
            // Aplicar paginação após filtro
            $total = count($aditamentos);
            $aditamentos = array_slice($aditamentos, $offset, $perPage);
            
            echo json_encode([
                'success' => true,
                'data' => $aditamentos,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'perPage' => $perPage,
                    'totalPages' => ceil($total / $perPage)
                ]
            ]);
            break;
            
        case 'get':
            if (!$accessControl->verificarPermissao('aditamento', 'acessar')) {
                throw new Exception('Sem permissão para acessar aditamentos');
            }
            
            $id = $_GET['id'] ?? 0;
            $clienteId = $_SESSION['cliente_id'];
            
            $sql = "SELECT a.*, 
                    c.codigo as contrato_codigo,
                    l.codigo as licitacao_codigo
                    FROM aditamento_combustivel a
                    INNER JOIN contrato c ON a.id_contrato = c.id_contrato
                    INNER JOIN licitacao l ON a.id_licitacao = l.id_licitacao
                    WHERE a.id_aditamento_combustivel = ? AND a.id_cliente = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta');
            }
            
            $stmt->bind_param('ii', $id, $clienteId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Aditamento não encontrado');
            }
            
            $aditamento = $result->fetch_assoc();
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $aditamento
            ]);
            break;
            
        case 'get_contratos':
            if (!$accessControl->verificarPermissao('aditamento', 'acessar')) {
                throw new Exception('Sem permissão para acessar contratos');
            }
            
            $idLicitacao = $_GET['id_licitacao'] ?? 0;
            $clienteId = $_SESSION['cliente_id'];
            
            // Filtro para usuários do grupo 4 (fornecedores)
            $fornecedorId = $accessControl->getFornecedorId();
            $whereConditions = ["c.id_licitacao = ?", "c.id_cliente = ?"];
            $params = [$idLicitacao, $clienteId];
            $types = 'ii';
            
            if ($fornecedorId) {
                $whereConditions[] = "c.id_fornecedor = ?";
                $params[] = $fornecedorId;
                $types .= 'i';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            $sql = "SELECT c.id_contrato, c.codigo, c.descricao, f.razao_social as fornecedor_nome
                    FROM contrato c
                    INNER JOIN fornecedor f ON c.id_fornecedor = f.id_fornecedor
                    WHERE $whereClause
                    ORDER BY c.codigo";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $contratos = [];
            while ($row = $result->fetch_assoc()) {
                $contratos[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $contratos
            ]);
            break;
            
        case 'get_produtos':
            if (!$accessControl->verificarPermissao('aditamento', 'acessar')) {
                throw new Exception('Sem permissão para acessar produtos');
            }
            
            $idAditamento = $_GET['id_aditamento'] ?? 0;
            
            if (empty($idAditamento)) {
                throw new Exception('ID do aditamento é obrigatório');
            }
            
            // Buscar produtos vinculados ao contrato do aditamento
            $sql = "SELECT p.id_produto, p.descricao as nome, u.sigla as unidade 
                    FROM produto p
                    INNER JOIN un_medida u ON p.id_un_medida = u.id_un_medida
                    INNER JOIN contrato_produto cp ON p.id_produto = cp.id_produto
                    INNER JOIN aditamento_combustivel a ON cp.id_contrato = a.id_contrato
                    WHERE a.id_aditamento_combustivel = ? 
                    AND cp.ativo = 1
                    AND p.id_situacao = 1
                    ORDER BY p.descricao";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar consulta');
            }
            
            $stmt->bind_param('i', $idAditamento);
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
            
        case 'get_precos':
            if (!$accessControl->verificarPermissao('aditamento', 'acessar')) {
                throw new Exception('Sem permissão para acessar preços');
            }
            
            $idAditamento = $_GET['id_aditamento'] ?? 0;
            
            $sql = "SELECT pc.*, p.descricao as produto_nome, u.sigla as unidade
                    FROM preco_combustivel pc
                    INNER JOIN produto p ON pc.id_produto = p.id_produto
                    INNER JOIN un_medida u ON p.id_un_medida = u.id_un_medida
                    WHERE pc.id_aditamento_combustivel = ?
                    ORDER BY p.descricao";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $idAditamento);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $precos = [];
            while ($row = $result->fetch_assoc()) {
                // Determinar status de vigência
                $now = date('Y-m-d H:i:s');
                if ($row['inicio_vigencia'] > $now) {
                    $row['status'] = 'futuro';
                } elseif (empty($row['fim_vigencia']) || $row['fim_vigencia'] > $now) {
                    $row['status'] = 'ativo';
                } else {
                    $row['status'] = 'encerrado';
                }
                $precos[] = $row;
            }
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'data' => $precos
            ]);
            break;
            
        case 'create':
            if (!$accessControl->verificarPermissao('aditamento', 'criar')) {
                throw new Exception('Sem permissão para criar aditamentos');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['codigo']) || empty($data['data']) || empty($data['descricao']) || 
                empty($data['id_contrato']) || empty($data['id_licitacao'])) {
                throw new Exception('Preencha todos os campos obrigatórios');
            }
            
            $codigo = trim($data['codigo']);
            $dataAditamento = trim($data['data']);
            $descricao = trim($data['descricao']);
            $idContrato = (int)$data['id_contrato'];
            $idLicitacao = (int)$data['id_licitacao'];
            $empresaId = $_SESSION['empresa_id'] ?? 1;
            $clienteId = $_SESSION['cliente_id'];
            
            // Verificar se contrato existe e pertence ao cliente
            $checkContrato = "SELECT id_contrato FROM contrato WHERE id_contrato = ? AND id_cliente = ?";
            $stmtCheck = $conn->prepare($checkContrato);
            $stmtCheck->bind_param('ii', $idContrato, $clienteId);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) {
                throw new Exception('Contrato não encontrado');
            }
            $stmtCheck->close();
            
            // Verificar se licitação existe e pertence ao cliente
            $checkLicitacao = "SELECT id_licitacao FROM licitacao WHERE id_licitacao = ? AND id_cliente = ?";
            $stmtCheck = $conn->prepare($checkLicitacao);
            $stmtCheck->bind_param('ii', $idLicitacao, $clienteId);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows === 0) {
                throw new Exception('Licitação não encontrada');
            }
            $stmtCheck->close();
            
            // Verificar se código já existe para este cliente
            $checkSql = "SELECT id_aditamento_combustivel FROM aditamento_combustivel WHERE codigo = ? AND id_cliente = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('si', $codigo, $clienteId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Já existe um aditamento com este código');
            }
            $checkStmt->close();
            
            $sql = "INSERT INTO aditamento_combustivel (codigo, data, descricao, id_contrato, id_licitacao, id_cliente) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar inserção');
            }
            
            $stmt->bind_param('sssiii', $codigo, $dataAditamento, $descricao, $idContrato, $idLicitacao, $clienteId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao criar aditamento');
            }
            
            $novoId = $conn->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Aditamento criado com sucesso',
                'id' => $novoId
            ]);
            break;
            
        case 'update':
            if (!$accessControl->verificarPermissao('aditamento', 'editar')) {
                throw new Exception('Sem permissão para editar aditamentos');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id']) || empty($data['codigo']) || empty($data['data']) || empty($data['descricao'])) {
                throw new Exception('Preencha todos os campos obrigatórios');
            }
            
            $id = (int)$data['id'];
            $codigo = trim($data['codigo']);
            $dataAditamento = trim($data['data']);
            $descricao = trim($data['descricao']);
            $clienteId = $_SESSION['cliente_id'];
            
            // Verificar se código já existe em outro aditamento
            $checkSql = "SELECT id_aditamento_combustivel FROM aditamento_combustivel WHERE codigo = ? AND id_cliente = ? AND id_aditamento_combustivel != ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param('sii', $codigo, $clienteId, $id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Já existe outro aditamento com este código');
            }
            $checkStmt->close();
            
            $sql = "UPDATE aditamento_combustivel SET codigo = ?, data = ?, descricao = ? 
                    WHERE id_aditamento_combustivel = ? AND id_cliente = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar atualização');
            }
            
            $stmt->bind_param('sssii', $codigo, $dataAditamento, $descricao, $id, $clienteId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar aditamento');
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Aditamento atualizado com sucesso'
            ]);
            break;
            
        case 'delete':
            if (!$accessControl->verificarPermissao('aditamento', 'excluir')) {
                throw new Exception('Sem permissão para excluir aditamentos');
            }
            
            $id = $_GET['id'] ?? 0;
            $clienteId = $_SESSION['cliente_id'];
            
            // Iniciar transação
            $conn->begin_transaction();
            
            try {
                // Excluir preços vinculados
                $deletePrecosSQL = "DELETE FROM preco_combustivel WHERE id_aditamento_combustivel = ?";
                $stmtPrecos = $conn->prepare($deletePrecosSQL);
                $stmtPrecos->bind_param('i', $id);
                $stmtPrecos->execute();
                $stmtPrecos->close();
                
                // Excluir aditamento
                $sql = "DELETE FROM aditamento_combustivel WHERE id_aditamento_combustivel = ? AND id_cliente = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    throw new Exception('Erro ao preparar exclusão');
                }
                
                $stmt->bind_param('ii', $id, $clienteId);
                
                if (!$stmt->execute()) {
                    throw new Exception('Erro ao excluir aditamento');
                }
                
                if ($stmt->affected_rows === 0) {
                    throw new Exception('Aditamento não encontrado');
                }
                
                $stmt->close();
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Aditamento excluído com sucesso'
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;
            
        case 'add_preco':
            if (!$accessControl->verificarPermissao('aditamento', 'editar')) {
                throw new Exception('Sem permissão para adicionar preços');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id_aditamento_combustivel']) || empty($data['id_produto']) || 
                empty($data['valor']) || empty($data['inicio_vigencia'])) {
                throw new Exception('Preencha todos os campos obrigatórios');
            }
            
            $idAditamento = (int)$data['id_aditamento_combustivel'];
            $idProduto = (int)$data['id_produto'];
            $valor = (float)$data['valor'];
            $inicioVigencia = trim($data['inicio_vigencia']);
            $empresaId = $_SESSION['empresa_id'] ?? 1;
            $clienteId = $_SESSION['cliente_id'];
            
            // Buscar preço anterior ativo para este produto (em qualquer aditamento do mesmo contrato)
            $searchSql = "SELECT pc.id_preco_combustivel, pc.id_aditamento_combustivel, pc.inicio_vigencia
                         FROM preco_combustivel pc
                         INNER JOIN aditamento_combustivel a1 ON pc.id_aditamento_combustivel = a1.id_aditamento_combustivel
                         INNER JOIN aditamento_combustivel a2 ON a1.id_contrato = a2.id_contrato
                         WHERE a2.id_aditamento_combustivel = ?
                         AND pc.id_produto = ?
                         AND pc.fim_vigencia IS NULL
                         ORDER BY pc.inicio_vigencia DESC
                         LIMIT 1";
            $searchStmt = $conn->prepare($searchSql);
            $searchStmt->bind_param('ii', $idAditamento, $idProduto);
            $searchStmt->execute();
            $resultSearch = $searchStmt->get_result();
            $precoAnterior = $resultSearch->fetch_assoc();
            $searchStmt->close();
            
            // Se existe preço anterior ativo, encerrar com a data 1 segundo antes do novo
            if ($precoAnterior) {
                $fimVigenciaAnterior = date('Y-m-d H:i:s', strtotime($inicioVigencia . ' -1 second'));
                
                $updateSql = "UPDATE preco_combustivel 
                             SET fim_vigencia = ? 
                             WHERE id_preco_combustivel = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param('si', $fimVigenciaAnterior, $precoAnterior['id_preco_combustivel']);
                
                if (!$updateStmt->execute()) {
                    throw new Exception('Erro ao encerrar preço anterior');
                }
                $updateStmt->close();
            }
            
            // Inserir novo preço
            $sql = "INSERT INTO preco_combustivel (id_aditamento_combustivel, id_produto, valor, inicio_vigencia, id_cliente) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar inserção de preço');
            }
            
            $stmt->bind_param('iidsi', $idAditamento, $idProduto, $valor, $inicioVigencia, $clienteId);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao adicionar preço');
            }
            
            $novoId = $conn->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Preço adicionado com sucesso',
                'id' => $novoId
            ]);
            break;
            
        case 'delete_preco':
            if (!$accessControl->verificarPermissao('aditamento', 'editar')) {
                throw new Exception('Sem permissão para excluir preços');
            }
            
            $id = $_GET['id'] ?? 0;
            
            $sql = "DELETE FROM preco_combustivel WHERE id_preco_combustivel = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Erro ao preparar exclusão');
            }
            
            $stmt->bind_param('i', $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Erro ao excluir preço');
            }
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Preço não encontrado');
            }
            
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Preço excluído com sucesso'
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
