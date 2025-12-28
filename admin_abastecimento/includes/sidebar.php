<?php
// Menu hierárquico de 3 níveis baseado em permissões
require_once __DIR__ . '/../config/access_control.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$userId = $_SESSION['userId'] ?? null;

// Inicializar controle de acesso
$accessControl = new AccessControl($userId);

// Obter menu hierárquico completo (3 níveis) com permissões
$menuHierarquico = $accessControl->getMenuHierarquico();
?>

<aside class="sidebar">
    <ul class="sidebar-menu">
        <?php foreach ($menuHierarquico as $modulo): ?>
            <?php if ($modulo['expandido']): ?>
                <!-- Módulo expansível (Nível 1) -->
                <li class="sidebar-item has-submenu" data-module="<?php echo $modulo['codigo']; ?>">
                    <a href="javascript:void(0)" class="sidebar-link" onclick="toggleSubmenu(this)">
                        <i class="fas <?php echo $modulo['icone']; ?>"></i>
                        <span><?php echo $modulo['nome']; ?></span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <?php foreach ($modulo['submenus'] as $submenu): ?>
                            <?php if ($submenu['expandido']): ?>
                                <!-- Submenu expansível (Nível 2) -->
                                <li class="submenu-item has-subsubmenu" data-submenu="<?php echo $submenu['codigo']; ?>">
                                    <a href="javascript:void(0)" class="submenu-link" onclick="toggleSubsubmenu(this)">
                                        <i class="fas <?php echo $submenu['icone']; ?>"></i>
                                        <span><?php echo $submenu['nome']; ?></span>
                                        <i class="fas fa-chevron-down subsubmenu-arrow"></i>
                                    </a>
                                    <ul class="subsubmenu">
                                        <?php foreach ($submenu['subsubmenus'] as $subsubmenu): ?>
                                            <!-- Sub-submenu link direto (Nível 3) -->
                                            <li class="subsubmenu-item <?php echo ($currentPage == $subsubmenu['url']) ? 'active' : ''; ?>">
                                                <a href="<?php echo htmlspecialchars($subsubmenu['url']); ?>" class="subsubmenu-link">
                                                    <i class="fas <?php echo htmlspecialchars($subsubmenu['icone']); ?>"></i>
                                                    <span><?php echo htmlspecialchars($subsubmenu['nome']); ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <!-- Submenu link direto (Nível 2) -->
                                <li class="submenu-item <?php echo ($currentPage == $submenu['url']) ? 'active' : ''; ?>">
                                    <a href="<?php echo htmlspecialchars($submenu['url']); ?>" class="submenu-link">
                                        <i class="fas <?php echo htmlspecialchars($submenu['icone']); ?>"></i>
                                        <span><?php echo htmlspecialchars($submenu['nome']); ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php else: ?>
                <!-- Módulo link direto (Nível 1) -->
                <li class="sidebar-item <?php echo ($currentPage == $modulo['url']) ? 'active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($modulo['url']); ?>" class="sidebar-link" title="<?php echo htmlspecialchars($modulo['nome']); ?>">
                        <i class="fas <?php echo htmlspecialchars($modulo['icone']); ?>"></i>
                        <span><?php echo htmlspecialchars($modulo['nome']); ?></span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <li class="sidebar-item" style="border-top: 1px solid #e9ecef; margin-top: 12px; padding-top: 8px;">
            <a href="../api/logout.php" class="sidebar-link" title="Sair">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </li>
    </ul>
</aside>

<script>
// Toggle submenu (Nível 2)
function toggleSubmenu(element) {
    const parent = element.parentElement;
    const submenu = parent.querySelector('.submenu');
    const arrow = element.querySelector('.submenu-arrow');
    
    // Fechar outros submenus (Nível 1)
    document.querySelectorAll('.sidebar-item.has-submenu').forEach(item => {
        if (item !== parent && item.classList.contains('open')) {
            item.classList.remove('open');
            item.querySelector('.submenu').style.display = 'none';
            item.querySelector('.submenu-arrow').style.transform = 'rotate(0deg)';
        }
    });
    
    // Toggle submenu atual
    if (parent.classList.contains('open')) {
        parent.classList.remove('open');
        submenu.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    } else {
        parent.classList.add('open');
        submenu.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    }
}

// Toggle sub-submenu (Nível 3)
function toggleSubsubmenu(element) {
    const parent = element.parentElement;
    const subsubmenu = parent.querySelector('.subsubmenu');
    const arrow = element.querySelector('.subsubmenu-arrow');
    
    // Fechar outros sub-submenus dentro do mesmo submenu
    const parentSubmenu = parent.closest('.submenu');
    if (parentSubmenu) {
        parentSubmenu.querySelectorAll('.submenu-item.has-subsubmenu').forEach(item => {
            if (item !== parent && item.classList.contains('open')) {
                item.classList.remove('open');
                item.querySelector('.subsubmenu').style.display = 'none';
                item.querySelector('.subsubmenu-arrow').style.transform = 'rotate(0deg)';
            }
        });
    }
    
    // Toggle sub-submenu atual
    if (parent.classList.contains('open')) {
        parent.classList.remove('open');
        subsubmenu.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    } else {
        parent.classList.add('open');
        subsubmenu.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    }
}

// Manter hierarquia aberta se página ativa estiver em qualquer nível
document.addEventListener('DOMContentLoaded', function() {
    // Verificar sub-submenu ativo (Nível 3)
    const activeSubsubmenuItem = document.querySelector('.subsubmenu-item.active');
    if (activeSubsubmenuItem) {
        // Abrir sub-submenu pai
        const parentSubsubmenu = activeSubsubmenuItem.closest('.submenu-item.has-subsubmenu');
        if (parentSubsubmenu) {
            parentSubsubmenu.classList.add('open');
            parentSubsubmenu.querySelector('.subsubmenu').style.display = 'block';
            parentSubsubmenu.querySelector('.subsubmenu-arrow').style.transform = 'rotate(180deg)';
        }
        
        // Abrir submenu pai (Nível 2)
        const parentSubmenu = activeSubsubmenuItem.closest('.sidebar-item.has-submenu');
        if (parentSubmenu) {
            parentSubmenu.classList.add('open');
            parentSubmenu.querySelector('.submenu').style.display = 'block';
            parentSubmenu.querySelector('.submenu-arrow').style.transform = 'rotate(180deg)';
        }
    } else {
        // Verificar submenu ativo (Nível 2)
        const activeSubmenuItem = document.querySelector('.submenu-item.active');
        if (activeSubmenuItem) {
            const parentModule = activeSubmenuItem.closest('.sidebar-item.has-submenu');
            if (parentModule) {
                parentModule.classList.add('open');
                parentModule.querySelector('.submenu').style.display = 'block';
                parentModule.querySelector('.submenu-arrow').style.transform = 'rotate(180deg)';
            }
        }
    }
});
</script>