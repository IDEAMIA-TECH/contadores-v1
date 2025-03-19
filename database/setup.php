<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Leer y ejecutar el archivo schema.sql
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Dividir el archivo en consultas individuales
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $db->exec($query);
        }
    }
    
    echo "Base de datos configurada correctamente\n";
    
} catch (PDOException $e) {
    die("Error configurando la base de datos: " . $e->getMessage() . "\n");
} 