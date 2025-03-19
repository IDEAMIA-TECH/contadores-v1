<?php
return [
    'session' => [
        'name' => 'IDEAMIA_SESSION',
        'lifetime' => 7200, // 2 horas
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    'csrf' => [
        'token_length' => 32,
        'token_lifetime' => 7200 // 2 horas
    ],
    'password' => [
        'algo' => PASSWORD_ARGON2ID,
        'options' => [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]
    ]
];

class Security {
    public function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCsrfToken($token) {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verifyPassword($password, $hash) {
        // Debug: Verificar los valores recibidos
        error_log("Verificando contraseña - Hash almacenado: " . $hash);
        return password_verify($password, $hash);
    }

    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
} 