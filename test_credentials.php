<?php 
require 'vendor/autoload.php';

use PhpCfdi\Credentials\Credential;
use PhpCfdi\SatWsDescargaMasiva\Shared\Fiel;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;

try {
    // Rutas de los archivos
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';
    $passPhrase = 'Japc20078';

    // Crear Fiel desde Credential
    $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
    $fiel = new Fiel($credential);

    if (! $fiel->isValid()) {
        throw new Exception('La FIEL no es válida.');
    }

    // Crear los servicios
    $webClient = new GuzzleWebClient();
    $requestBuilder = new FielRequestBuilder($fiel);
    $service = new Service($requestBuilder, $webClient);

    // Definir el periodo de fechas para descargar los CFDI "recibidos"
    $period = DateTimePeriod::createFromValues('2024-03-01T00:00:00', '2024-03-15T23:59:59');

    // Realizar la solicitud
    $queryResult = $service->query($period, 'recibidos', 'xml');

    if (! $queryResult->isAccepted()) {
        throw new Exception('La solicitud fue rechazada por el SAT: ' . $queryResult->getStatusMessage());
    }

    $requestId = $queryResult->getRequestId();
    echo "Solicitud aceptada. ID: $requestId\n";

    // Verificar el estado de la solicitud
    do {
        sleep(10); // Esperar antes de volver a verificar
        $verifyResult = $service->verify($requestId);
        echo "Estado: " . $verifyResult->getStatusRequest() . "\n";
    } while (! $verifyResult->isFinished());

    // Descargar paquetes
    $packageIds = $verifyResult->getPackagesIds();
    foreach ($packageIds as $index => $packageId) {
        $downloadResult = $service->download($packageId);
        if (! $downloadResult->isAccepted()) {
            echo "Error al descargar paquete {$packageId}\n";
            continue;
        }

        $fileName = "CFDI_{$index}.zip";
        file_put_contents($fileName, $downloadResult->getZipFileContents());
        echo "Paquete {$index} descargado como {$fileName}\n";
    }

    echo "Proceso completado.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>