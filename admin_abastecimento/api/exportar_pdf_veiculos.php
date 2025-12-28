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

if (!isset($input['veiculos']) || !is_array($input['veiculos'])) {
    http_response_code(400);
    die('Dados inválidos');
}

$veiculos = $input['veiculos'];
$agrupamento = $input['agrupamento'] ?? 'none';
$ordenacao = $input['ordenacao'] ?? 'placa';
$filtros = $input['filtros'] ?? [];

// Validar se há veículos
if (empty($veiculos)) {
    http_response_code(400);
    die('Nenhum veículo para exportar');
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

// Criar classe customizada para cabeçalho em todas as páginas
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
        // Cabeçalho com logo e nome do cliente
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
        
        // Preparar texto dos filtros - sempre exibir todos
        $placaTexto = !empty($this->filtros['placa']) ? $this->filtros['placa'] : 'Todos';
        $setorTexto = !empty($this->filtros['setor']) ? $this->filtros['setor'] : 'Todos';
        $situacaoTexto = !empty($this->filtros['situacao']) ? $this->filtros['situacao'] : 'Todos';
        
        // Verificar se precisa quebrar linha (mais de um item selecionado)
        $setorMultiplo = !empty($this->filtros['setor']) && substr_count($this->filtros['setor'], ',') > 0;
        $setores = $setorMultiplo ? explode(', ', $setorTexto) : [];
        
        // Nome do cliente ao lado do logo ou no topo
        if ($hasLogo) {
            // Alinhar texto ao topo com o logo
            $this->SetXY($logoX + $logoSize + 5, $logoY + 1.5);
            $this->Cell(0, 5, $this->clientName, 0, 1, 'L');
            
            $this->SetX($logoX + $logoSize + 5);
            $this->SetFont('helvetica', 'B', 12);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(0, 5, 'Relatório de Veículos', 0, 1, 'L');
        } else {
            $this->Cell(0, 8, $this->clientName, 0, 1, 'C');
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 6, 'Relatório de Veículos', 0, 1, 'C');
        }
        
        // Calcular altura necessária para os filtros
        $alturaFiltros = 4; // Placa
        if ($setorMultiplo) {
            $alturaFiltros += 4 + (count($setores) * 3); // Label + cada setor
        } else {
            $alturaFiltros += 4; // Setor simples
        }
        $alturaFiltros += 4; // Situação
        
        // Garantir que há espaço suficiente antes dos filtros
        $minY = $hasLogo ? ($logoY + $logoSize + 2) : ($this->GetY() + 2);
        $filtroStartY = $hasLogo ? $logoY + 1.5 : 10;
        $filtroEndY = $filtroStartY + $alturaFiltros;
        
        // Se os filtros acabam abaixo do conteúdo esquerdo, usar essa posição
        if ($filtroEndY > $minY) {
            $separatorY = $filtroEndY;
        } else {
            $separatorY = $minY;
        }
        
        // Posicionar cursor após título/logo
        if ($hasLogo) {
            $this->SetY($logoY + $logoSize);
        }
        
        // Exibir filtros no canto direito
        $this->SetXY(140, $filtroStartY);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(80, 80, 80);
        
        // Placa
        $this->Cell(0, 4, 'Placa: ' . $placaTexto, 0, 1, 'R');
        
        // Setor - com quebra de linha se múltiplo
        if ($setorMultiplo) {
            $this->SetX(140);
            $this->Cell(0, 4, 'Setor:', 0, 1, 'R');
            foreach ($setores as $setor) {
                $this->SetX(140);
                $this->SetFont('helvetica', '', 7);
                $this->Cell(0, 3, '  • ' . $setor, 0, 1, 'R');
            }
            $this->SetFont('helvetica', '', 8);
        } else {
            $this->SetX(140);
            $this->Cell(0, 4, 'Setor: ' . $setorTexto, 0, 1, 'R');
        }
        
        // Situação
        $this->SetX(140);
        $this->Cell(0, 4, 'Situação: ' . $situacaoTexto, 0, 1, 'R');
        
        // Posicionar cursor na linha do separador
        $this->SetY($separatorY);
        
        // Linha separadora
        $this->Ln(2);
        $this->SetDrawColor(74, 144, 226);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);
    }
    
    public function Footer() {
        // Posicionar a 15mm do rodapé
        $this->SetY(-12);
        // Fonte
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        // Número da página
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages() . ' - Gerado em ' . date('d/m/Y H:i:s'), 0, 0, 'C');
    }
}

// Criar PDF
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setClientInfo($nomeCliente, $logoPath, $filtros);

// Configurações do documento
$pdf->SetCreator('Sistema de Abastecimento');
$pdf->SetAuthor('Relatório Automatizado');
$pdf->SetTitle('Relatório de Veículos');
$pdf->SetSubject('Listagem de Veículos');

// Calcular margem superior dinamicamente baseado nos filtros
$setorMultiplo = !empty($filtros['setor']) && substr_count($filtros['setor'], ',') > 0;
$margemSuperior = 24;
if ($setorMultiplo) {
    $numSetores = substr_count($filtros['setor'], ',') + 1;
    $margemSuperior = 24 + ($numSetores * 3); // 3mm por setor adicional
}

// Configurações de margens (ajustadas dinamicamente para acomodar o cabeçalho)
$pdf->SetMargins(10, $margemSuperior, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetAutoPageBreak(true, 15);

// Adicionar página
$pdf->AddPage();

// Aplicar ordenação aos veículos
usort($veiculos, function($a, $b) use ($ordenacao) {
    switch($ordenacao) {
        case 'placa':
            return strcasecmp($a['placa'] ?? '', $b['placa'] ?? '');
        case 'modelo':
            return strcasecmp($a['modelo'] ?? '', $b['modelo'] ?? '');
        case 'marca':
            return strcasecmp($a['marca'] ?? '', $b['marca'] ?? '');
        case 'setor':
            return strcasecmp($a['setor'] ?? '', $b['setor'] ?? '');
        default:
            return 0;
    }
});

// Total de veículos
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6, 'Total de Veículos: ' . count($veiculos), 0, 1);
$pdf->Ln(2);

// Função para renderizar cabeçalho da tabela
function renderTableHeader($pdf) {
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Cell(20, 7, 'Placa', 1, 0, 'C', true);
    $pdf->Cell(55, 7, 'Modelo', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Marca', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Setor', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Situação', 1, 1, 'C', true);
}

// Função para renderizar linha de veículo
function renderVehicleRow($pdf, $veiculo) {
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    
    // Calcular altura necessária (multiline para campos longos)
    $placaHeight = $pdf->getStringHeight(20, $veiculo['placa'] ?? '');
    $modeloHeight = $pdf->getStringHeight(55, $veiculo['modelo'] ?? '');
    $marcaHeight = $pdf->getStringHeight(35, $veiculo['marca'] ?? '');
    $setorHeight = $pdf->getStringHeight(60, $veiculo['setor'] ?? '');
    $situacaoHeight = $pdf->getStringHeight(20, $veiculo['situacao'] ?? '');
    
    $rowHeight = max(6, $placaHeight, $modeloHeight, $marcaHeight, $setorHeight, $situacaoHeight);
    
    // Verificar se precisa adicionar nova página
    if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - 15) {
        $pdf->AddPage();
        renderTableHeader($pdf);
        // Resetar fonte para normal após cabeçalho
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
    }
    
    $pdf->MultiCell(20, $rowHeight, $veiculo['placa'] ?? '', 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell(55, $rowHeight, $veiculo['modelo'] ?? '', 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell(35, $rowHeight, $veiculo['marca'] ?? '', 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell(60, $rowHeight, $veiculo['setor'] ?? '', 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
    $pdf->MultiCell(20, $rowHeight, $veiculo['situacao'] ?? '', 1, 'L', false, 1, '', '', true, 0, false, true, $rowHeight, 'M');
}

// Renderizar dados conforme agrupamento
if ($agrupamento === 'none') {
    // Sem agrupamento - renderizar tabela única
    renderTableHeader($pdf);
    
    foreach ($veiculos as $veiculo) {
        renderVehicleRow($pdf, $veiculo);
    }
} else {
    // Com agrupamento - agrupar dados
    $grupos = [];
    foreach ($veiculos as $veiculo) {
        $chave = $veiculo[$agrupamento] ?? 'Não informado';
        if (!isset($grupos[$chave])) {
            $grupos[$chave] = [];
        }
        $grupos[$chave][] = $veiculo;
    }
    
    // Renderizar cada grupo
    $primeiroGrupo = true;
    foreach ($grupos as $nomeGrupo => $veiculosGrupo) {
        if (!$primeiroGrupo) {
            $pdf->Ln(5);
        }
        $primeiroGrupo = false;
        
        // Cabeçalho do grupo
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(74, 144, 226);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 8, $nomeGrupo . ' (' . count($veiculosGrupo) . ' veículo' . (count($veiculosGrupo) != 1 ? 's' : '') . ')', 0, 1, 'L', true);
        $pdf->Ln(1);
        
        // Tabela do grupo
        renderTableHeader($pdf);
        
        foreach ($veiculosGrupo as $veiculo) {
            renderVehicleRow($pdf, $veiculo);
        }
    }
}

// Gerar PDF
try {
    // Limpar qualquer output anterior
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Nome do arquivo
    $filename = 'relatorio_veiculos_' . date('Y-m-d_His') . '.pdf';
    
    // Output do PDF
    $pdf->Output($filename, 'I'); // 'I' = inline no browser, 'D' = download
    exit;
    
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    die('Erro ao gerar PDF: ' . $e->getMessage());
}
