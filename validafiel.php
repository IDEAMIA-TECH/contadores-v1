<?php
require 'vendor/autoload.php';

use PhpCfdi\Credentials\Credential;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;

try {
    // Rutas de los archivos de la FIEL
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';
    $passPhrase = 'Japc20078';

    // Cargar la FIEL desde los archivos
    $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
    $fiel = new Fiel($credential);

    // 🔹 Validar si la FIEL es válida
    if (! $fiel->isValid()) {
        throw new Exception('❌ La FIEL no es válida. Verifica los archivos y la contraseña.');
    }

    // 🔹 Obtener la fecha de vencimiento del certificado
    $expirationDate = $credential->certificate()->validTo();
    $today = new DateTime();

    if ($expirationDate < $today) {
        throw new Exception('⚠️ La FIEL ha expirado. Fecha de vencimiento: ' . $expirationDate->format('Y-m-d'));
    }

    // 🔹 Información del certificado
    echo "✅ La FIEL es válida.\n";
    echo "🔹 RFC: " . $credential->certificate()->rfc() . "\n";
    echo "🔹 Nombre: " . $credential->certificate()->legalName() . "\n";
    echo "🔹 Fecha de emisión: " . $credential->certificate()->validFrom()->format('Y-m-d') . "\n";
    echo "🔹 Fecha de vencimiento: " . $expirationDate->format('Y-m-d') . "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>