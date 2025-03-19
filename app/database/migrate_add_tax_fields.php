<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si las columnas ya existen
    $checkColumns = $db->query("SHOW COLUMNS FROM client_xmls LIKE 'impuesto'");
    if ($checkColumns->rowCount() == 0) {
        // SQL para agregar los nuevos campos
        $sql = "ALTER TABLE client_xmls
                ADD COLUMN impuesto varchar(10) DEFAULT NULL AFTER total_impuestos_trasladados,
                ADD COLUMN tasa_o_cuota decimal(8,6) DEFAULT NULL AFTER impuesto,
                ADD COLUMN tipo_factor varchar(10) DEFAULT NULL AFTER tasa_o_cuota";
        
        $db->exec($sql);
        echo "Campos de impuestos agregados exitosamente\n";
    } else {
        echo "Los campos ya existen en la tabla\n";
    }
    
} catch (PDOException $e) {
    die("Error al ejecutar la migración: " . $e->getMessage() . "\n");
} 