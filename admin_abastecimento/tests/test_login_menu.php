
<?php
session_start();
require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['userId'])) {
    die("Faça login primeiro!");
}

$userId = $_SESSION['userId'];
$db = new Database();
$db->connect();

echo "<pre>";
echo "=== ANÁLISE COMPLETA DO MENU ===\n\n";

// 1. Listar todos os módulos
$modulos = $db->query("SELECT * FROM modulo WHERE ativo = 1 ORDER BY ordem");

while ($mod = $modulos->fetch_assoc()) {
    echo "📁 MÓDULO: {$mod['nome']}\n";
    echo "   ID: {$mod['id']}, Código: {$mod['codigo']}\n";
    echo "   Expandido: " . ($mod['expandido'] ? 'SIM' : 'NÃO') . "\n";
    echo "   Aplicacao_ID: " . ($mod['aplicacao_id'] ?? 'NULL') . "\n";
    
    // Se não é expandido, testar permissão
    if (!$mod['expandido'] && $mod['aplicacao_id']) {
        $perm = $db->query("SELECT pode_acessar FROM v_permissoes_efetivas 
                           WHERE usuario_id = $userId AND aplicacao_id = {$mod['aplicacao_id']}")->fetch_assoc();
        $temPermissao = $perm['pode_acessar'] ?? 0;
        echo "   ✓ Permissão: " . ($temPermissao ? 'SIM (aparecerá)' : 'NÃO (não aparecerá)') . "\n";
    }
    
    // Se é expandido, buscar submenus
    if ($mod['expandido']) {
        $submenus = $db->query("SELECT * FROM submenu WHERE modulo_id = {$mod['id']} AND ativo = 1 ORDER BY ordem");
        $countSub = $submenus->num_rows;
        echo "   Submenus encontrados: $countSub\n";
        
        $submenusComPermissao = 0;
        
        while ($sub = $submenus->fetch_assoc()) {
            echo "\n   ├─ SUBMENU: {$sub['nome']}\n";
            echo "      ID: {$sub['id']}, Código: {$sub['codigo']}\n";
            echo "      Expandido: " . ($sub['expandido'] ? 'SIM' : 'NÃO') . "\n";
            echo "      Aplicacao_ID: " . ($sub['aplicacao_id'] ?? 'NULL') . "\n";
            
            $subTemPermissao = false;
            
            // Se não é expandido, testar permissão
            if (!$sub['expandido'] && $sub['aplicacao_id']) {
                $perm = $db->query("SELECT pode_acessar FROM v_permissoes_efetivas 
                                   WHERE usuario_id = $userId AND aplicacao_id = {$sub['aplicacao_id']}")->fetch_assoc();
                $subTemPermissao = $perm['pode_acessar'] ?? 0;
                echo "      Permissão: " . ($subTemPermissao ? 'SIM' : 'NÃO') . "\n";
                if ($subTemPermissao) $submenusComPermissao++;
            }
            
            // Se é expandido, buscar sub-submenus
            if ($sub['expandido']) {
                $subsubmenus = $db->query("SELECT * FROM subsubmenu WHERE submenu_id = {$sub['id']} AND ativo = 1 ORDER BY ordem");
                $countSubSub = $subsubmenus->num_rows;
                echo "      Sub-submenus encontrados: $countSubSub\n";
                
                $subsubmenusComPermissao = 0;
                
                while ($subsub = $subsubmenus->fetch_assoc()) {
                    echo "\n      └─ SUB-SUBMENU: {$subsub['nome']}\n";
                    echo "         ID: {$subsub['id']}, Código: {$subsub['codigo']}\n";
                    echo "         Aplicacao_ID: {$subsub['aplicacao_id']}\n";
                    
                    $perm = $db->query("SELECT pode_acessar FROM v_permissoes_efetivas 
                                       WHERE usuario_id = $userId AND aplicacao_id = {$subsub['aplicacao_id']}")->fetch_assoc();
                    $subsubTemPermissao = $perm['pode_acessar'] ?? 0;
                    echo "         Permissão: " . ($subsubTemPermissao ? 'SIM' : 'NÃO') . "\n";
                    
                    if ($subsubTemPermissao) $subsubmenusComPermissao++;
                }
                
                echo "\n      ➤ Sub-submenus COM permissão: $subsubmenusComPermissao\n";
                if ($subsubmenusComPermissao > 0) {
                    $submenusComPermissao++;
                    echo "      ✓ ESTE SUBMENU APARECERÁ (tem sub-submenus com permissão)\n";
                } else {
                    echo "      ✗ ESTE SUBMENU NÃO APARECERÁ (nenhum sub-submenu com permissão)\n";
                }
            }
        }
        
        echo "\n   ➤ Submenus COM permissão: $submenusComPermissao\n";
        if ($submenusComPermissao > 0) {
            echo "   ✓ ESTE MÓDULO APARECERÁ\n";
        } else {
            echo "   ✗ ESTE MÓDULO NÃO APARECERÁ (nenhum submenu com permissão)\n";
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "Se nenhum módulo mostrou '✓ APARECERÁ', então o menu ficará vazio!\n";
echo "Causa: Falta de permissões ou aplicacao_id não configurados nos submenus/sub-submenus.\n";

$db->close();
echo "</pre>";
?>