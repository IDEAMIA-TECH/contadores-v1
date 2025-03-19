<?php
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../helpers/PdfParser.php';

class ClientController {
    private $db;
    private $client;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->client = new Client($this->db);
        $this->security = new Security();
        $this->security->initSession();
    }
    
    public function showCreateForm() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
            header('Location: /login');
            exit;
        }
        
        $token = $this->security->generateCsrfToken();
        require_once __DIR__ . '/../views/clients/create.php';
    }
    
    public function create() {
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /clients/create');
            exit;
        }
        
        // Validar datos básicos
        $clientData = [
            'rfc' => filter_input(INPUT_POST, 'rfc', FILTER_SANITIZE_STRING),
            'business_name' => filter_input(INPUT_POST, 'business_name', FILTER_SANITIZE_STRING),
            'legal_name' => filter_input(INPUT_POST, 'legal_name', FILTER_SANITIZE_STRING),
            'fiscal_regime' => filter_input(INPUT_POST, 'fiscal_regime', FILTER_SANITIZE_STRING),
            'address' => filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'phone' => filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING),
            'contact_name' => filter_input(INPUT_POST, 'contact_name', FILTER_SANITIZE_STRING),
            'contact_phone' => filter_input(INPUT_POST, 'contact_phone', FILTER_SANITIZE_STRING),
            'contact_email' => filter_input(INPUT_POST, 'contact_email', FILTER_SANITIZE_EMAIL),
            'accountant_id' => $_SESSION['user_id']
        ];
        
        // Validar campos requeridos
        if (empty($clientData['rfc']) || empty($clientData['business_name'])) {
            $_SESSION['error'] = 'Los campos RFC y Razón Social son obligatorios';
            $_SESSION['form_data'] = $clientData;
            header('Location: /clients/create');
            exit;
        }
        
        // Validar formato de RFC
        if (!preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/', $clientData['rfc'])) {
            $_SESSION['error'] = 'El formato del RFC no es válido';
            $_SESSION['form_data'] = $clientData;
            header('Location: /clients/create');
            exit;
        }
        
        // Procesar archivo CSF si fue enviado
        if (isset($_FILES['csf_pdf']) && $_FILES['csf_pdf']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_PATH . '/csf/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = $clientData['rfc'] . '_' . time() . '.pdf';
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['csf_pdf']['tmp_name'], $filePath)) {
                $clientData['csf_path'] = $filePath;
                
                // Parsear PDF para extraer datos
                $pdfParser = new PdfParser();
                $extractedData = $pdfParser->parseCSF($filePath);
                
                if ($extractedData) {
                    // Actualizar datos con la información extraída del PDF
                    $clientData = array_merge($clientData, $extractedData);
                }
            }
        }
        
        try {
            $clientId = $this->client->create($clientData);
            
            if ($clientId) {
                $_SESSION['success'] = 'Cliente registrado correctamente';
                header('Location: /clients');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al registrar el cliente: ' . $e->getMessage();
            $_SESSION['form_data'] = $clientData;
            header('Location: /clients/create');
            exit;
        }
    }
    
    public function extractCsfData() {
        if (!isset($_FILES['csf_pdf']) || $_FILES['csf_pdf']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No se recibió el archivo PDF']);
            return;
        }
        
        $tempFile = $_FILES['csf_pdf']['tmp_name'];
        $pdfParser = new PdfParser();
        $extractedData = $pdfParser->parseCSF($tempFile);
        
        if ($extractedData) {
            echo json_encode(['success' => true, 'data' => $extractedData]);
        } else {
            echo json_encode(['error' => 'No se pudieron extraer los datos del PDF']);
        }
    }
} 