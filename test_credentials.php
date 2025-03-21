<?php 
require 'vendor/autoload.php';
date_default_timezone_set("America/Mexico_City");

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
        
        // Obtener fechas del certificado
        $validFrom = $certificate->validFrom();
        $validTo = $certificate->validTo();
        
        echo "- Tipo de validFrom: " . gettype($validFrom) . "\n";
        echo "- Tipo de validTo: " . gettype($validTo) . "\n";
        
        // Formatear fechas de manera segura
        if (is_string($validFrom)) {
            echo "- Válido desde (string): " . $validFrom . "\n";
            $validFrom = new DateTimeImmutable($validFrom);
        }
        echo "- Válido desde: " . $validFrom->format('Y-m-d H:i:s') . "\n";
        
        if (is_string($validTo)) {
            echo "- Válido hasta (string): " . $validTo . "\n";
            $validTo = new DateTimeImmutable($validTo);
        }
        echo "- Válido hasta: " . $validTo->format('Y-m-d H:i:s') . "\n";

        // Verificar si el certificado está vigente
        $now = new DateTimeImmutable();
        echo "- Fecha actual: " . $now->format('Y-m-d H:i:s') . "\n";

        // Verificar validez usando el método validOn
        $isValid = $certificate->validOn($now);
        echo "- ¿Es certificado válido? " . ($isValid ? "SÍ" : "NO") . "\n";

        if (!$isValid) {
            throw new Exception("El certificado no es válido en la fecha actual");
        }

        echo "La FIEL ha sido validada correctamente\n";

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
    
    // Verificar validez del certificado con logs detallados
    $now = new DateTimeImmutable();
    echo "\nValidación detallada del certificado:\n";
    echo "- Fecha actual: " . $now->format('Y-m-d H:i:s') . "\n";
    echo "- Fecha inicio certificado: " . $certificate->validFrom()->format('Y-m-d H:i:s') . "\n";
    echo "- Fecha fin certificado: " . $certificate->validTo()->format('Y-m-d H:i:s') . "\n";

    // Verificar si está dentro del rango de fechas
    $isAfterStart = $now >= $certificate->validFrom();
    $isBeforeEnd = $now <= $certificate->validTo();
    
    echo "- ¿Posterior a fecha inicio? " . ($isAfterStart ? "SÍ" : "NO") . "\n";
    echo "- ¿Anterior a fecha fin? " . ($isBeforeEnd ? "SÍ" : "NO") . "\n";
    
    // Verificar otros aspectos del certificado
    echo "- RFC del certificado: " . $certificate->rfc() . "\n";
    echo "- Número de serie: " . $certificate->serialNumber()->bytes() . "\n";
    echo "- ¿Es certificado válido? " . ($certificate->validOn($now) ? "SÍ" : "NO") . "\n";

    // Verificar si es FIEL
    try {
        if (!$certificate->validOn($now)) {
            throw new Exception(sprintf(
                "El certificado no es válido en la fecha actual.\nVálido desde: %s\nVálido hasta: %s\nFecha actual: %s",
                $certificate->validFrom()->format('Y-m-d H:i:s'),
                $certificate->validTo()->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s')
            ));
        }
        
        echo "La FIEL ha sido validada correctamente\n";
    } catch (Exception $e) {
        throw new Exception("Error de validación del certificado: " . $e->getMessage());
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