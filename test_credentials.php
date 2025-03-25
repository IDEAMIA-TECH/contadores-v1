<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/database.php';

use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\WebClient\GuzzleWebClient;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;

// 🔄 Mostrar formulario si no hay POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db = Database::getInstance()->getConnection();
    $clients = $db->query("SELECT id, rfc FROM clients")->fetchAll();
    ?>

    <form method="POST">
        <label>Cliente:
            <select name="client_id" required>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>"><?= $client['rfc'] ?></option>
                <?php endforeach; ?>
            </select>
        </label><br><br>

        <label>Fecha inicio: <input type="date" name="fecha_inicio" required></label><br>
        <label>Fecha fin: <input type="date" name="fecha_fin" required></label><br><br>

        <label>Tipo de solicitud:
            <select name="request_type">
                <option value="xml">XML</option>
                <option value="metadata">Metadata</option>
            </select>
        </label><br>

        <label>Tipo de documento:
            <select name="document_type">
                <option value="issued">Emitidos</option>
                <option value="received">Recibidos</option>
            </select>
        </label><br><br>

        <button type="submit">Enviar solicitud al SAT</button>
    </form>

    <?php
    exit;
}

try {
    // 🧠 Obtener datos del formulario
    $clientId = $_POST['client_id'];
    $fechaInicio = $_POST['fecha_inicio'];
    $fechaFin = $_POST['fecha_fin'];
    $requestType = $_POST['request_type'] === 'metadata' ? RequestType::metadata() : RequestType::xml();
    $downloadType = $_POST['document_type'] === 'issued' ? DownloadType::issued() : DownloadType::received();

    // 🔌 Conectar a la BD y obtener info del cliente
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();

    if (!$client) {
        throw new Exception("Cliente no encontrado.");
    }

    $cerFile = __DIR__ . '/uploads/' . $client['cer_path'];
    $keyFile = __DIR__ . '/uploads/' . $client['key_path'];

    if (!file_exists($cerFile) || !file_exists($keyFile)) {
        throw new Exception("Certificado o llave no encontrados.");
    }

    // 🔐 Desencriptar contraseña con APP_KEY
    $appKey = getenv('APP_KEY');
    $iv = substr($appKey, 0, 16);

    $passPhrase = openssl_decrypt(
        $client['key_password'],
        'AES-256-CBC',
        $appKey,
        0,
        $iv
    );

    if ($passPhrase === false) {
        throw new Exception("Error al desencriptar la contraseña de la FIEL.");
    }

    // 🔐 Crear credencial FIEL
    $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
    $fiel = new Fiel($credential);

    if (!$fiel->isValid()) {
        throw new Exception('FIEL no válida.');
    }

    // 📡 Crear servicio SAT
    $webClient = new GuzzleWebClient();
    $requestBuilder = new FielRequestBuilder($fiel);
    $service = new Service($requestBuilder, $webClient);

    $start = $fechaInicio . 'T00:00:00';
    $end = $fechaFin . 'T23:59:59';
    $period = DateTimePeriod::createFromValues($start, $end);

    $parameters = QueryParameters::create($period, $downloadType, $requestType);
    $queryResult = $service->query($parameters);

    if (!$queryResult->getStatus()->isAccepted()) {
        throw new Exception('SAT rechazó la solicitud: ' . $queryResult->getStatus()->getMessage());
    }

    $requestId = $queryResult->getRequestId();
    echo "✅ Solicitud aceptada. ID: $requestId<br>";

    // 🔁 Esperar a que se procese
    do {
        sleep(10);
        $verifyResult = $service->verify($requestId);
        echo "⏳ Estado: " . $verifyResult->getStatusRequest()->getMessage() . "<br>";
    } while (!$verifyResult->getStatusRequest()->isFinished());

    // 📦 Descargar paquetes
    $packageIds = $verifyResult->getPackagesIds();
    if (empty($packageIds)) {
        echo "⚠️ No se encontraron CFDI.\n";
    } else {
        echo "📦 " . count($packageIds) . " paquete(s) encontrados.<br>";

        $outputDir = __DIR__ . "/descargas_xml/cliente_{$clientId}";
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        foreach ($packageIds as $index => $packageId) {
            $downloadResult = $service->download($packageId);
            if (!$downloadResult->getStatus()->isAccepted()) {
                echo "❌ Error en paquete $packageId<br>";
                continue;
            }

            $file = $outputDir . "/CFDI_{$index}.zip";
            file_put_contents($file, $downloadResult->getPackageContent());
            echo "✅ Descargado: CFDI_{$index}.zip<br>";
        }

        echo "🎉 Descarga completada.";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>