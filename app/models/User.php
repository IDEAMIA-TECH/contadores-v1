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
            // Debug: Verificar los valores antes de la consulta
            error_log("Saving reset token - User ID: $userId, Token: $token, Expiry: $expiry");
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET reset_token = :token,
                    reset_token_expiry = :expiry 
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                'id' => $userId,
                'token' => $token,
                'expiry' => $expiry
            ]);
            
            // Verificar si la actualización fue exitosa
            if (!$result) {
                error_log("Error al ejecutar la consulta de actualización del token");
                error_log(print_r($stmt->errorInfo(), true));
                return false;
            }
            
            // Verificar si se actualizó alguna fila
            if ($stmt->rowCount() === 0) {
                error_log("No se actualizó ninguna fila al guardar el token");
                return false;
            }
            
            // Verificar que los datos se guardaron correctamente
            $verifyStmt = $this->db->prepare("
                SELECT reset_token, reset_token_expiry 
                FROM users 
                WHERE id = ?
            ");
            $verifyStmt->execute([$userId]);
            $result = $verifyStmt->fetch();
            
            if ($result['reset_token'] !== $token) {
                error_log("El token guardado no coincide con el token original");
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error en saveResetToken: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
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