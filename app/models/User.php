<?php
class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function findByUsername($username) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE username = ? 
                LIMIT 1
            ");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error en findByUsername: " . $e->getMessage());
            throw new Exception("Error al buscar usuario");
        }
    }
    
    public function findByEmail($email) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE email = :email 
                AND status = 'active'
                LIMIT 1
            ");
            
            $stmt->execute(['email' => $email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error en findByEmail: " . $e->getMessage());
            throw new Exception("Error al buscar usuario por email");
        }
    }
    
    public function saveResetToken($userId, $token, $expiry) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET reset_token = :token,
                    reset_token_expiry = :expiry 
                WHERE id = :id
            ");
            
            return $stmt->execute([
                'id' => $userId,
                'token' => $token,
                'expiry' => $expiry
            ]);
        } catch (PDOException $e) {
            error_log("Error en saveResetToken: " . $e->getMessage());
            throw new Exception("Error al guardar el token de recuperación");
        }
    }
    
    public function findByResetToken($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE reset_token = :token 
                AND reset_token_expiry > NOW()
                AND status = 'active'
                LIMIT 1
            ");
            
            $stmt->execute(['token' => $token]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error en findByResetToken: " . $e->getMessage());
            throw new Exception("Error al verificar el token de recuperación");
        }
    }
    
    public function updatePassword($userId, $hashedPassword) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password = :password,
                    reset_token = NULL,
                    reset_token_expiry = NULL
                WHERE id = :id
            ");
            
            return $stmt->execute([
                'id' => $userId,
                'password' => $hashedPassword
            ]);
        } catch (PDOException $e) {
            error_log("Error en updatePassword: " . $e->getMessage());
            throw new Exception("Error al actualizar la contraseña");
        }
    }
    
    public function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET last_login = NOW() 
                WHERE id = :id
            ");
            
            return $stmt->execute(['id' => $userId]);
        } catch (PDOException $e) {
            error_log("Error en updateLastLogin: " . $e->getMessage());
            throw new Exception("Error al actualizar último login");
        }
    }
} 