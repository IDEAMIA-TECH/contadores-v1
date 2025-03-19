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
        $this->security->initSession();
    }
    
    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            switch ($_SESSION['role']) {
                case 'contador':
                    header('Location: /clients');
                    break;
                default:
                    header('Location: /dashboard');
                    break;
            }
            exit;
        }
        
        $token = $this->security->generateCsrfToken();
        include __DIR__ . '/../views/auth/login.php';
    }
    
    public function login() {
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /login');
            exit;
        }
        
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Por favor complete todos los campos';
            header('Location: /login');
            exit;
        }
        
        $user = $this->user->findByUsername($username);
        
        if (!$user || !$this->security->verifyPassword($password, $user['password'])) {
            $_SESSION['error'] = 'Credenciales inválidas';
            header('Location: /login');
            exit;
        }
        
        if ($user['status'] !== 'active') {
            $_SESSION['error'] = 'Su cuenta está desactivada';
            header('Location: /login');
            exit;
        }
        
        // Actualizar último login
        $this->user->updateLastLogin($user['id']);
        
        // Crear sesión
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Redirigir según el rol
        switch ($user['role']) {
            case 'contador':
                header('Location: /clients');
                break;
            case 'admin':
                header('Location: /dashboard');
                break;
            default:
                header('Location: /dashboard');
        }
        exit;
    }
    
    public function logout() {
        session_destroy();
        header('Location: /login');
        exit;
    }
    
    public function showForgotPassword() {
        $token = $this->security->generateCsrfToken();
        require_once __DIR__ . '/../views/auth/forgot-password.php';
    }
    
    public function processForgotPassword() {
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /forgot-password');
            exit;
        }
        
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Por favor ingrese un email válido';
            header('Location: /forgot-password');
            exit;
        }
        
        $user = $this->user->findByEmail($email);
        
        if ($user) {
            // Generar token único
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            if ($this->user->saveResetToken($user['id'], $token, $expiry)) {
                // Enviar email
                $resetLink = APP_URL . "/reset-password?token=" . $token;
                $this->sendResetEmail($email, $resetLink);
                
                $_SESSION['success'] = 'Se ha enviado un enlace de recuperación a su email';
            } else {
                $_SESSION['error'] = 'Error al procesar la solicitud';
            }
        } else {
            // Por seguridad, no revelamos si el email existe o no
            $_SESSION['success'] = 'Si el email existe, recibirá instrucciones para restablecer su contraseña';
        }
        
        header('Location: /forgot-password');
        exit;
    }
    
    public function showResetPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token) || !$this->user->findByResetToken($token)) {
            $_SESSION['error'] = 'Token inválido o expirado';
            header('Location: /login');
            exit;
        }
        
        $csrfToken = $this->security->generateCsrfToken();
        require_once __DIR__ . '/../views/auth/reset-password.php';
    }
    
    public function processResetPassword() {
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /login');
            exit;
        }
        
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        if (empty($password) || strlen($password) < 8) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres';
            header("Location: /reset-password?token=$token");
            exit;
        }
        
        if ($password !== $passwordConfirm) {
            $_SESSION['error'] = 'Las contraseñas no coinciden';
            header("Location: /reset-password?token=$token");
            exit;
        }
        
        $user = $this->user->findByResetToken($token);
        
        if (!$user) {
            $_SESSION['error'] = 'Token inválido o expirado';
            header('Location: /login');
            exit;
        }
        
        $hashedPassword = $this->security->hashPassword($password);
        
        if ($this->user->updatePassword($user['id'], $hashedPassword)) {
            $_SESSION['success'] = 'Contraseña actualizada correctamente';
            header('Location: /login');
        } else {
            $_SESSION['error'] = 'Error al actualizar la contraseña';
            header("Location: /reset-password?token=$token");
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
        
        mail($to, $subject, $message, implode("\r\n", $headers));
    }
} 