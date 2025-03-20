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
        if (!$this->security->isAuthenticated()) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        try {
            // Validar token CSRF usando el método correcto
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Token de seguridad inválido');
            }

            // Validar formato
            if (!isset($_POST['format']) || !in_array($_POST['format'], ['excel', 'pdf'])) {
                throw new Exception('Formato no válido');
            }

            // Preparar los filtros para el reporte
            $filters = [
                'start_date' => $_POST['start_date'] ?? null,
                'end_date' => $_POST['end_date'] ?? null,
                'client_id' => !empty($_POST['client_id']) ? (int)$_POST['client_id'] : null,
                'type' => isset($_POST['type']) ? (array)$_POST['type'] : []
            ];

            // Validar fechas requeridas
            if (empty($filters['start_date']) || empty($filters['end_date'])) {
                throw new Exception('Las fechas son obligatorias');
            }

            // Obtener datos del reporte
            $reportData = $this->report->generateReport($filters);

            // Exportar según el formato
            if ($_POST['format'] === 'excel') {
                return $this->report->generateExcelReport($reportData);
            } else {
                return $this->report->generatePdfReport($reportData);
            }

        } catch (Exception $e) {
            error_log("Error en exportación: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    private function getReportData($params) {
        // Validar parámetros requeridos
        if (empty($params['start_date']) || empty($params['end_date'])) {
            throw new Exception('Las fechas son obligatorias');
        }

        $query = "
            SELECT 
                cx.fecha,
                cx.emisor_nombre,
                cx.emisor_rfc,
                cx.nombre_receptor as receptor_nombre,
                cx.rfc_receptor as receptor_rfc,
                cx.uuid,
                cx.subtotal,
                cx.total,
                cxt.tasa_o_cuota,
                cxt.tipo_factor,
                cxt.total_impuestos_trasladados,
                cx.tipo_comprobante
            FROM client_xmls cx
            LEFT JOIN client_xml_taxes cxt ON cx.id = cxt.xml_id
            WHERE cx.fecha BETWEEN :start_date AND :end_date
        ";

        $parameters = [
            ':start_date' => $params['start_date'],
            ':end_date' => $params['end_date']
        ];

        if (!empty($params['client_id'])) {
            $query .= " AND cx.client_id = :client_id";
            $parameters[':client_id'] = $params['client_id'];
        }

        if (!empty($params['type'])) {
            $types = (array)$params['type'];
            $query .= " AND cx.tipo_comprobante IN (" . implode(',', array_fill(0, count($types), '?')) . ")";
            $parameters = array_merge($parameters, $types);
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($parameters);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 