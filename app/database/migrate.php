<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // SQL para crear la tabla client_xml
    $sql = "CREATE TABLE IF NOT EXISTS `client_xml` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `client_id` int(11) NOT NULL,
        `xml_path` varchar(255) NOT NULL,
        `uuid` varchar(36) DEFAULT NULL,
        `serie` varchar(50) DEFAULT NULL,
        `folio` varchar(50) DEFAULT NULL,
        `fecha` datetime DEFAULT NULL,
        `subtotal` decimal(10,2) DEFAULT 0.00,
        `total` decimal(10,2) DEFAULT 0.00,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `client_id` (`client_id`),
        CONSTRAINT `client_xml_ibfk_1` FOREIGN KEY (`client_id`) 
            REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "Tabla client_xml creada exitosamente\n";
    
} catch (PDOException $e) {
    die("Error al crear la tabla: " . $e->getMessage() . "\n");
} 