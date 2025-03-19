<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // SQL para crear la tabla client_xmls
    $sql = "CREATE TABLE IF NOT EXISTS `client_xmls` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `client_id` int(11) NOT NULL,
        `xml_path` varchar(255) NOT NULL,
        `uuid` varchar(36) NOT NULL,
        `serie` varchar(50) DEFAULT NULL,
        `folio` varchar(50) DEFAULT NULL,
        `fecha` datetime NOT NULL,
        `fecha_timbrado` datetime NOT NULL,
        `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
        `total` decimal(12,2) NOT NULL DEFAULT 0.00,
        `tipo_comprobante` varchar(10) NOT NULL,
        `forma_pago` varchar(10) DEFAULT NULL,
        `metodo_pago` varchar(10) DEFAULT NULL,
        `moneda` varchar(5) NOT NULL DEFAULT 'MXN',
        `lugar_expedicion` varchar(10) NOT NULL,
        `emisor_rfc` varchar(13) NOT NULL,
        `emisor_nombre` varchar(255) NOT NULL,
        `emisor_regimen_fiscal` varchar(10) NOT NULL,
        `receptor_rfc` varchar(13) NOT NULL,
        `receptor_nombre` varchar(255) NOT NULL,
        `receptor_regimen_fiscal` varchar(10) NOT NULL,
        `receptor_domicilio_fiscal` varchar(10) NOT NULL,
        `receptor_uso_cfdi` varchar(10) NOT NULL,
        `total_impuestos_trasladados` decimal(12,2) NOT NULL DEFAULT 0.00,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `client_id` (`client_id`),
        KEY `uuid` (`uuid`),
        KEY `fecha` (`fecha`),
        CONSTRAINT `client_xmls_ibfk_1` FOREIGN KEY (`client_id`) 
            REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "Tabla client_xmls creada exitosamente\n";
    
} catch (PDOException $e) {
    die("Error al crear la tabla: " . $e->getMessage() . "\n");
} 