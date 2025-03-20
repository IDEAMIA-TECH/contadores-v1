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
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder;
use PhpCfdi\Credentials\Credential;
use PhpCfdi\Credentials\Certificate;
use PhpCfdi\Credentials\PrivateKey;

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

            // Validar CSRF token primero
            if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de seguridad inválido');
            }

            // Debug de los datos recibidos
            error_log("Datos POST recibidos: " . print_r($_POST, true));

            // Validar parámetros con más detalle
            $clientId = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
            $requestType = filter_input(INPUT_POST, 'request_type'); // metadata o cfdi
            $documentType = filter_input(INPUT_POST, 'document_type'); // issued o received
            $fechaInicio = filter_input(INPUT_POST, 'fecha_inicio');
            $fechaFin = filter_input(INPUT_POST, 'fecha_fin');

            // Validación detallada de parámetros
            if (!$clientId) {
                throw new Exception('ID de cliente no válido');
            }
            if (!in_array($requestType, ['metadata', 'cfdi'])) {
                throw new Exception('Tipo de solicitud no válido');
            }
            if (!in_array($documentType, ['issued', 'received'])) {
                throw new Exception('Tipo de documento no válido');
            }
            if (!$fechaInicio || !$fechaFin) {
                throw new Exception('Fechas no válidas');
            }

            // Obtener cliente y sus archivos SAT
            $client = $this->client->getClientById($clientId);
            if (!$client || empty($client['cer_path']) || empty($client['key_path'])) {
                throw new Exception('Cliente no tiene configurada su e.firma');
            }

            // Crear credencial con los archivos del cliente
            $cerFile = ROOT_PATH . '/uploads/' . $client['cer_path'];
            $keyFile = ROOT_PATH . '/uploads/' . $client['key_path'];
            $password = $client['key_password'];

            if (!file_exists($cerFile)) {
                throw new Exception('Archivo CER no encontrado');
            }
            if (!file_exists($keyFile)) {
                throw new Exception('Archivo KEY no encontrado');
            }

            try {
                $certificate = new Certificate(
                    file_get_contents($cerFile)
                );
                
                // Agregar logs para diagnóstico del certificado
                error_log("Información del certificado:");
                error_log("Serial Number: " . $certificate->serialNumber()->bytes());
                error_log("RFC: " . $certificate->rfc());
                
                // Convertir las fechas a objetos DateTime antes de formatearlas
                $validFrom = $certificate->validFrom();
                $validTo = $certificate->validTo();
                
                error_log("Válido desde: " . ($validFrom instanceof \DateTime ? $validFrom->format('Y-m-d H:i:s') : 'No disponible'));
                error_log("Válido hasta: " . ($validTo instanceof \DateTime ? $validTo->format('Y-m-d H:i:s') : 'No disponible'));
                
                // Obtener y mostrar todos los key usages
                $parsed = $certificate->publicKey()->parsed();
                $keyUsages = $parsed['tbsCertificate']['extensions']['keyUsage'] ?? [];
                error_log("Key Usages encontrados: " . print_r($keyUsages, true));
                
                // Obtener información extendida del certificado
                $extendedInfo = openssl_x509_parse(
                    $certificate->pem()
                );
                error_log("Información extendida del certificado: " . print_r($extendedInfo, true));

                // Nueva validación más flexible para FIEL vs CSD
                $isFiel = false;
                
                // Verificar por key usages
                if (!empty($keyUsages)) {
                    $requiredUsages = ['digitalSignature', 'nonRepudiation', 'keyEncipherment'];
                    $foundUsages = array_intersect($requiredUsages, $keyUsages);
                    $isFiel = count($foundUsages) >= 2; // Si tiene al menos 2 de los usos requeridos
                }
                
                // Verificar por propósito del certificado en extensiones
                if (isset($extendedInfo['extensions']['extendedKeyUsage'])) {
                    $keyPurpose = $extendedInfo['extensions']['extendedKeyUsage'];
                    // Las FIEL suelen tener estos propósitos
                    if (strpos($keyPurpose, 'clientAuth') !== false || 
                        strpos($keyPurpose, 'emailProtection') !== false) {
                        $isFiel = true;
                    }
                }
                
                // Verificar por el nombre del certificado
                if (isset($extendedInfo['subject']['OU'])) {
                    $ou = $extendedInfo['subject']['OU'];
                    if (stripos($ou, 'FIEL') !== false || stripos($ou, 'e.firma') !== false) {
                        $isFiel = true;
                    }
                }

                if (!$isFiel) {
                    error_log("Certificado no validado como FIEL. Detalles de validación:");
                    error_log("Key Usages: " . implode(', ', $keyUsages));
                    error_log("Extended Key Usage: " . ($extendedInfo['extensions']['extendedKeyUsage'] ?? 'No disponible'));
                    error_log("OU: " . ($extendedInfo['subject']['OU'] ?? 'No disponible'));
                    throw new Exception('El certificado no parece ser una FIEL válida. Por favor, verifique que está usando el certificado correcto.');
                }

                // Obtener y validar la contraseña
                if (empty($client['key_password'])) {
                    throw new Exception('No se ha configurado la contraseña de la FIEL');
                }

                // Log de la contraseña encriptada
                error_log("Contraseña encriptada en BD: " . $client['key_password']);
                
                // Intentar desencriptar la contraseña
                $decryptedPassword = openssl_decrypt(
                    $client['key_password'],
                    'AES-256-CBC',
                    getenv('APP_KEY'),
                    0,
                    substr(getenv('APP_KEY'), 0, 16)
                );
                
                // Log de la contraseña desencriptada
                error_log("Contraseña desencriptada: " . ($decryptedPassword ?: 'ERROR AL DESENCRIPTAR'));
                error_log("APP_KEY utilizada: " . substr(getenv('APP_KEY'), 0, 10) . '...');
                
                if ($decryptedPassword === false) {
                    throw new Exception('Error al desencriptar la contraseña. Verifique APP_KEY.');
                }
                
                try {
                    // Intentar crear la llave privada con la contraseña desencriptada
                    $privateKey = new PrivateKey(
                        file_get_contents($keyFile),
                        $decryptedPassword
                    );
                    
                    // Si llegamos aquí, la contraseña funcionó
                    error_log("Llave privada creada exitosamente con la contraseña desencriptada");
                    
                } catch (Exception $e) {
                    error_log("Error al crear llave privada: " . $e->getMessage());
                    error_log("Longitud de la contraseña desencriptada: " . strlen($decryptedPassword));
                    
                    // Intentar con la contraseña sin desencriptar como fallback
                    try {
                        $privateKey = new PrivateKey(
                            file_get_contents($keyFile),
                            $client['key_password']
                        );
                        error_log("Llave privada creada exitosamente con la contraseña encriptada (fallback)");
                    } catch (Exception $e2) {
                        throw new Exception('La contraseña de la llave privada es incorrecta. Error: ' . $e2->getMessage());
                    }
                }

                // Verificar que la llave privada funcione
                if (!$privateKey->sign('test')) {
                    throw new Exception('La llave privada no es válida o está dañada');
                }

                // Crear credencial
                $fiel = new Credential($certificate, $privateKey);

                // Crear el servicio de descarga masiva
                $requestBuilder = new FielRequestBuilder($fiel);
                $webClient = new GuzzleWebClient();
                $service = new Service($requestBuilder, $webClient);

                // Crear el periodo de consulta
                $period = new DateTimePeriod(
                    new DateTime($fechaInicio),
                    new DateTime($fechaFin)
                );

                // Crear los parámetros de consulta
                $request = new QueryParameters(
                    $period,
                    $documentType === 'issued' ? QueryParameters::DOCUMENT_TYPE_ISSUED : QueryParameters::DOCUMENT_TYPE_RECEIVED,
                    $requestType === 'metadata' ? QueryParameters::DOWNLOAD_TYPE_METADATA : QueryParameters::DOWNLOAD_TYPE_CFDI
                );

                // Realizar la consulta
                $query = $service->query($request);

                // Verificar el resultado
                if (!$query->getStatus()->isAccepted()) {
                    throw new Exception('Error al realizar la consulta: ' . $query->getStatus()->getMessage());
                }

                // Devolver respuesta exitosa
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Solicitud iniciada correctamente',
                    'requestId' => $query->getRequestId(),
                    'data' => [
                        'requestType' => $requestType,
                        'documentType' => $documentType,
                        'fechaInicio' => $fechaInicio,
                        'fechaFin' => $fechaFin
                    ]
                ]);
                exit;

            } catch (Exception $e) {
                error_log("Error detallado en proceso de FIEL: " . $e->getMessage());
                throw new Exception('Error al procesar la e.firma: ' . $e->getMessage());
            }

        } catch (Exception $e) {
            error_log("Error en downloadSatMasivo: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    private function processXmlFile($xmlContent, $clientId, $fileName) {
        try {
            // Crear instancia del parser de XML
            $xmlParser = new CfdiXmlParser();
            
            // Validar que el contenido sea un XML válido
            if (!$xmlParser->isValidXml($xmlContent)) {
                throw new Exception("El archivo {$fileName} no es un XML válido");
            }
            
            // Parsear el XML
            $xmlData = $xmlParser->parse($xmlContent);
            
            // Validar que sea un CFDI
            if (!$xmlParser->isCfdi($xmlData)) {
                throw new Exception("El archivo {$fileName} no es un CFDI válido");
            }
            
            // Preparar datos para la base de datos
            $data = [
                'client_id' => $clientId,
                'uuid' => $xmlParser->getUuid($xmlData),
                'xml_content' => $xmlContent,
                'emisor_rfc' => $xmlParser->getEmisorRfc($xmlData),
                'emisor_nombre' => $xmlParser->getEmisorNombre($xmlData),
                'receptor_rfc' => $xmlParser->getReceptorRfc($xmlData),
                'receptor_nombre' => $xmlParser->getReceptorNombre($xmlData),
                'fecha' => $xmlParser->getFecha($xmlData),
                'tipo_comprobante' => $xmlParser->getTipoComprobante($xmlData),
                'total' => $xmlParser->getTotal($xmlData),
                'file_name' => $fileName,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Crear instancia del modelo ClientXml
            $clientXml = new ClientXml($this->db);
            
            // Verificar si el XML ya existe
            if ($clientXml->exists($data['uuid'], $clientId)) {
                throw new Exception("El XML {$fileName} ya existe en la base de datos");
            }
            
            // Guardar en la base de datos
            if (!$clientXml->create($data)) {
                throw new Exception("Error al guardar el XML {$fileName} en la base de datos");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error procesando XML {$fileName}: " . $e->getMessage());
            throw $e;
        }
    }
} 