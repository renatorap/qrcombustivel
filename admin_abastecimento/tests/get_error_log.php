<?php
/**
 * Exibe últimos erros do log do Apache/PHP
 */

$logFile = '/var/log/apache2/error.log';

if (!file_exists($logFile)) {
    echo "Arquivo de log não encontrado: $logFile";
    exit;
}

// Pegar últimas 100 linhas
$lines = [];
$file = new SplFileObject($logFile);
$file->seek(PHP_INT_MAX);
$lastLine = $file->key();
$startLine = max(0, $lastLine - 100);

$file->seek($startLine);
while (!$file->eof()) {
    $line = $file->current();
    if (strpos($line, 'user_cliente') !== false || 
        strpos($line, 'Fatal error') !== false ||
        strpos($line, 'Parse error') !== false) {
        $lines[] = htmlspecialchars($line);
    }
    $file->next();
}

if (empty($lines)) {
    echo "Nenhum erro relacionado encontrado nas últimas 100 linhas";
} else {
    echo implode("\n", array_slice($lines, -20)); // Últimas 20 linhas relevantes
}
