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
        error_log("Verificando contraseña");
        error_log("Password recibido (longitud): " . strlen($password));
        error_log("Hash almacenado: " . $hash);
        
        $result = password_verify($password, $hash);
        error_log("Resultado de verificación: " . ($result ? "true" : "false"));
        
        return $result;
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
        return isset($_SESSION['user_id']);
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