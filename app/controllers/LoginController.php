<?php
class LoginController {
    // ... otros métodos existentes ...

    public function logout() {
        try {
            error_log("Iniciando proceso de logout");
            
            // Verificar si hay una sesión activa
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Verificar el token CSRF solo si es POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    error_log("Token CSRF inválido en logout");
                    $_SESSION['error'] = 'Error de seguridad. Por favor intente nuevamente.';
                    header('Location: ' . BASE_URL . '/login');
                    exit();
                }
            }

            // Limpiar la sesión
            $_SESSION = array();

            // Destruir la cookie de sesión si existe
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }

            // Destruir la sesión
            session_destroy();
            error_log("Sesión destruida correctamente");

            // Redirigir al login
            header('Location: ' . BASE_URL . '/login');
            exit();

        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            header('Location: ' . BASE_URL . '/login');
            exit();
        }
    }
} 