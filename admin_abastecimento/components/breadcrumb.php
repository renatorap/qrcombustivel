<?php
/**
 * Componente de Breadcrumbs Dinâmico
 * Sincronizado com a hierarquia do menu
 */

class Breadcrumb {
    private $accessControl;
    private $currentPage;
    private $menuHierarquico;
    
    public function __construct($accessControl, $currentPage) {
        $this->accessControl = $accessControl;
        $this->currentPage = $currentPage;
        $this->menuHierarquico = $accessControl->getMenuHierarquico();
    }
    
    /**
     * Gera o HTML do breadcrumb
     */
    public function render() {
        $trail = $this->buildTrail();
        
        if (empty($trail)) {
            return '';
        }
        
        $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
        $html .= '<li class="breadcrumb-item"><a href="dashboard.php"><i class="fas fa-home"></i> Início</a></li>';
        
        foreach ($trail as $index => $item) {
            $isLast = ($index === count($trail) - 1);
            
            if ($isLast) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">';
                $html .= '<i class="fas ' . htmlspecialchars($item['icone']) . '"></i> ';
                $html .= htmlspecialchars($item['nome']);
                $html .= '</li>';
            } else {
                $html .= '<li class="breadcrumb-item">';
                if (!empty($item['url'])) {
                    $html .= '<a href="' . htmlspecialchars($item['url']) . '">';
                    $html .= '<i class="fas ' . htmlspecialchars($item['icone']) . '"></i> ';
                    $html .= htmlspecialchars($item['nome']);
                    $html .= '</a>';
                } else {
                    $html .= '<i class="fas ' . htmlspecialchars($item['icone']) . '"></i> ';
                    $html .= htmlspecialchars($item['nome']);
                }
                $html .= '</li>';
            }
        }
        
        $html .= '</ol></nav>';
        
        return $html;
    }
    
    /**
     * Constrói o caminho do breadcrumb
     */
    private function buildTrail() {
        $trail = [];
        
        // Percorrer hierarquia para encontrar página atual
        foreach ($this->menuHierarquico as $modulo) {
            // Verificar se é o próprio módulo
            if (!$modulo['expandido'] && $modulo['url'] === $this->currentPage) {
                $trail[] = [
                    'nome' => $modulo['nome'],
                    'icone' => $modulo['icone'],
                    'url' => $modulo['url']
                ];
                return $trail;
            }
            
            // Verificar submenus
            if (!empty($modulo['submenus'])) {
                foreach ($modulo['submenus'] as $submenu) {
                    // Verificar se é o próprio submenu
                    if (!$submenu['expandido'] && $submenu['url'] === $this->currentPage) {
                        $trail[] = [
                            'nome' => $modulo['nome'],
                            'icone' => $modulo['icone'],
                            'url' => null
                        ];
                        $trail[] = [
                            'nome' => $submenu['nome'],
                            'icone' => $submenu['icone'],
                            'url' => $submenu['url']
                        ];
                        return $trail;
                    }
                    
                    // Verificar sub-submenus
                    if (!empty($submenu['subsubmenus'])) {
                        foreach ($submenu['subsubmenus'] as $subsubmenu) {
                            if ($subsubmenu['url'] === $this->currentPage) {
                                $trail[] = [
                                    'nome' => $modulo['nome'],
                                    'icone' => $modulo['icone'],
                                    'url' => null
                                ];
                                $trail[] = [
                                    'nome' => $submenu['nome'],
                                    'icone' => $submenu['icone'],
                                    'url' => null
                                ];
                                $trail[] = [
                                    'nome' => $subsubmenu['nome'],
                                    'icone' => $subsubmenu['icone'],
                                    'url' => $subsubmenu['url']
                                ];
                                return $trail;
                            }
                        }
                    }
                }
            }
        }
        
        return $trail;
    }
    
    /**
     * Retorna o título da página atual
     */
    public function getPageTitle() {
        $trail = $this->buildTrail();
        
        if (empty($trail)) {
            return 'Página';
        }
        
        return end($trail)['nome'];
    }
    
    /**
     * Retorna o ícone da página atual
     */
    public function getPageIcon() {
        $trail = $this->buildTrail();
        
        if (empty($trail)) {
            return 'fa-file';
        }
        
        return end($trail)['icone'];
    }
}
