<?php 
require 'vendor/autoload.php';

use PhpCfdi\Credentials\Credential;
use PhpCfdi\SatWsDescargaMasiva\WebClient\WebService;
use PhpCfdi\SatWsDescargaMasiva\Services\Authenticate\AuthenticateService;
use PhpCfdi\SatWsDescargaMasiva\Services\Request\RequestService;
use PhpCfdi\SatWsDescargaMasiva\Services\Verify\VerifyService;
use PhpCfdi\SatWsDescargaMasiva\Services\Download\DownloadService;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;


    // Rutas de los archivos
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';
    $passPhrase = 'Japc20078';
// 🔹 Configurar credenciales e.firma
$credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);

// 🔹 Autenticación
$webService = new WebService();
$authService = new AuthenticateService($webService);
$authToken = $authService->authenticate($credential);

// 🔹 Hacer la solicitud de descarga
$requestService = new RequestService($webService);
$requestResult = $requestService->request(
    $authToken,
    '2025-01-01T00:00:00', // Fecha de inicio
    '2025-01-31T23:59:59', // Fecha de fin
    'Recibidos',// Opción: 'Emitidos' o 'Recibidos'
    'XML' // Tipo de descarga: 'XML' o 'ZIP'
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