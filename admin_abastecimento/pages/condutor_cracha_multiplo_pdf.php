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

$clienteId = $_SESSION['cliente_id'] ?? null;

if (!$clienteId) {
    die('Selecione um cliente no menu superior');
}

$db = new Database();
$db->connect();

// Construir WHERE baseado nos parâmetros
$where = "c.id_cliente = $clienteId";

// Se IDs específicos foram fornecidos
if (!empty($_GET['ids'])) {
    $ids = array_map('intval', explode(',', $_GET['ids']));
    $idsStr = implode(',', $ids);
    $where .= " AND c.id_condutor IN ($idsStr)";
} else {
    // Aplicar filtros se não houver IDs específicos
    if (!empty($_GET['nome'])) {
        $nome = Security::sanitize($_GET['nome']);
        $where .= " AND LOWER(c.nome) LIKE '%" . strtolower($nome) . "%'";
    }
    
    if (!empty($_GET['cpf'])) {
        $cpf = Security::sanitize($_GET['cpf']);
        $cpf = preg_replace('/\D/', '', $cpf);
        $where .= " AND c.cpf LIKE '%$cpf%'";
    }
    
    if (!empty($_GET['cnh'])) {
        $cnh = Security::sanitize($_GET['cnh']);
        $where .= " AND c.cnh LIKE '%$cnh%'";
    }
    
    if (!empty($_GET['id_situacao'])) {
        $id_situacao = intval($_GET['id_situacao']);
        $where .= " AND c.id_situacao = $id_situacao";
    } else {
        // Por padrão, apenas condutores ativos
        $where .= " AND c.id_situacao = 1";
    }
    
    if (isset($_GET['e_condutor']) && $_GET['e_condutor'] !== '') {
        $e_condutor = intval($_GET['e_condutor']);
        $where .= " AND c.e_condutor = $e_condutor";
    }
}

// Buscar condutores com via ativa do crachá
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
        WHERE $where
        ORDER BY c.nome ASC";

$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    die('Nenhum condutor encontrado');
}

$condutores = [];
while ($row = $result->fetch_assoc()) {
    // Incluir condutor independente de ter via ativa (usará CNH como fallback)
    $condutores[] = $row;
}

if (empty($condutores)) {
    die('Nenhum condutor encontrado');
}

// Criar PDF A4 (210mm x 297mm)
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('Sistema de Abastecimento');
$pdf->SetAuthor($condutores[0]['cliente_nome'] ?? 'Sistema');
$pdf->SetTitle('Crachás - ' . ($condutores[0]['cliente_nome'] ?? 'Cliente'));
$pdf->SetSubject('Impressão de Crachás');

// Remover header e footer padrão
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar margens (5mm em todos os lados)
$pdf->SetMargins(5, 5, 5);
$pdf->SetAutoPageBreak(false, 5);

// Dimensões do crachá (proporcional a 1352x428 = 3.16:1)
$crachLargura = 200; // 200mm de largura
$crachAltura = 63.3; // altura proporcional (200/3.16)
$espacoVertical = 5; // Espaço entre crachás

// Calcular quantos crachás cabem por página
$crachasPorPagina = 4;
$totalCondutores = count($condutores);
$totalPaginas = ceil($totalCondutores / $crachasPorPagina);

$condutorIndex = 0;

for ($pagina = 0; $pagina < $totalPaginas; $pagina++) {
    $pdf->AddPage();
    
    for ($i = 0; $i < $crachasPorPagina && $condutorIndex < $totalCondutores; $i++) {
        $condutor = $condutores[$condutorIndex];
        $condutorIndex++;
        
        // Calcular posição Y para este crachá
        $posY = 5 + ($i * ($crachAltura + $espacoVertical));
        
        // Dados para o QR Code - usando código único da via ou CNH como fallback
        if (!empty($condutor['codigo_unico'])) {
            $qrCodeData = $condutor['codigo_unico'];
        } elseif (!empty($condutor['id_via'])) {
            $qrCodeData = 'CRC' . str_pad($condutor['id_via'], 10, '0', STR_PAD_LEFT);
        } else {
            $qrCodeData = $condutor['cnh'];
        }
        
        // Imagem de fundo do crachá
        $pdf->Image('../assets/cracha.png', 5, $posY, $crachLargura, $crachAltura, '', '', '', false, 300, '', false, false, 0);
        
        // Logo do cliente (posicionado sobre o background)
        if (!empty($condutor['cliente_logo']) && file_exists('../' . $condutor['cliente_logo'])) {
            $pdf->Image('../' . $condutor['cliente_logo'], 15, $posY + 2, 14, 14, '', '', '', false, 300, '', false, false, 0);
        }
        
        // Foto do condutor (posicionado sobre o background)
        if (!empty($condutor['foto']) && file_exists('../' . $condutor['foto'])) {
            $pdf->Image('../' . $condutor['foto'], 11, $posY + 18, 24, 32, '', '', '', false, 300, '', false, false, 0);
        }
        
        // Configurar cor do texto para preto
        $pdf->SetTextColor(0, 0, 0);
        
        // Nome do cliente (canto superior direito, antes do QR Code)
        $pdf->SetFont('helvetica', 'B', 10);
        $clienteNome = mb_strtoupper($condutor['cliente_nome'] ?? '', 'UTF-8');
        $larguraTexto = $pdf->GetStringWidth($clienteNome);
        $pdf->SetXY(28, $posY + 4);
        if ($larguraTexto > 60) {
            $pdf->MultiCell(60, 4, $clienteNome, 0, 'C', false, 1);
        } else {
            $pdf->Cell(60, 4, $clienteNome, 0, 0, 'C');
        }
        
        // Nome do condutor
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(19.5, $posY + 54);
        $pdf->MultiCell(145, 6, mb_strtoupper($condutor['nome'], 'UTF-8'), 0, 'L', false, 1);
        
        // Matrícula
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(55, $posY + 22.25);
        $pdf->Cell(60, 5, $condutor['matricula'] ?? '-', 0, 1, 'L');
        
        // Cargo
        $cargo = ($condutor['e_condutor'] == 1) ? 'MOTORISTA' : ($condutor['cargo_nome'] ?? '-');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(48, $posY + 31.5);
        $pdf->Cell(60, 5, $cargo, 0, 1, 'L');
        
        // CNH
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(44, $posY + 41.25);
        $pdf->Cell(60, 5, $condutor['cnh'], 0, 1, 'L');
        
        // CPF (com máscara)
        $cpf = $condutor['cpf'] ?? '';
        $cpfFormatado = '-';
        if (!empty($cpf)) {
            $cpf = preg_replace('/\D/', '', $cpf);
            if (strlen($cpf) == 11) {
                $cpfFormatado = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
            } else {
                $cpfFormatado = $cpf;
            }
        }
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(117, $posY + 5);
        $pdf->Cell(60, 5, $cpfFormatado, 0, 1, 'L');
        
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
        $pdf->Image($qrCodeImage, 164, $posY + 3, 36, 36, '', '', '', false, 300, '', false, false, 0);
    }
}

// Determinar o modo de saída (download ou exibir)
$outputMode = isset($_GET['download']) ? 'D' : 'I';
$filename = 'crachas_' . date('Y-m-d_His') . '.pdf';

$pdf->Output($filename, $outputMode);
