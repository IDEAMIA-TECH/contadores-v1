<?php 
require 'vendor/autoload.php';

use PhpCfdi\Credentials\Credential;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\ServiceEndpoints;

try {
    // Rutas de los archivos
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';
    $passPhrase = 'Japc20078';

    // Crear credencial
    $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);

    // Crear Fiel y servicio según la documentación
    $fiel = new Fiel($credential);
    $webClient = new GuzzleWebClient();
    $requestBuilder = new FielRequestBuilder($fiel);
    $service = new Service($requestBuilder, $webClient);

    // Crear parámetros de consulta
    $parameters = QueryParameters::create(
        DateTimePeriod::createFromValues('2024-03-01T00:00:00', '2024-03-15T23:59:59'),
        new RequestType('Recibidos'),
        new DownloadType('xml')
    );

    // Realizar la consulta
    $query = $service->query($parameters);
    
    if ($query->getStatus()->isAccepted()) {
        $requestId = $query->getRequestId();
        echo "Solicitud aceptada. ID: $requestId\n";

        // Verificar estado
        $verify = $service->verify($requestId);
        echo "Estado de la solicitud: " . $verify->getStatus()->getMessage() . "\n";

        if ($verify->getStatusRequest()->isFinished()) {
            foreach ($verify->getPackagesIds() as $packageId) {
                $download = $service->download($packageId);
                if ($download->getStatus()->isAccepted()) {
                    file_put_contents("CFDI_{$packageId}.zip", $download->getPackageContent());
                    echo "Paquete {$packageId} descargado.\n";
                }
            }
        }
    } else {
        echo "Error en la solicitud: " . $query->getStatus()->getMessage() . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>