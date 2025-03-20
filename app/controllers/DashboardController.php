<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/Security.php';
require_once __DIR__ . '/../models/Client.php';

class DashboardController {
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new Security();
    }
    
    public function index() {
        if (!$this->security->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        try {
            // Obtener el ID del contador actual
            $accountantId = $_SESSION['user_id'];
            
            // Obtener total de clientes
            $clientsQuery = "SELECT COUNT(*) as total FROM clients WHERE accountant_id = ?";
            $stmt = $this->db->prepare($clientsQuery);
            $stmt->execute([$accountantId]);
            $totalClients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener total de XMLs procesados
            $xmlsQuery = "SELECT COUNT(*) as total FROM client_xmls cx 
                         INNER JOIN clients c ON cx.client_id = c.id 
                         WHERE c.accountant_id = ?";
            $stmt = $this->db->prepare($xmlsQuery);
            $stmt->execute([$accountantId]);
            $totalXmls = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener total de reportes generados (si tienes una tabla de reportes)
            $reportsQuery = "SELECT COUNT(*) as total FROM reports r 
                           INNER JOIN clients c ON r.client_id = c.id 
                           WHERE c.accountant_id = ?";
            $stmt = $this->db->prepare($reportsQuery);
            $stmt->execute([$accountantId]);
            $totalReports = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Pasar los datos a la vista
            $data = [
                'totalClients' => $totalClients,
                'totalXmls' => $totalXmls,
                'totalReports' => $totalReports
            ];
            
            // Incluir la vista con los datos
            extract($data);
            require_once __DIR__ . '/../views/dashboard/index.php';
            
        } catch (Exception $e) {
            error_log("Error en Dashboard: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el dashboard';
            require_once __DIR__ . '/../views/dashboard/index.php';
        }
    }
} 