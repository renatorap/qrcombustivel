<?php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Verificar autenticação
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    die('Não autenticado');
}

// Receber dados via POST
if (!isset($_POST['dados'])) {
    http_response_code(400);
    die('Dados não fornecidos');
}

$input = json_decode($_POST['dados'], true);

if (!isset($input['condutores']) || !is_array($input['condutores'])) {
    http_response_code(400);
    die('Dados inválidos');
}

$condutores = $input['condutores'];
$agrupamento = $input['agrupamento'] ?? 'none';
$ordenacao = $input['ordenacao'] ?? 'nome';
$filtros = $input['filtros'] ?? [];

// Validar se há condutores
if (empty($condutores)) {
    http_response_code(400);
    die('Nenhum condutor para exportar');
}

// Buscar informações do cliente
$db = new Database();
$db->connect();
$clienteId = $_SESSION['cliente_id'];

$sql = "SELECT razao_social, nome_fantasia, logo_path FROM clientes WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $clienteId);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();
$stmt->close();

$nomeCliente = $cliente['nome_fantasia'] ?? $cliente['razao_social'] ?? 'Cliente';
$logoPath = $cliente['logo_path'] ?? null;

// Criar classe customizada para cabeçalho
class MYPDF extends TCPDF {
    private $clientName;
    private $logoPath;
    private $filtros;
    
    public function setClientInfo($name, $logo, $filtros) {
        $this->clientName = $name;
        $this->logoPath = $logo;
        $this->filtros = $filtros;
    }
    
    public function Header() {
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        
        $logoSize = 15;
        $logoX = 10;
        $logoY = 4;
        $hasLogo = false;
        
        if ($this->logoPath && file_exists(__DIR__ . '/../' . $this->logoPath)) {
            try {
                $this->Image(__DIR__ . '/../' . $this->logoPath, $logoX, $logoY, $logoSize, $logoSize, '', '', '', true, 150, '', false, false, 0);
                $hasLogo = true;
            } catch (Exception $e) {
                // Se falhar ao carregar logo, continua sem ela
            }
        }
        
        // Preparar texto dos filtros
        $nomeTexto = !empty($this->filtros['nome']) ? $this->filtros['nome'] : 'Todos';
        $cnhTexto = !empty($this->filtros['cnh']) ? $this->filtros['cnh'] : 'Todos';
        $validadeCNHTexto = !empty($this->filtros['validade_cnh_texto']) ? $this->filtros['validade_cnh_texto'] : 'Todos';
        $situacaoTexto = !empty($this->filtros['situacao_texto']) ? $this->filtros['situacao_texto'] : 'Todos';
        
        // Nome do cliente
        if ($hasLogo) {
            $this->SetXY($logoX + $logoSize + 5, $logoY + 1.5);
            $this->Cell(0, 5, $this->clientName, 0, 1, 'L');
            
            $this->SetX($logoX + $logoSize + 5);
            $this->SetFont('helvetica', 'B', 12);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(0, 5, 'Relatório de Condutores', 0, 1, 'L');
        } else {
            $this->Cell(0, 8, $this->clientName, 0, 1, 'C');
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 6, 'Relatório de Condutores', 0, 1, 'C');
        }
        
        // Calcular altura dos filtros
        $alturaFiltros = 16; // 4 linhas de filtros
        $minY = $hasLogo ? ($logoY + $logoSize + 2) : ($this->GetY() + 2);
        $filtroStartY = $hasLogo ? $logoY + 1.5 : 10;
        $filtroEndY = $filtroStartY + $alturaFiltros;
        $separatorY = ($filtroEndY > $minY) ? $filtroEndY : $minY;
        
        if ($hasLogo) {
            $this->SetY($logoY + $logoSize);
        }
        
        // Exibir filtros
        $this->SetXY(140, $filtroStartY);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(80, 80, 80);
        
        $this->Cell(0, 4, 'Nome: ' . $nomeTexto, 0, 1, 'R');
        $this->SetX(140);
        $this->Cell(0, 4, 'CNH: ' . $cnhTexto, 0, 1, 'R');
        $this->SetX(140);
        $this->Cell(0, 4, 'Validade: ' . $validadeCNHTexto, 0, 1, 'R');
        $this->SetX(140);
        $this->Cell(0, 4, 'Situação: ' . $situacaoTexto, 0, 1, 'R');
        
        $this->SetY($separatorY);
        $this->Ln(2);
        $this->SetDrawColor(74, 144, 226);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);
    }
    
    public function Footer() {
        $this->SetY(-12);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages() . ' - Gerado em ' . date('d/m/Y H:i:s'), 0, 0, 'C');
    }
}

// Criar PDF
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setClientInfo($nomeCliente, $logoPath, $filtros);

$pdf->SetCreator('Sistema de Abastecimento');
$pdf->SetAuthor('Relatório Automatizado');
$pdf->SetTitle('Relatório de Condutores');
$pdf->SetSubject('Listagem de Condutores');

$pdf->SetMargins(10, 30, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetAutoPageBreak(true, 15);

$pdf->AddPage();

// Aplicar ordenação
usort($condutores, function($a, $b) use ($ordenacao) {
    switch($ordenacao) {
        case 'nome':
            return strcasecmp($a['nome'] ?? '', $b['nome'] ?? '');
        case 'cnh':
            return strcasecmp($a['cnh'] ?? '', $b['cnh'] ?? '');
        case 'validade_cnh':
            return strcasecmp($a['validade_cnh'] ?? '', $b['validade_cnh'] ?? '');
        default:
            return 0;
    }
});

// Total
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6, 'Total de Condutores: ' . count($condutores), 0, 1);
$pdf->Ln(2);

function renderTableHeader($pdf) {
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Cell(50, 7, 'Nome', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'CNH', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Validade CNH', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Status CNH', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Situação', 1, 1, 'C', true);
}

function renderCondutorRow($pdf, $condutor) {
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    
    $nomeHeight = $pdf->getStringHeight(50, $condutor['nome'] ?? '');
    $cnhHeight = $pdf->getStringHeight(30, $condutor['cnh'] ?? '');
    $validadeHeight = $pdf->getStringHeight(30, $condutor['validade_cnh_formatada'] ?? '');
    $statusHeight = $pdf->getStringHeight(30, $condutor['status_cnh'] ?? '');
    $situacaoHeight = $pdf->getStringHeight(50, $condutor['situacao'] == 1 ? 'Ativo' : 'Inativo');
    
    $rowHeight = max(6, $nomeHeight, $cnhHeight, $validadeHeight, $statusHeight, $situacaoHeight);
    
    if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - 15) {
        $pdf->AddPage();
        renderTableHeader($pdf);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
    }
    
    $situacao = $condutor['situacao'] == 1 ? 'Ativo' : 'Inativo';
    
    $pdf->MultiCell(50, $rowHeight, $condutor['nome'] ?? '', 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell(30, $rowHeight, $condutor['cnh'] ?? '', 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell(30, $rowHeight, $condutor['validade_cnh_formatada'] ?? '-', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell(30, $rowHeight, $condutor['status_cnh'] ?? '-', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell(50, $rowHeight, $situacao, 1, 'C', false, 1, '', '', true, 0, false, true, $rowHeight, 'M');
}

if ($agrupamento === 'none') {
    renderTableHeader($pdf);
    
    foreach ($condutores as $condutor) {
        renderCondutorRow($pdf, $condutor);
    }
} else {
    $grupos = [];
    foreach ($condutores as $condutor) {
        if ($agrupamento === 'situacao') {
            $chave = $condutor['situacao'] == 1 ? 'Ativo' : 'Inativo';
        } else if ($agrupamento === 'status_cnh') {
            $chave = $condutor['status_cnh'] ?? 'Não informado';
        } else {
            $chave = 'Outros';
        }
        
        if (!isset($grupos[$chave])) {
            $grupos[$chave] = [];
        }
        $grupos[$chave][] = $condutor;
    }
    
    $primeiroGrupo = true;
    foreach ($grupos as $nomeGrupo => $condutoresGrupo) {
        if (!$primeiroGrupo) {
            $pdf->Ln(5);
        }
        $primeiroGrupo = false;
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(74, 144, 226);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 8, $nomeGrupo . ' (' . count($condutoresGrupo) . ' condutor' . (count($condutoresGrupo) != 1 ? 'es' : '') . ')', 0, 1, 'L', true);
        $pdf->Ln(1);
        
        renderTableHeader($pdf);
        
        foreach ($condutoresGrupo as $condutor) {
            renderCondutorRow($pdf, $condutor);
        }
    }
}

try {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $filename = 'relatorio_condutores_' . date('Y-m-d_His') . '.pdf';
    $pdf->Output($filename, 'I');
    exit;
    
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    die('Erro ao gerar PDF: ' . $e->getMessage());
}
