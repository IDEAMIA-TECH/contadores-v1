<?php
class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function findByUsername($username) {
        $stmt = $this->db->prepare('
            SELECT u.* 
            FROM users u 
            WHERE u.username = :username
        ');
        
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }
    
    public function updateLastLogin($userId) {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET last_login = CURRENT_TIMESTAMP 
            WHERE id = :id
        ');
        
        return $stmt->execute(['id' => $userId]);
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare('
            SELECT * FROM users 
            WHERE email = :email AND status = "active"
        ');
        
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
    
    public function saveResetToken($userId, $token, $expiry) {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET reset_token = :token,
                reset_token_expiry = :expiry 
            WHERE id = :id
        ');
        
        return $stmt->execute([
            'id' => $userId,
            'token' => $token,
            'expiry' => $expiry
        ]);
    }
    
    public function findByResetToken($token) {
        $stmt = $this->db->prepare('
            SELECT * FROM users 
            WHERE reset_token = :token 
            AND reset_token_expiry > CURRENT_TIMESTAMP
            AND status = "active"
        ');
        
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
    }
    
    public function updatePassword($userId, $hashedPassword) {
        $stmt = $this->db->prepare('
            UPDATE users 
            SET password = :password,
                reset_token = NULL,
                reset_token_expiry = NULL
            WHERE id = :id
        ');
        
        return $stmt->execute([
            'id' => $userId,
            'password' => $hashedPassword
        ]);
    }
} 