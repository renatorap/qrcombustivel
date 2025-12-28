<?php
/**
 * Dashboard Components - Helper para Breadcrumbs e outros componentes
 */

// Inclui a classe Breadcrumb
require_once __DIR__ . '/breadcrumb.php';

/**
 * Renderiza breadcrumb para a página atual
 * 
 * @param AccessControl $accessControl Instância do controle de acesso
 * @param string $currentPage Nome do arquivo da página atual
 * @return string HTML do breadcrumb
 */
function renderBreadcrumb($accessControl, $currentPage = null) {
    if ($currentPage === null) {
        $currentPage = basename($_SERVER['PHP_SELF']);
    }
    
    $breadcrumb = new Breadcrumb($accessControl, $currentPage);
    return $breadcrumb->render();
}

/**
 * Retorna o título da página atual baseado no menu
 * 
 * @param AccessControl $accessControl Instância do controle de acesso
 * @param string $currentPage Nome do arquivo da página atual
 * @return string Título da página
 */
function getPageTitleFromMenu($accessControl, $currentPage = null) {
    if ($currentPage === null) {
        $currentPage = basename($_SERVER['PHP_SELF']);
    }
    
    $breadcrumb = new Breadcrumb($accessControl, $currentPage);
    return $breadcrumb->getPageTitle();
}

/**
 * Retorna o ícone da página atual baseado no menu
 * 
 * @param AccessControl $accessControl Instância do controle de acesso
 * @param string $currentPage Nome do arquivo da página atual
 * @return string Classe do ícone FontAwesome
 */
function getPageIconFromMenu($accessControl, $currentPage = null) {
    if ($currentPage === null) {
        $currentPage = basename($_SERVER['PHP_SELF']);
    }
    
    $breadcrumb = new Breadcrumb($accessControl, $currentPage);
    return $breadcrumb->getPageIcon();
}
