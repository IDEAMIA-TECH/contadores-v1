<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

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
    echo "Conectando a la base de datos con:\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Usuario: " . DB_USER . "\n";
    echo "Base de datos: " . DB_NAME . "\n";
    
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Eliminar tablas de prueba si existen
    $pdo->exec("DROP TABLE IF EXISTS users_test");
    $pdo->exec("DROP TABLE IF EXISTS clients_test");
    $pdo->exec("DROP TABLE IF EXISTS accountants_test");
    $pdo->exec("DROP TABLE IF EXISTS client_contacts_test");
    $pdo->exec("DROP TABLE IF EXISTS client_documents_test");
    $pdo->exec("DROP TABLE IF EXISTS accountant_clients_test");
    
    // Crear tablas de prueba
    $pdo->exec("
        CREATE TABLE users_test (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            role ENUM('admin', 'contador', 'cliente') NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $pdo->exec("
        CREATE TABLE clients_test (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            rfc VARCHAR(13) NOT NULL UNIQUE,
            business_name VARCHAR(150) NOT NULL,
            legal_name VARCHAR(150) NOT NULL,
            fiscal_regime VARCHAR(100),
            address TEXT,
            phone VARCHAR(20),
            email VARCHAR(100),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users_test(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $pdo->exec("
        CREATE TABLE accountants_test (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users_test(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $pdo->exec("
        CREATE TABLE client_contacts_test (
            id INT PRIMARY KEY AUTO_INCREMENT,
            client_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES clients_test(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $pdo->exec("
        CREATE TABLE accountant_clients_test (
            id INT PRIMARY KEY AUTO_INCREMENT,
            accountant_id INT NOT NULL,
            client_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (accountant_id) REFERENCES users_test(id),
            FOREIGN KEY (client_id) REFERENCES clients_test(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Insertar datos de prueba
    // 1. Insertar usuario contador de prueba
    $pdo->exec("
        INSERT INTO users_test (id, username, password, email, role) VALUES 
        (1, 'contador_test', '" . password_hash('test123', PASSWORD_ARGON2ID) . "', 'contador@test.com', 'contador')
    ");
    
    // 2. Insertar registro de contador
    $pdo->exec("
        INSERT INTO accountants_test (id, user_id) VALUES 
        (1, 1)
    ");
    
    // 3. Insertar cliente de prueba base
    $pdo->exec("
        INSERT INTO clients_test (
            id, rfc, business_name, legal_name, fiscal_regime, 
            address, email, phone, status
        ) VALUES (
            1,
            'BASE123456ABC',
            'Empresa Base SA de CV',
            'Empresa Base',
            '601 - General de Ley',
            'Calle Base 123',
            'base@test.com',
            '5555555555',
            'active'
        )
    ");
    
    // 4. Insertar relación contador-cliente
    $pdo->exec("
        INSERT INTO accountant_clients_test (
            accountant_id, client_id
        ) VALUES (
            1, 1
        )
    ");
    
    // 5. Insertar contacto de prueba
    $pdo->exec("
        INSERT INTO client_contacts_test (
            client_id, name, email, phone
        ) VALUES (
            1,
            'Contacto Base',
            'contacto@test.com',
            '5555555555'
        )
    ");
    
    echo "Tablas de prueba creadas correctamente\n";
    echo "Datos de prueba insertados correctamente\n";
    
} catch (PDOException $e) {
    die("Error configurando base de datos: " . $e->getMessage() . "\n");
} 