<?php
/**
 * Script de teste para verificar variáveis de sessão
 * Útil para debug durante desenvolvimento
 */

session_start();
header('Content-Type: application/json');

$sessionData = [
    'cliente_id' => $_SESSION['cliente_id'] ?? null,
    'cliente_nome' => $_SESSION['cliente_nome'] ?? null,
    'cliente_logo' => $_SESSION['cliente_logo'] ?? null,
    'userId' => $_SESSION['userId'] ?? null,
    'login' => $_SESSION['login'] ?? null,
    'nome' => $_SESSION['nome'] ?? null,
    'grupoId' => $_SESSION['grupoId'] ?? null,
    'grupoNome' => $_SESSION['grupoNome'] ?? null,
    'userRole' => $_SESSION['userRole'] ?? null,
    'token' => isset($_SESSION['token']) ? 'presente' : 'ausente'
];

echo json_encode($sessionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
