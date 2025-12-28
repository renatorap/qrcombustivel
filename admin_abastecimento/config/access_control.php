<?php
/**
 * Classe de Controle de Acesso e Permissões
 * Gerencia verificação de permissões baseada em grupos e usuários
 */

class AccessControl {
    private $db;
    private $userId;
    private $grupoId;
    private $permissoesCache = [];
    
    public function __construct($userId = null) {
        $this->db = new Database();
        $this->db->connect();
        
        if ($userId) {
            $this->userId = $userId;
            $this->loadUserInfo();
        } elseif (isset($_SESSION['userId'])) {
            $this->userId = $_SESSION['userId'];
            $this->loadUserInfo();
        }
    }
    
    /**
     * Carrega informações do usuário
     */
    private function loadUserInfo() {
        $sql = "SELECT grupo_id FROM usuarios WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $this->grupoId = $row['grupo_id'];
        }
        $stmt->close();
    }
    
    /**
     * Verifica se o usuário tem permissão para uma ação específica
     * 
     * @param string $aplicacaoCodigo Código da aplicação
     * @param string $acao Ação: acessar, criar, visualizar, editar, excluir, exportar, importar
     * @return bool
     */
    public function verificarPermissao($aplicacaoCodigo, $acao = 'acessar') {
        if (!$this->userId) {
            return false;
        }
        
        // Cache key
        $cacheKey = "{$this->userId}_{$aplicacaoCodigo}_{$acao}";
        
        if (isset($this->permissoesCache[$cacheKey])) {
            return $this->permissoesCache[$cacheKey];
        }
        
        // Buscar permissão efetiva (considera permissão especial do usuário ou do grupo)
        $sql = "SELECT ";
        
        switch ($acao) {
            case 'acessar':
                $sql .= "pode_acessar";
                break;
            case 'criar':
                $sql .= "pode_criar";
                break;
            case 'visualizar':
                $sql .= "pode_visualizar";
                break;
            case 'editar':
                $sql .= "pode_editar";
                break;
            case 'excluir':
                $sql .= "pode_excluir";
                break;
            case 'exportar':
                $sql .= "pode_exportar";
                break;
            case 'importar':
                $sql .= "pode_importar";
                break;
            default:
                return false;
        }
        
        $sql .= " FROM v_permissoes_efetivas WHERE usuario_id = ? AND codigo = ? LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("is", $this->userId, $aplicacaoCodigo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $permitido = false;
        if ($row = $result->fetch_assoc()) {
            $permitido = (bool) array_values($row)[0];
        }
        
        $stmt->close();
        
        // Cachear resultado
        $this->permissoesCache[$cacheKey] = $permitido;
        
        return $permitido;
    }
    
    /**
     * Retorna todas as permissões do usuário para uma aplicação
     * 
     * @param string $aplicacaoCodigo
     * @return array
     */
    public function getPermissoes($aplicacaoCodigo) {
        if (!$this->userId) {
            return [];
        }
        
        $sql = "SELECT 
                    pode_acessar,
                    pode_criar,
                    pode_visualizar,
                    pode_editar,
                    pode_excluir,
                    pode_exportar,
                    pode_importar
                FROM v_permissoes_efetivas 
                WHERE usuario_id = ? AND codigo = ? 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("is", $this->userId, $aplicacaoCodigo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $permissoes = [];
        if ($row = $result->fetch_assoc()) {
            $permissoes = [
                'acessar' => (bool) $row['pode_acessar'],
                'criar' => (bool) $row['pode_criar'],
                'visualizar' => (bool) $row['pode_visualizar'],
                'editar' => (bool) $row['pode_editar'],
                'excluir' => (bool) $row['pode_excluir'],
                'exportar' => (bool) $row['pode_exportar'],
                'importar' => (bool) $row['pode_importar']
            ];
        }
        
        $stmt->close();
        return $permissoes;
    }
    
    /**
     * Retorna todos os módulos ativos do sistema
     * 
     * @return array
     */
    public function getModulos() {
        $sql = "SELECT id, codigo, nome, icone, expandido, aplicacao_id, ordem
                FROM modulo 
                WHERE ativo = 1 
                ORDER BY ordem";
        
        $result = $this->db->query($sql);
        
        $modulos = [];
        while ($row = $result->fetch_assoc()) {
            $modulos[$row['codigo']] = [
                'id' => $row['id'],
                'codigo' => $row['codigo'],
                'nome' => $row['nome'],
                'icone' => $row['icone'] ?? 'fa-folder',
                'expandido' => (bool) $row['expandido'],
                'aplicacao_id' => $row['aplicacao_id'],
                'ordem' => $row['ordem']
            ];
        }
        
        return $modulos;
    }
    
    /**
     * Retorna menu hierárquico completo com 3 níveis baseado em permissões
     * 
     * @return array
     */
    public function getMenuHierarquico() {
        if (!$this->userId) {
            return [];
        }
        
        $menu = [];
        
        // 1. Buscar módulos
        $sqlModulos = "SELECT DISTINCT m.id, m.codigo, m.nome, m.icone, m.expandido, m.aplicacao_id, m.ordem
                       FROM modulo m
                       WHERE m.ativo = 1
                       ORDER BY m.ordem";
        
        $resultModulos = $this->db->query($sqlModulos);
        
        while ($modulo = $resultModulos->fetch_assoc()) {
            $moduloData = [
                'id' => $modulo['id'],
                'codigo' => $modulo['codigo'],
                'nome' => $modulo['nome'],
                'icone' => $modulo['icone'] ?? 'fa-folder',
                'expandido' => (bool) $modulo['expandido'],
                'aplicacao_id' => $modulo['aplicacao_id'],
                'url' => null,
                'submenus' => []
            ];
            
            // Se não é expandido, verificar permissão e pegar URL
            if (!$modulo['expandido'] && $modulo['aplicacao_id']) {
                if ($this->verificarPermissaoAplicacaoId($modulo['aplicacao_id'])) {
                    $moduloData['url'] = $this->getUrlAplicacao($modulo['aplicacao_id']);
                    $menu[] = $moduloData;
                }
            } else {
                // É expandido, buscar submenus
                $moduloData['submenus'] = $this->getSubmenus($modulo['id']);
                
                // Só adiciona módulo se tiver pelo menos 1 submenu com permissão
                if (count($moduloData['submenus']) > 0) {
                    $menu[] = $moduloData;
                }
            }
        }
        
        return $menu;
    }
    
    /**
     * Retorna submenus de um módulo com permissões
     * 
     * @param int $moduloId
     * @return array
     */
    private function getSubmenus($moduloId) {
        $submenus = [];
        
        $sql = "SELECT id, codigo, nome, icone, expandido, aplicacao_id, ordem
                FROM submenu
                WHERE modulo_id = ? AND ativo = 1
                ORDER BY ordem";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $moduloId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($submenu = $result->fetch_assoc()) {
            $submenuData = [
                'id' => $submenu['id'],
                'codigo' => $submenu['codigo'],
                'nome' => $submenu['nome'],
                'icone' => $submenu['icone'] ?? 'fa-file',
                'expandido' => (bool) $submenu['expandido'],
                'aplicacao_id' => $submenu['aplicacao_id'],
                'url' => null,
                'subsubmenus' => []
            ];
            
            // Se não é expandido, verificar permissão e pegar URL
            if (!$submenu['expandido'] && $submenu['aplicacao_id']) {
                if ($this->verificarPermissaoAplicacaoId($submenu['aplicacao_id'])) {
                    $submenuData['url'] = $this->getUrlAplicacao($submenu['aplicacao_id']);
                    $submenus[] = $submenuData;
                }
            } else {
                // É expandido, buscar sub-submenus
                $submenuData['subsubmenus'] = $this->getSubsubmenus($submenu['id']);
                
                // Só adiciona submenu se tiver pelo menos 1 sub-submenu com permissão
                if (count($submenuData['subsubmenus']) > 0) {
                    $submenus[] = $submenuData;
                }
            }
        }
        
        $stmt->close();
        return $submenus;
    }
    
    /**
     * Retorna sub-submenus de um submenu com permissões
     * 
     * @param int $submenuId
     * @return array
     */
    private function getSubsubmenus($submenuId) {
        $subsubmenus = [];
        
        $sql = "SELECT id, codigo, nome, icone, aplicacao_id, ordem
                FROM subsubmenu
                WHERE submenu_id = ? AND ativo = 1
                ORDER BY ordem";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $submenuId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($subsubmenu = $result->fetch_assoc()) {
            // Sub-submenus sempre têm aplicacao_id
            if ($this->verificarPermissaoAplicacaoId($subsubmenu['aplicacao_id'])) {
                $subsubmenus[] = [
                    'id' => $subsubmenu['id'],
                    'codigo' => $subsubmenu['codigo'],
                    'nome' => $subsubmenu['nome'],
                    'icone' => $subsubmenu['icone'] ?? 'fa-circle',
                    'aplicacao_id' => $subsubmenu['aplicacao_id'],
                    'url' => $this->getUrlAplicacao($subsubmenu['aplicacao_id'])
                ];
            }
        }
        
        $stmt->close();
        return $subsubmenus;
    }
    
    /**
     * Verifica permissão de acesso por ID da aplicação
     * 
     * @param int $aplicacaoId
     * @return bool
     */
    private function verificarPermissaoAplicacaoId($aplicacaoId) {
        $sql = "SELECT pode_acessar
                FROM v_permissoes_efetivas
                WHERE usuario_id = ? AND aplicacao_id = ?
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("ii", $this->userId, $aplicacaoId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $permitido = false;
        if ($row = $result->fetch_assoc()) {
            $permitido = (bool) $row['pode_acessar'];
        }
        
        $stmt->close();
        return $permitido;
    }
    
    /**
     * Retorna URL da aplicação por ID
     * 
     * @param int $aplicacaoId
     * @return string|null
     */
    private function getUrlAplicacao($aplicacaoId) {
        $sql = "SELECT url FROM aplicacoes WHERE id = ? LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $aplicacaoId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $url = null;
        if ($row = $result->fetch_assoc()) {
            $url = basename($row['url']); // Retorna apenas o nome do arquivo
        }
        
        $stmt->close();
        return $url;
    }
    
    /**
     * Retorna menu personalizado baseado nas permissões do usuário
     * 
     * @return array
     */
    public function getMenuPermitido() {
        if (!$this->userId) {
            return [];
        }
        
        $sql = "SELECT DISTINCT
                    a.codigo,
                    a.nome,
                    a.url,
                    a.icone,
                    a.modulo,
                    a.ordem
                FROM v_permissoes_efetivas vpe
                INNER JOIN aplicacoes a ON vpe.aplicacao_id = a.id
                WHERE vpe.usuario_id = ? 
                  AND vpe.pode_acessar = 1 
                  AND a.ativo = 1
                ORDER BY a.ordem, a.nome";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $menu = [];
        while ($row = $result->fetch_assoc()) {
            $modulo = $row['modulo'];
            if (!isset($menu[$modulo])) {
                $menu[$modulo] = [];
            }
            $menu[$modulo][] = $row;
        }
        
        $stmt->close();
        return $menu;
    }
    
    /**
     * Registra uma ação do usuário no log de auditoria
     * 
     * @param string $aplicacaoCodigo
     * @param string $acao
     * @param string $descricao
     * @param string $resultado sucesso, falha, negado
     * @param array $dadosAlterados
     * @return bool
     */
    public function registrarAcao($aplicacaoCodigo, $acao, $descricao = '', $resultado = 'sucesso', $dadosAlterados = null) {
        if (!$this->userId) {
            return false;
        }
        
        // Buscar ID da aplicação
        $sql = "SELECT id FROM aplicacoes WHERE codigo = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $aplicacaoCodigo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $aplicacaoId = null;
        if ($row = $result->fetch_assoc()) {
            $aplicacaoId = $row['id'];
        }
        $stmt->close();
        
        // Capturar informações da requisição
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $dadosJson = $dadosAlterados ? json_encode($dadosAlterados) : null;
        
        // Inserir log
        $sql = "INSERT INTO acoes_usuario 
                (usuario_id, aplicacao_id, acao, descricao, ip_address, user_agent, dados_alterados, resultado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iissssss", $this->userId, $aplicacaoId, $acao, $descricao, $ipAddress, $userAgent, $dadosJson, $resultado);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Bloqueia acesso se não tiver permissão
     * Redireciona para página de acesso negado
     * 
     * @param string $aplicacaoCodigo
     * @param string $acao
     */
    public function requerPermissao($aplicacaoCodigo, $acao = 'acessar') {
        if (!$this->verificarPermissao($aplicacaoCodigo, $acao)) {
            // Registrar tentativa de acesso negado
            $this->registrarAcao(
                $aplicacaoCodigo, 
                'acesso_negado', 
                "Tentativa de {$acao} sem permissão",
                'negado'
            );
            
            // Redirecionar
            $_SESSION['erro_acesso'] = 'Você não possui permissão para acessar esta funcionalidade.';
            header('Location: ' . BASE_URL . 'pages/acesso_negado.php');
            exit;
        }
    }
    
    /**
     * Sincroniza aplicações do sistema (scanner de páginas)
     * 
     * @param string $pagesDir Diretório das páginas
     * @return array Resultado da sincronização
     */
    public function sincronizarAplicacoes($pagesDir = '../pages/') {
        $resultado = [
            'novas' => 0,
            'atualizadas' => 0,
            'erros' => []
        ];
        
        if (!is_dir($pagesDir)) {
            $resultado['erros'][] = "Diretório não encontrado: {$pagesDir}";
            return $resultado;
        }
        
        $arquivos = scandir($pagesDir);
        
        foreach ($arquivos as $arquivo) {
            if ($arquivo == '.' || $arquivo == '..') continue;
            
            // Apenas arquivos .php
            if (!preg_match('/\.php$/', $arquivo)) continue;
            
            $codigo = str_replace('.php', '', $arquivo);
            $nome = ucfirst(str_replace(['_', '-'], ' ', $codigo));
            $url = 'pages/' . $arquivo;
            
            // Verificar se já existe
            $sql = "SELECT id FROM aplicacoes WHERE codigo = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                // Inserir nova aplicação
                $sqlInsert = "INSERT INTO aplicacoes (codigo, nome, url, modulo, ativo) 
                              VALUES (?, ?, ?, 'sistema', 1)";
                $stmtInsert = $this->db->prepare($sqlInsert);
                $stmtInsert->bind_param("sss", $codigo, $nome, $url);
                
                if ($stmtInsert->execute()) {
                    $resultado['novas']++;
                } else {
                    $resultado['erros'][] = "Erro ao inserir: {$codigo}";
                }
                $stmtInsert->close();
            } else {
                $resultado['atualizadas']++;
            }
            
            $stmt->close();
        }
        
        return $resultado;
    }
    
    /**
     * Verifica se o usuário pertence ao grupo de fornecedores (grupo 4)
     * 
     * @return bool
     */
    public function isFornecedor() {
        return $this->grupoId == 4;
    }
    
    /**
     * Obtém o ID do fornecedor vinculado ao usuário (se grupo 4)
     * 
     * @return int|null ID do fornecedor ou null se não for fornecedor ou não tiver vínculo
     */
    public function getFornecedorId() {
        if (!$this->isFornecedor()) {
            return null;
        }
        
        $sql = "SELECT fornecedor_id FROM usuario_fornecedor 
                WHERE usuario_id = ? AND ativo = 1 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $fornecedorId = $row['fornecedor_id'];
            $stmt->close();
            return $fornecedorId;
        }
        
        $stmt->close();
        return null;
    }
    
    /**
     * Aplica filtro de fornecedor na query SQL se usuário for do grupo 4
     * 
     * @param string $tabelaAlias Alias da tabela fornecedor na query (ex: 'f', 'fornecedor')
     * @return string Condição SQL adicional (vazia se não for fornecedor)
     */
    public function aplicarFiltroFornecedor($tabelaAlias = 'f') {
        $fornecedorId = $this->getFornecedorId();
        
        if ($fornecedorId) {
            return " AND {$tabelaAlias}.id_fornecedor = {$fornecedorId}";
        }
        
        return '';
    }
    
    /**
     * Verifica se o usuário pertence ao grupo 3 (cliente)
     * 
     * @return bool
     */
    public function isCliente() {
        return $this->grupoId == 3;
    }
    
    /**
     * Obtém o ID do cliente da sessão (se grupo 3)
     * 
     * @return int|null ID do cliente ou null se não for cliente
     */
    public function getClienteId() {
        if (!$this->isCliente()) {
            return null;
        }
        
        return $_SESSION['cliente_id'] ?? null;
    }
    
    /**
     * Fecha conexão com banco de dados
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}
?>
