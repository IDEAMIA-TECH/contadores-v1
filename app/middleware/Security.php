<?php
class Security {
    private $config;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../config/security.php';
        $this->initSession();
    }
    
    public function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Inicializar variables de sesión si no existen
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = '';
        }
        if (!isset($_SESSION['token_expiry'])) {
            $_SESSION['token_expiry'] = 0;
        }
    }
    
    public function generateCsrfToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['token_expiry'] = time() + 3600; // 1 hora de validez
        return $token;
    }
    
    public function validateCsrfToken($token) {
        if (empty($token) || empty($_SESSION['csrf_token']) || empty($_SESSION['token_expiry'])) {
            return false;
        }
        
        if (time() > $_SESSION['token_expiry']) {
            // Token expirado
            unset($_SESSION['csrf_token']);
            unset($_SESSION['token_expiry']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public function generateRandomToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public function validatePassword($password) {
        // Mínimo 8 caracteres, al menos una letra y un número
        return strlen($password) >= 8 && 
               preg_match('/[A-Za-z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }
    
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function hasRole($role) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $role;
    }
    
    public function checkPermission($permission) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Implementar lógica de permisos según necesidades
        switch ($_SESSION['role']) {
            case 'admin':
                return true; // El admin tiene todos los permisos
            case 'contador':
                $contadorPermisos = ['ver_clientes', 'crear_cliente', 'editar_cliente'];
                return in_array($permission, $contadorPermisos);
            default:
                return false;
        }
    }
    
    public function logout() {
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión si existe
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destruir la sesión
        session_destroy();
    }
} 