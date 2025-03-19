<?php
class TestCase {
    protected $pdo;
    protected $lastInsertId;
    
    public function __construct() {
        $this->pdo = new PDO(
            "mysql:host=" . getenv('DB_HOST') . ";dbname=ideamia_cobranza_test;charset=utf8mb4",
            getenv('DB_USER'),
            getenv('DB_PASS'),
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