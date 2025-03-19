<?php
require_once __DIR__ . '/../app/config/database.php';

class TestCase {
    protected $pdo;
    protected $lastInsertId;
    
    public function __construct() {
        $this->pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    
    protected function setUp() {
        $this->pdo->beginTransaction();
    }
    
    protected function tearDown() {
        $this->pdo->rollBack();
    }
    
    protected function assert($condition, $message) {
        if (!$condition) {
            throw new Exception("Assertion failed: $message");
        }
    }
} 