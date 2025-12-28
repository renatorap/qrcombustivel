<?php
/**
 * Script de Teste - Menu Hierárquico com Permissões
 * 
 * Testa o menu hierárquico com diferentes usuários/grupos
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/access_control.php';

echo "==============================================\n";
echo "TESTE: Menu Hierárquico - Permissões RBAC\n";
echo "==============================================\n\n";

// Função para exibir menu de forma hierárquica
function displayMenu($menu, $indent = 0) {
    $prefix = str_repeat('   ', $indent);
    
    foreach ($menu as $item) {
        $arrow = $item['expandido'] ? '▼' : '→';
        echo $prefix . "$arrow {$item['nome']} ({$item['codigo']})";
        
        if (!$item['expandido'] && $item['url']) {
            echo " → {$item['url']}";
        }
        echo "\n";
        
        if (isset($item['submenus']) && count($item['submenus']) > 0) {
            displayMenu($item['submenus'], $indent + 1);
        }
        
        if (isset($item['subsubmenus']) && count($item['subsubmenus']) > 0) {
            displayMenu($item['subsubmenus'], $indent + 1);
        }
    }
}

// Função para contar itens do menu
function countMenuItems($menu) {
    $count = count($menu);
    
    foreach ($menu as $item) {
        if (isset($item['submenus'])) {
            $count += countMenuItems($item['submenus']);
        }
        if (isset($item['subsubmenus'])) {
            $count += countMenuItems($item['subsubmenus']);
        }
    }
    
    return $count;
}

// Testar com usuário Administrador (ID = 1)
echo "1. TESTE COM USUÁRIO ADMINISTRADOR (ID: 1)\n";
echo "   (Deve ver todos os itens do menu)\n\n";

$adminAC = new AccessControl(1);
$adminMenu = $adminAC->getMenuHierarquico();

displayMenu($adminMenu);

$adminCount = countMenuItems($adminMenu);
echo "\n   Total de itens visíveis: $adminCount\n";

// Separador
echo "\n" . str_repeat('-', 50) . "\n\n";

// Buscar outro grupo de usuário (ex: Operador Prefeitura - ID = 2)
$db = new Database();
$conn = $db->getConnection();

$sqlUsuario = "SELECT u.id, u.nome, g.nome as grupo_nome 
               FROM usuarios u 
               LEFT JOIN grupos g ON u.grupo_id = g.id 
               WHERE u.id != 1 AND u.ativo = 1 
               LIMIT 1";

$result = $conn->query($sqlUsuario);

if ($result && $row = $result->fetch_assoc()) {
    $userId = $row['id'];
    $userName = $row['nome'];
    $userGroup = $row['grupo_nome'] ?? 'Sem Grupo';
    
    echo "2. TESTE COM USUÁRIO COMUM\n";
    echo "   Usuário: $userName (ID: $userId)\n";
    echo "   Grupo: $userGroup\n";
    echo "   (Menu filtrado por permissões do grupo)\n\n";
    
    $userAC = new AccessControl($userId);
    $userMenu = $userAC->getMenuHierarquico();
    
    displayMenu($userMenu);
    
    $userCount = countMenuItems($userMenu);
    echo "\n   Total de itens visíveis: $userCount\n";
    
    // Comparar
    $diff = $adminCount - $userCount;
    $percentage = ($userCount / $adminCount) * 100;
    
    echo "\n" . str_repeat('-', 50) . "\n";
    echo "\n3. COMPARAÇÃO:\n";
    echo "   - Administrador vê: $adminCount itens\n";
    echo "   - $userName vê: $userCount itens\n";
    echo "   - Diferença: $diff itens\n";
    echo "   - Acesso: " . number_format($percentage, 1) . "% do total\n";
} else {
    echo "2. TESTE COM USUÁRIO COMUM\n";
    echo "   Nenhum outro usuário ativo encontrado no banco.\n";
    echo "   Crie um usuário com grupo 'Operador Prefeitura' para testar permissões.\n";
}

echo "\n==============================================\n";
echo "TESTE CONCLUÍDO\n";
echo "==============================================\n";
