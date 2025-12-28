<?php
/**
 * Teste da API Menu Manager
 */

// Iniciar sessão
session_start();
$_SESSION['userId'] = 1; // Simular usuário administrador

// Simular requisição
$_GET['action'] = 'list';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capturar output
ob_start();
require __DIR__ . '/../api/menu_manager.php';
$output = ob_get_clean();

// Exibir resultado
echo "=== TESTE API MENU MANAGER ===\n\n";
echo "Output:\n";
echo $output . "\n\n";

// Decodificar JSON
$data = json_decode($output, true);

if ($data && isset($data['success'])) {
    if ($data['success']) {
        echo "✓ API funcionando corretamente!\n";
        echo "✓ Total de módulos retornados: " . count($data['data']) . "\n";
        
        if (count($data['data']) > 0) {
            echo "\nPrimeiro módulo:\n";
            print_r($data['data'][0]);
        }
    } else {
        echo "✗ API retornou erro: " . $data['message'] . "\n";
    }
} else {
    echo "✗ Resposta inválida da API\n";
    echo "Output bruto: " . $output . "\n";
}
