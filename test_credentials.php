<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpCfdi\Credentials\Credential;
use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;

$cerFile = __DIR__ . '/uploads/sat/sat_cer_67db47408517a.cer';
$pemKeyFile = __DIR__ . '/uploads/sat/sat_key_67db4740851a2.key';
$passPhrase = 'Japc20078'; // contraseña para abrir la llave privada

$fiel = PhpCfdi\Credentials\Credential::openFiles($cerFile, $pemKeyFile, $passPhrase);

$sourceString = 'texto a firmar';
// alias de privateKey/sign/verify
$signature = $fiel->sign($sourceString);
echo base64_encode($signature), PHP_EOL;

// alias de certificado/publicKey/verify
$verify = $fiel->verify($sourceString, $signature);
var_dump($verify); // bool(true)

// objeto certificado
$certificado = $fiel->certificate();
echo $certificado->rfc(), PHP_EOL; // el RFC del certificado
echo $certificado->legalName(), PHP_EOL; // el nombre del propietario del certificado
echo $certificado->branchName(), PHP_EOL; // el nombre de la sucursal (en CSD, en FIEL está vacía)
echo $certificado->serialNumber()->bytes(), PHP_EOL; // número de serie del certificado