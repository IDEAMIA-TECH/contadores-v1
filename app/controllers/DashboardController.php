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
            // Inicializar variables con valores por defecto
            $totalClients = 0;
            $totalXmls = 0;
            $totalReports = 0;
            
            // Obtener el ID del contador actual
            $userId = $_SESSION['user_id'];
            
            // Obtener total de clientes (usando user_id en lugar de accountant_id)
            $clientsQuery = "SELECT COUNT(*) as total FROM clients WHERE user_id = ?";
            $stmt = $this->db->prepare($clientsQuery);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $totalClients = $result['total'];
            }
            
            // Obtener total de XMLs procesados
            $xmlsQuery = "SELECT COUNT(*) as total FROM client_xmls cx 
                         INNER JOIN clients c ON cx.client_id = c.id 
                         WHERE c.user_id = ?";
            $stmt = $this->db->prepare($xmlsQuery);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $totalXmls = $result['total'];
            }
            
            // Verificar si existe la tabla reports antes de consultar
            $checkTableQuery = "SHOW TABLES LIKE 'reports'";
            $stmt = $this->db->prepare($checkTableQuery);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                // La tabla reports existe, hacer la consulta
                $reportsQuery = "SELECT COUNT(*) as total FROM reports r 
                               INNER JOIN clients c ON r.client_id = c.id 
                               WHERE c.user_id = ?";
                $stmt = $this->db->prepare($reportsQuery);
                $stmt->execute([$userId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $totalReports = $result['total'];
                }
            }
            
        } catch (Exception $e) {
            error_log("Error en Dashboard: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el dashboard';
        }
        
        // Asegurar que las variables estén definidas incluso si hay error
        $data = [
            'totalClients' => $totalClients ?? 0,
            'totalXmls' => $totalXmls ?? 0,
            'totalReports' => $totalReports ?? 0,
            'error' => $_SESSION['error'] ?? null
        ];
        
        // Limpiar mensaje de error después de usarlo
        unset($_SESSION['error']);
        
        // Incluir la vista con los datos
        extract($data);
        require_once __DIR__ . '/../views/dashboard/index.php';
    }
} 