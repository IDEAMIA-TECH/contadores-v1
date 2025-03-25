<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/Security.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $security;
    private $user;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new Security();
        $this->user = new User($this->db);
    }
    
    public function showLogin() {
        error_log("=== Mostrando formulario de login ===");
        
        // Verificar si ya está autenticado
        if ($this->security->isAuthenticated()) {
            error_log("Usuario ya autenticado, redirigiendo...");
            $this->redirectBasedOnRole();
            exit;
        }
        
        // Generar un nuevo token CSRF
        $token = $this->security->generateCsrfToken();
        error_log("Token CSRF generado para el formulario: " . $token);
        
        // Pasar el token a la vista
        $data = ['token' => $token];
        
        // Incluir la vista con los datos
        extract($data);
        include __DIR__ . '/../views/auth/login.php';
    }
    
    private function redirectBasedOnRole() {
        if (!isset($_SESSION['role'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Mapear rutas a controladores
        $controllerMap = [
            'Dashboard' => 'DashboardController',
            'Clients' => 'ClientsController',  // Cambiar de ClientController a ClientsController
            'Auth' => 'AuthController',
            'Reports' => 'ReportController'
        ];
        
        switch ($_SESSION['role']) {
            case 'contador':
                header('Location: ' . BASE_URL . '/clients');
                break;
            default:
                header('Location: ' . BASE_URL . '/dashboard');
                break;
        }
    }
    
    public function login() {
        // Por implementar si se necesita
    }
    
    public function logout() {
        try {
            // Log de inicio del proceso de logout
            error_log("Iniciando proceso de logout");

            // Iniciar sesión si no está iniciada
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Log de información de la sesión antes de destruirla
            error_log("Sesión actual: " . print_r($_SESSION, true));

            // Destruir todas las variables de sesión
            $_SESSION = array();

            // Destruir la cookie de sesión si existe
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
                error_log("Cookie de sesión eliminada");
            }

            // Destruir la sesión
            session_destroy();
            error_log("Sesión destruida completamente");

            // Redirigir al login
            header('Location: ' . BASE_URL . '/login');
            error_log("Redirigiendo a login");
            exit();
        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            header('Location: ' . BASE_URL . '/login');
            exit();
        }
    }
    
    public function showForgotPassword() {
        $token = $this->security->generateCsrfToken();
        require_once __DIR__ . '/../views/auth/forgot-password.php';
    }
    
    public function processForgotPassword() {
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: ' . BASE_URL . '/forgot-password');
            exit;
        }
        
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Por favor ingrese un email válido';
            header('Location: ' . BASE_URL . '/forgot-password');
            exit;
        }
        
        try {
            $user = $this->user->findByEmail($email);
            
            if ($user) {
                // Generar token único
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Guardar el token en la base de datos
                if ($this->user->saveResetToken($user['id'], $token, $expiry)) {
                    // Enviar email
                    $resetLink = APP_URL . "/reset-password?token=" . $token;
                    
                    // Debug: Verificar los valores
                    error_log("Reset Token: " . $token);
                    error_log("Expiry: " . $expiry);
                    error_log("User ID: " . $user['id']);
                    error_log("Reset Link: " . $resetLink);
                    
                    $this->sendResetEmail($email, $resetLink);
                    $_SESSION['success'] = 'Se ha enviado un enlace de recuperación a su email';
                } else {
                    throw new Exception('Error al guardar el token de recuperación');
                }
            } else {
                // Por seguridad, no revelamos si el email existe o no
                $_SESSION['success'] = 'Si el email existe, recibirá instrucciones para restablecer su contraseña';
            }
            
        } catch (Exception $e) {
            error_log("Error en processForgotPassword: " . $e->getMessage());
            $_SESSION['error'] = 'Error al procesar la solicitud. Por favor intente nuevamente.';
        }
        
        header('Location: ' . BASE_URL . '/forgot-password');
        exit;
    }
    
    public function showResetPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token) || !$this->user->findByResetToken($token)) {
            $_SESSION['error'] = 'Token inválido o expirado';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Generar token CSRF
        $csrfToken = $this->security->generateCsrfToken();
        include __DIR__ . '/../views/auth/reset-password.php';
    }
    
    public function processResetPassword() {
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $resetToken = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        if (empty($password) || strlen($password) < 8) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres';
            header('Location: ' . BASE_URL . '/reset-password?token=' . $resetToken);
            exit;
        }
        
        if ($password !== $passwordConfirm) {
            $_SESSION['error'] = 'Las contraseñas no coinciden';
            header('Location: ' . BASE_URL . '/reset-password?token=' . $resetToken);
            exit;
        }
        
        $user = $this->user->findByResetToken($resetToken);
        
        if (!$user) {
            $_SESSION['error'] = 'Token inválido o expirado';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $hashedPassword = $this->security->hashPassword($password);
        
        if ($this->user->updatePassword($user['id'], $hashedPassword)) {
            $_SESSION['success'] = 'Contraseña actualizada correctamente';
            header('Location: ' . BASE_URL . '/login');
        } else {
            $_SESSION['error'] = 'Error al actualizar la contraseña';
            header('Location: ' . BASE_URL . '/reset-password?token=' . $resetToken);
        }
        exit;
    }
    
    private function sendResetEmail($email, $resetLink) {
        $to = $email;
        $subject = 'Recuperación de Contraseña - IDEAMIA Tech';
        
        $message = "
        <html>
        <head>
            <title>Recuperación de Contraseña</title>
        </head>
        <body>
            <h2>Recuperación de Contraseña</h2>
            <p>Se ha solicitado un restablecimiento de contraseña para su cuenta.</p>
            <p>Para continuar, haga clic en el siguiente enlace:</p>
            <p><a href='$resetLink'>Restablecer Contraseña</a></p>
            <p>Este enlace expirará en 1 hora.</p>
            <p>Si usted no solicitó este cambio, puede ignorar este mensaje.</p>
            <br>
            <p>Saludos,<br>IDEAMIA Tech</p>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: IDEAMIA Tech <noreply@ideamia.tech>',
            'Reply-To: soporte@ideamia.tech'
        ];
        
        if (!mail($to, $subject, $message, implode("\r\n", $headers))) {
            error_log("Error al enviar email de recuperación a: " . $email);
            throw new Exception("Error al enviar el email de recuperación");
        }
    }
} 