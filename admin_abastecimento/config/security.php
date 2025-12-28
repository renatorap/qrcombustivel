<?php
require_once 'config.php';

class Security {
    
    // Gera JWT Token
    public static function generateToken($userId, $grupoId = null) {
        $header = [
            'alg' => JWT_ALGORITHM,
            'typ' => 'JWT'
        ];

        $payload = [
            'userId' => $userId,
            'grupoId' => $grupoId,
            'iat' => time(),
            'exp' => time() + TOKEN_EXPIRY
        ];

        $headerEncoded = base64_encode(json_encode($header));
        $payloadEncoded = base64_encode(json_encode($payload));

        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
        $signatureEncoded = base64_encode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    // Valida JWT Token
    public static function validateToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) != 3) {
            return false;
        }

        $headerEncoded = $parts[0];
        $payloadEncoded = $parts[1];
        $signatureEncoded = $parts[2];

        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", JWT_SECRET, true);
        $signatureCalculated = base64_encode($signature);

        if ($signatureCalculated !== $signatureEncoded) {
            return false;
        }

        $payload = json_decode(base64_decode($payloadEncoded), true);

        if ($payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    // Criptografa dados com AES-256
    public static function encrypt($data) {
        $key = hash('sha256', JWT_SECRET, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, true, $iv);
        return base64_encode($iv . $encrypted);
    }

    // Descriptografa dados com AES-256
    public static function decrypt($data) {
        $key = hash('sha256', JWT_SECRET, true);
        $data = base64_decode($data);
        $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, true, $iv);
    }

    // Sanitiza entrada contra SQL Injection
    public static function sanitize($input) {
        $db = new Database();
        $db->connect();
        $sanitized = $db->escape(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8'));
        return $sanitized;
    }

    // Valida email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Gera hash de senha
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // Valida senha
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}

?>