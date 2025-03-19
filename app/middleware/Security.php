<?php
class Security {
    private $config;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../config/security.php';
    }
    
    public function initSession() {
        $sessionConfig = $this->config['session'];
        
        ini_set('session.name', $sessionConfig['name']);
        ini_set('session.cookie_lifetime', $sessionConfig['lifetime']);
        ini_set('session.cookie_path', $sessionConfig['path']);
        ini_set('session.cookie_domain', $sessionConfig['domain']);
        ini_set('session.cookie_secure', $sessionConfig['secure']);
        ini_set('session.cookie_httponly', $sessionConfig['httponly']);
        ini_set('session.cookie_samesite', $sessionConfig['samesite']);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes($this->config['csrf']['token_length']));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    public function validateCsrfToken($token) {
        if (empty($_SESSION['csrf_token']) || 
            empty($_SESSION['csrf_token_time']) ||
            $token !== $_SESSION['csrf_token']) {
            return false;
        }
        
        $tokenAge = time() - $_SESSION['csrf_token_time'];
        if ($tokenAge > $this->config['csrf']['token_lifetime']) {
            return false;
        }
        
        return true;
    }
    
    public function hashPassword($password) {
        return password_hash(
            $password, 
            $this->config['password']['algo'],
            $this->config['password']['options']
        );
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
} 