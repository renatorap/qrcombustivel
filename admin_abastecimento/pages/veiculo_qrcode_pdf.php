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
$accessControl->requerPermissao('veiculos', 'acessar');

$clienteId = $_SESSION['cliente_id'] ?? null;

if (!$clienteId) {
    die('Selecione um cliente no menu superior');
}

$db = new Database();
$db->connect();

// Construir WHERE baseado nos parâmetros
$where = "v.id_cliente = $clienteId";

// Se IDs específicos foram fornecidos
if (!empty($_GET['ids'])) {
    $ids = array_map('intval', explode(',', $_GET['ids']));
    $idsStr = implode(',', $ids);
    $where .= " AND v.id_veiculo IN ($idsStr)";
} elseif (!empty($_GET['id'])) {
    // Impressão de um único veículo
    $id = intval($_GET['id']);
    $where .= " AND v.id_veiculo = $id";
} else {
    // Aplicar filtros se não houver IDs específicos
    if (!empty($_GET['placa'])) {
        $placa = Security::sanitize($_GET['placa']);
        $where .= " AND LOWER(v.placa) LIKE '%" . strtolower($placa) . "%'";
    }
    
    if (!empty($_GET['modelo'])) {
        $modelo = Security::sanitize($_GET['modelo']);
        $where .= " AND LOWER(v.modelo) LIKE '%" . strtolower($modelo) . "%'";
    }
    
    if (!empty($_GET['id_situacao'])) {
        $id_situacao = intval($_GET['id_situacao']);
        $where .= " AND v.id_situacao = $id_situacao";
    } else {
        // Por padrão, apenas veículos ativos
        $where .= " AND v.id_situacao = 1";
    }
}

// Buscar veículos com via ativa do QR Code
$sql = "SELECT v.id_veiculo, v.placa, v.modelo, v.ano,
               m.descricao as marca_nome,
               cli.nome_fantasia as cliente_nome,
               vq.id as id_via,
               vq.codigo_unico
        FROM veiculo v
        LEFT JOIN marca_veiculo m ON v.id_marca_veiculo = m.id_marca_veiculo
        LEFT JOIN clientes cli ON v.id_cliente = cli.id
        LEFT JOIN veiculo_qrcode vq ON v.id_veiculo = vq.id_veiculo
            AND vq.id_situacao = 1
            AND vq.fim_vigencia IS NULL
        WHERE $where
        ORDER BY v.placa ASC";

$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    die('Nenhum veículo encontrado');
}

$veiculos = [];
while ($row = $result->fetch_assoc()) {
    $veiculos[] = $row;
}

if (empty($veiculos)) {
    die('Nenhum veículo encontrado');
}

// Criar PDF A4 (210mm x 297mm)
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('Sistema de Abastecimento');
$pdf->SetAuthor($veiculos[0]['cliente_nome'] ?? 'Sistema');
$pdf->SetTitle('QR Codes - Veículos');
$pdf->SetSubject('Impressão de QR Codes de Veículos');

// Remover header e footer padrão
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar margens (10mm em todos os lados)
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(false, 10);

// Dimensões do QR Code: 50mm x 50mm + espaço para placa
$qrSize = 50; // 50mm
$placaHeight = 10; // 10mm para a placa
$totalHeight = $qrSize + $placaHeight; // Altura total de cada QR

// Definir quantidade fixa de QR codes por página para melhor distribuição
$qrCodesPorLinha = 3; // 3 colunas
$linhasPorPagina = 4; // 4 linhas
$qrCodesPorPagina = $qrCodesPorLinha * $linhasPorPagina; // 12 QR codes por página

// Calcular espaçamentos para distribuir uniformemente
// Largura útil: 210mm - 20mm (margens) = 190mm
// Altura útil: 297mm - 20mm (margens) = 277mm
$larguraUtil = 190;
$alturaUtil = 277;

// Calcular espaçamento horizontal: (largura_util - (qr_size * colunas)) / (colunas + 1)
$espacoHorizontal = ($larguraUtil - ($qrSize * $qrCodesPorLinha)) / ($qrCodesPorLinha + 1);

// Calcular espaçamento vertical: (altura_util - (total_height * linhas)) / (linhas + 1)
$espacoVertical = ($alturaUtil - ($totalHeight * $linhasPorPagina)) / ($linhasPorPagina + 1);

$totalVeiculos = count($veiculos);
$totalPaginas = ceil($totalVeiculos / $qrCodesPorPagina);

$veiculoIndex = 0;

for ($pagina = 0; $pagina < $totalPaginas; $pagina++) {
    $pdf->AddPage();
    
    $linha = 0;
    $coluna = 0;
    
    while ($veiculoIndex < $totalVeiculos && ($linha * $qrCodesPorLinha + $coluna) < $qrCodesPorPagina) {
        $veiculo = $veiculos[$veiculoIndex];
        $veiculoIndex++;
        
        // Calcular posição X e Y com espaçamento uniforme
        // X: margem_esquerda + espaço_inicial + (coluna * (qr_size + espaço_entre))
        $posX = 10 + $espacoHorizontal + ($coluna * ($qrSize + $espacoHorizontal));
        
        // Y: margem_superior + espaço_inicial + (linha * (altura_total + espaço_entre))
        $posY = 10 + $espacoVertical + ($linha * ($totalHeight + $espacoVertical));
        
        // Dados para o QR Code - usando código único da via ou placa como fallback
        $qrCodeData = !empty($veiculo['codigo_unico']) ? $veiculo['codigo_unico'] : $veiculo['placa'];
        
        // Gerar QR Code localmente
        $options = new QROptions([
            'version'      => QRCode::VERSION_AUTO,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_L,
            'scale'        => 10,
            'imageBase64'  => false,
        ]);
        
        $qrcode = new QRCode($options);
        $qrCodeImage = '@' . $qrcode->render($qrCodeData);
        $pdf->Image($qrCodeImage, $posX, $posY, $qrSize, $qrSize, '', '', '', false, 300, '', false, false, 0);
        
        // Adicionar placa abaixo do QR Code
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($posX, $posY + $qrSize + 2);
        $pdf->Cell($qrSize, 8, mb_strtoupper($veiculo['placa'], 'UTF-8'), 0, 0, 'C');
        
        // Próxima coluna
        $coluna++;
        if ($coluna >= $qrCodesPorLinha) {
            $coluna = 0;
            $linha++;
        }
    }
}

// Determinar o modo de saída (download ou exibir)
$outputMode = isset($_GET['download']) ? 'D' : 'I';
$filename = 'qrcodes_veiculos_' . date('Y-m-d_His') . '.pdf';

$pdf->Output($filename, $outputMode);
