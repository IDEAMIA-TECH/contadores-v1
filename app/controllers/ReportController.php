<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/Security.php';
require_once __DIR__ . '/../models/Report.php';

class ReportController {
    private $db;
    private $security;
    private $report;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new Security();
        $this->report = new Report($this->db);
    }
    
    public function index() {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }
            
            // Obtener parámetros de filtrado
            $filters = [
                'client_id' => filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT),
                'start_date' => filter_input(INPUT_GET, 'start_date'),
                'end_date' => filter_input(INPUT_GET, 'end_date'),
                'type' => filter_input(INPUT_GET, 'type')
            ];
            
            // Obtener datos para el reporte
            $reportData = $this->report->generateReport($filters);
            
            // Generar token CSRF para el formulario
            $token = $this->security->generateCsrfToken();
            
            include __DIR__ . '/../views/reports/index.php';
            
        } catch (Exception $e) {
            error_log("Error en reports/index: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }
    
    public function export() {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }
            
            // Validar token CSRF
            if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de seguridad inválido');
            }
            
            // Obtener parámetros de filtrado
            $filters = [
                'client_id' => filter_input(INPUT_POST, 'client_id', FILTER_VALIDATE_INT),
                'start_date' => filter_input(INPUT_POST, 'start_date'),
                'end_date' => filter_input(INPUT_POST, 'end_date'),
                'type' => filter_input(INPUT_POST, 'type'),
                'format' => filter_input(INPUT_POST, 'format')
            ];
            
            // Generar y exportar reporte
            $this->report->exportReport($filters);
            
        } catch (Exception $e) {
            error_log("Error en reports/export: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/reports');
            exit;
        }
    }
} 