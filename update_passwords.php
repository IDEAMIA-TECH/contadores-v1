<?php
require_once __DIR__ . '/app/config/database.php';

$db = Database::getInstance()->getConnection();
$oldAppKey = 'TU_ANTIGUA_APP_KEY'; // La APP_KEY que se usó originalmente
$newAppKey = getenv('APP_KEY'); // La nueva APP_KEY

// Obtener todos los clientes con contraseñas
$stmt = $db->prepare("SELECT id, key_password FROM clients WHERE key_password IS NOT NULL");
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($clients as $client) {
    // Desencriptar con la antigua key
    $decrypted = openssl_decrypt(
        $client['key_password'],
        'AES-256-CBC',
        $oldAppKey,
        0,
        substr($oldAppKey, 0, 16)
    );
    
    // Encriptar con la nueva key
    $encrypted = openssl_encrypt(
        $decrypted,
        'AES-256-CBC',
        $newAppKey,
        0,
        substr($newAppKey, 0, 16)
    );
    
    // Actualizar en la base de datos
    $updateStmt = $db->prepare("UPDATE clients SET key_password = ? WHERE id = ?");
    $updateStmt->execute([$encrypted, $client['id']]);
    
    echo "Actualizado cliente ID: {$client['id']}\n";
} 