<?php
/**
 * Script de Teste - Menu Hierárquico 3 Níveis
 * 
 * Testa o método getMenuHierarquico() e exibe estrutura JSON
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/access_control.php';

// Simular sessão de usuário Administrador (ID = 1)
$userId = 1;

echo "==============================================\n";
echo "TESTE: Menu Hierárquico de 3 Níveis\n";
echo "==============================================\n\n";

// Inicializar AccessControl
$accessControl = new AccessControl($userId);

echo "1. Testando getMenuHierarquico() para usuário ID: $userId\n\n";

// Buscar menu hierárquico
$menuHierarquico = $accessControl->getMenuHierarquico();

echo "2. Estrutura do Menu (JSON formatado):\n\n";
echo json_encode($menuHierarquico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "3. Análise Detalhada:\n\n";

$totalModulos = count($menuHierarquico);
echo "   - Total de Módulos (Nível 1): $totalModulos\n";

$totalSubmenus = 0;
$totalSubsubmenus = 0;

foreach ($menuHierarquico as $modulo) {
    echo "\n   Módulo: {$modulo['nome']} ({$modulo['codigo']})\n";
    echo "      - Tipo: " . ($modulo['expandido'] ? 'Expansível' : 'Link Direto') . "\n";
    
    if ($modulo['expandido']) {
        $countSubmenus = count($modulo['submenus']);
        $totalSubmenus += $countSubmenus;
        echo "      - Submenus: $countSubmenus\n";
        
        foreach ($modulo['submenus'] as $submenu) {
            echo "         └─ {$submenu['nome']} ({$submenu['codigo']}) - ";
            echo ($submenu['expandido'] ? 'Expansível' : 'Link: ' . $submenu['url']) . "\n";
            
            if ($submenu['expandido']) {
                $countSubsubmenus = count($submenu['subsubmenus']);
                $totalSubsubmenus += $countSubsubmenus;
                echo "            Sub-submenus: $countSubsubmenus\n";
                
                foreach ($submenu['subsubmenus'] as $subsubmenu) {
                    echo "               └─ {$subsubmenu['nome']} - Link: {$subsubmenu['url']}\n";
                }
            }
        }
    } else {
        echo "      - URL: {$modulo['url']}\n";
    }
}

echo "\n4. Resumo:\n";
echo "   - Módulos: $totalModulos\n";
echo "   - Submenus: $totalSubmenus\n";
echo "   - Sub-submenus: $totalSubsubmenus\n";
echo "   - Total de Itens: " . ($totalModulos + $totalSubmenus + $totalSubsubmenus) . "\n";

echo "\n==============================================\n";
echo "TESTE CONCLUÍDO\n";
echo "==============================================\n";
