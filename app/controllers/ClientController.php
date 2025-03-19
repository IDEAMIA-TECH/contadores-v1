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
        if (!$this->security->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: ' . BASE_URL . '/clients/create');
            exit;
        }
        
        // Validar y guardar el nuevo cliente
        try {
            $data = [
                'rfc' => $_POST['rfc'] ?? '',
                'business_name' => $_POST['business_name'] ?? '',
                'legal_name' => $_POST['legal_name'] ?? '',
                'fiscal_regime' => $_POST['fiscal_regime'] ?? '',
                'address' => $_POST['address'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'contact_name' => $_POST['contact_name'] ?? '',
                'contact_email' => $_POST['contact_email'] ?? '',
                'contact_phone' => $_POST['contact_phone'] ?? ''
            ];
            
            // Validar datos
            if (empty($data['rfc']) || empty($data['business_name']) || empty($data['email'])) {
                throw new Exception('Por favor complete los campos obligatorios');
            }
            
            if ($this->client->create($data)) {
                $_SESSION['success'] = 'Cliente creado exitosamente';
                header('Location: ' . BASE_URL . '/clients');
            } else {
                throw new Exception('Error al crear el cliente');
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/clients/create');
        }
        exit;
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
} 