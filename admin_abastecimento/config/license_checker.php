<?php
/**
 * Verificador de Licença
 * Verifica se o cliente possui licença ativa antes de permitir acesso ao sistema
 */

class LicenseChecker {
    private $db;
    private $clienteId;
    private $grupoId;
    private $paginasIsentas = [
        'index.php',
        'login.php', 
        'logout.php',
        'reset_password.php',
        'password_reset_confirm.php'
    ];
    
    private $paginasOperadorLicenca = [
        'ativar_licenca.php'
    ];

    public function __construct($clienteId, $grupoId = null) {
        $this->db = new Database();
        $this->clienteId = $clienteId;
        $this->grupoId = $grupoId;
    }

    /**
     * Verifica se a página atual está isenta de verificação de licença
     */
    private function isPaginaIsenta() {
        $paginaAtual = basename($_SERVER['PHP_SELF']);
        return in_array($paginaAtual, $this->paginasIsentas);
    }

    /**
     * Verifica se a página é acessível por operadores mesmo com licença expirada
     */
    private function isPaginaOperadorLicenca() {
        $paginaAtual = basename($_SERVER['PHP_SELF']);
        return in_array($paginaAtual, $this->paginasOperadorLicenca);
    }

    /**
     * Verifica se o usuário é administrador (isento de verificação)
     */
    private function isAdministrador() {
        return $this->grupoId == 1;
    }

    /**
     * Verifica se o usuário pode acessar página de ativação de licença
     * Grupos 2 e 3 (Operador Administrativo e Operador Prefeitura)
     */
    private function podeAtivarLicenca() {
        return in_array($this->grupoId, [2, 3]);
    }

    /**
     * Verifica se o usuário deve ser totalmente bloqueado com licença expirada
     * Grupos 4 e 10 (Operador Posto e Abastecimento)
     */
    private function bloqueioTotal() {
        return in_array($this->grupoId, [4, 10]);
    }

    /**
     * Verifica se existe licença ativa para o cliente
     */
    public function verificarLicenca() {
        // Páginas isentas não precisam de licença
        if ($this->isPaginaIsenta()) {
            return ['ativa' => true, 'isento' => true];
        }

        // Administradores não precisam de licença
        if ($this->isAdministrador()) {
            return ['ativa' => true, 'isento' => true];
        }

        if (!$this->clienteId) {
            return [
                'ativa' => false,
                'motivo' => 'Cliente não identificado',
                'acao' => 'redirect_login'
            ];
        }

        $conn = $this->db->getConnection();
        
        // Atualizar status de licenças expiradas
        $this->atualizarLicencasExpiradas();
        
        // Buscar licença ativa
        $sql = "SELECT l.*, 
                       DATEDIFF(l.data_expiracao, CURDATE()) as dias_restantes
                FROM licenca l
                WHERE l.id_cliente = {$this->clienteId}
                AND l.status = 'ativa'
                AND l.data_expiracao >= CURDATE()
                ORDER BY l.data_expiracao DESC
                LIMIT 1";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $licenca = $result->fetch_assoc();
            
            return [
                'ativa' => true,
                'licenca' => $licenca,
                'dias_restantes' => $licenca['dias_restantes'],
                'expira_em' => date('d/m/Y', strtotime($licenca['data_expiracao'])),
                'aviso_expiracao' => $licenca['dias_restantes'] <= 7
            ];
        }
        
        // SEM LICENÇA ATIVA - verificar se pode acessar página de ativação
        // Operadores dos grupos 2 e 3 podem acessar APENAS a página de ativação
        if ($this->isPaginaOperadorLicenca() && $this->podeAtivarLicenca()) {
            // Permite acesso apenas à página de ativação
            return ['ativa' => true, 'isento' => true, 'acesso_limitado' => true];
        }
        
        // Verificar se existe licença pendente
        $sqlPendente = "SELECT COUNT(*) as total 
                        FROM licenca 
                        WHERE id_cliente = {$this->clienteId}
                        AND status = 'pendente'
                        AND data_expiracao >= CURDATE()";
        
        $resultPendente = $conn->query($sqlPendente);
        $rowPendente = $resultPendente->fetch_assoc();
        
        if ($rowPendente['total'] > 0) {
            return [
                'ativa' => false,
                'motivo' => 'Licença pendente de ativação',
                'acao' => 'ativar_licenca',
                'tem_pendente' => true
            ];
        }
        
        return [
            'ativa' => false,
            'motivo' => 'Nenhuma licença ativa encontrada',
            'acao' => 'contatar_admin',
            'tem_pendente' => false
        ];
    }

    /**
     * Atualiza status de licenças expiradas
     */
    private function atualizarLicencasExpiradas() {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE licenca 
                SET status = 'expirada'
                WHERE status = 'ativa'
                AND data_expiracao < CURDATE()";
        
        $conn->query($sql);
    }

    /**
     * Bloqueia acesso e redireciona para página apropriada
     */
    public function bloquearAcesso($statusLicenca) {
        $_SESSION['licenca_bloqueio'] = $statusLicenca;
        
        // Operadores administrativos e de prefeitura são redirecionados para ativação
        if ($this->podeAtivarLicenca()) {
            // Se já está na página de ativação, não redireciona
            if (basename($_SERVER['PHP_SELF']) == 'ativar_licenca.php') {
                return;
            }
            header('Location: ativar_licenca.php?motivo=expirada');
            exit;
        }
        
        // Operadores de posto e abastecimento são totalmente bloqueados
        if ($this->bloqueioTotal()) {
            header('Location: ../index.php?erro=licenca_expirada');
            exit;
        }
        
        // Outros casos (não deveria chegar aqui normalmente)
        if ($statusLicenca['acao'] == 'ativar_licenca') {
            header('Location: ativar_licenca.php?motivo=pendente');
        } else {
            header('Location: ../index.php?erro=licenca_expirada');
        }
        exit;
    }

    /**
     * Retorna HTML de aviso de expiração próxima
     */
    public static function getAvisoExpiracao($diasRestantes, $dataExpiracao) {
        if ($diasRestantes > 7) {
            return '';
        }

        $classe = $diasRestantes <= 3 ? 'danger' : 'warning';
        $icone = $diasRestantes <= 3 ? 'exclamation-circle' : 'exclamation-triangle';
        
        $mensagem = $diasRestantes == 0 
            ? "Sua licença expira HOJE!" 
            : "Sua licença expira em {$diasRestantes} dia(s) ({$dataExpiracao})";

        return "
            <div class='alert alert-{$classe} alert-dismissible fade show' role='alert'>
                <i class='fas fa-{$icone}'></i> <strong>Atenção!</strong> {$mensagem}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>
        ";
    }

    /**
     * Método estático para verificação rápida em qualquer página
     */
    public static function verificarEBloquear($clienteId, $grupoId = null) {
        $checker = new self($clienteId, $grupoId);
        $status = $checker->verificarLicenca();
        
        if (!$status['ativa'] && !isset($status['isento'])) {
            $checker->bloquearAcesso($status);
        }
        
        return $status;
    }
}
