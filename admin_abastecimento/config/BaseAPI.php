<?php
/**
 * Classe Base para APIs
 * Fornece métodos reutilizáveis para paginação, validação e respostas
 */
class BaseAPI {
    protected $db;
    protected $conn;
    protected $clienteId;
    protected $userId;
    protected $empresaId;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->clienteId = $_SESSION['cliente_id'] ?? null;
        $this->userId = $_SESSION['userId'] ?? null;
        $this->empresaId = $_SESSION['empresa_id'] ?? null;
    }

    /**
     * Retorna resposta JSON
     */
    protected function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Retorna resposta de sucesso
     */
    protected function success($message = 'Operação realizada com sucesso', $data = []) {
        $response = ['success' => true, 'message' => $message];
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        $this->jsonResponse($response);
    }

    /**
     * Retorna resposta de erro
     */
    protected function error($message = 'Erro ao processar requisição', $code = 400) {
        http_response_code($code);
        $this->jsonResponse(['success' => false, 'message' => $message]);
    }

    /**
     * Valida se o usuário está autenticado
     */
    protected function requireAuth() {
        $token = Security::validateToken($_SESSION['token'] ?? '');
        if (!$token) {
            $this->error('Sessão inválida ou expirada', 401);
        }
        return true;
    }

    /**
     * Obtém parâmetros de paginação
     */
    protected function getPaginationParams() {
        $page = intval($_GET['page'] ?? 1);
        $limit = PAGINATION_LIMIT;
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Calcula total de páginas
     */
    protected function calculateTotalPages($total, $limit = null) {
        if ($limit === null) {
            $limit = PAGINATION_LIMIT;
        }
        return ceil($total / $limit);
    }

    /**
     * Monta resposta paginada
     */
    protected function paginatedResponse($data, $total, $currentPage, $dataKey = 'data') {
        $totalPages = $this->calculateTotalPages($total);
        
        return [
            'success' => true,
            $dataKey => $data,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage
        ];
    }

    /**
     * Sanitiza string de busca
     */
    protected function sanitizeSearch($search) {
        return Security::sanitize($search ?? '');
    }

    /**
     * Valida ID
     */
    protected function validateId($id, $fieldName = 'ID') {
        $id = intval($id);
        if ($id <= 0) {
            $this->error("$fieldName inválido");
        }
        return $id;
    }

    /**
     * Valida campos obrigatórios
     */
    protected function validateRequired($data, $fields) {
        $missing = [];
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->error('Campos obrigatórios não preenchidos: ' . implode(', ', $missing));
        }
        return true;
    }

    /**
     * Executa query e retorna resultado
     */
    protected function executeQuery($sql, $params = [], $types = '') {
        if (empty($params)) {
            return $this->conn->query($sql);
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt->get_result();
    }

    /**
     * Conta total de registros
     */
    protected function countRecords($table, $where = '1=1', $params = [], $types = '') {
        $sql = "SELECT COUNT(*) as total FROM $table WHERE $where";
        $result = $this->executeQuery($sql, $params, $types);
        
        if ($result && $row = $result->fetch_assoc()) {
            return intval($row['total']);
        }
        
        return 0;
    }

    /**
     * Verifica se cliente está definido
     */
    protected function requireCliente() {
        if (!$this->clienteId) {
            $this->error('Selecione um cliente no menu superior');
        }
        return true;
    }

    /**
     * Monta cláusula WHERE para cliente
     */
    protected function getClienteWhere($tableAlias = '') {
        $prefix = $tableAlias ? "$tableAlias." : '';
        return $this->clienteId ? "{$prefix}id_cliente = {$this->clienteId}" : "1=1";
    }

    /**
     * Adiciona filtro de busca ao WHERE
     */
    protected function addSearchFilter($where, $search, $fields, $tableAlias = '') {
        if (empty($search)) {
            return $where;
        }
        
        $search = strtolower($search);
        $conditions = [];
        $prefix = $tableAlias ? "$tableAlias." : '';
        
        foreach ($fields as $field) {
            $conditions[] = "LOWER({$prefix}{$field}) LIKE '%{$search}%'";
        }
        
        $where .= " AND (" . implode(' OR ', $conditions) . ")";
        return $where;
    }
}
