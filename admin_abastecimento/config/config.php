<?php
// Configurações gerais do sistema
define('DB_HOST', 'localhost');
define('DB_USER', 'renatorap');
define('DB_PASS', 'J@melancia01');
define('DB_NAME', 'conceit1_combustivel');

define('COMPANY_NAME', 'QR Combustível');
define('COMPANY_LOGO', 'assets/QR_Combustivel.png');

// Cores primárias
define('COLOR_PRIMARY_1', '#ced4da');
define('COLOR_PRIMARY_2', '#2f6b8f');

// Cores secundárias
define('COLOR_SECONDARY_1', '#c1c3c7');
define('COLOR_SECONDARY_2', '#f59b4c');
define('COLOR_SECONDARY_3', '#1f5734');

// Configurações de segurança
define('JWT_SECRET', 'QRAMORACOMBUSTIVEL0721');
define('JWT_ALGORITHM', 'HS256');
define('TOKEN_EXPIRY', 3600); // 1 hora

// Configurações de paginação
define('PAGINATION_LIMIT', 10); // Quantidade de registros por página
define('PAGINATION_MAX_LINKS', 5); // Máximo de links de paginação visíveis

// Configurações de produção
define('BASE_URL', 'http://localhost/admin_abastecimento/');
// Mail settings (configure for your environment)
define('MAIL_FROM', 'no-reply@admincombustivel.com.br');
define('MAIL_FROM_NAME', 'QR Combustível');
define('PASSWORD_RESET_EXPIRY', 3600); // seconds (1 hour)

// Set MAIL_USE_SMTP to true to enable SMTP via PHPMailer (recommended in production)
define('MAIL_USE_SMTP', false);
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'smtp_user');
define('SMTP_PASS', 'smtp_password');
define('SMTP_SECURE', 'tls'); // tls or ssl or ''
define('DEBUG', false);

// If Composer autoload exists, include it so PHPMailer (and other packages) are available
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
	require_once $composerAutoload;
}

session_start();
?>