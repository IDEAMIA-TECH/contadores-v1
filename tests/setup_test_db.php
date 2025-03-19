<?php
require_once __DIR__ . '/../app/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";charset=utf8mb4",
        getenv('DB_USER'),
        getenv('DB_PASS')
    );
    
    // Crear base de datos de prueba
    $pdo->exec("DROP DATABASE IF EXISTS ideamia_cobranza_test");
    $pdo->exec("CREATE DATABASE ideamia_cobranza_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE ideamia_cobranza_test");
    
    // Cargar esquema
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($schema);
    
    // Insertar datos de prueba
    $pdo->exec("
        INSERT INTO users (username, password, email, role) VALUES 
        ('contador_test', '" . password_hash('test123', PASSWORD_ARGON2ID) . "', 'contador@test.com', 'contador')
    ");
    
    echo "Base de datos de prueba configurada correctamente\n";
    
} catch (PDOException $e) {
    die("Error configurando base de datos: " . $e->getMessage() . "\n");
} 