<?php
class LoginController {
    // ... otros métodos existentes ...

    public function index() {
        // Si hay una sesión activa, destruirla primero
        if (session_status() === PHP_SESSION_ACTIVE) {
            error_log("Destruyendo sesión existente en index de login");
            // Limpiar la sesión
            $_SESSION = array();
            
            // Destruir la cookie de sesión
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            // Destruir la sesión
            session_destroy();
        }

        // Iniciar una nueva sesión limpia
        session_start();
        
        // Generar nuevo token CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $token = $_SESSION['csrf_token'];
        
        error_log("Nueva sesión iniciada en login - ID: " . session_id());
        error_log("Nuevo token CSRF generado: " . $token);

        // Renderizar la vista de login
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function logout() {
        error_log("Iniciando proceso de logout");
        
        // Asegurar que hay una sesión activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpiar todas las variables de sesión
        $_SESSION = array();

        // Destruir la cookie de sesión
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
            error_log("Cookie de sesión eliminada");
        }

        // Destruir la sesión
        session_destroy();
        error_log("Sesión destruida");

        // Forzar expiración del cache
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        // Redirigir al login
        header('Location: ' . BASE_URL . '/login');
        exit();
    }
} 