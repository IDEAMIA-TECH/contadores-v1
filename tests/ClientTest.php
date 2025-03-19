<?php
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../app/models/Client.php';
require_once __DIR__ . '/../app/controllers/ClientController.php';

class ClientTest extends TestCase {
    private $client;
    private $controller;
    
    public function __construct() {
        parent::__construct();
        $this->client = new Client($this->pdo, true);
        $this->controller = new ClientController();
    }
    
    public function testCreateClient() {
        $this->setUp();
        
        $clientData = [
            'rfc' => 'TEST' . time() . 'ABC',
            'business_name' => 'Empresa de Prueba SA de CV',
            'legal_name' => 'Empresa de Prueba',
            'fiscal_regime' => '601 - General de Ley',
            'address' => 'Calle Test 123',
            'email' => 'test' . time() . '@empresa.com',
            'phone' => '5555555555',
            'accountant_id' => 1
        ];
        
        try {
            $clientId = $this->client->create($clientData);
            $this->assert($clientId > 0, "El cliente debería haberse creado con un ID válido");
            
            // Verificar que el cliente existe
            $stmt = $this->pdo->prepare("SELECT * FROM clients_test WHERE id = ?");
            $stmt->execute([$clientId]);
            $savedClient = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->assert($savedClient['rfc'] === $clientData['rfc'], "El RFC debería coincidir");
            $this->assert($savedClient['business_name'] === $clientData['business_name'], "La razón social debería coincidir");
            
            echo "✓ testCreateClient pasó\n";
        } catch (Exception $e) {
            echo "✗ testCreateClient falló: " . $e->getMessage() . "\n";
        }
        
        $this->tearDown();
    }
    
    public function testCreateClientWithContact() {
        $this->setUp();
        
        $clientData = [
            'rfc' => 'TEST' . time() . 'XYZ',
            'business_name' => 'Otra Empresa SA de CV',
            'legal_name' => 'Otra Empresa',
            'fiscal_regime' => '601 - General de Ley',
            'address' => 'Calle Otra 123',
            'email' => 'otra' . time() . '@empresa.com',
            'phone' => '5555555555',
            'contact_name' => 'Juan Prueba',
            'contact_email' => 'juan' . time() . '@test.com',
            'contact_phone' => '5555555555',
            'accountant_id' => 1
        ];
        
        try {
            $clientId = $this->client->create($clientData);
            
            // Verificar que el contacto existe
            $stmt = $this->pdo->prepare("SELECT * FROM client_contacts_test WHERE client_id = ?");
            $stmt->execute([$clientId]);
            $contact = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->assert($contact['name'] === $clientData['contact_name'], "El nombre del contacto debería coincidir");
            $this->assert($contact['email'] === $clientData['contact_email'], "El email del contacto debería coincidir");
            
            echo "✓ testCreateClientWithContact pasó\n";
        } catch (Exception $e) {
            echo "✗ testCreateClientWithContact falló: " . $e->getMessage() . "\n";
        }
        
        $this->tearDown();
    }
    
    public function testParseCsf() {
        try {
            // Crear un PDF de prueba
            $pdfContent = "
                RFC: TEST999888XYZ
                Denominación/Razón Social: EMPRESA PRUEBA CSF SA DE CV
                Régimen Fiscal: 601 - General de Ley
                Calle: TEST
                Número Exterior: 123
                Colonia: CENTRO
                Código Postal: 12345
            ";
            
            $tempFile = tempnam(sys_get_temp_dir(), 'csf_test');
            file_put_contents($tempFile, $pdfContent);
            
            $parser = new PdfParser();
            $data = $parser->parseCSF($tempFile);
            
            unlink($tempFile);
            
            $this->assert($data['rfc'] === 'TEST999888XYZ', "El RFC debería extraerse correctamente");
            $this->assert($data['business_name'] === 'EMPRESA PRUEBA CSF SA DE CV', "La razón social debería extraerse correctamente");
            
            echo "✓ testParseCsf pasó\n";
        } catch (Exception $e) {
            echo "✗ testParseCsf falló: " . $e->getMessage() . "\n";
        }
    }
} 