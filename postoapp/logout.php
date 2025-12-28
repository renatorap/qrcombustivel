<?php

if(session_status() === PHP_SESSION_NONE) session_start();

// Deletar o cookie
setcookie('token');

// Deletar SESSION
session_unset();
session_destroy();

// Headers para limpar cache do navegador
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Redireciona para a página de login na raiz
header("Location: /login.php");
exit;
