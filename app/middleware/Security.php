<?php
class Security {
    private $config;
    
    public function __construct() {
        error_log("=== Inicializando Security ===");
        
        $this->config = require_once __DIR__ . '/../config/security.php';
        
        // Inicializar sesión primero
        $this->initSession();
        
        error_log("Session ID después de init: " . session_id());
        error_log("CSRF Token después de init: " . ($_SESSION['csrf_token'] ?? 'no existe'));
    }
    
    public function initSession() {
        error_log("=== Iniciando sesión ===");
        
        if (session_status() === PHP_SESSION_NONE) {
            error_log("Iniciando nueva sesión");
            session_start();
        } else {
            error_log("Sesión ya iniciada - ID: " . session_id());
        }
        
        // Solo generar token si no existe
        if (!isset($_SESSION['csrf_token'])) {
            error_log("No hay token CSRF, generando uno nuevo en initSession");
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            error_log("Token generado: " . $_SESSION['csrf_token']);
        } else {
            error_log("Token CSRF existente en initSession: " . $_SESSION['csrf_token']);
        }
    }
    
    public function generateCsrfToken() {
        error_log("Generando nuevo token CSRF");
        
        // Siempre generar un nuevo token y guardarlo en sesión
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        error_log("Nuevo token generado: " . $_SESSION['csrf_token']);
        
        return $_SESSION['csrf_token'];
    }
    
    public function validateCsrfToken($token) {
        error_log("=== Validando token CSRF ===");
        error_log("Token recibido: " . ($token ?: 'vacío'));
        error_log("Token en sesión: " . ($_SESSION['csrf_token'] ?? 'no existe'));
        
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            error_log("Token vacío o no existe en sesión");
            return false;
        }
        
        $result = hash_equals($_SESSION['csrf_token'], $token);
        error_log("Resultado de validación: " . ($result ? 'válido' : 'inválido'));
        
        if ($result) {
            // Si la validación fue exitosa, generar un nuevo token para la siguiente solicitud
            $this->generateCsrfToken();
        }
        
        return $result;
    }
    
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    public function verifyPassword($password, $hash) {
        // Debug: Verificar los valores exactos
        error_log("Verificando contraseña");
        error_log("Password exacto (primeros 3 caracteres): '" . substr($password, 0, 3) . "'");
        error_log("Hash exacto: '" . $hash . "'");
        
        // Verificar que los valores no estén vacíos
        if (empty($password) || empty($hash)) {
            error_log("Password o hash vacíos");
            return false;
        }
        
        // Verificar que el hash tenga el formato correcto
        if (!str_starts_with($hash, '$argon2id$')) {
            error_log("Hash no tiene formato Argon2id");
            return false;
        }
        
        // Intentar verificar la contraseña
        try {
            $result = password_verify($password, $hash);
            error_log("Resultado de verificación: " . ($result ? "true" : "false"));
            return $result;
        } catch (Exception $e) {
            error_log("Error al verificar contraseña: " . $e->getMessage());
            return false;
        }
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