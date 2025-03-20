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
    
    // Método por defecto para la ruta /login
    public function index() {
        error_log("=== Mostrando formulario de login ===");
        
        // Verificar si ya está autenticado
        if ($this->security->isAuthenticated()) {
            error_log("Usuario ya autenticado, redirigiendo...");
            $this->redirectBasedOnRole();
            exit;
        }
        
        // Usar el token existente o generar uno nuevo
        if (empty($_SESSION['csrf_token'])) {
            error_log("No hay token CSRF en sesión, generando uno nuevo");
            $token = $this->security->generateCsrfToken();
        } else {
            error_log("Usando token CSRF existente en sesión");
            $token = $_SESSION['csrf_token'];
        }
        
        error_log("Token CSRF que se usará en el formulario: " . $token);
        
        // Preparar datos para la vista
        $data = [
            'token' => $token,
            'title' => 'Iniciar Sesión'
        ];
        
        $this->view('auth/login', $data);
    }

    // Método para procesar el login (POST)
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login');
        }

        try {
            // Validar CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Error de validación del formulario');
            }

            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                throw new Exception('Por favor complete todos los campos');
            }

            // Intentar autenticar
            $user = $this->user->findByUsername($username);
            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception('Credenciales inválidas');
            }

            // Login exitoso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Generar nuevo token CSRF
            $_SESSION['csrf_token'] = $this->security->generateCsrfToken();
            
            $this->redirectBasedOnRole();

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect('/login');
        }
    }

    private function view($view, $data = []) {
        extract($data);
        require_once "../app/views/{$view}.php";
    }

    private function redirectBasedOnRole() {
        if (!isset($_SESSION['role'])) {
            redirect('/login');
        }
        
        switch ($_SESSION['role']) {
            case 'contador':
                redirect('/clients');
                break;
            default:
                redirect('/dashboard');
                break;
        }
    }
    
    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
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