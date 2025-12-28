<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/access_control.php';

// Simular usuário logado (Operador Prefeitura - ID 2)
$_SESSION['userId'] = 2;
$_SESSION['token'] = 'test';

echo "<h2>Teste de Permissões - Usuário ID: 2 (Operador Prefeitura)</h2>\n\n";

$accessControl = new AccessControl($_SESSION['userId']);

// Testar clientes
echo "<h3>CLIENTES:</h3>\n";
echo "pode_acessar: " . ($accessControl->verificarPermissao('clientes', 'acessar') ? 'SIM' : 'NÃO') . "\n";
echo "pode_criar: " . ($accessControl->verificarPermissao('clientes', 'criar') ? 'SIM' : 'NÃO') . "\n";
echo "pode_visualizar: " . ($accessControl->verificarPermissao('clientes', 'visualizar') ? 'SIM' : 'NÃO') . "\n";
echo "pode_editar: " . ($accessControl->verificarPermissao('clientes', 'editar') ? 'SIM' : 'NÃO') . "\n";
echo "pode_excluir: " . ($accessControl->verificarPermissao('clientes', 'excluir') ? 'SIM' : 'NÃO') . "\n\n";

// Testar veículos
echo "<h3>VEÍCULOS:</h3>\n";
echo "pode_acessar: " . ($accessControl->verificarPermissao('veiculos', 'acessar') ? 'SIM' : 'NÃO') . "\n";
echo "pode_criar: " . ($accessControl->verificarPermissao('veiculos', 'criar') ? 'SIM' : 'NÃO') . "\n";
echo "pode_visualizar: " . ($accessControl->verificarPermissao('veiculos', 'visualizar') ? 'SIM' : 'NÃO') . "\n";
echo "pode_editar: " . ($accessControl->verificarPermissao('veiculos', 'editar') ? 'SIM' : 'NÃO') . "\n";
echo "pode_excluir: " . ($accessControl->verificarPermissao('veiculos', 'excluir') ? 'SIM' : 'NÃO') . "\n\n";

// Verificar diretamente no banco
echo "<h3>VERIFICAÇÃO DIRETA NO BANCO:</h3>\n";
$db = new Database();
$db->connect();

$sql = "SELECT usuario_id, codigo, pode_acessar, pode_criar, pode_visualizar, pode_editar, pode_excluir 
        FROM v_permissoes_efetivas 
        WHERE usuario_id = 2 AND codigo IN ('clientes', 'veiculos')";
$result = $db->query($sql);

echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
