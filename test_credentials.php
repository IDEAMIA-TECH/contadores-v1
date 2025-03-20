<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpCfdi\Credentials\Credential;
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
    
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';  // Certificado .cer
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';  // Llave privada .key
    $passPhrase = 'Japc20078';

    echo "Verificando archivos...\n";
    echo "Certificado existe: " . (file_exists($cerFile) ? 'Sí' : 'No') . "\n";
    echo "Llave existe: " . (file_exists($keyFile) ? 'Sí' : 'No') . "\n";

    // Verificar el contenido de los archivos
    if (file_exists($cerFile)) {
        echo "Tamaño del certificado: " . filesize($cerFile) . " bytes\n";
        echo "Contenido del certificado (primeros 50 bytes): " . bin2hex(substr(file_get_contents($cerFile), 0, 50)) . "\n";
    }
    
    if (file_exists($keyFile)) {
        echo "Tamaño de la llave: " . filesize($keyFile) . " bytes\n";
        echo "Contenido de la llave (primeros 50 bytes): " . bin2hex(substr(file_get_contents($keyFile), 0, 50)) . "\n";
    }

    // Crear credencial y mostrar información
    echo "\nCreando credencial...\n";
    $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
    
    echo "Información del certificado:\n";
    echo "- RFC: " . $credential->certificate()->rfc() . "\n";
    echo "- Nombre legal: " . $credential->certificate()->legalName() . "\n";
    echo "- Número de serie: " . $credential->certificate()->serialNumber()->bytes() . "\n";
    
    // Probar firma
    echo "\nProbando firma digital...\n";
    $testString = 'Prueba de firma ' . date('Y-m-d H:i:s');
    $signature = $credential->sign($testString);
    $verified = $credential->verify($testString, $signature);
    echo "Verificación de firma: " . ($verified ? 'Exitosa' : 'Fallida') . "\n";

    // 2. Ahora probamos la creación del servicio y una consulta
    echo "\n=== Prueba de servicio SAT ===\n";
    
    // Crear objetos necesarios para el servicio
    $fiel = new Fiel($credential);
    $requestBuilder = new FielRequestBuilder($fiel);
    $webClient = new GuzzleWebClient();
    $service = new Service($requestBuilder, $webClient);

    // Crear periodo de prueba
    echo "Creando periodo de prueba...\n";
    $startDate = DateTime::create('2024-01-01T00:00:00');
    $endDate = DateTime::create('2024-01-31T23:59:59');
    $period = new DateTimePeriod($startDate, $endDate);

    // Crear tipos para la consulta
    echo "Creando parámetros de consulta...\n";
    $downloadType = new DownloadType('metadata');
    $requestType = new RequestType('EMITIDOS');

    // Crear parámetros de consulta
    $parameters = QueryParameters::create(
        $period,
        $requestType,
        $downloadType
    );

    // Intentar hacer una consulta
    echo "Enviando consulta al SAT...\n";
    $query = $service->query($parameters);
    
    echo "Respuesta del SAT:\n";
    echo "- Status: " . $query->getStatus()->getMessage() . "\n";
    if ($query->getStatus()->isAccepted()) {
        echo "- Request ID: " . $query->getRequestId() . "\n";
    }

    echo "\nPrueba completada exitosamente!\n";

} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} 