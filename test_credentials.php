<?php
require 'vendor/autoload.php';

use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceEndpoints;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\PrivateKey;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTime as SatDateTime;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;

try {
    // 🔐 Rutas de los archivos de la FIEL
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67ddeea05318f.cer';
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67ddeea0531c5.key';
    $passPhrase = 'Japc20078';

    // ✅ Cargar y validar la FIEL
    $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
    $fiel = new Fiel($credential);

    if (! $fiel->isValid()) {
        throw new Exception('❌ La FIEL no es válida.');
    }

    // 🌐 Crear el cliente del SAT
    $webClient = new GuzzleWebClient();
    $requestBuilder = new FielRequestBuilder($fiel);
    $service = new Service($requestBuilder, $webClient);

    // 📅 Rango de fechas: enero 2025
    $period = DateTimePeriod::createFromValues('2025-01-01T00:00:00', '2025-01-31T23:59:59');

    // 📨 Realizar la solicitud de CFDI recibidos
    $queryResult = $service->query($period, 'recibidos');

    if (! $queryResult->isAccepted()) {
        throw new Exception('❌ La solicitud fue rechazada por el SAT: ' . $queryResult->getStatusMessage());
    }

    $requestId = $queryResult->getRequestId();
    echo "✅ Solicitud aceptada. ID: $requestId\n";

    // 🔄 Verificar el estado cada 10 segundos
    do {
        sleep(10);
        $verifyResult = $service->verify($requestId);
        echo "⏳ Estado actual: " . $verifyResult->getStatusRequest() . "\n";
    } while (! $verifyResult->isFinished());

    // 📦 Descargar los paquetes ZIP
    $packageIds = $verifyResult->getPackagesIds();

    if (empty($packageIds)) {
        echo "⚠️ No se encontraron CFDI para este periodo.\n";
    } else {
        foreach ($packageIds as $index => $packageId) {
            $downloadResult = $service->download($packageId);
            if (! $downloadResult->isAccepted()) {
                echo "❌ Error al descargar paquete {$packageId}\n";
                continue;
            }

            $outputFile = __DIR__ . "/CFDI_{$index}.zip";
            file_put_contents($outputFile, $downloadResult->getZipFileContents());
            echo "✅ Paquete {$index} descargado como {$outputFile}\n";
        }
        echo "🎉 Proceso de descarga completado.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>