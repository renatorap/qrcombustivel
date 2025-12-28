<?php
if(session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Incluir o arquivo com a conexão com banco de dados
include_once 'conexao.php';

// Incluir o arquivo para validar token
include_once 'validar_token.php';

// Log para debug
$debug_log = [];
$debug_log[] = "Início do processamento";

// Validar token
if(!validarToken()){
    echo json_encode([
        'success' => false,
        'message' => 'Sessão expirada. Faça login novamente.'
    ]);
    exit();
}

$debug_log[] = "Token válido";
$debug_log[] = "id_empr (id_cliente) da sessão: " . ($_SESSION['id_empr'] ?? 'NOT SET');

// Receber o QR Code
$qr_code = filter_input(INPUT_POST, 'qr_code', FILTER_SANITIZE_STRING);

$debug_log[] = "QR Code recebido: " . $qr_code;

if(empty($qr_code)) {
    echo json_encode([
        'success' => false,
        'message' => 'QR Code inválido.',
        'debug' => $debug_log
    ]);
    exit();
}

try {
    $veiculo = null;
    $metodo_identificacao = '';
    
    // Tentar identificar pelo QR Code ativo primeiro
    $debug_log[] = "Tentando buscar por QR Code ativo: " . $qr_code;
    
    $query = "SELECT 
                v.id_veiculo,
                v.placa
              FROM veiculo v
              INNER JOIN veiculo_qrcode vq ON vq.id_veiculo = v.id_veiculo 
                                          AND vq.id_cliente = :id_cliente
                                          AND vq.codigo_unico = :codigo_unico
                                          AND vq.id_situacao = 1
                                          AND (vq.fim_vigencia IS NULL OR vq.fim_vigencia >= NOW())
              WHERE v.id_cliente = :id_cliente2 
              AND v.id_situacao = 1
              LIMIT 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt->bindParam(':id_cliente2', $_SESSION['id_empr'], PDO::PARAM_INT);
    $stmt->bindParam(':codigo_unico', $qr_code, PDO::PARAM_STR);
    $stmt->execute();
    
    $debug_log[] = "Busca por QR Code retornou " . $stmt->rowCount() . " resultado(s)";
    
    if($stmt->rowCount() > 0) {
        $veiculo = $stmt->fetch(PDO::FETCH_ASSOC);
        $metodo_identificacao = 'QR Code';
        $debug_log[] = "Veículo encontrado por QR Code: " . $veiculo['placa'];
    }
    
    // Se não encontrou pelo QR Code, tentar pela placa
    if(!$veiculo) {
        // Limpar o código recebido (remover caracteres especiais e converter para maiúscula)
        $placa_limpa = strtoupper(preg_replace('/[^A-Z0-9]/', '', $qr_code));
        $debug_log[] = "Tentando buscar por placa: " . $placa_limpa;
        
        // Buscar por placa - veículo ativo mesmo sem QR Code
        $query = "SELECT 
                    v.id_veiculo,
                    v.placa
                  FROM veiculo v
                  WHERE v.id_cliente = :id_cliente 
                  AND v.id_situacao = 1
                  AND REPLACE(REPLACE(UPPER(v.placa), '-', ''), ' ', '') = :placa
                  LIMIT 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_cliente', $_SESSION['id_empr'], PDO::PARAM_INT);
        $stmt->bindParam(':placa', $placa_limpa, PDO::PARAM_STR);
        $stmt->execute();
        
        $debug_log[] = "Busca por placa retornou " . $stmt->rowCount() . " resultado(s)";
        
        if($stmt->rowCount() > 0) {
            $veiculo = $stmt->fetch(PDO::FETCH_ASSOC);
            $metodo_identificacao = 'Placa';
            $debug_log[] = "Veículo encontrado por placa: " . $veiculo['placa'];
        }
    }
    
    // Retornar resultado
    if($veiculo) {
        $debug_log[] = "SUCESSO: Veículo encontrado";
        echo json_encode([
            'success' => true,
            'id_veiculo' => $veiculo['id_veiculo'],
            'placa_veiculo' => $veiculo['placa'],
            'metodo' => $metodo_identificacao,
            'debug' => $debug_log
        ]);
    } else {
        $debug_log[] = "FALHA: Veículo não encontrado";
        echo json_encode([
            'success' => false,
            'message' => 'Veículo não encontrado ou inativo. Verifique o QR Code ou placa.',
            'debug' => $debug_log
        ]);
    }
    
} catch(PDOException $e) {
    $debug_log[] = "ERRO PDO: " . $e->getMessage();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar veículo: ' . $e->getMessage(),
        'debug' => $debug_log
    ]);
}
?>
