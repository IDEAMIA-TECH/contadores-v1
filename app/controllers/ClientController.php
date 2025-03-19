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

            // Si es una solicitud GET, mostrar el formulario
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
                return;
            }

            // Para solicitudes POST, procesar la carga del archivo
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

                // Cargar y validar el XML
                $xmlContent = file_get_contents($filePath);
                if ($xmlContent === false) {
                    throw new Exception('Error al leer el archivo XML');
                }

                try {
                    // Cargar el XML como objeto
                    $xml = new SimpleXMLElement($xmlContent);
                    
                    // Debug: Mostrar el contenido del XML
                    error_log("Contenido del XML: " . $xmlContent);
                    
                    // Registrar los namespaces
                    $namespaces = $xml->getNamespaces(true);
                    error_log("Namespaces encontrados: " . print_r($namespaces, true));
                    
                    // Acceder a los elementos usando los namespaces correctos
                    $cfdi = $xml->children($namespaces['cfdi']);
                    error_log("Datos del CFDI: " . print_r($cfdi, true));
                    
                    // El TimbreFiscalDigital está dentro del Complemento
                    $tfd = null;
                    if (isset($cfdi->Complemento)) {
                        error_log("Complemento encontrado: " . print_r($cfdi->Complemento, true));
                        
                        // Registrar los namespaces del complemento
                        $complementoNamespaces = $cfdi->Complemento->getNamespaces(true);
                        error_log("Namespaces del complemento: " . print_r($complementoNamespaces, true));
                        
                        foreach ($cfdi->Complemento->children() as $complemento) {
                            error_log("Procesando complemento: " . $complemento->getName());
                            if ($complemento->getName() === 'TimbreFiscalDigital') {
                                $tfd = $complemento;
                                error_log("TimbreFiscalDigital encontrado: " . print_r($tfd, true));
                                break;
                            }
                        }
                    } else {
                        error_log("No se encontró el nodo Complemento en el XML");
                    }
                    
                    if (!$tfd) {
                        throw new Exception('El XML no contiene el TimbreFiscalDigital');
                    }

                    // Extraer datos del XML con validación
                    $xmlData = [
                        'client_id' => $clientId,
                        'xml_path' => 'xml/' . $fileName,
                        'uuid' => (string)$tfd['UUID'],
                        'serie' => (string)($xml['Serie'] ?? ''),
                        'folio' => (string)($xml['Folio'] ?? ''),
                        'fecha' => (string)$xml['Fecha'],
                        'fecha_timbrado' => (string)$tfd['FechaTimbrado'],
                        'subtotal' => (float)($xml['SubTotal'] ?? 0),
                        'total' => (float)($xml['Total'] ?? 0),
                        'tipo_comprobante' => (string)($xml['TipoDeComprobante'] ?? ''),
                        'forma_pago' => (string)($xml['FormaPago'] ?? ''),
                        'metodo_pago' => (string)($xml['MetodoPago'] ?? ''),
                        'moneda' => (string)($xml['Moneda'] ?? 'MXN'),
                        'lugar_expedicion' => (string)($xml['LugarExpedicion'] ?? ''),
                        'emisor_rfc' => (string)($cfdi->Emisor['Rfc'] ?? ''),
                        'emisor_nombre' => (string)($cfdi->Emisor['Nombre'] ?? ''),
                        'emisor_regimen_fiscal' => (string)($cfdi->Emisor['RegimenFiscal'] ?? ''),
                        'receptor_rfc' => (string)($cfdi->Receptor['Rfc'] ?? ''),
                        'receptor_nombre' => (string)($cfdi->Receptor['Nombre'] ?? ''),
                        'receptor_regimen_fiscal' => (string)($cfdi->Receptor['RegimenFiscalReceptor'] ?? ''),
                        'receptor_domicilio_fiscal' => (string)($cfdi->Receptor['DomicilioFiscalReceptor'] ?? ''),
                        'receptor_uso_cfdi' => (string)($cfdi->Receptor['UsoCFDI'] ?? ''),
                        'total_impuestos_trasladados' => (float)($cfdi->Impuestos['TotalImpuestosTrasladados'] ?? 0),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    // Debug: Mostrar los datos extraídos
                    error_log("Datos extraídos del XML: " . print_r($xmlData, true));

                    // Validar campos requeridos antes de guardar
                    $requiredFields = [
                        'uuid', 'fecha', 'fecha_timbrado',
                        'emisor_rfc', 'emisor_nombre', 'emisor_regimen_fiscal',
                        'receptor_rfc', 'receptor_nombre', 'receptor_regimen_fiscal'
                    ];

                    foreach ($requiredFields as $field) {
                        if (empty($xmlData[$field])) {
                            error_log("Campo requerido vacío: {$field}");
                            throw new Exception("El campo {$field} es requerido y está vacío en el XML");
                        }
                    }

                    // Guardar en la base de datos
                    $xmlModel = new ClientXml($this->db);
                    if (!$xmlModel->create($xmlData)) {
                        // Si hay error, eliminar el archivo subido
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        throw new Exception('Error al guardar el XML');
                    }

                } catch (Exception $e) {
                    // Si hay error, eliminar el archivo subido
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    error_log("Error detallado: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
                    throw new Exception('Error al procesar el XML: ' . $e->getMessage());
                }

                $_SESSION['success'] = 'XML procesado correctamente';
                header('Location: ' . BASE_URL . '/clients/view/' . $clientId);
                exit;
            }

        } catch (Exception $e) {
            error_log("Error en uploadXml: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            
            // Si hay error y se subió un archivo, eliminarlo
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }

            // Redirigir según el tipo de solicitud
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Location: ' . BASE_URL . '/clients/upload-xml?id=' . ($clientId ?? ''));
            } else {
                header('Location: ' . BASE_URL . '/clients');
            }
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