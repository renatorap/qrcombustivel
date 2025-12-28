<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

// Validar sessão
if (empty($_SESSION['token'])) {
    die('Não autenticado');
}

$token = Security::validateToken($_SESSION['token']);
if (!$token) {
    $_SESSION = array();
    session_destroy();
    die('Sessão inválida');
}

$accessControl = new AccessControl($_SESSION['userId']);

// Permitir acesso apenas para grupo 4 (fornecedores)
if (!$accessControl->isFornecedor()) {
    die('Acesso negado. Este relatório está disponível apenas para fornecedores.');
}

$fornecedorId = $accessControl->getFornecedorId();

// Tipo de exportação
$exportType = $_GET['export'] ?? 'excel';

// Filtros
$tipoData = $_GET['tipoData'] ?? 'intervalo';
$dataInicio = $_GET['dataInicio'] ?? '';
$dataFim = $_GET['dataFim'] ?? '';
$dataUnica = $_GET['dataUnica'] ?? '';
$horaInicio = $_GET['horaInicio'] ?? '';
$horaFim = $_GET['horaFim'] ?? '';
$idVeiculo = isset($_GET['veiculo']) && $_GET['veiculo'] !== '' ? intval($_GET['veiculo']) : null;
$idCondutor = isset($_GET['condutor']) && $_GET['condutor'] !== '' ? intval($_GET['condutor']) : null;

// Conectar ao banco
$db = new Database();
$db->connect();

// Construir WHERE
$where = [];

// Filtro de fornecedor (apenas se for fornecedor)
if ($fornecedorId) {
    $where[] = "cc.id_fornecedor = " . intval($fornecedorId);
}

// Filtros de data
switch ($tipoData) {
    case 'intervalo':
        if (!empty($dataInicio) && !empty($dataFim)) {
            $where[] = "cc.data BETWEEN '" . $db->escape($dataInicio) . "' AND '" . $db->escape($dataFim) . "'";
        }
        break;
    case 'unica':
        if (!empty($dataUnica)) {
            $where[] = "cc.data = '" . $db->escape($dataUnica) . "'";
        }
        break;
    case 'maior':
        if (!empty($dataInicio)) {
            $where[] = "cc.data >= '" . $db->escape($dataInicio) . "'";
        }
        break;
    case 'menor':
        if (!empty($dataFim)) {
            $where[] = "cc.data <= '" . $db->escape($dataFim) . "'";
        }
        break;
}

// Filtros de horário
if (!empty($horaInicio) && !empty($horaFim)) {
    $where[] = "cc.hora BETWEEN '" . $db->escape($horaInicio) . "' AND '" . $db->escape($horaFim) . "'";
} elseif (!empty($horaInicio)) {
    $where[] = "cc.hora >= '" . $db->escape($horaInicio) . "'";
} elseif (!empty($horaFim)) {
    $where[] = "cc.hora <= '" . $db->escape($horaFim) . "'";
}

// Filtro de veículo
if ($idVeiculo) {
    $where[] = "cc.id_veiculo = " . intval($idVeiculo);
}

// Filtro de condutor
if ($idCondutor) {
    $where[] = "cc.id_condutor = " . intval($idCondutor);
}

// Se não houver filtros, adicionar condição sempre verdadeira
$whereClause = !empty($where) ? implode(' AND ', $where) : '1=1';

// Preparar textos dos filtros para o PDF
$filtros = [];

// Filtro de data
switch ($tipoData) {
    case 'intervalo':
        if (!empty($dataInicio) && !empty($dataFim)) {
            $filtros['data'] = date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim));
        } else {
            $filtros['data'] = 'Todos';
        }
        break;
    case 'unica':
        $filtros['data'] = !empty($dataUnica) ? date('d/m/Y', strtotime($dataUnica)) : 'Todos';
        break;
    case 'maior':
        $filtros['data'] = !empty($dataInicio) ? 'Maior que ' . date('d/m/Y', strtotime($dataInicio)) : 'Todos';
        break;
    case 'menor':
        $filtros['data'] = !empty($dataFim) ? 'Menor que ' . date('d/m/Y', strtotime($dataFim)) : 'Todos';
        break;
}

// Filtro de horário
if (!empty($horaInicio) && !empty($horaFim)) {
    $filtros['horario'] = substr($horaInicio, 0, 5) . ' a ' . substr($horaFim, 0, 5);
} elseif (!empty($horaInicio)) {
    $filtros['horario'] = 'A partir de ' . substr($horaInicio, 0, 5);
} elseif (!empty($horaFim)) {
    $filtros['horario'] = 'Até ' . substr($horaFim, 0, 5);
} else {
    $filtros['horario'] = 'Todos';
}

// Filtro de veículo
if ($idVeiculo) {
    $sqlVeiculo = "SELECT placa, modelo FROM veiculo WHERE id_veiculo = $idVeiculo";
    $resultVeiculo = $db->query($sqlVeiculo);
    if ($resultVeiculo && $veiculo = $resultVeiculo->fetch_assoc()) {
        $filtros['veiculo'] = $veiculo['placa'] . ' - ' . $veiculo['modelo'];
    } else {
        $filtros['veiculo'] = 'Todos';
    }
} else {
    $filtros['veiculo'] = 'Todos';
}

// Filtro de condutor
if ($idCondutor) {
    $sqlCondutor = "SELECT nome FROM condutor WHERE id_condutor = $idCondutor";
    $resultCondutor = $db->query($sqlCondutor);
    if ($resultCondutor && $condutor = $resultCondutor->fetch_assoc()) {
        $filtros['condutor'] = $condutor['nome'];
    } else {
        $filtros['condutor'] = 'Todos';
    }
} else {
    $filtros['condutor'] = 'Todos';
}

// Query principal (sem limite para exportar tudo)
$sql = "SELECT cc.*,
               v.placa,
               v.modelo as veiculo_modelo,
               c.nome as condutor_nome,
               p.descricao as produto_descricao,
               f.razao_social as fornecedor_nome
        FROM consumo_combustivel cc
        LEFT JOIN veiculo v ON cc.id_veiculo = v.id_veiculo
        LEFT JOIN condutor c ON cc.id_condutor = c.id_condutor
        LEFT JOIN produto p ON cc.id_produto = p.id_produto
        LEFT JOIN fornecedor f ON cc.id_fornecedor = f.id_fornecedor
        WHERE $whereClause
        ORDER BY cc.data DESC, cc.hora DESC";

$result = $db->query($sql);

if (!$result) {
    die('Erro ao buscar dados: ' . $db->getError());
}

$consumos = [];
while ($row = $result->fetch_assoc()) {
    $consumos[] = $row;
}

// Estatísticas
$sqlStats = "SELECT 
                COUNT(*) as total_abastecimentos,
                SUM(cc.litragem) as total_litros,
                SUM(cc.valor_total) as total_valor,
                AVG(cc.valor_total) as media_valor
             FROM consumo_combustivel cc
             WHERE $whereClause";
$resultStats = $db->query($sqlStats);
$stats = $resultStats->fetch_assoc();

// Exportar
if ($exportType === 'excel') {
    exportarExcel($consumos, $stats);
} else if ($exportType === 'pdf') {
    exportarPDF($consumos, $stats, $filtros);
}

function exportarExcel($consumos, $stats) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=relatorio_consumo_' . date('Y-m-d_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalhos de estatísticas
    fputcsv($output, ['RESUMO DO RELATÓRIO'], ';');
    fputcsv($output, ['Total de Abastecimentos', $stats['total_abastecimentos']], ';');
    fputcsv($output, ['Total de Litros', number_format($stats['total_litros'], 2, ',', '.')], ';');
    fputcsv($output, ['Valor Total', 'R$ ' . number_format($stats['total_valor'], 2, ',', '.')], ';');
    fputcsv($output, ['Valor Médio', 'R$ ' . number_format($stats['media_valor'], 2, ',', '.')], ';');
    fputcsv($output, [], ';');
    
    // Cabeçalho da tabela
    fputcsv($output, [
        'Data',
        'Hora',
        'Veículo',
        'Modelo',
        'Condutor',
        'Combustível',
        'Litros',
        'Valor Unitário',
        'Valor Total',
        'KM Veículo',
        'Fornecedor'
    ], ';');
    
    // Dados
    foreach ($consumos as $c) {
        fputcsv($output, [
            $c['data'] ?? '-',
            $c['hora'] ?? '-',
            $c['placa'] ?? '-',
            $c['veiculo_modelo'] ?? '-',
            $c['condutor_nome'] ?? '-',
            $c['produto_descricao'] ?? '-',
            number_format($c['litragem'] ?? 0, 2, ',', '.'),
            number_format($c['valor_unitario'] ?? 0, 2, ',', '.'),
            number_format($c['valor_total'] ?? 0, 2, ',', '.'),
            $c['km_veiculo'] ?? '-',
            $c['fornecedor_nome'] ?? '-'
        ], ';');
    }
    
    fclose($output);
    exit;
}

function exportarPDF($consumos, $stats, $filtros) {
    require_once '../vendor/autoload.php';
    
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
    
    // Criar classe customizada para cabeçalho e rodapé
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
            
            // Nome do cliente
            if ($hasLogo) {
                $this->SetXY($logoX + $logoSize + 5, $logoY + 1.5);
                $this->Cell(0, 5, $this->clientName, 0, 1, 'L');
                
                $this->SetX($logoX + $logoSize + 5);
                $this->SetFont('helvetica', 'B', 12);
                $this->SetTextColor(0, 0, 0);
                $this->Cell(0, 5, 'Relatório de Consumo de Combustível', 0, 1, 'L');
            } else {
                $this->Cell(0, 8, $this->clientName, 0, 1, 'C');
                $this->SetFont('helvetica', 'B', 12);
                $this->Cell(0, 6, 'Relatório de Consumo de Combustível', 0, 1, 'C');
            }
            
            // Calcular altura dos filtros/estatísticas
            $alturaStats = 16; // 4 linhas de estatísticas
            $minY = $hasLogo ? ($logoY + $logoSize + 2) : ($this->GetY() + 2);
            $statsStartY = $hasLogo ? $logoY + 1.5 : 10;
            $statsEndY = $statsStartY + $alturaStats;
            $separatorY = ($statsEndY > $minY) ? $statsEndY : $minY;
            
            if ($hasLogo) {
                $this->SetY($logoY + $logoSize);
            }
            
            // Exibir filtros
            $this->SetXY(200, $statsStartY);
            $this->SetFont('helvetica', '', 8);
            $this->SetTextColor(80, 80, 80);
            
            $this->Cell(0, 4, 'Data: ' . ($this->filtros['data'] ?? 'Todos'), 0, 1, 'R');
            $this->SetX(200);
            $this->Cell(0, 4, 'Horário: ' . ($this->filtros['horario'] ?? 'Todos'), 0, 1, 'R');
            $this->SetX(200);
            $this->Cell(0, 4, 'Veículo: ' . ($this->filtros['veiculo'] ?? 'Todos'), 0, 1, 'R');
            $this->SetX(200);
            $this->Cell(0, 4, 'Condutor: ' . ($this->filtros['condutor'] ?? 'Todos'), 0, 1, 'R');
            
            $this->SetY($separatorY);
            $this->Ln(2);
            $this->SetDrawColor(74, 144, 226);
            $this->SetLineWidth(0.5);
            $this->Line(10, $this->GetY(), 287, $this->GetY());
            $this->Ln(3);
        }
        
        public function Footer() {
            $this->SetY(-12);
            $this->SetFont('helvetica', 'I', 8);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages() . ' - Gerado em ' . date('d/m/Y H:i:s'), 0, 0, 'C');
        }
    }
    
    // Configurar TCPDF
    $pdf = new MYPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setClientInfo($nomeCliente, $logoPath, $filtros);
    
    $pdf->SetCreator('Sistema de Abastecimento');
    $pdf->SetAuthor('Relatório Automatizado');
    $pdf->SetTitle('Relatório de Consumo de Combustível');
    $pdf->SetSubject('Relatório de Consumo');
    
    $pdf->SetMargins(10, 30, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);
    
    // Cabeçalho da tabela
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(102, 126, 234);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell(26, 7, 'Data/Hora', 1, 0, 'C', true);
    $pdf->Cell(22, 7, 'Placa', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Modelo', 1, 0, 'C', true);
    $pdf->Cell(68, 7, 'Condutor', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Combustível', 1, 0, 'C', true);
    $pdf->Cell(16, 7, 'Litros', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Valor Unit.', 1, 0, 'C', true);
    $pdf->Cell(22, 7, 'Valor Total', 1, 0, 'C', true);
    $pdf->Cell(18, 7, 'KM', 1, 1, 'C', true);
    
    // Dados
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);
    $fill = false;
    
    foreach ($consumos as $c) {
        $dataHora = ($c['data'] ?? '-') . ' ' . substr($c['hora'] ?? '', 0, 5);
        $placa = $c['placa'] ?? '-';
        $modelo = $c['veiculo_modelo'] ?? '-';
        $condutor = $c['condutor_nome'] ?? '-';
        $combustivel = strlen($c['produto_descricao'] ?? '-') > 20 ? substr($c['produto_descricao'], 0, 17) . '...' : ($c['produto_descricao'] ?? '-');
        
        // Calcular altura necessária para a linha
        $modeloHeight = $pdf->getStringHeight(60, $modelo);
        $condutorHeight = $pdf->getStringHeight(60, $condutor);
        $rowHeight = max(6, $modeloHeight, $condutorHeight);
        
        // Verificar se precisa quebrar página
        if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - 20) {
            $pdf->AddPage();
            // Recriar cabeçalho da tabela
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(102, 126, 234);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(26, 7, 'Data/Hora', 1, 0, 'C', true);
            $pdf->Cell(22, 7, 'Placa', 1, 0, 'C', true);
            $pdf->Cell(60, 7, 'Modelo', 1, 0, 'C', true);
            $pdf->Cell(68, 7, 'Condutor', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Combustível', 1, 0, 'C', true);
            $pdf->Cell(16, 7, 'Litros', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Valor Unit.', 1, 0, 'C', true);
            $pdf->Cell(22, 7, 'Valor Total', 1, 0, 'C', true);
            $pdf->Cell(18, 7, 'KM', 1, 1, 'C', true);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetTextColor(0, 0, 0);
        }
        
        $pdf->SetFillColor(248, 249, 250);
        $pdf->MultiCell(26, $rowHeight, $dataHora, 1, 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
        $pdf->MultiCell(22, $rowHeight, $placa, 1, 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
        $pdf->MultiCell(60, $rowHeight, $modelo, 1, 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
        $pdf->MultiCell(68, $rowHeight, $condutor, 1, 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
        $pdf->MultiCell(25, $rowHeight, $combustivel, 1, 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
        $pdf->MultiCell(16, $rowHeight, number_format($c['litragem'] ?? 0, 3, ',', '.'), 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
        $pdf->MultiCell(20, $rowHeight, 'R$ ' . number_format($c['valor_unitario'] ?? 0, 3, ',', '.'), 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
        $pdf->MultiCell(22, $rowHeight, 'R$ ' . number_format($c['valor_total'] ?? 0, 2, ',', '.'), 1, 'R', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
        $pdf->MultiCell(18, $rowHeight, $c['km_veiculo'] ?? '-', 1, 'R', $fill, 1, '', '', true, 0, false, true, $rowHeight, 'M');
        
        $fill = !$fill;
    }
    
    // Resumo de Estatísticas após a tabela
    // Verificar se há espaço suficiente para o título + tabela (5 + 8 + 2 + 8 + 10 = 33mm)
    $alturaResumo = 33;
    if ($pdf->GetY() + $alturaResumo > $pdf->getPageHeight() - 20) {
        $pdf->AddPage();
    }
    
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(74, 144, 226);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 8, 'Resumo do Relatório', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(248, 249, 250);
    $pdf->SetTextColor(0, 0, 0);
    
    $colWidth = 69.5; // 277mm / 4 colunas
    $pdf->Cell($colWidth, 8, 'Total de Abastecimentos', 1, 0, 'C', true);
    $pdf->Cell($colWidth, 8, 'Total de Litros', 1, 0, 'C', true);
    $pdf->Cell($colWidth, 8, 'Valor Total', 1, 0, 'C', true);
    $pdf->Cell($colWidth, 8, 'Valor Médio', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(74, 144, 226);
    $pdf->Cell($colWidth, 10, number_format($stats['total_abastecimentos'], 0, ',', '.'), 1, 0, 'C');
    $pdf->Cell($colWidth, 10, number_format($stats['total_litros'], 2, ',', '.') . ' L', 1, 0, 'C');
    $pdf->Cell($colWidth, 10, 'R$ ' . number_format($stats['total_valor'], 2, ',', '.'), 1, 0, 'C');
    $pdf->Cell($colWidth, 10, 'R$ ' . number_format($stats['media_valor'], 2, ',', '.'), 1, 1, 'C');
    
    try {
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $filename = 'relatorio_consumo_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'I');
    } catch (Exception $e) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code(500);
        die('Erro ao gerar PDF: ' . $e->getMessage());
    }
    exit;
}
?>
