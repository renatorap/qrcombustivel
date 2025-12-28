<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

$response = ['success' => false, 'message' => '', 'code' => null];

$token = $_POST['token'] ?? '';
$newPassword = $_POST['password'] ?? '';

if (empty($token) || empty($newPassword)) {
    $response['message'] = 'Token e nova senha são obrigatórios.';
    $response['code'] = 'missing_fields';
    echo json_encode($response);
    exit;
}

// Basic password policy: min 6 chars
if (strlen($newPassword) < 6) {
    $response['message'] = 'A senha deve ter pelo menos 6 caracteres.';
    $response['code'] = 'weak_password';
    echo json_encode($response);
    exit;
}

$db = new Database();
$db->connect();

$sql = "SELECT pr.id as pr_id, pr.user_id, pr.expires_at, pr.used, u.login FROM password_resets pr JOIN usuarios u ON pr.user_id = u.id WHERE pr.token = ? LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Token inválido.';
    $response['code'] = 'invalid_token';
    echo json_encode($response);
    $stmt->close();
    $db->close();
    exit;
}

$row = $result->fetch_assoc();

if ((int)$row['used'] === 1) {
    $response['message'] = 'Este link de redefinição já foi utilizado.';
    $response['code'] = 'token_used';
    echo json_encode($response);
    $stmt->close();
    $db->close();
    exit;
}

if (strtotime($row['expires_at']) < time()) {
    $response['message'] = 'O link de redefinição expirou.';
    $response['code'] = 'token_expired';
    echo json_encode($response);
    $stmt->close();
    $db->close();
    exit;
}

// All good: update user's password and mark token used
$newHash = Security::hashPassword($newPassword);

$updateSql = "UPDATE usuarios SET senha = ? WHERE id = ?";
$up = $db->prepare($updateSql);
$up->bind_param('si', $newHash, $row['user_id']);
$up->execute();
$up->close();

$markSql = "UPDATE password_resets SET used = 1 WHERE id = ?";
$mk = $db->prepare($markSql);
$mk->bind_param('i', $row['pr_id']);
$mk->execute();
$mk->close();

$response['success'] = true;
$response['message'] = 'Senha redefinida com sucesso. Você já pode fazer login com a nova senha.';
$response['code'] = 'success';

echo json_encode($response);

$stmt->close();
$db->close();

?>