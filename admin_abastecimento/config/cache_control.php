<?php
/**
 * Controle de Cache Global
 * Arquivo para ser incluído em todas as páginas autenticadas
 * Previne cache de páginas e dados sensíveis
 */

// Headers para prevenir cache no navegador
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Data no passado

// Headers de segurança adicionais
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Prevenir acesso sem sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não houver token de sessão, redirecionar para login
if (empty($_SESSION['token'])) {
    // Limpar qualquer dado de sessão remanescente
    $_SESSION = array();
    session_destroy();
    
    // Redirecionar para login
    $redirect = (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false) 
        ? '../index.php' 
        : 'index.php';
    
    header('Location: ' . $redirect);
    exit;
}

// Verificar timeout de inatividade (opcional - 30 minutos)
$timeout_duration = 1800; // 30 minutos em segundos

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    // Sessão expirada por inatividade
    $_SESSION = array();
    session_destroy();
    
    $redirect = (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false) 
        ? '../index.php?timeout=1' 
        : 'index.php?timeout=1';
    
    header('Location: ' . $redirect);
    exit;
}

// Atualizar timestamp da última atividade
$_SESSION['LAST_ACTIVITY'] = time();

// Regenerar ID de sessão periodicamente para segurança (a cada 30 minutos)
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 1800) {
    // Sessão criada há mais de 30 minutos, regenerar ID
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}
