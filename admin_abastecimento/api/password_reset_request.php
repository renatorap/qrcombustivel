<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

// Basic response template
$response = ['success' => false, 'message' => '', 'code' => null];

try {

// Helper to log internal errors (not returned to client)
function _pr_log_error($msg) {
    $logDir = __DIR__ . '/../storage';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/password_reset_errors.log';
    $entry = date('Y-m-d H:i:s') . " | " . $msg . "\n";
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

// Accept either 'login' or 'email'
$input = trim($_POST['login'] ?? $_POST['email'] ?? '');

if (empty($input)) {
    $response['message'] = 'Informe o usuário ou e-mail.';
    $response['code'] = 'missing_input';
    echo json_encode($response);
    exit;
}

try {
    $db = new Database();
    $db->connect();
} catch (Throwable $e) {
    _pr_log_error('DB connect error: ' . $e->getMessage());
    $response['message'] = 'Erro interno. Tente novamente mais tarde.';
    echo json_encode($response);
    exit;
}

// Determine whether input is email
$isEmail = Security::validateEmail($input);

if ($isEmail) {
    $sql = "SELECT id, login, nome, email, ativo FROM usuarios WHERE email = ? LIMIT 1";
} else {
    $sql = "SELECT id, login, nome, email, ativo FROM usuarios WHERE login = ? LIMIT 1";
}

// Prepare statement and handle environments where the column may not exist yet
$stmt = $db->prepare($sql);
if ($stmt === false) {
    // If we tried to query by email but the column doesn't exist, fall back to username
    if ($isEmail) {
        _pr_log_error('Prepare failed for email lookup, falling back to login. SQL: ' . $sql . ' | DB errno: ' . ($db->connect()->errno ?? 'n/a'));
        $sql = "SELECT id, login, nome, email, ativo FROM usuarios WHERE login = ? LIMIT 1";
        $stmt = $db->prepare($sql);
    }
}

if ($stmt === false) {
    // Can't prepare statement: return generic success response to avoid leaking presence
    _pr_log_error('Prepare failed for password reset request: ' . json_encode([$sql, $db]));
    $response['message'] = 'Se o usuário/e-mail existir, você receberá instruções para redefinir a senha.';
    $response['code'] = 'request_queued';
    echo json_encode($response);
    $db->close();
    exit;
}

// Bind and execute
try {
    $stmt->bind_param("s", $input);
    $stmt->execute();
    $result = $stmt->get_result();
} catch (Throwable $e) {
    _pr_log_error('Statement execute/get_result error: ' . $e->getMessage());
    $response['message'] = 'Se o usuário/e-mail existir, você receberá instruções para redefinir a senha.';
    $response['code'] = 'request_queued';
    echo json_encode($response);
    $stmt->close();
    $db->close();
    exit;
}

if ($result->num_rows === 0) {
    // Do not reveal whether email exists — for security we can respond generically
    $response['message'] = 'Se o usuário/e-mail existir, você receberá instruções para redefinir a senha.';
    $response['code'] = 'request_queued';
    echo json_encode($response);
    $stmt->close();
    $db->close();
    exit;
}

$user = $result->fetch_assoc();

if ((int)$user['ativo'] !== 1) {
    $response['message'] = 'Conta inativa. Contate o administrador.';
    $response['code'] = 'user_inactive';
    echo json_encode($response);
    $stmt->close();
    $db->close();
    exit;
}

if (empty($user['email'])) {
    // No email set for user
    $response['message'] = 'Nenhum e-mail cadastrado para este usuário. Contate o administrador.';
    $response['code'] = 'no_email';
    echo json_encode($response);
    $stmt->close();
    $db->close();
    exit;
}

// Generate secure token
$token = bin2hex(random_bytes(32));
$expiry = date('Y-m-d H:i:s', time() + (defined('PASSWORD_RESET_EXPIRY') ? PASSWORD_RESET_EXPIRY : 3600));

// Store token
// Store token
$insertSql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
$ins = $db->prepare($insertSql);
if ($ins === false) {
    _pr_log_error('Failed to prepare insert for password_resets: ' . $db->connect()->error);
} else {
    try {
        $ins->bind_param('iss', $user['id'], $token, $expiry);
        $ins->execute();
        $ins->close();
    } catch (Throwable $e) {
        _pr_log_error('Failed to insert password_reset token: ' . $e->getMessage());
    }
}

$resetLink = rtrim(BASE_URL, '/') . '/reset_password.php?token=' . $token;

// Prepare email
$to = $user['email'];
$subject = COMPANY_NAME . " - Redefinição de senha";
$message = "Olá {$user['login']},\n\nRecebemos uma solicitação para redefinir a senha da sua conta. Clique no link abaixo para criar uma nova senha (válido por 1 hora):\n\n" . $resetLink . "\n\nSe você não solicitou essa ação, ignore esta mensagem.\n\nAtenciosamente,\n" . COMPANY_NAME;

$headers = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
$headers .= "Reply-To: " . MAIL_FROM . "\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8\r\n";

// Prefer PHPMailer via SMTP if configured and available
$mailSent = false;
if (defined('MAIL_USE_SMTP') && MAIL_USE_SMTP && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    try {
        // Use PHPMailer
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        // Server settings
        if (!empty(SMTP_HOST)) {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            if (!empty(SMTP_SECURE)) $mail->SMTPSecure = SMTP_SECURE;
        }

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        $mailSent = true;
    } catch (Throwable $e) {
        _pr_log_error('PHPMailer send failed: ' . $e->getMessage());
        $mailSent = false;
    }
} else {
    // Fallback to PHP mail()
    try {
        $mailSent = mail($to, $subject, $message, $headers);
    } catch (Exception $e) {
        $mailSent = false;
    }
}

if (!$mailSent) {
    // Fallback: log the reset request WITHOUT token to avoid leaking tokens in logs
    $logDir = __DIR__ . '/../storage';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/password_reset.log';
    $entry = date('Y-m-d H:i:s') . " | user_id={$user['id']} | email={$user['email']} | token_generated=1 | expires_at={$expiry}\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

$response['success'] = true;
$response['message'] = 'Se o usuário/e-mail existir, você receberá instruções para redefinir a senha.';
$response['code'] = 'request_queued';

echo json_encode($response);

$stmt->close();
$db->close();

} catch (Throwable $e) {
    // Ensure error logger exists
    if (!function_exists('_pr_log_error')) {
        function _pr_log_error($msg) {
            $logDir = __DIR__ . '/../storage';
            if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
            $logFile = $logDir . '/password_reset_errors.log';
            $entry = date('Y-m-d H:i:s') . " | " . $msg . "\n";
            @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        }
    }

    _pr_log_error('Unhandled exception in password_reset_request: ' . $e->getMessage() . ' | trace: ' . $e->getTraceAsString());
    http_response_code(500);
    $response['message'] = 'Erro interno. Tente novamente mais tarde.';
    echo json_encode($response);
    exit;
}

?>