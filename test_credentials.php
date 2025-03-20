<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\PrivateKey;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTime;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;

try {
    // 1. Primero probamos que podemos leer y usar las credenciales
    echo "=== Prueba de credenciales ===\n";
    
    // Rutas de los archivos
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';
    $passPhrase = 'Japc20078';

    echo "Verificando archivos...\n";
    echo "Certificado existe: " . (file_exists($cerFile) ? 'Sí' : 'No') . "\n";
    echo "Llave existe: " . (file_exists($keyFile) ? 'Sí' : 'No') . "\n";

    // Verificar contenido de los archivos
    if (file_exists($cerFile) && file_exists($keyFile)) {
        $cerContent = file_get_contents($cerFile);
        $keyContent = file_get_contents($keyFile);
        
        // Crear el certificado y la llave privada
        $certificate = new Certificate($cerContent);
        $privateKey = new PrivateKey($keyContent, $passPhrase);
        
        // Crear credencial
        $credential = new Credential($certificate, $privateKey);
        
        echo "\nInformación del certificado:\n";
        echo "- RFC: " . $certificate->rfc() . "\n";
        echo "- Nombre legal: " . $certificate->legalName() . "\n";
        echo "- Número de serie: " . $certificate->serialNumber()->bytes() . "\n";

        // Crear Fiel y probar la firma
        $fiel = new Fiel($credential);
        
        // Crear el servicio
        $webClient = new GuzzleWebClient();
        $requestBuilder = new FielRequestBuilder($fiel);
        $service = new Service($requestBuilder, $webClient);

        // Crear los parámetros de consulta según la documentación
        $parameters = QueryParameters::create(
            DateTimePeriod::createFromValues('2024-01-01T00:00:00', '2024-01-31T23:59:59'),
            new RequestType('RECIBIDOS'),  // RECIBIDOS o EMITIDOS en mayúsculas
            new DownloadType('XML')        // XML o METADATA en mayúsculas
        );

        echo "\nRealizando consulta de prueba al SAT...\n";
        echo "Periodo: 2024-01-01T00:00:00 a 2024-01-31T23:59:59\n";
        echo "Tipo: RECIBIDOS\n";
        echo "Formato: XML\n";

        $query = $service->query($parameters);
        
        if ($query->getStatus()->isAccepted()) {
            echo "Consulta aceptada. Request ID: " . $query->getRequestId() . "\n";
        } else {
            echo "Consulta rechazada: " . $query->getStatus()->getMessage() . "\n";
            echo "Código de estado: " . $query->getStatus()->getCode() . "\n";
        }

    } else {
        throw new Exception("No se encontraron los archivos necesarios");
    }

} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}