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
    // Rutas de los archivos
    $cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';
    $keyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';
    $passPhrase = 'Japc20078';

    // Verificar que los archivos existan
    if (!file_exists($cerFile)) {
        throw new Exception("El archivo del certificado no existe: $cerFile");
    }
    if (!file_exists($keyFile)) {
        throw new Exception("El archivo de la llave privada no existe: $keyFile");
    }

    echo "Verificando archivos...\n";
    echo "Certificado: " . $cerFile . " (" . filesize($cerFile) . " bytes)\n";
    echo "Llave privada: " . $keyFile . " (" . filesize($keyFile) . " bytes)\n";

    // Crear el certificado y la llave privada primero para validar
    try {
        $certificateContents = file_get_contents($cerFile);
        echo "Contenido del certificado leído: " . (empty($certificateContents) ? "VACÍO" : strlen($certificateContents) . " bytes") . "\n";
        
        $certificate = new Certificate($certificateContents);
        
        echo "Información del certificado:\n";
        echo "- RFC: " . $certificate->rfc() . "\n";
        echo "- Número de serie: " . $certificate->serialNumber()->bytes() . "\n";
        
        // Convertir las fechas a DateTime si es necesario
        $validFrom = $certificate->validFrom();
        $validTo = $certificate->validTo();
        
        echo "- Tipo de validFrom: " . gettype($validFrom) . "\n";
        echo "- Tipo de validTo: " . gettype($validTo) . "\n";
        
        // Formatear fechas con verificación de tipo
        $validFromStr = ($validFrom instanceof DateTime) ? 
            $validFrom->format('Y-m-d H:i:s') : 
            "No se pudo obtener la fecha de inicio";
            
        $validToStr = ($validTo instanceof DateTime) ? 
            $validTo->format('Y-m-d H:i:s') : 
            "No se pudo obtener la fecha de fin";
            
        echo "- Válido desde: " . $validFromStr . "\n";
        echo "- Válido hasta: " . $validToStr . "\n";
        
        // Verificar si el certificado está vigente
        $now = new DateTime();
        echo "- Fecha actual: " . $now->format('Y-m-d H:i:s') . "\n";
        
        if ($validFrom instanceof DateTime && $validTo instanceof DateTime) {
            echo "- ¿Certificado vigente? " . 
                ($now >= $validFrom && $now <= $validTo ? "SÍ" : "NO") . "\n";
        } else {
            echo "- No se puede determinar la vigencia del certificado\n";
        }

    } catch (Exception $e) {
        throw new Exception("Error al procesar el certificado: " . $e->getMessage());
    }

    // Crear la llave privada
    $privateKey = new PrivateKey(file_get_contents($keyFile), $passPhrase);
    
    // Crear credencial
    $credential = new Credential($certificate, $privateKey);

    // Verificar que la llave privada corresponde al certificado
    if (!$credential->privateKey()->belongsTo($credential->certificate())) {
        throw new Exception("La llave privada no corresponde al certificado");
    }

    // Crear Fiel y verificar validez
    $fiel = new Fiel($credential);
    
    // Verificar que sea FIEL y no CSD usando DateTimeImmutable en lugar de DateTime
    $now = new DateTimeImmutable();
    if (!$certificate->validOn($now)) {
        throw new Exception("El certificado no es válido en la fecha actual");
    }

    // Si llegamos aquí, la FIEL es válida
    echo "La FIEL ha sido validada correctamente\n";

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