<?php
// Configuración de base de datos
$pdo = new PDO('mysql:host=localhost;dbname=ideamiadev_contadores;charset=utf8mb4', 'ideamiadev_contadores', '?y#rPKn59xyretAN');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Mostrar formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Obtener lista de clientes
    $stmt = $pdo->query("SELECT id, name FROM clients ORDER BY name");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <form method="POST">
        <label>Cliente:</label>
        <select name="client_id" required>
            <option value="">Selecciona un cliente</option>
            <?php foreach ($clientes as $cliente): ?>
                <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['name']) ?></option>
            <?php endforeach; ?>
        </select><br>

        <label>Fecha Inicio:</label>
        <input type="date" name="fecha_inicio" required><br>

        <label>Fecha Fin:</label>
        <input type="date" name="fecha_fin" required><br>

        <label>Tipo de Documento:</label>
        <select name="tipo_documento">
            <option value="issued">Emitidos</option>
            <option value="received">Recibidos</option>
        </select><br>

        <label>Tipo de Solicitud:</label>
        <select name="tipo_solicitud">
            <option value="xml">XML</option>
            <option value="metadata">Metadata</option>
        </select><br>

        <button type="submit">Consultar CFDI</button>
    </form>

    <?php exit;
}
?>

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
    $clientId = (int) $_POST['client_id'];

    // Obtener certificado del cliente
    $stmt = $pdo->prepare("SELECT cer_path, key_path, key_password FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        throw new Exception('Cliente no encontrado');
    }

    // Rutas completas
    $cerFile = __DIR__ . '/uploads/' . $cliente['cer_path'];
    $keyFile = __DIR__ . '/uploads/' . $cliente['key_path'];
    $passPhrase = openssl_decrypt(
        $cliente['key_password'],
        'AES-256-CBC',
        getenv('APP_KEY'), // o pon tu llave manual si estás fuera de un framework
        0,
        substr(getenv('APP_KEY'), 0, 16)
    );

    if (!$passPhrase) {
        throw new Exception("No se pudo desencriptar la contraseña del cliente.");
    }

    $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
    $fiel = new Fiel($credential);

    if (! $fiel->isValid()) {
        throw new Exception('La FIEL no es válida.');
    }

    $webClient = new GuzzleWebClient();
    $requestBuilder = new FielRequestBuilder($fiel);
    $service = new Service($requestBuilder, $webClient);

    // Fechas del formulario
    $fechaInicio = $_POST['fecha_inicio'] . 'T00:00:00';
    $fechaFin = $_POST['fecha_fin'] . 'T23:59:59';
    $period = DateTimePeriod::createFromValues($fechaInicio, $fechaFin);

    $downloadType = ($_POST['tipo_documento'] === 'issued') ? DownloadType::issued() : DownloadType::received();
    $requestType = ($_POST['tipo_solicitud'] === 'metadata') ? RequestType::metadata() : RequestType::xml();

    $parameters = QueryParameters::create($period, $downloadType, $requestType);
    $queryResult = $service->query($parameters);

    if (!$queryResult->getStatus()->isAccepted()) {
        throw new Exception('La solicitud fue rechazada: ' . $queryResult->getStatus()->getMessage());
    }

    $requestId = $queryResult->getRequestId();
    echo "✅ Solicitud aceptada. ID: $requestId<br>";

    do {
        sleep(10);
        $verifyResult = $service->verify($requestId);
        echo "⏳ Estado: " . $verifyResult->getStatusRequest()->getMessage() . "<br>";
    } while (!$verifyResult->getStatusRequest()->isFinished());

    $packages = $verifyResult->getPackagesIds();
    if (empty($packages)) {
        echo "⚠️ No se encontraron CFDI.<br>";
        exit;
    }

    $outputDir = __DIR__ . '/descargas_xml';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    foreach ($packages as $i => $packageId) {
        $download = $service->download($packageId);
        if (!$download->getStatus()->isAccepted()) {
            echo "❌ Error al descargar paquete {$packageId}<br>";
            continue;
        }

        $filePath = $outputDir . "/cliente{$clientId}_cfdi_{$i}.zip";
        file_put_contents($filePath, $download->getPackageContent());
        echo "✅ Paquete {$i} guardado como {$filePath}<br>";
    }

    echo "🎉 Descarga finalizada.<br>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>