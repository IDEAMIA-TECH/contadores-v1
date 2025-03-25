<?php
// Definir constantes de base de datos antes de cualquier require
define('DB_HOST', 'localhost');
define('DB_NAME', 'ideamiadev_contadores');
define('DB_USER', 'ideamiadev_contadores');
define('DB_PASS', '?y#rPKn59xyretAN');
define('APP_KEY', 'cNSwqrBEKHYf+qdpED41jKHZf0iIfuvF8K698Sgx3p4=');

// Verificar rutas
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("Error: No se encuentra el archivo autoload.php");
}
if (!file_exists(__DIR__ . '/app/config/database.php')) {
    die("Error: No se encuentra el archivo de configuración de la base de datos");
}

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

    // 📡 Crear servicio SAT con timeouts más largos
    $guzzleClient = new \GuzzleHttp\Client([
        'timeout' => 300, // 5 minutos
        'connect_timeout' => 60,
        'http_errors' => false,
        'verify' => true
    ]);
    
    $webClient = new GuzzleWebClient($guzzleClient);
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

    // 🔁 Esperar a que se procese con reintentos
    $maxAttempts = 30; // Máximo número de intentos
    $attempt = 0;
    $waitTime = 10; // Tiempo inicial de espera en segundos

    do {
        try {
            sleep($waitTime);
            $verifyResult = $service->verify($requestId);
            echo "⏳ Estado: " . $verifyResult->getStatusRequest()->getMessage() . "<br>";
            
            // Incrementar tiempo de espera gradualmente
            $waitTime = min(30, $waitTime + 5);
            $attempt++;
            
            // Si llevamos muchos intentos, informar al usuario
            if ($attempt % 5 == 0) {
                echo "⌛ Intentando... ($attempt/$maxAttempts)<br>";
                ob_flush();
                flush();
            }
            
        } catch (Exception $e) {
            echo "⚠️ Reintentando... (" . $e->getMessage() . ")<br>";
            sleep(5); // Esperar antes de reintentar
            continue;
        }
        
        // Salir si se alcanza el máximo de intentos
        if ($attempt >= $maxAttempts) {
            throw new Exception("Se alcanzó el tiempo máximo de espera. Por favor, verifique más tarde con el ID: $requestId");
        }
        
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