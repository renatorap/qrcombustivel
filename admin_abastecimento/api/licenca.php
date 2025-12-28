<?php
require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Validar token
$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Sessão inválida']);
    exit;
}

// Controle de acesso
$accessControl = new AccessControl($_SESSION['userId']);
$db = new Database();
$conn = $db->getConnection();

$response = ['success' => false];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Informações do usuário
$userId = $_SESSION['userId'];
$clienteId = $_SESSION['cliente_id'] ?? null;
$empresaId = $_SESSION['empresa_id'] ?? null;
$grupoId = $_SESSION['grupoId'] ?? null;

// Definir permissões baseadas no grupo
// 1 = Administrador, 2 = Operador Administrativo, 3 = Operador Prefeitura
$podeGerar = in_array($grupoId, [1]); // Apenas administrador
$podeAtivar = in_array($grupoId, [1, 2, 3]); // Admin + Operadores
$podeVisualizar = in_array($grupoId, [1]); // Apenas administrador

switch ($action) {
    case 'list':
        // Listar licenças (apenas administradores)
        if (!$podeVisualizar) {
            $response['message'] = 'Sem permissão para visualizar licenças';
            break;
        }

        $filtroCliente = isset($_GET['cliente_id']) ? intval($_GET['cliente_id']) : (isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 0);
        $filtroStatus = $_GET['status'] ?? '';
        
        $sql = "SELECT l.*, 
                       c.nome_fantasia as cliente_nome,
                       u1.nome as gerado_por_nome,
                       u2.nome as ativado_por_nome
                FROM licenca l
                LEFT JOIN clientes c ON l.id_cliente = c.id
                LEFT JOIN usuarios u1 ON l.gerado_por = u1.id
                LEFT JOIN usuarios u2 ON l.ativado_por = u2.id
                WHERE 1=1";
        
        if ($filtroCliente > 0) {
            $sql .= " AND l.id_cliente = $filtroCliente";
        }
        
        if (!empty($filtroStatus)) {
            $sql .= " AND l.status = '" . $conn->real_escape_string($filtroStatus) . "'";
        }
        
        $sql .= " ORDER BY l.data_geracao DESC LIMIT 100";
        
        $result = $conn->query($sql);
        $licencas = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $licencas[] = $row;
            }
        }
        
        $response['success'] = true;
        $response['licencas'] = $licencas;
        break;

    case 'generate':
        // Gerar nova licença (apenas administradores)
        if (!$podeGerar) {
            $response['message'] = 'Sem permissão para gerar licenças';
            break;
        }

        $idClienteLicenca = intval($_POST['id_cliente'] ?? 0);
        $dataExpiracao = $_POST['data_expiracao'] ?? '';
        $observacao = $_POST['observacao'] ?? '';
        
        if (!$idClienteLicenca || !$dataExpiracao) {
            $response['message'] = 'Cliente e data de expiração são obrigatórios';
            break;
        }
        
        // Verificar se já existe licença ativa ou pendente para este cliente
        $sqlCheck = "SELECT COUNT(*) as total FROM licenca 
                     WHERE id_cliente = $idClienteLicenca 
                     AND status IN ('ativa', 'pendente')
                     AND data_expiracao >= CURDATE()";
        $resultCheck = $conn->query($sqlCheck);
        $rowCheck = $resultCheck->fetch_assoc();
        
        if ($rowCheck['total'] > 0) {
            $response['message'] = 'Cliente já possui licença ativa ou pendente';
            break;
        }
        
        // Gerar código único de licença (formato: CLI-YYYYMM-XXXXXXXX)
        $codigoLicenca = 'LIC-' . date('Ym') . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        
        $sqlInsert = "INSERT INTO licenca 
                      (id_cliente, codigo_licenca, data_geracao, data_expiracao, status, gerado_por, observacao)
                      VALUES ($idClienteLicenca, '$codigoLicenca', NOW(), '$dataExpiracao', 'pendente', $userId, ?)";
        
        $stmt = $conn->prepare($sqlInsert);
        $stmt->bind_param('s', $observacao);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Licença gerada com sucesso';
            $response['codigo'] = $codigoLicenca;
            $response['id_licenca'] = $conn->insert_id;
        } else {
            $response['message'] = 'Erro ao gerar licença: ' . $conn->error;
        }
        break;

    case 'activate':
        // Ativar licença (administradores e operadores)
        if (!$podeAtivar) {
            $response['message'] = 'Sem permissão para ativar licenças';
            break;
        }

        $codigoLicenca = trim($_POST['codigo_licenca'] ?? '');
        
        if (empty($codigoLicenca)) {
            $response['message'] = 'Código de licença é obrigatório';
            break;
        }
        
        // Buscar licença (normalizar código para uppercase)
        $codigoLicenca = strtoupper($codigoLicenca);
        
        $sqlLicenca = "SELECT l.*, c.id AS id_cliente, c.nome_fantasia 
                       FROM licenca l
                       LEFT JOIN clientes c ON l.id_cliente = c.id
                       WHERE l.codigo_licenca = ?";
        
        $stmt = $conn->prepare($sqlLicenca);
        $stmt->bind_param('s', $codigoLicenca);
        $stmt->execute();
        $resultLicenca = $stmt->get_result();
        
        if ($resultLicenca->num_rows == 0) {
            $response['message'] = 'Código de licença não encontrado. Verifique se digitou corretamente.';
            break;
        }
        
        $licenca = $resultLicenca->fetch_assoc();
        
        // Verificar se já está ativa
        if ($licenca['status'] == 'ativa') {
            $response['message'] = 'Esta licença já está ativa';
            $response['warning'] = true;
            break;
        }
        
        // Verificar se está expirada
        if ($licenca['status'] == 'expirada') {
            $response['message'] = 'Esta licença está expirada. Solicite uma nova licença.';
            break;
        }
        
        // Verificar se está cancelada
        if ($licenca['status'] == 'cancelada') {
            $response['message'] = 'Esta licença foi cancelada. Solicite uma nova licença.';
            break;
        }
        
        // Ativar licença
        $sqlUpdate = "UPDATE licenca 
                      SET status = 'ativa', 
                          data_ativacao = NOW(), 
                          ativado_por = $userId 
                      WHERE id_licenca = " . $licenca['id_licenca'];
        
        if ($conn->query($sqlUpdate)) {
            $response['success'] = true;
            $response['message'] = 'Licença ativada com sucesso!';
            $response['cliente'] = $licenca['nome_fantasia'];
            $response['expiracao'] = date('d/m/Y', strtotime($licenca['data_expiracao']));
        } else {
            $response['message'] = 'Erro ao ativar licença';
        }
        break;

    case 'extend':
        // Adiar data de expiração (apenas administradores)
        if (!$podeGerar) {
            $response['message'] = 'Sem permissão para adiar licenças';
            break;
        }

        $idLicenca = intval($_POST['id_licenca'] ?? 0);
        $novaDataExpiracao = $_POST['nova_data_expiracao'] ?? '';
        
        if (!$idLicenca || !$novaDataExpiracao) {
            $response['message'] = 'Licença e nova data são obrigatórios';
            break;
        }
        
        $sqlUpdate = "UPDATE licenca 
                      SET data_expiracao = '$novaDataExpiracao'
                      WHERE id_licenca = $idLicenca";
        
        if ($conn->query($sqlUpdate)) {
            $response['success'] = true;
            $response['message'] = 'Data de expiração atualizada com sucesso';
        } else {
            $response['message'] = 'Erro ao atualizar data';
        }
        break;

    case 'send_email':
        // Enviar licença por e-mail (apenas administradores)
        if (!$podeGerar) {
            $response['message'] = 'Sem permissão para enviar licenças';
            break;
        }

        $idLicenca = intval($_POST['id_licenca'] ?? 0);
        
        if (!$idLicenca) {
            $response['message'] = 'ID da licença é obrigatório';
            break;
        }
        
        // Buscar dados da licença e cliente
        $sql = "SELECT l.*, c.nome_fantasia, c.email, c.razao_social
                       FROM licenca l
                       LEFT JOIN clientes c ON l.id_cliente = c.id
                       WHERE l.id_licenca = $idLicenca";
        
        $resultLicenca = $conn->query($sql);
        
        if ($resultLicenca->num_rows == 0) {
            $response['message'] = 'Licença não encontrada';
            break;
        }
        
        $licenca = $resultLicenca->fetch_assoc();
        
        if (empty($licenca['email'])) {
            $response['message'] = 'Cliente não possui e-mail cadastrado';
            break;
        }
        
        // Enviar e-mail usando PHPMailer
        $mail = new PHPMailer(true);
        
        try {
            // Configurações do servidor SMTP
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Remetente e destinatário
            $mail->setFrom(SMTP_USER, 'Sistema QR Combustível');
            $mail->addAddress($licenca['email'], $licenca['nome_fantasia']);
            
            // Conteúdo do e-mail
            $mail->isHTML(true);
            $mail->Subject = 'Licença de Acesso - Sistema QR Combustível';
            
            $dataExpiracao = date('d/m/Y', strtotime($licenca['data_expiracao']));
            
            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; }
                        .license-code { background: white; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 2px; border: 2px dashed #007bff; margin: 20px 0; border-radius: 5px; }
                        .info { margin: 20px 0; }
                        .footer { text-align: center; padding: 20px; color: #6c757d; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Licença de Acesso - QR Combustível</h2>
                        </div>
                        <div class='content'>
                            <p>Olá, <strong>{$licenca['nome_fantasia']}</strong></p>
                            
                            <p>Sua licença de acesso ao Sistema QR Combustível foi gerada com sucesso!</p>
                            
                            <div class='license-code'>
                                {$licenca['codigo_licenca']}
                            </div>
                            
                            <div class='info'>
                                <p><strong>Validade:</strong> {$dataExpiracao}</p>
                                <p><strong>Status:</strong> Pendente de Ativação</p>
                            </div>
                            
                            <p><strong>Como ativar sua licença:</strong></p>
                            <ol>
                                <li>Acesse o sistema com suas credenciais</li>
                                <li>Vá até o menu 'Ativar Licença'</li>
                                <li>Insira o código acima</li>
                                <li>Clique em 'Ativar'</li>
                            </ol>
                            
                            <p><strong>Importante:</strong> Esta licença deve ser ativada antes de {$dataExpiracao}. Após esta data, será necessário solicitar uma nova licença.</p>
                        </div>
                        <div class='footer'>
                            <p>Este é um e-mail automático. Não responda esta mensagem.</p>
                            <p>&copy; " . date('Y') . " Sistema QR Combustível - Todos os direitos reservados</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            $mail->AltBody = "Licença de Acesso - Sistema QR Combustível\n\n" .
                           "Cliente: {$licenca['nome_fantasia']}\n" .
                           "Código da Licença: {$licenca['codigo_licenca']}\n" .
                           "Validade: {$dataExpiracao}\n\n" .
                           "Acesse o sistema e ative sua licença na área 'Ativar Licença'.";
            
            $mail->send();
            
            $response['success'] = true;
            $response['message'] = 'E-mail enviado com sucesso para ' . $licenca['email'];
            
        } catch (Exception $e) {
            $response['message'] = 'Erro ao enviar e-mail: ' . $mail->ErrorInfo;
        }
        break;

    case 'check_status':
        // Verificar status da licença do cliente logado
        if (!$clienteId) {
            $response['message'] = 'Cliente não identificado';
            break;
        }
        
        $sql = "SELECT * FROM licenca 
                WHERE id_cliente = $clienteId 
                AND status = 'ativa'
                AND data_expiracao >= CURDATE()
                ORDER BY data_expiracao DESC
                LIMIT 1";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $licenca = $result->fetch_assoc();
            $response['success'] = true;
            $response['ativa'] = true;
            $response['expiracao'] = $licenca['data_expiracao'];
            $response['dias_restantes'] = (strtotime($licenca['data_expiracao']) - strtotime(date('Y-m-d'))) / 86400;
        } else {
            $response['success'] = true;
            $response['ativa'] = false;
            $response['message'] = 'Nenhuma licença ativa encontrada';
        }
        break;

    case 'get_clientes':
        // Listar clientes para seleção (apenas administradores e operadores que podem visualizar)
        if (!$podeVisualizar) {
            $response['success'] = false;
            $response['message'] = 'Sem permissão para visualizar clientes';
            break;
        }

        $sql = "SELECT id AS id_cliente, nome_fantasia, razao_social, email 
                FROM clientes 
                WHERE ativo = 1 
                ORDER BY nome_fantasia, razao_social";
        
        $result = $conn->query($sql);
        $clientes = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }
        }
        
        $response['success'] = true;
        $response['clientes'] = $clientes;
        $response['total'] = count($clientes);
        break;

    case 'cancel':
        // Cancelar licença (apenas administradores)
        if (!$podeGerar) {
            $response['message'] = 'Sem permissão para cancelar licenças';
            break;
        }

        $idLicenca = intval($_POST['id_licenca'] ?? 0);
        
        if (!$idLicenca) {
            $response['message'] = 'ID da licença é obrigatório';
            break;
        }
        
        $sqlUpdate = "UPDATE licenca 
                      SET status = 'cancelada'
                      WHERE id_licenca = $idLicenca";
        
        if ($conn->query($sqlUpdate)) {
            $response['success'] = true;
            $response['message'] = 'Licença cancelada com sucesso';
        } else {
            $response['message'] = 'Erro ao cancelar licença';
        }
        break;

    default:
        $response['message'] = 'Ação não reconhecida';
        break;
}

header('Content-Type: application/json');
echo json_encode($response);
