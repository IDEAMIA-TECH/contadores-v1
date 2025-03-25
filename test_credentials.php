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

// Si el formulario no ha sido enviado, mostrar el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Obtener lista de clientes de la base de datos
    try {
        require_once __DIR__ . '/app/config/database.php';
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("
            SELECT id, business_name, rfc 
            FROM clients 
            WHERE cer_path IS NOT NULL 
            AND key_path IS NOT NULL 
            AND status = 'active'
            ORDER BY business_name ASC
        ");
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error de conexión: " . $e->getMessage());
        die("Error de conexión a la base de datos");
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test Descarga SAT</title>
        <style>
            body { font-family: Arial; padding: 20px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            select, input { padding: 5px; width: 300px; }
            button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h2>Test Descarga SAT</h2>
        <form method="POST">
            <div class="form-group">
                <label>Cliente:</label>
                <select name="client_id" required>
                    <option value="">Seleccione un cliente</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['business_name']) ?> (<?= $client['rfc'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Fecha Inicio:</label>
                <input type="date" name="fecha_inicio" required>
            </div>
            <div class="form-group">
                <label>Fecha Fin:</label>
                <input type="date" name="fecha_fin" required>
            </div>
            <div class="form-group">
                <label>Tipo de Documento:</label>
                <select name="document_type" required>
                    <option value="issued">Emitidos</option>
                    <option value="received">Recibidos</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de Solicitud:</label>
                <select name="request_type" required>
                    <option value="metadata">Metadata</option>
                    <option value="xml">XML</option>
                </select>
            </div>
            <button type="submit">Iniciar Descarga</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Procesar el formulario
try {
    require_once __DIR__ . '/app/config/database.php';
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT cer_path, key_path, key_password 
        FROM clients 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->execute([$_POST['client_id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        throw new Exception("Cliente no encontrado");
    }

    // Configurar rutas y contraseña
    $cerFile = __DIR__ . '/uploads/' . $client['cer_path'];
    $keyFile = __DIR__ . '/uploads/' . $client['key_path'];
    $passPhrase = openssl_decrypt(
        $client['key_password'],
        'AES-256-CBC',
        getenv('APP_KEY'),
        0,
        substr(getenv('APP_KEY'), 0, 16)
    );

    // ✅ Cargar y validar la FIEL
    $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
    $fiel = new Fiel($credential);

    if (! $fiel->isValid()) {
        throw new Exception('❌ La FIEL no es válida.');
    }

    // 🌐 Crear el cliente del SAT
    $webClient = new GuzzleWebClient();
    $requestBuilder = new FielRequestBuilder($fiel);
    $service = new Service($requestBuilder, $webClient);

    // Crear periodo con las fechas del formulario
    $period = DateTimePeriod::createFromValues(
        $_POST['fecha_inicio'] . 'T00:00:00',
        $_POST['fecha_fin'] . 'T23:59:59'
    );

    // Crear parámetros según selección del formulario
    $parameters = QueryParameters::create(
        $period,
        $_POST['document_type'] === 'issued' ? DownloadType::issued() : DownloadType::received(),
        $_POST['request_type'] === 'metadata' ? RequestType::metadata() : RequestType::xml()
    );

    // 📨 Realizar la solicitud
    $queryResult = $service->query($parameters);

    if (! $queryResult->getStatus()->isAccepted()) {
        throw new Exception('❌ La solicitud fue rechazada por el SAT: ' . $queryResult->getStatus()->getMessage());
    }

    $requestId = $queryResult->getRequestId();
    echo "✅ Solicitud aceptada. ID: $requestId\n";

    // 🔄 Verificar el estado cada 10 segundos
    do {
        sleep(10);
        $verifyResult = $service->verify($requestId);
        echo "⏳ Estado actual: " . $verifyResult->getStatusRequest()->getMessage() . "\n"; // ✅ CORREGIDO
    } while (! $verifyResult->getStatusRequest()->isFinished());

    // 📦 Descargar los paquetes ZIP
    $packageIds = $verifyResult->getPackagesIds();

    if (empty($packageIds)) {
        echo "⚠️ No se encontraron CFDI para este periodo.\n";
    } else {
        echo "📦 Se encontraron " . count($packageIds) . " paquete(s) para descargar.\n";

        // 🗂 Crear carpeta para guardar los ZIP
        $outputDir = __DIR__ . '/descargas_xml';
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        foreach ($packageIds as $index => $packageId) {
            $downloadResult = $service->download($packageId);
            if (! $downloadResult->getStatus()->isAccepted()) {
                echo "❌ Error al descargar paquete {$packageId}\n";
                continue;
            }

            $outputFile = $outputDir . "/CFDI_{$index}.zip";
            file_put_contents($outputFile, $downloadResult->getPackageContent());
            echo "✅ Paquete {$index} descargado como {$outputFile}\n";
        }

        echo "🎉 Proceso de descarga completado.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>