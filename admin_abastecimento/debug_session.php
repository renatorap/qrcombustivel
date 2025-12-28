<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session' => $_SESSION,
    'cliente_id' => $_SESSION['cliente_id'] ?? 'NÃO DEFINIDO',
    'userId' => $_SESSION['userId'] ?? 'NÃO DEFINIDO',
    'userName' => $_SESSION['userName'] ?? 'NÃO DEFINIDO'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
