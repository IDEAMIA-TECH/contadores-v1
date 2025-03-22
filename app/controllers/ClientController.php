<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/Security.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../helpers/PdfParser.php';
require_once __DIR__ . '/../models/ClientXml.php';
require_once __DIR__ . '/../helpers/CfdiXmlParser.php';
require_once __DIR__ . '/../services/SatService.php';

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

class ClientController {
    private $db;
    private $security;
    private $client;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new Security();
        $this->client = new Client($this->db);
    }
    
    public function index() {
        if (!$this->security->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Obtener lista de clientes
        $clients = $this->client->getAllClients();
        include __DIR__ . '/../views/clients/index.php';
    }
    
    public function create() {
        if (!$this->security->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Generar token CSRF para el formulario
        $token = $this->security->generateCsrfToken();
        include __DIR__ . '/../views/clients/create.php';
    }
    
    public function store() {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }
            
            if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de seguridad inválido');
            }
            
            // Validar datos requeridos
            $requiredFields = [
                'rfc', 'business_name', 'fiscal_regime',
                'street', 'exterior_number', 'neighborhood',
                'city', 'state', 'zip_code', 'email', 'phone'
            ];
            
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("El campo {$field} es obligatorio");
                }
            }
            
            // Preparar datos para el modelo
            $data = [
                'rfc' => $_POST['rfc'],
                'business_name' => $_POST['business_name'],
                'legal_name' => $_POST['legal_name'] ?? null,
                'fiscal_regime' => $_POST['fiscal_regime'],
                'street' => $_POST['street'],
                'exterior_number' => $_POST['exterior_number'],
                'interior_number' => $_POST['interior_number'] ?? null,
                'neighborhood' => $_POST['neighborhood'],
                'city' => $_POST['city'],
                'state' => $_POST['state'],
                'zip_code' => $_POST['zip_code'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'contact_name' => $_POST['contact_name'] ?? null,
                'contact_email' => $_POST['contact_email'] ?? null,
                'contact_phone' => $_POST['contact_phone'] ?? null,
                'accountant_id' => $_SESSION['user_id']
            ];

            // Procesar archivos del SAT si se proporcionaron
            if (isset($_FILES['cer_file']) && $_FILES['cer_file']['error'] === UPLOAD_ERR_OK) {
                $cerFile = $this->processSatFile($_FILES['cer_file'], 'cer');
                $data['cer_path'] = $cerFile;
            }

            if (isset($_FILES['key_file']) && $_FILES['key_file']['error'] === UPLOAD_ERR_OK) {
                $keyFile = $this->processSatFile($_FILES['key_file'], 'key');
                $data['key_path'] = $keyFile;
            }

            // Procesar contraseña de la FIEL si se proporcionó
            if (!empty($_POST['key_password'])) {
                // Log de la contraseña original
                error_log("Contraseña original a guardar: " . $_POST['key_password']);
                
                // Encriptar la contraseña
                $encryptedPassword = openssl_encrypt(
                    $_POST['key_password'],
                    'AES-256-CBC',
                    getenv('APP_KEY'),
                    0,
                    substr(getenv('APP_KEY'), 0, 16)
                );
                
                // Log de la contraseña encriptada
                error_log("Contraseña encriptada para BD: " . $encryptedPassword);
                
                $data['key_password'] = $encryptedPassword;
            }
            
            if ($this->client->create($data)) {
                $_SESSION['success'] = 'Cliente creado exitosamente';
                header('Location: ' . BASE_URL . '/clients');
            } else {
                throw new Exception('Error al crear el cliente');
            }
            
        } catch (Exception $e) {
            error_log("Error en store: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . '/clients/create');
        }
        exit;
    }
    
    private function formatAddress($data) {
        $parts = [];
        if (!empty($data['street'])) $parts[] = $data['street'];
        if (!empty($data['exterior_number'])) $parts[] = "Ext. " . $data['exterior_number'];
        if (!empty($data['interior_number'])) $parts[] = "Int. " . $data['interior_number'];
        if (!empty($data['neighborhood'])) $parts[] = $data['neighborhood'];
        if (!empty($data['city'])) $parts[] = $data['city'];
        if (!empty($data['state'])) $parts[] = $data['state'];
        if (!empty($data['zip_code'])) $parts[] = "C.P. " . $data['zip_code'];
        
        return implode(', ', array_filter($parts));
    }
    
    public function extractCsfData() {
        if (!isset($_FILES['csf']) || $_FILES['csf']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No se recibió el archivo']);
            return;
        }
        
        try {
            $parser = new PdfParser();
            $data = $parser->parseCSF($_FILES['csf']['tmp_name']);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    public function showUploadXml() {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }

            // Obtener el ID del cliente de la URL
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('ID de cliente no válido');
            }

            // Obtener datos del cliente
            $client = $this->client->getClientById($id);
            if (!$client) {
                throw new Exception('Cliente no encontrado');
            }

            // Generar token CSRF para el formulario
            $token = $this->security->generateCsrfToken();
            
            include __DIR__ . '/../views/clients/upload-xml.php';

        } catch (Exception $e) {
            error_log("Error en showUploadXml: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
    }
    
    public function uploadXml() {
        try {
            // Verificar autenticación
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }

            // Si es GET, mostrar la vista
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $clientId = isset($_GET['id']) ? $_GET['id'] : null;
                if (!$clientId) {
                    throw new Exception('ID de cliente no proporcionado');
                }

                $client = $this->client->getClientById($clientId);
                if (!$client) {
                    throw new Exception('Cliente no encontrado');
                }

                // Generar nuevo token CSRF si no existe
                if (!isset($_SESSION['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }

                include APP_PATH . '/views/clients/upload-xml.php';
                return;
            }

            // Si es POST, procesar la carga de archivos
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Debug de tokens
                $receivedToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : 'no token';
                $sessionToken = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'no token en sesión';
                error_log("Token recibido: " . $receivedToken);
                error_log("Token en sesión: " . $sessionToken);

                // Validar token CSRF
                if (!isset($_POST['csrf_token'])) {
                    throw new Exception('Token CSRF no proporcionado');
                }

                if (!$this->security->validateCsrfToken($_POST['csrf_token'])) {
                    error_log("Tokens no coinciden - Recibido: {$_POST['csrf_token']} vs Sesión: {$_SESSION['csrf_token']}");
                    throw new Exception('Token de seguridad inválido');
                }

                // Aumentar límites de PHP
                ini_set('max_execution_time', '300');
                ini_set('max_input_time', '300');
                ini_set('memory_limit', '512M');
                ini_set('post_max_size', '500M');
                ini_set('upload_max_filesize', '500M');
                set_time_limit(300);

                // Validar client_id
                if (empty($_POST['client_id'])) {
                    throw new Exception('ID de cliente no proporcionado');
                }

                // Validar archivos
                if (empty($_FILES['xml_files'])) {
                    throw new Exception('No se recibieron archivos');
                }

                $clientId = $_POST['client_id'];
                $filesProcessed = 0;
                $errors = [];
                
                // Procesar archivos en lotes para evitar sobrecarga de memoria
                $batchSize = 50; // Procesar 50 archivos a la vez
                $totalFiles = count($_FILES['xml_files']['name']);
                
                for ($i = 0; $i < $totalFiles; $i += $batchSize) {
                    $batch = array_slice($_FILES['xml_files']['name'], $i, $batchSize);
                    $batchTmp = array_slice($_FILES['xml_files']['tmp_name'], $i, $batchSize);
                    $batchErrors = array_slice($_FILES['xml_files']['error'], $i, $batchSize);
                    
                    foreach ($batch as $index => $fileName) {
                        if ($batchErrors[$index] === UPLOAD_ERR_OK) {
                            try {
                                $xmlContent = file_get_contents($batchTmp[$index]);
                                
                                // Procesar el XML
                                $this->processXmlFile($xmlContent, $clientId, $fileName);
                                $filesProcessed++;
                                
                                // Liberar memoria
                                unset($xmlContent);
                            } catch (Exception $e) {
                                $errors[] = "Error en archivo {$fileName}: " . $e->getMessage();
                            }
                        } else {
                            $errors[] = "Error al subir {$fileName}: " . $this->getUploadErrorMessage($batchErrors[$index]);
                        }
                    }
                    
                    // Liberar memoria después de cada lote
                    gc_collect_cycles();
                }

                // Preparar respuesta
                $response = [
                    'success' => true,
                    'files_processed' => $filesProcessed,
                    'redirect_url' => BASE_URL . '/clients/view/' . $clientId
                ];

                if (!empty($errors)) {
                    $response['errors'] = $errors;
                }

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }

        } catch (Exception $e) {
            error_log("Error en uploadXml: " . $e->getMessage());
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            } else {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ' . BASE_URL . '/clients');
            }
            exit;
        }
    }

    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'El archivo excede el tamaño máximo permitido por el servidor';
            case UPLOAD_ERR_FORM_SIZE:
                return 'El archivo excede el tamaño máximo permitido por el formulario';
            case UPLOAD_ERR_PARTIAL:
                return 'El archivo se subió parcialmente';
            case UPLOAD_ERR_NO_FILE:
                return 'No se subió ningún archivo';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Falta la carpeta temporal';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Error al escribir el archivo en el disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Una extensión de PHP detuvo la carga del archivo';
            default:
                return 'Error desconocido al subir el archivo';
        }
    }

    public function processCsf() {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }

            if (!isset($_FILES['csf_file']) || $_FILES['csf_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se recibió el archivo correctamente');
            }

            // Validar el token CSRF
            if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de seguridad inválido');
            }

            // Validar el tipo de archivo
            $fileType = mime_content_type($_FILES['csf_file']['tmp_name']);
            if ($fileType !== 'application/pdf') {
                throw new Exception('El archivo debe ser un PDF');
            }

            // Procesar el PDF
            $pdfParser = new PdfParser();
            $extractedData = $pdfParser->parseCSF($_FILES['csf_file']['tmp_name']);

            // Mover el archivo a una ubicación permanente
            $uploadDir = ROOT_PATH . '/uploads/csf/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = uniqid('csf_') . '.pdf';
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['csf_file']['tmp_name'], $filePath)) {
                throw new Exception('Error al guardar el archivo');
            }

            // Formatear los datos para el formulario
            $formData = [
                'rfc' => $extractedData['rfc'] ?? '',
                'business_name' => $extractedData['razon_social'] ?? '',
                'legal_name' => $extractedData['nombre_legal'] ?? '',
                'fiscal_regime' => $extractedData['regimen_fiscal'] ?? '',
                'street' => $extractedData['calle'] ?? '',
                'exterior_number' => $extractedData['numero_exterior'] ?? '',
                'interior_number' => $extractedData['numero_interior'] ?? '',
                'neighborhood' => $extractedData['colonia'] ?? '',
                'city' => $extractedData['municipio'] ?? '',
                'state' => $extractedData['estado'] ?? '',
                'zip_code' => $extractedData['codigo_postal'] ?? '',
                'csf_path' => 'csf/' . $fileName
            ];

            // Devolver respuesta
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $formData
            ]);

        } catch (Exception $e) {
            error_log("Error en processCsf: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function edit($id = null) {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }
            
            if (!$id) {
                throw new Exception('ID de cliente no proporcionado');
            }
            
            // Obtener datos del cliente
            $client = $this->client->getClientById($id);
            if (!$client) {
                throw new Exception('Cliente no encontrado');
            }
            
            // Obtener datos de contacto
            $contact = $this->client->getClientContact($id);
            
            // Generar token CSRF para el formulario
            $token = $this->security->generateCsrfToken();
            
            include __DIR__ . '/../views/clients/edit.php';
            
        } catch (Exception $e) {
            error_log("Error en edit: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
    }

    public function update($id = null) {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }
            
            if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de seguridad inválido');
            }
            
            if (!$id) {
                throw new Exception('ID de cliente no proporcionado');
            }
            
            // Validar datos requeridos
            $requiredFields = [
                'rfc', 'business_name', 'fiscal_regime',
                'street', 'exterior_number', 'neighborhood',
                'city', 'state', 'zip_code', 'email', 'phone'
            ];
            
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("El campo {$field} es obligatorio");
                }
            }
            
            // Preparar datos para actualizar
            $data = [
                'id' => $id,
                'rfc' => $_POST['rfc'],
                'business_name' => $_POST['business_name'],
                'legal_name' => $_POST['legal_name'] ?? null,
                'fiscal_regime' => $_POST['fiscal_regime'],
                'street' => $_POST['street'],
                'exterior_number' => $_POST['exterior_number'],
                'interior_number' => $_POST['interior_number'] ?? null,
                'neighborhood' => $_POST['neighborhood'],
                'city' => $_POST['city'],
                'state' => $_POST['state'],
                'zip_code' => $_POST['zip_code'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'contact_name' => $_POST['contact_name'] ?? null,
                'contact_email' => $_POST['contact_email'] ?? null,
                'contact_phone' => $_POST['contact_phone'] ?? null
            ];

            // Procesar archivos del SAT si se proporcionaron
            if (isset($_FILES['cer_file']) && $_FILES['cer_file']['error'] === UPLOAD_ERR_OK) {
                $cerFile = $this->processSatFile($_FILES['cer_file'], 'cer');
                $data['cer_path'] = $cerFile;
            }

            if (isset($_FILES['key_file']) && $_FILES['key_file']['error'] === UPLOAD_ERR_OK) {
                $keyFile = $this->processSatFile($_FILES['key_file'], 'key');
                $data['key_path'] = $keyFile;
            }

            // Actualizar contraseña solo si se proporcionó una nueva
            if (!empty($_POST['key_password'])) {
                $data['key_password'] = openssl_encrypt(
                    $_POST['key_password'],
                    'AES-256-CBC',
                    getenv('APP_KEY'),
                    0,
                    substr(getenv('APP_KEY'), 0, 16)
                );
            }

            if ($this->client->update($data)) {
                $_SESSION['success'] = 'Cliente actualizado exitosamente';
                header('Location: ' . BASE_URL . '/clients/view/' . $id);
            } else {
                throw new Exception('Error al actualizar el cliente');
            }
            
        } catch (Exception $e) {
            error_log("Error en update: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/clients/edit/' . $id);
        }
        exit;
    }

    private function processSatFile($file, $type) {
        try {
            // Validar tipo de archivo
            $allowedExtensions = [
                'cer' => ['cer'],
                'key' => ['key']
            ];

            $fileInfo = pathinfo($file['name']);
            $extension = strtolower($fileInfo['extension']);

            if (!in_array($extension, $allowedExtensions[$type])) {
                throw new Exception("Tipo de archivo no válido para {$type}");
            }

            // Crear directorio si no existe
            $uploadDir = ROOT_PATH . '/uploads/sat/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generar nombre único
            $fileName = uniqid("sat_{$type}_") . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception("Error al guardar el archivo {$type}");
            }

            return 'sat/' . $fileName;

        } catch (Exception $e) {
            error_log("Error procesando archivo SAT: " . $e->getMessage());
            throw $e;
        }
    }

    public function view($id = null) {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }
            
            if (!$id) {
                throw new Exception('ID de cliente no proporcionado');
            }
            
            // Obtener datos del cliente
            $client = $this->client->getClientById($id);
            if (!$client) {
                throw new Exception('Cliente no encontrado');
            }
            
            // Obtener datos de contacto
            $contact = $this->client->getClientContact($id);
            
            // Obtener documentos del cliente
            $documents = $this->client->getClientDocuments($id);
            
            // Generar token CSRF para los formularios de descarga
            $token = $this->security->generateCsrfToken();
            
            include __DIR__ . '/../views/clients/view.php';
            
        } catch (Exception $e) {
            error_log("Error en view: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
    }

    public function downloadSat() {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }

            // Validar token CSRF
            if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de seguridad inválido');
            }

            // Validar parámetros
            $clientId = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
            $tipo = filter_input(INPUT_POST, 'tipo');
            $fechaInicio = filter_input(INPUT_POST, 'fecha_inicio');
            $fechaFin = filter_input(INPUT_POST, 'fecha_fin');

            if (!$clientId || !$tipo || !$fechaInicio || !$fechaFin) {
                throw new Exception('Parámetros inválidos');
            }

            if (!in_array($tipo, ['emitidas', 'recibidas'])) {
                throw new Exception('Tipo de descarga inválido');
            }

            // Inicializar servicio SAT
            $satService = new SatService($this->db);
            
            // Descargar XMLs
            $zipFile = $satService->downloadXmls($clientId, $tipo, $fechaInicio, $fechaFin);
            
            // Enviar archivo
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="facturas_' . $tipo . '_' . date('Y-m-d') . '.zip"');
            header('Content-Length: ' . filesize($zipFile));
            header('Pragma: no-cache');
            header('Expires: 0');
            
            readfile($zipFile);
            unlink($zipFile);
            exit;

        } catch (Exception $e) {
            error_log("Error en downloadSat: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function satPortal($client_id) {
        try {
            // Verificar autenticación
            if (!$this->security->isAuthenticated()) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }

            // Verificar que el client_id sea válido
            if (!is_numeric($client_id)) {
                throw new Exception('ID de cliente inválido');
            }

            // Obtener el cliente
            $client = $this->client->getClientById($client_id);
            if (!$client) {
                throw new Exception('Cliente no encontrado');
            }

            // Generar token CSRF
            $token = $this->security->generateCsrfToken();

            // Pasar datos a la vista
            $data = [
                'client_id' => $client_id,
                'client' => $client,
                'token' => $token // Agregamos el token CSRF
            ];
            
            // Incluir la vista con los datos
            extract($data);
            require_once __DIR__ . '/../views/clients/sat_portal.php';

        } catch (Exception $e) {
            error_log("Error en satPortal: " . $e->getMessage());
            $_SESSION['error'] = 'Error al acceder al portal SAT';
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
    }

    public function downloadSatMasivo() {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }
    
            if (!isset($_POST['csrf_token']) || !$this->security->validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception('Token de seguridad inválido');
            }
    
            $clientId = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
            if (!$clientId) {
                throw new Exception('ID de cliente no proporcionado o inválido');
            }
    
            $client = $this->client->getClientById($clientId);
            if (!$client) {
                throw new Exception('Cliente no encontrado');
            }
    
            $cerFile = ROOT_PATH . '/uploads/' . $client['cer_path'];
            $keyFile = ROOT_PATH . '/uploads/' . $client['key_path'];
    
            if (!file_exists($cerFile) || !file_exists($keyFile)) {
                throw new Exception('Archivos de certificado o llave privada no encontrados');
            }
    
            $passPhrase = openssl_decrypt(
                $client['key_password'],
                'AES-256-CBC',
                getenv('APP_KEY'),
                0,
                substr(getenv('APP_KEY'), 0, 16)
            );
    
            if ($passPhrase === false) {
                throw new Exception('Error al desencriptar la contraseña');
            }
    
            $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
            $fiel = new Fiel($credential);
    
            if (!$fiel->isValid()) {
                throw new Exception('La FIEL no es válida');
            }
    
            $webClient = new GuzzleWebClient();
            $requestBuilder = new FielRequestBuilder($fiel);
            $service = new Service($requestBuilder, $webClient);
    
            $startDate = $_POST['fecha_inicio'] ?? '';
            $endDate = $_POST['fecha_fin'] ?? '';
    
            if (empty($startDate) || empty($endDate)) {
                throw new Exception('Fechas no proporcionadas');
            }
    
            $startDateTime = (strpos($startDate, 'T') === false) ? $startDate . 'T00:00:00' : $startDate;
            $endDateTime = (strpos($endDate, 'T') === false) ? $endDate . 'T23:59:59' : $endDate;
    
            $period = DateTimePeriod::createFromValues($startDateTime, $endDateTime);
    
            // ✅ DownloadType: issued / received (descarga de CFDI)
            $downloadType = ($_POST['document_type'] === 'issued')
                ? DownloadType::issued()
                : DownloadType::received();
    
            // ✅ RequestType: xml / metadata (tipo de contenido)
            $requestType = ($_POST['request_type'] === 'metadata')
                ? RequestType::metadata()
                : RequestType::xml();
    
            // ✅ Crear parámetros de consulta con orden correcto
            $parameters = QueryParameters::create(
                $period,
                $downloadType,
                $requestType
            );
    
            $queryResult = $service->query($parameters);
    
            if (!$queryResult->getStatus()->isAccepted()) {
                throw new Exception('La solicitud fue rechazada por el SAT: ' . $queryResult->getStatus()->getMessage());
            }
    
            $requestId = $queryResult->getRequestId();
    
            $stmt = $this->db->prepare("
                INSERT INTO sat_download_requests 
                (client_id, request_id, request_type, document_type, start_date, end_date, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'REQUESTED', NOW(), NOW())
            ");
    
            $stmt->execute([
                $clientId,
                $requestId,
                $_POST['request_type'],
                $_POST['document_type'],
                $startDate,
                $endDate
            ]);
    
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Solicitud de descarga iniciada correctamente',
                'requestId' => $requestId
            ]);
    
        } catch (Exception $e) {
            error_log("Error en downloadSatMasivo: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function checkDownloadStatus() {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }

            // Obtener requestId de POST o GET
            $requestId = $_POST['requestId'] ?? $_GET['requestId'] ?? null;
            
            // Log para diagnóstico
            error_log("Verificando estado de solicitud:");
            error_log("POST data: " . print_r($_POST, true));
            error_log("GET data: " . print_r($_GET, true));
            error_log("RequestId recibido: " . ($requestId ?? 'no proporcionado'));

            if (empty($requestId)) {
                throw new Exception('ID de solicitud no proporcionado');
            }

            // Obtener información de la solicitud
            $stmt = $this->db->prepare("
                SELECT r.*, c.cer_path, c.key_path, c.key_password 
                FROM sat_download_requests r
                JOIN clients c ON c.id = r.client_id
                WHERE r.request_id = ? AND r.status != 'COMPLETED'
                LIMIT 1
            ");
            
            error_log("Ejecutando consulta para requestId: " . $requestId);
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new Exception('Solicitud no encontrada o ya completada');
            }

            error_log("Solicitud encontrada, procesando verificación...");

            // Crear credenciales
            $cerFile = ROOT_PATH . '/uploads/' . $request['cer_path'];
            $keyFile = ROOT_PATH . '/uploads/' . $request['key_path'];
            $passPhrase = openssl_decrypt(
                $request['key_password'],
                'AES-256-CBC',
                getenv('APP_KEY'),
                0,
                substr(getenv('APP_KEY'), 0, 16)
            );

            $credential = Credential::openFiles($cerFile, $keyFile, $passPhrase);
            $fiel = new Fiel($credential);
            
            // Crear servicio
            $webClient = new GuzzleWebClient();
            $requestBuilder = new FielRequestBuilder($fiel);
            $service = new Service($requestBuilder, $webClient);

            // Verificar estado
            $verify = $service->verify($requestId);
            $status = $verify->getStatus();

            if ($status->isExpired()) {
                throw new Exception('La solicitud ha expirado');
            }

            $response = [
                'success' => true,
                'requestId' => $requestId, // Agregamos el requestId en la respuesta
                'status' => $status->isPending() ? 'PENDING' : ($status->isFinished() ? 'READY' : 'UNKNOWN'),
                'message' => $status->isPending() ? 
                    'La solicitud está siendo procesada' : 
                    ($status->isFinished() ? 'Los archivos están listos para descargar' : 'Estado desconocido')
            ];

            if ($status->isFinished()) {
                $response['packagesCount'] = $verify->getPackagesCount();
            }

            error_log("Enviando respuesta: " . print_r($response, true));
            
            header('Content-Type: application/json');
            echo json_encode($response);

        } catch (Exception $e) {
            error_log("Error en checkDownloadStatus: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'post' => $_POST,
                    'get' => $_GET
                ]
            ]);
        }
        exit;
    }

    private function processXmlFile($xmlContent, $clientId, $fileName) {
        try {
            // Crear directorio para XMLs si no existe
            $uploadDir = ROOT_PATH . '/uploads/xml/' . $clientId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generar nombre único para el archivo
            $uniqueFileName = uniqid('xml_') . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $fileName);
            $filePath = $uploadDir . $uniqueFileName;

            // Guardar el archivo XML
            if (file_put_contents($filePath, $xmlContent) === false) {
                throw new Exception("Error al guardar el archivo {$fileName}");
            }

            // Parsear el XML
            $xmlParser = new CfdiXmlParser();
            $xmlData = $xmlParser->parse($xmlContent);

            // Guardar en la base de datos
            $clientXml = new ClientXml($this->db);
            $xmlData['client_id'] = $clientId;
            $xmlData['xml_path'] = 'xml/' . $clientId . '/' . $uniqueFileName;
            
            if (!$clientXml->create($xmlData)) {
                throw new Exception("Error al guardar los datos del XML {$fileName} en la base de datos");
            }

            return true;

        } catch (Exception $e) {
            error_log("Error procesando XML {$fileName}: " . $e->getMessage());
            throw new Exception("Error al procesar {$fileName}: " . $e->getMessage());
        }
    }

    public function downloadPackages($requestId) {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }

            // Obtener información de la solicitud
            $stmt = $this->db->prepare("
                SELECT * FROM sat_download_requests 
                WHERE request_id = ? AND status = 'READY_TO_DOWNLOAD'
                LIMIT 1
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new Exception('Solicitud no encontrada o no lista para descargar');
            }

            // Verificar estado y obtener IDs de paquetes
            $verify = $service->verify($requestId);
            if (!$verify->getStatus()->isAccepted() || !$verify->getStatusRequest()->isFinished()) {
                throw new Exception('La solicitud no está lista para descargar');
            }

            $packagesIds = $verify->getPackagesIds();
            $downloadedFiles = [];

            foreach ($packagesIds as $packageId) {
                $download = $service->download($packageId);
                if (!$download->getStatus()->isAccepted()) {
                    error_log("Error al descargar paquete {$packageId}: " . $download->getStatus()->getMessage());
                    continue;
                }

                // Guardar el paquete
                $fileName = "sat_package_{$packageId}.zip";
                $filePath = ROOT_PATH . '/uploads/sat/packages/' . $fileName;
                if (!is_dir(dirname($filePath))) {
                    mkdir(dirname($filePath), 0755, true);
                }

                file_put_contents($filePath, $download->getPackageContent());
                $downloadedFiles[] = $fileName;
            }

            return [
                'success' => true,
                'files' => $downloadedFiles
            ];

        } catch (Exception $e) {
            error_log("Error en downloadPackages: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 