<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/Security.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Client.php';

class ReportController {
    private $db;
    private $security;
    private $report;
    private $client;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new Security();
        $this->report = new Report($this->db);
        $this->client = new Client($this->db);
    }
    
    public function index() {
        try {
            if (!$this->security->isAuthenticated()) {
                throw new Exception('No autorizado');
            }
            
            // Obtener lista de clientes para el dropdown
            $clients = $this->client->getAllClients();
            
            // Inicializar reportData como array vacío
            $reportData = [];
            
            // Solo procesar el reporte si se envió el formulario
            if (isset($_GET['search'])) {
                // Validar fechas requeridas
                if (empty($_GET['start_date']) || empty($_GET['end_date'])) {
                    throw new Exception('Las fechas son obligatorias');
                }
                
                // Obtener parámetros de filtrado
                $filters = [
                    'client_id' => filter_input(INPUT_GET, 'client_id', FILTER_VALIDATE_INT),
                    'start_date' => filter_input(INPUT_GET, 'start_date'),
                    'end_date' => filter_input(INPUT_GET, 'end_date'),
                    'type' => filter_input(INPUT_GET, 'type', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?: []
                ];
                
                // Validar fechas
                if ($filters['start_date'] > $filters['end_date']) {
                    throw new Exception('La fecha de inicio no puede ser posterior a la fecha final');
                }
                
                // Obtener datos para el reporte
                $reportData = $this->report->generateReport($filters);
            }
            
            // Generar token CSRF para el formulario
            $token = $this->security->generateCsrfToken();
            
            include __DIR__ . '/../views/reports/index.php';
            
        } catch (Exception $e) {
            error_log("Error en reports/index: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . BASE_URL . '/reports');
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
            
            if (!in_array($filters['format'], ['excel', 'pdf'])) {
                throw new Exception('Formato de exportación no válido');
            }

            // Obtener los datos del reporte
            $reportData = $this->report->generateReport($filters);
            
            // Exportar según el formato
            if ($filters['format'] === 'excel') {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="reporte.xlsx"');
                $this->report->exportToExcel($reportData);
            } else {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="reporte.pdf"');
                $this->report->exportToPdf($reportData);
            }
            
        } catch (Exception $e) {
            error_log("Error en reports/export: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
} 