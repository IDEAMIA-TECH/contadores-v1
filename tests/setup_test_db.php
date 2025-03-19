<?php
require_once __DIR__ . '/../app/config/config.php';

// Cargar variables de entorno desde .env.test
function loadEnv() {
    $envFile = __DIR__ . '/../.env.test';
    if (!file_exists($envFile)) {
        die("Archivo .env.test no encontrado\n");
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

loadEnv();

try {
    // Usar las credenciales del .env.test
    $host = getenv('DB_HOST');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
    
    echo "Conectando a la base de datos con:\n";
    echo "Host: $host\n";
    echo "Usuario: $user\n";
    
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Crear base de datos de prueba
    $testDbName = DB_NAME . '_test';
    $pdo->exec("DROP DATABASE IF EXISTS `$testDbName`");
    $pdo->exec("CREATE DATABASE `$testDbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$testDbName`");
    
    echo "Base de datos $testDbName creada correctamente\n";
    
    // Cargar esquema
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaFile)) {
        die("Archivo schema.sql no encontrado en $schemaFile\n");
    }
    
    $schema = file_get_contents($schemaFile);
    $pdo->exec($schema);
    
    echo "Esquema de base de datos cargado correctamente\n";
    
    // Insertar datos de prueba
    $pdo->exec("
        INSERT INTO users (username, password, email, role) VALUES 
        ('contador_test', '" . password_hash('test123', PASSWORD_ARGON2ID) . "', 'contador@test.com', 'contador')
    ");
    
    echo "Datos de prueba insertados correctamente\n";
    echo "Base de datos de prueba configurada correctamente\n";
    
} catch (PDOException $e) {
    die("Error configurando base de datos: " . $e->getMessage() . "\n");
} 