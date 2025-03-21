<?php
require 'vendor/autoload.php';

use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;

try {
    // Rutas de los archivos de la FIEL
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67ddeea05318f.cer';
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67ddeea0531c5.key';
    $passPhrase = 'Japc20078';

    // Cargar y validar la FIEL
    $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
    $fiel = new Fiel($credential);

    if (! $fiel->isValid()) {
        throw new Exception('La FIEL no es válida.');
    }

    // Crear el cliente del SAT
    $webClient = new GuzzleWebClient();
    $requestBuilder = new FielRequestBuilder($fiel);
    $service = new Service($requestBuilder, $webClient);

    // Definir el período de fechas: enero 2025
    $period = DateTimePeriod::createFromValues('2025-01-01T00:00:00', '2025-01-31T23:59:59');

    // Crear los parámetros de la consulta
    $parameters = QueryParameters::create(
        $period,
        DownloadType::received(),    // CFDI recibidos
        RequestType::xml()           // Solicitar archivos XML completos
    );

    // Realizar la solicitud de CFDI recibidos
    $queryResult = $service->query($parameters);

    if (! $queryResult->getStatus()->isAccepted()) {
        throw new Exception('La solicitud fue rechazada por el SAT: ' . $queryResult->getStatus()->getMessage());
    }

    $requestId = $queryResult->getRequestId();
    echo "Solicitud aceptada. ID: $requestId\n";

    // Verificar el estado cada 10 segundos
    do {
        sleep(10);
        $verifyResult = $service->verify($requestId);
        echo "Estado actual: " . $verifyResult->getStatusRequest() . "\n";
    } while (! $verifyResult->getStatusRequest()->isFinished());

    // Descargar los paquetes ZIP
    $packageIds = $verifyResult->getPackagesIds();

    if (empty($packageIds)) {
        echo "No se encontraron CFDI para este periodo.\n";
    } else {
        echo "Se encontraron " . count($packageIds) . " paquete(s) para descargar.\n";

        // Crear carpeta para guardar los ZIP
        $outputDir = __DIR__ . '/descargas_xml';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        foreach ($packageIds as $index => $packageId) {
            $downloadResult = $service->download($packageId);
            if (! $downloadResult->getStatus()->isAccepted()) {
                echo "Error al descargar paquete {$packageId}\n";
                continue;
            }

            $outputFile = $outputDir . "/CFDI_{$index}.zip";
            file_put_contents($outputFile, $downloadResult->getPackageContent());
            echo "Paquete {$index} descargado como {$outputFile}\n";
        }

        echo "Proceso de descarga completado.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>