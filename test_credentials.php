<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpCfdi\Credentials\Credential;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;


$cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';
$pemKeyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';
$passPhrase = 'Japc20078'; // contraseña para abrir la llave privada

$fiel = PhpCfdi\Credentials\Credential::openFiles($cerFile, $pemKeyFile, $passPhrase);

$sourceString = 'texto a firmar';
// alias de privateKey/sign/verify
$signature = $fiel->sign($sourceString);
echo "</br>Firma: </br>" . base64_encode($signature) . PHP_EOL;

// alias de certificado/publicKey/verify
$verify = $fiel->verify($sourceString, $signature);
var_dump($verify); // bool(true)

// objeto certificado
$certificado = $fiel->certificate();
echo "</br>RFC: </br>" . $certificado->rfc() . PHP_EOL; // el RFC del certificado
echo "</br>Nombre:  </br>" . $certificado->legalName() . PHP_EOL; // el nombre del propietario del certificado
echo "</br>Sucursal: </br>" . $certificado->branchName() . PHP_EOL; // el nombre de la sucursal (en CSD, en FIEL está vacía)
echo "</br>Serial: </br>" . $certificado->serialNumber()->bytes() . PHP_EOL; // número de serie del certificado









// verificar que la FIEL sea válida (no sea CSD y sea vigente acorde a la fecha del sistema)
if (! $fiel->isValid()) {
    return;
}

// creación del web client basado en Guzzle que implementa WebClientInterface
// para usarlo necesitas instalar guzzlehttp/guzzle, pues no es una dependencia directa
$webClient = new GuzzleWebClient();

// creación del objeto encargado de crear las solicitudes firmadas usando una FIEL
$requestBuilder = new FielRequestBuilder($fiel);

// Creación del servicio
$service = new Service($requestBuilder, $webClient);