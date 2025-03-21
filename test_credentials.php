<?php 
require 'vendor/autoload.php';

use PhpCfdi\SatWsDescargaMasiva\Shared\Credential;
use PhpCfdi\SatWsDescargaMasiva\WebClient\WebService;
use PhpCfdi\SatWsDescargaMasiva\Services\Authenticate\AuthenticateService;
use PhpCfdi\SatWsDescargaMasiva\Services\Request\RequestService;
use PhpCfdi\SatWsDescargaMasiva\Services\Verify\VerifyService;
use PhpCfdi\SatWsDescargaMasiva\Services\Download\DownloadService;


    // Rutas de los archivos
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';
    $passPhrase = 'Japc20078';
// 🔹 Configurar credenciales e.firma
$credential = Credential::createFromFiel($cerFile, $keyFile, $passPhrase);

// 🔹 Autenticación
$webService = new WebService();
$authService = new AuthenticateService($webService);
$authToken = $authService->authenticate($credential);

// 🔹 Hacer la solicitud de descarga
$requestService = new RequestService($webService);
$requestResult = $requestService->request(
    $authToken,
    '2024-03-01T00:00:00', // Fecha de inicio
    '2024-03-15T23:59:59', // Fecha de fin
    'Recibidos' // Opción: 'Emitidos' o 'Recibidos'
);

// Obtener el ID de la solicitud
$requestId = $requestResult->getRequestId();
echo "Solicitud enviada. ID: $requestId\n";

// 🔹 Verificar si la solicitud está lista para descarga
$verifyService = new VerifyService($webService);
do {
    sleep(10); // Esperar 10 segundos antes de revisar
    $verifyResult = $verifyService->verify($authToken, $requestId);
    echo "Estado de la solicitud: " . $verifyResult->getStatus() . "\n";
} while (!$verifyResult->isReady());

// 🔹 Descargar los XML cuando estén listos
$downloadService = new DownloadService($webService);
$downloadResult = $downloadService->download($authToken, $requestId);

foreach ($downloadResult->getPackages() as $index => $package) {
    file_put_contents("CFDI_{$index}.zip", $package->getContents());
    echo "Paquete {$index} descargado.\n";
}

echo "Descarga finalizada.\n";
?>