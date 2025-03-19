<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../helpers/PdfParser.php';
require_once __DIR__ . '/../models/ClientXml.php';
require_once __DIR__ . '/../helpers/XmlParser.php';

class ClientController {
    private $db;
    private $client;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->client = new Client($this->db);
        $this->security = new Security();
    }
    
    public function index() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Obtener lista de clientes del contador actual
        $clients = $this->client->getByAccountant($_SESSION['user_id']);
        include __DIR__ . '/../views/clients/index.php';
    }
    
    public function showCreateForm() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        $token = $this->security->generateCsrfToken();
        include __DIR__ . '/../views/clients/create.php';
    }
    
    public function create() {
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /clients/create');
            exit;
        }
        
        // Procesar archivo CSF si fue enviado
        $csfPath = '';
        if (isset($_FILES['csf']) && $_FILES['csf']['error'] === UPLOAD_ERR_OK) {
            $csfPath = $this->processCsfFile($_FILES['csf']);
        }
        
        $clientData = [
            'rfc' => $_POST['rfc'],
            'business_name' => $_POST['business_name'],
            'legal_name' => $_POST['legal_name'],
            'fiscal_regime' => $_POST['fiscal_regime'],
            'address' => $_POST['address'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'contact_name' => $_POST['contact_name'] ?? '',
            'contact_email' => $_POST['contact_email'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'accountant_id' => $_SESSION['user_id'],
            'csf_path' => $csfPath
        ];
        
        try {
            $this->client->create($clientData);
            $_SESSION['success'] = 'Cliente creado exitosamente';
            header('Location: /clients');
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al crear el cliente: ' . $e->getMessage();
            header('Location: /clients/create');
        }
    }
    
    private function processCsfFile($file) {
        $targetDir = UPLOAD_PATH . '/csf/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($file['name']);
        $targetFile = $targetDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return $targetFile;
        }
        
        throw new Exception('Error al subir el archivo CSF');
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
            $parser = new XmlParser();
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