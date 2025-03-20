<?php
class Report {
    private $db;
    
    public function __construct($db = null) {
        if ($db === null) {
            $this->db = Database::getInstance()->getConnection();
        } else {
            $this->db = $db;
        }
    }
    
    public function generateReport($filters) {
        try {
            $query = "
                SELECT 
                    c.business_name as cliente,
                    x.fecha,
                    x.uuid,
                    x.total,
                    x.subtotal,
                    x.impuesto,
                    COALESCE(x.tasa_o_cuota, 0) as tasa_o_cuota,
                    COALESCE(x.tipo_factor, '') as tipo_factor,
                    COALESCE(x.total_impuestos_trasladados, 0) as total_impuestos_trasladados,
                    x.emisor_rfc,
                    x.emisor_nombre,
                    x.receptor_rfc,
                    x.receptor_nombre,
                    x.tipo_comprobante
                FROM client_xmls x
                JOIN clients c ON x.client_id = c.id
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($filters['client_id'])) {
                $query .= " AND x.client_id = ?";
                $params[] = $filters['client_id'];
            }
            
            if (!empty($filters['start_date'])) {
                $query .= " AND DATE(x.fecha) >= ?";
                $params[] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $query .= " AND DATE(x.fecha) <= ?";
                $params[] = $filters['end_date'];
            }
            
            if (!empty($filters['type'])) {
                $placeholders = str_repeat('?,', count($filters['type']) - 1) . '?';
                $query .= " AND x.tipo_comprobante IN ($placeholders)";
                $params = array_merge($params, $filters['type']);
            }
            
            $query .= " ORDER BY x.fecha DESC";
            
            error_log("Query de reporte: " . $query);
            error_log("Parámetros: " . print_r($params, true));
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Resultados encontrados: " . count($results));
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Error en generateReport: " . $e->getMessage());
            throw new Exception("Error al generar el reporte");
        }
    }
    
    public function exportReport($filters) {
        try {
            $data = $this->generateReport($filters);
            
            switch ($filters['format']) {
                case 'excel':
                    $this->exportToExcel($data);
                    break;
                case 'pdf':
                    $this->exportToPdf($data);
                    break;
                default:
                    throw new Exception('Formato de exportación no válido');
            }
            
        } catch (Exception $e) {
            error_log("Error en exportReport: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function exportToExcel($data) {
        // Implementación pendiente
    }
    
    private function exportToPdf($data) {
        // Implementación pendiente
    }

    public function generateExcelReport($data) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Establecer encabezados
            $headers = [
                'Fecha', 'Emisor', 'RFC Emisor', 'Receptor', 'RFC Receptor',
                'UUID', 'Subtotal', 'Total', 'Tasa o Cuota', 'Tipo Factor',
                'Impuestos Trasladados', 'Tipo'
            ];

            foreach (range('A', 'L') as $i => $column) {
                $sheet->setCellValue($column . '1', $headers[$i]);
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Agregar datos
            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($item['fecha'])));
                $sheet->setCellValue('B' . $row, $item['emisor_nombre']);
                $sheet->setCellValue('C' . $row, $item['emisor_rfc']);
                $sheet->setCellValue('D' . $row, $item['receptor_nombre']);
                $sheet->setCellValue('E' . $row, $item['receptor_rfc']);
                $sheet->setCellValue('F' . $row, $item['uuid']);
                $sheet->setCellValue('G' . $row, $item['subtotal']);
                $sheet->setCellValue('H' . $row, $item['total']);
                $sheet->setCellValue('I' . $row, ($item['tasa_o_cuota'] * 100) . '%');
                $sheet->setCellValue('J' . $row, $item['tipo_factor']);
                $sheet->setCellValue('K' . $row, $item['total_impuestos_trasladados']);
                $sheet->setCellValue('L' . $row, $item['tipo_comprobante']);
                $row++;
            }

            // Configurar la respuesta
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="reporte.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            error_log("Error generando Excel: " . $e->getMessage());
            throw new Exception('Error al generar el archivo Excel');
        }
    }

    public function generatePdfReport($data) {
        // Implementar la generación de PDF aquí
        throw new Exception('Generación de PDF no implementada aún');
    }
} 