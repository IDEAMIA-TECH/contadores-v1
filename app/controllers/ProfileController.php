<?php

class ProfileController {
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new Security();
    }
    
    public function index() {
        if (!$this->security->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        try {
            $userId = $_SESSION['user_id'];
            
            // Obtener la estructura de la tabla users
            $describeQuery = "DESCRIBE users";
            $stmt = $this->db->prepare($describeQuery);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Columnas en tabla users: " . print_r($columns, true));
            
            // Obtener datos del usuario usando las columnas correctas
            $query = "SELECT id, username, email, created_at FROM users WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }
            
            // Agregar el campo name para la vista (usando username)
            $user['name'] = $user['username'];
            
            require_once __DIR__ . '/../views/profile/index.php';
            
        } catch (Exception $e) {
            error_log("Error en Profile: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el perfil';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }
    
    public function update() {
        if (!$this->security->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        try {
            $userId = $_SESSION['user_id'];
            
            // Validar datos
            if (empty($_POST['name']) || empty($_POST['email'])) {
                throw new Exception('Todos los campos son obligatorios');
            }
            
            // Validar email
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }
            
            // Verificar si el email ya existe para otro usuario
            $query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$_POST['email'], $userId]);
            if ($stmt->fetch()) {
                throw new Exception('El email ya está en uso');
            }
            
            // Actualizar datos básicos (username en lugar de name)
            $query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $_POST['name'], // Usamos el campo 'name' del formulario para username
                $_POST['email'],
                $userId
            ]);
            
            // Actualizar contraseña si se proporcionó
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < 6) {
                    throw new Exception('La contraseña debe tener al menos 6 caracteres');
                }
                
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$hashedPassword, $userId]);
            }
            
            $_SESSION['success'] = 'Perfil actualizado correctamente';
            $_SESSION['username'] = $_POST['name']; // Actualizar el nombre en la sesión
            
        } catch (Exception $e) {
            error_log("Error en Profile Update: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . '/profile');
        exit;
    }
} 