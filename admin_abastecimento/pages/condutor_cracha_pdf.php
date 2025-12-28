<?php
require_once '../config/cache_control.php';
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';
require_once '../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    $_SESSION = array();
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('condutores', 'acessar');

$idCondutor = intval($_GET['id'] ?? 0);
$clienteId = $_SESSION['cliente_id'] ?? null;

if (!$idCondutor) {
    die('ID do condutor não informado');
}

// Buscar dados do condutor e via ativa do crachá
$db = new Database();
$db->connect();

$whereCliente = $clienteId ? "AND c.id_cliente = $clienteId" : "";

$sql = "SELECT c.*, 
               cq.id as id_via, 
               cq.codigo_unico,
               cq.inicio_vigencia,
               car.descricao as cargo_nome,
               cat.codigo as categoria_cnh,
               cli.nome_fantasia as cliente_nome,
               cli.logo_path as cliente_logo
        FROM condutor c
        LEFT JOIN condutor_qrcode cq ON c.id_condutor = cq.id_condutor 
            AND cq.id_situacao = 1 
            AND cq.fim_vigencia IS NULL
        LEFT JOIN cargo car ON c.id_cargo = car.id_cargo
        LEFT JOIN cat_cnh cat ON c.id_cat_cnh = cat.id_cat_cnh
        LEFT JOIN clientes cli ON c.id_cliente = cli.id
        WHERE c.id_condutor = $idCondutor 
        $whereCliente";

$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    die('Condutor não encontrado');
}

$condutor = $result->fetch_assoc();

if (!$condutor['id_via']) {
    die('Nenhuma via ativa do crachá encontrada');
}

// Dados para o QR Code - usando código único
$qrCodeData = $condutor['codigo_unico'] ?? 'CRC' . str_pad($condutor['id_via'], 10, '0', STR_PAD_LEFT);

// Criar PDF com dimensões proporcionais à imagem (1352x428 = 3.16:1 ratio)
// Usando largura 169mm (tamanho A6 landscape) altura 53.5mm
$pdf = new TCPDF('L', 'mm', array(169, 53.5), true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('Sistema de Abastecimento');
$pdf->SetAuthor($condutor['cliente_nome'] ?? 'Sistema');
$pdf->SetTitle('Crachá - ' . $condutor['nome']);
$pdf->SetSubject('Crachá do Condutor');

// Remover header e footer padrão
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar margens
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);

// ===== PÁGINA 1: FRENTE =====
$pdf->AddPage();

// Imagem de fundo do crachá (frente)
$pdf->Image('../assets/cracha.png', 0, 0, 169, 53.5, '', '', '', false, 300, '', false, false, 0);

// Logo do cliente (posicionado sobre o background)
if (!empty($condutor['cliente_logo']) && file_exists('../' . $condutor['cliente_logo'])) {
    $pdf->Image('../' . $condutor['cliente_logo'], 9, 2, 12, 12, '', '', '', false, 300, '', false, false, 0);
}

// Foto do condutor (posicionado sobre o background)
if (!empty($condutor['foto']) && file_exists('../' . $condutor['foto'])) {
    $pdf->Image('../' . $condutor['foto'], 5, 15, 20, 27, '', '', '', false, 300, '', false, false, 0);
}

// Configurar cor do texto para preto
$pdf->SetTextColor(0, 0, 0);

// Nome do cliente (canto superior direito, antes do QR Code)
$pdf->SetFont('helvetica', 'B', 10);
$clienteNome = mb_strtoupper($condutor['cliente_nome'] ?? '', 'UTF-8');
$larguraTexto = $pdf->GetStringWidth($clienteNome);
$pdf->SetXY(21, 3.5);
if ($larguraTexto > 52) {
    $pdf->MultiCell(52, 3, $clienteNome, 0, 'C', false, 1);
} else {
    $pdf->Cell(52, 3, $clienteNome, 0, 0, 'C');
}

// Nome (lado direito)
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY(13, 45.125);
$pdf->MultiCell(120, 5, mb_strtoupper($condutor['nome'], 'UTF-8'), 0, 'L', false, 1);

// Matrícula
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY(42, 18.875);
$pdf->Cell(50, 4, $condutor['matricula'] ?? '-', 0, 1, 'L');

// Cargo
$cargo = ($condutor['e_condutor'] == 1) ? 'MOTORISTA' : ($condutor['cargo_nome'] ?? '-');

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY(36, 26.75);
$pdf->Cell(50, 4, $cargo, 0, 1, 'L');

// CNH
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY(33, 35);
$pdf->Cell(50, 4, $condutor['cnh'], 0, 1, 'L');

// CPF (com máscara)
$cpf = $condutor['cpf'] ?? '';
$cpfFormatado = '-';
if (!empty($cpf)) {
    $cpf = preg_replace('/\D/', '', $cpf); // Remove caracteres não numéricos
    if (strlen($cpf) == 11) {
        $cpfFormatado = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    } else {
        $cpfFormatado = $cpf;
    }
}
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY(96, 4.4);
$pdf->Cell(50, 4, $cpfFormatado, 0, 1, 'L');

// QR Code (canto superior direito) - Gerar localmente
$options = new QROptions([
    'version'      => QRCode::VERSION_AUTO,
    'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
    'eccLevel'     => QRCode::ECC_L,
    'scale'        => 10,
    'imageBase64'  => false,
]);

$qrcode = new QRCode($options);
$qrCodeImage = '@' . $qrcode->render($qrCodeData);
$pdf->Image($qrCodeImage, 134, 2.5, 31, 31, '', '', '', false, 300, '', false, false, 0);

// Output do PDF
$pdf->Output('cracha_' . $condutor['id_condutor'] . '.pdf', 'I');
