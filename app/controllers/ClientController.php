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
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }

            // Validar token CSRF
            if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de seguridad inválido');
            }

            $clientId = filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT);
            if (!$clientId) {
                throw new Exception('Cliente no válido');
            }

            // Validar que se haya subido un archivo
            if (!isset($_FILES['xml_file']) || $_FILES['xml_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No se recibió el archivo XML correctamente');
            }

            // Validar tipo de archivo
            $fileInfo = pathinfo($_FILES['xml_file']['name']);
            if (strtolower($fileInfo['extension']) !== 'xml') {
                throw new Exception('El archivo debe ser un XML');
            }

            // Crear directorio si no existe
            $uploadDir = ROOT_PATH . '/uploads/xml/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generar nombre único para el archivo
            $fileName = uniqid('xml_') . '.xml';
            $filePath = $uploadDir . $fileName;

            // Mover archivo
            if (!move_uploaded_file($_FILES['xml_file']['tmp_name'], $filePath)) {
                throw new Exception('Error al guardar el archivo');
            }

            // Procesar XML
            $xmlParser = new CfdiXmlParser();
            $xmlData = $xmlParser->parse($filePath);

            // Guardar información en la base de datos
            $xmlModel = new ClientXml($this->db);
            $xmlData['client_id'] = $clientId;
            $xmlData['file_path'] = 'xml/' . $fileName;
            
            if (!$xmlModel->create($xmlData)) {
                throw new Exception('Error al guardar la información del XML');
            }

            $_SESSION['success'] = 'XML procesado correctamente';
            header('Location: ' . BASE_URL . '/clients/view/' . $clientId);

        } catch (Exception $e) {
            error_log("Error en uploadXml: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/clients/upload-xml?id=' . ($clientId ?? ''));
            exit;
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
            
            if ($this->client->update($data)) {
                $_SESSION['success'] = 'Cliente actualizado exitosamente';
                header('Location: ' . BASE_URL . '/clients');
            } else {
                throw new Exception('Error al actualizar el cliente');
            }
            
        } catch (Exception $e) {
            error_log("Error en update: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['form_data'] = $_POST;
            header('Location: ' . BASE_URL . '/clients/edit/' . $id);
        }
        exit;
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
            
            include __DIR__ . '/../views/clients/view.php';
            
        } catch (Exception $e) {
            error_log("Error en view: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
    }
} 