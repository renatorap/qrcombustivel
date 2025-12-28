<?php
require_once __DIR__ . '/../config/config.php';

echo "Validating mail configuration...\n\n";

$errors = [];

$useSmtp = defined('MAIL_USE_SMTP') && MAIL_USE_SMTP;
if ($useSmtp) {
    echo "MAIL_USE_SMTP = true\n";
    if (empty(SMTP_HOST) || SMTP_HOST === 'smtp.example.com') {
        $errors[] = "SMTP_HOST parece não configurado ou ainda com o placeholder 'smtp.example.com'.";
    }
    if (!is_numeric(SMTP_PORT) || SMTP_PORT <= 0) {
        $errors[] = "SMTP_PORT inválido: " . (defined('SMTP_PORT') ? SMTP_PORT : 'não definido');
    }
    if (empty(SMTP_USER) || SMTP_USER === 'smtp_user') {
        $errors[] = "SMTP_USER parece não configurado.";
    }
    if (empty(SMTP_PASS) || SMTP_PASS === 'smtp_password') {
        $errors[] = "SMTP_PASS parece não configurado.";
    }
    $validSecures = ['', 'tls', 'ssl'];
    if (!in_array(strtolower(SMTP_SECURE), $validSecures, true)) {
        $errors[] = "SMTP_SECURE deve ser '', 'tls' ou 'ssl'. Atual: '" . SMTP_SECURE . "'";
    }

    // Check PHPMailer availability
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $errors[] = "PHPMailer não encontrado via autoload. Verifique se 'composer require phpmailer/phpmailer' foi executado e se o autoload está incluído.";
    }
} else {
    echo "MAIL_USE_SMTP = false (fallback para mail()).\n";
    if (!function_exists('mail')) {
        $errors[] = "Função mail() não disponível no PHP. Considere habilitar SMTP e PHPMailer.";
    }
}

// Basic MAIL_FROM validation
if (empty(MAIL_FROM) || strpos(MAIL_FROM, '@') === false) {
    $errors[] = "MAIL_FROM inválido: " . (defined('MAIL_FROM') ? MAIL_FROM : 'não definido');
}

if (!empty($errors)) {
    echo "Encontrados problemas de configuração:\n";
    foreach ($errors as $e) {
        echo " - " . $e . "\n";
    }
    exit(2);
}

echo "Configurações básicas parecem OK.\n";

if ($useSmtp) {
    echo "Tentando conectar ao SMTP (teste de conexão, não enviando e-mail)...\n";

    // Try connecting via PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    if (!empty(SMTP_SECURE)) $mail->SMTPSecure = SMTP_SECURE;
    $mail->SMTPAutoTLS = (strtolower(SMTP_SECURE) === 'tls');
    $mail->Timeout = 10;

    try {
        // smtpConnect attempts to open the socket; it returns boolean
        $ok = $mail->smtpConnect();
        if ($ok) {
            echo "Conexão SMTP efetuada com sucesso.\n";
            $mail->smtpClose();
            exit(0);
        } else {
            echo "Falha ao conectar via smtpConnect().\n";
            exit(3);
        }
    } catch (Exception $ex) {
        echo "Erro ao conectar via SMTP: " . $ex->getMessage() . "\n";
        exit(4);
    }
} else {
    echo "Usando mail() do PHP; teste de envio não realizado aqui. Se quiser, posso tentar enviar um e-mail de teste (recomendado para ambiente controlado).\n";
    exit(0);
}
