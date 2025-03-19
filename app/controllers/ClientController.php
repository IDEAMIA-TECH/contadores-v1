<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/Security.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../helpers/PdfParser.php';
require_once __DIR__ . '/../models/ClientXml.php';
require_once __DIR__ . '/../helpers/CfdiXmlParser.php';

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
            
            error_log("Token recibido en store: " . ($_POST['csrf_token'] ?? 'no token'));
            error_log("Token en sesión: " . ($_SESSION['csrf_token'] ?? 'no token'));
            
            if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                error_log("Token CSRF inválido en store");
                throw new Exception('Token de seguridad inválido. Por favor, intente nuevamente.');
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
                'csf_path' => $_POST['csf_path'] ?? null,
                'contact_name' => $_POST['contact_name'] ?? null,
                'contact_email' => $_POST['contact_email'] ?? null,
                'contact_phone' => $_POST['contact_phone'] ?? null,
                'accountant_id' => $_SESSION['user_id'] // ID del contador actual
            ];
            
            error_log("Datos a insertar: " . print_r($data, true));
            
            if ($this->client->create($data)) {
                $_SESSION['success'] = 'Cliente creado exitosamente';
                header('Location: ' . BASE_URL . '/clients');
            } else {
                throw new Exception('Error al crear el cliente');
            }
            
        } catch (Exception $e) {
            error_log("Error en store: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST; // Mantener los datos del formulario
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
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$clientId) {
            $_SESSION['error'] = 'Cliente no válido';
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
        
        $client = $this->client->find($clientId);
        if (!$client) {
            $_SESSION['error'] = 'Cliente no encontrado';
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
        
        $token = $this->security->generateCsrfToken();
        include __DIR__ . '/../views/clients/upload-xml.php';
    }
    
    public function uploadXml() {
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
        
        $clientId = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
        if (!$clientId) {
            $_SESSION['error'] = 'Cliente no válido';
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
        
        if (!isset($_FILES['xml']) || $_FILES['xml']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Error al subir el archivo';
            header('Location: ' . BASE_URL . "/clients/upload-xml?id=$clientId");
            exit;
        }
        
        try {
            $xmlPath = $this->processXmlFile($_FILES['xml']);
            $parser = new CfdiXmlParser();
            $xmlData = $parser->parse($xmlPath);
            
            $xmlData['client_id'] = $clientId;
            $xmlData['xml_path'] = $xmlPath;
            
            $clientXml = new ClientXml($this->db);
            $clientXml->create($xmlData);
            
            $_SESSION['success'] = 'XML procesado correctamente';
            header('Location: ' . BASE_URL . "/clients/view?id=$clientId");
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . "/clients/upload-xml?id=$clientId");
        }
        exit;
    }
    
    private function processXmlFile($file) {
        $targetDir = UPLOAD_PATH . '/xml/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($file['name']);
        $targetFile = $targetDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return $targetFile;
        }
        
        throw new Exception('Error al guardar el archivo XML');
    }

    public function processCSF() {
        try {
            // Asegurarnos de que no haya salida antes
            ob_clean();
            
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }

            // Validar token CSRF
            if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de seguridad inválido');
            }

            // Validar archivo
            if (!isset($_FILES['csf_file']) || $_FILES['csf_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Error al subir el archivo');
            }

            $file = $_FILES['csf_file'];
            
            // Validar tipo de archivo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if ($mimeType !== 'application/pdf') {
                throw new Exception('El archivo debe ser PDF. Tipo detectado: ' . $mimeType);
            }

            // Procesar el archivo
            $targetDir = UPLOAD_PATH . '/csf/';
            if (!file_exists($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    throw new Exception('Error al crear el directorio para CSF');
                }
            }

            if (!is_writable($targetDir)) {
                throw new Exception('El directorio CSF no tiene permisos de escritura');
            }

            $fileName = uniqid() . '_' . basename($file['name']);
            $targetFile = $targetDir . $fileName;

            error_log("Intentando mover archivo a: " . $targetFile);
            
            if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
                throw new Exception('Error al guardar el archivo. Código: ' . $file['error']);
            }

            // Verificar que el archivo existe y es legible
            if (!file_exists($targetFile) || !is_readable($targetFile)) {
                throw new Exception('El archivo guardado no es accesible');
            }

            // Usar PdfParser para extraer los datos
            $parser = new PdfParser();
            $extractedData = $parser->parseCSF($targetFile);

            if (empty($extractedData)) {
                throw new Exception('No se pudieron extraer datos del PDF');
            }

            // Formatear la respuesta
            $data = [
                'success' => true,
                'data' => [
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
                    'csf_path' => $fileName
                ]
            ];

            error_log("Datos extraídos del PDF: " . print_r($extractedData, true));
            error_log("Datos formateados: " . print_r($data, true));

            // Asegurar que no haya salida antes del JSON
            if (headers_sent($file, $line)) {
                error_log("Headers already sent in $file:$line");
                throw new Exception('Error interno del servidor');
            }

            header('Content-Type: application/json');
            echo json_encode($data);

        } catch (Exception $e) {
            error_log("Error en processCSF: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Asegurar que no haya salida antes del JSON de error
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(400);
            }
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
} 