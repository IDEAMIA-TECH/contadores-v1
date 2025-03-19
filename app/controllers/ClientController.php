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

                // Validar que se hayan subido archivos
                if (!isset($_FILES['xml_files']) || empty($_FILES['xml_files']['name'][0])) {
                    throw new Exception('No se recibieron archivos XML');
                }

                $uploadedFiles = [];
                $errors = [];
                
                // Procesar cada archivo
                foreach ($_FILES['xml_files']['tmp_name'] as $key => $tmpName) {
                    $fileName = $_FILES['xml_files']['name'][$key];
                    $fileError = $_FILES['xml_files']['error'][$key];

                    // Validar el archivo
                    if ($fileError !== UPLOAD_ERR_OK) {
                        $errors[] = "Error al subir el archivo $fileName";
                        continue;
                    }

                    // Validar tipo de archivo
                    $fileInfo = pathinfo($fileName);
                    if (strtolower($fileInfo['extension']) !== 'xml') {
                        $errors[] = "El archivo $fileName debe ser un XML";
                        continue;
                    }

                    // Crear directorio si no existe
                    $uploadDir = ROOT_PATH . '/uploads/xml/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // Generar nombre único para el archivo
                    $newFileName = uniqid('xml_') . '.xml';
                    $filePath = $uploadDir . $newFileName;

                    // Mover archivo
                    if (!move_uploaded_file($tmpName, $filePath)) {
                        $errors[] = "Error al guardar el archivo $fileName";
                        continue;
                    }

                    try {
                        // Procesar el XML
                        $xmlContent = file_get_contents($filePath);
                        $xml = new SimpleXMLElement($xmlContent);
                        
                        // Registrar todos los namespaces disponibles
                        $namespaces = $xml->getDocNamespaces(true);
                        error_log("Namespaces encontrados: " . print_r($namespaces, true));
                        
                        // Registrar los namespaces manualmente
                        foreach ($namespaces as $prefix => $namespace) {
                            $xml->registerXPathNamespace($prefix ?: 'cfdi', $namespace);
                        }
                        
                        // Intentar diferentes rutas XPath para encontrar el TimbreFiscalDigital
                        $tfdPaths = [
                            '//tfd:TimbreFiscalDigital',
                            '//TimbreFiscalDigital',
                            '//*[local-name()="TimbreFiscalDigital"]'
                        ];
                        
                        $tfd = null;
                        foreach ($tfdPaths as $path) {
                            error_log("Intentando ruta XPath: " . $path);
                            $nodes = $xml->xpath($path);
                            if (!empty($nodes)) {
                                $tfd = $nodes[0];
                                error_log("TFD encontrado usando: " . $path);
                                break;
                            }
                        }
                        
                        if (!$tfd) {
                            error_log("XML completo: " . $xml->asXML());
                            throw new Exception('No se encontró el TimbreFiscalDigital en el XML');
                        }
                        
                        // Extraer datos del XML con validación y logs
                        $xmlData = [
                            'client_id' => $clientId,
                            'xml_path' => 'xml/' . $newFileName
                        ];
                        
                        // Extraer UUID y fecha de timbrado
                        $uuid = (string)$tfd['UUID'];
                        $fechaTimbrado = (string)$tfd['FechaTimbrado'];
                        
                        error_log("UUID encontrado: " . $uuid);
                        error_log("Fecha de timbrado encontrada: " . $fechaTimbrado);
                        
                        if (empty($uuid)) {
                            throw new Exception('UUID no encontrado en el XML');
                        }
                        
                        $xmlData['uuid'] = $uuid;
                        $xmlData['fecha_timbrado'] = $fechaTimbrado;
                        
                        // Extraer datos del comprobante
                        $comprobante = [
                            'Serie' => (string)$xml['Serie'],
                            'Folio' => (string)$xml['Folio'],
                            'Fecha' => (string)$xml['Fecha'],
                            'SubTotal' => (float)$xml['SubTotal'],
                            'Total' => (float)$xml['Total'],
                            'TipoDeComprobante' => (string)$xml['TipoDeComprobante'],
                            'FormaPago' => (string)$xml['FormaPago'],
                            'MetodoPago' => (string)$xml['MetodoPago'],
                            'Moneda' => (string)$xml['Moneda'],
                            'LugarExpedicion' => (string)$xml['LugarExpedicion']
                        ];
                        
                        error_log("Datos del comprobante: " . print_r($comprobante, true));
                        
                        // Agregar datos del comprobante al array principal
                        $xmlData = array_merge($xmlData, [
                            'serie' => $comprobante['Serie'],
                            'folio' => $comprobante['Folio'],
                            'fecha' => $comprobante['Fecha'],
                            'subtotal' => $comprobante['SubTotal'],
                            'total' => $comprobante['Total'],
                            'tipo_comprobante' => $comprobante['TipoDeComprobante'],
                            'forma_pago' => $comprobante['FormaPago'],
                            'metodo_pago' => $comprobante['MetodoPago'],
                            'moneda' => $comprobante['Moneda'],
                            'lugar_expedicion' => $comprobante['LugarExpedicion']
                        ]);
                        
                        // Extraer datos del emisor y receptor
                        $emisor = $xml->xpath('//cfdi:Emisor')[0] ?? null;
                        $receptor = $xml->xpath('//cfdi:Receptor')[0] ?? null;
                        
                        if (!$emisor || !$receptor) {
                            throw new Exception('No se encontraron datos de emisor o receptor');
                        }
                        
                        error_log("Datos del emisor: " . print_r($emisor, true));
                        error_log("Datos del receptor: " . print_r($receptor, true));
                        
                        // Agregar datos de emisor y receptor
                        $xmlData = array_merge($xmlData, [
                            'emisor_rfc' => (string)$emisor['Rfc'],
                            'emisor_nombre' => (string)$emisor['Nombre'],
                            'emisor_regimen_fiscal' => (string)$emisor['RegimenFiscal'],
                            'receptor_rfc' => (string)$receptor['Rfc'],
                            'receptor_nombre' => (string)$receptor['Nombre'],
                            'receptor_regimen_fiscal' => (string)$receptor['RegimenFiscalReceptor'],
                            'receptor_domicilio_fiscal' => (string)$receptor['DomicilioFiscalReceptor'],
                            'receptor_uso_cfdi' => (string)$receptor['UsoCFDI']
                        ]);
                        
                        // Extraer impuestos con más detalle
                        $impuestos = $xml->xpath('//cfdi:Impuestos')[0] ?? null;
                        $traslados = $xml->xpath('//cfdi:Traslados/cfdi:Traslado')[0] ?? null;
                        
                        // Datos de impuestos
                        $xmlData = array_merge($xmlData, [
                            'total_impuestos_trasladados' => $impuestos ? (float)$impuestos['TotalImpuestosTrasladados'] : 0,
                            'impuesto' => $traslados ? (string)$traslados['Impuesto'] : null,
                            'tasa_o_cuota' => $traslados ? (float)$traslados['TasaOCuota'] : null,
                            'tipo_factor' => $traslados ? (string)$traslados['TipoFactor'] : null
                        ]);
                        
                        error_log("Datos de impuestos encontrados: " . print_r([
                            'total' => $xmlData['total_impuestos_trasladados'],
                            'impuesto' => $xmlData['impuesto'],
                            'tasa_o_cuota' => $xmlData['tasa_o_cuota'],
                            'tipo_factor' => $xmlData['tipo_factor']
                        ], true));
                        
                        // Agregar timestamps
                        $xmlData['created_at'] = date('Y-m-d H:i:s');
                        $xmlData['updated_at'] = date('Y-m-d H:i:s');
                        
                        error_log("Datos finales a guardar: " . print_r($xmlData, true));
                        
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
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                            throw new Exception('Error al guardar el XML');
                        }
                        
                        $uploadedFiles[] = $filePath;
                    } catch (Exception $e) {
                        $errors[] = "Error al procesar el archivo $fileName: " . $e->getMessage();
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }

                // Preparar respuesta
                $response = [
                    'success' => empty($errors),
                    'message' => empty($errors) ? 'Archivos procesados correctamente' : 'Errores al procesar archivos',
                    'errors' => $errors,
                    'files_processed' => count($uploadedFiles),
                    'redirect_url' => BASE_URL . '/clients/view/' . $clientId
                ];

                // Enviar respuesta JSON
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
                    'message' => $e->getMessage(),
                    'redirect_url' => BASE_URL . '/clients/upload-xml?id=' . ($clientId ?? '')
                ]);
            } else {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ' . BASE_URL . '/clients/upload-xml?id=' . ($clientId ?? ''));
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

            // Obtener datos del cliente
            $client = $this->client->getClientById($clientId);
            if (!$client) {
                throw new Exception('Cliente no encontrado');
            }

            // Aquí implementarías la lógica de conexión con el SAT
            // Por ahora, simularemos la descarga
            
            // Simular proceso de descarga
            sleep(1); // Simular tiempo de proceso

            // Enviar respuesta
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="facturas_' . $tipo . '.zip"');
            
            // Aquí implementarías la descarga real de los XMLs
            echo "Simulación de descarga de XMLs del SAT";

        } catch (Exception $e) {
            error_log("Error en downloadSat: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
} 