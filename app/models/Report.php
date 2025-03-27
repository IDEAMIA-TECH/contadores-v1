<?php
class Report {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function generateReport($filters) {
        try {
            $query = "
                SELECT 
                    c.business_name as cliente,
                    cx.fecha,
                    cx.uuid,
                    cx.total,
                    cx.subtotal,
                    COALESCE(f.total_iva, 0) as impuesto,
                    COALESCE(
                        (
                            SELECT GROUP_CONCAT(
                                DISTINCT CASE 
                                    WHEN iv.tasa IS NULL THEN '0%'
                                    ELSE CONCAT(CAST(iv.tasa * 100 AS DECIMAL(5,1)), '%')
                                END
                            )
                            FROM ivas_factura iv 
                            JOIN facturas ff ON iv.factura_id = ff.id 
                            WHERE ff.uuid = cx.uuid
                        ), 
                        '0%'
                    ) as tasa_o_cuota,
                    'Tasa' as tipo_factor,
                    COALESCE(f.total_iva, 0) as total_impuestos_trasladados,
                    cx.emisor_rfc,
                    cx.emisor_nombre,
                    cx.receptor_rfc,
                    cx.receptor_nombre,
                    cx.tipo_comprobante
                FROM client_xmls cx
                JOIN clients c ON cx.client_id = c.id
                LEFT JOIN facturas f ON cx.uuid = f.uuid
                WHERE 1=1
            ";
            
            $params = [];
            
            if (!empty($filters['client_id'])) {
                $query .= " AND cx.client_id = ?";
                $params[] = $filters['client_id'];
            }
            
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $query .= " AND DATE(cx.fecha) BETWEEN ? AND ?";
                $params[] = $filters['start_date'];
                $params[] = $filters['end_date'];
            }
            
            if (!empty($filters['type'])) {
                $types = (array)$filters['type'];
                $placeholders = str_repeat('?,', count($types) - 1) . '?';
                $query .= " AND cx.tipo_comprobante IN ($placeholders)";
                $params = array_merge($params, $types);
            }
            
            $query .= " ORDER BY cx.fecha DESC";
            
            error_log("Query de reporte: " . $query);
            error_log("Parámetros: " . print_r($params, true));
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Resultados encontrados: " . count($results));
            
            // Asegurar que todos los campos numéricos sean 0 si son null
            foreach ($results as &$row) {
                $row['subtotal'] = $row['subtotal'] ?? 0;
                $row['total'] = $row['total'] ?? 0;
                $row['impuesto'] = $row['impuesto'] ?? 0;
                $row['total_impuestos_trasladados'] = $row['total_impuestos_trasladados'] ?? 0;
                $row['tasa_o_cuota'] = $row['tasa_o_cuota'] ?? '0%';
            }
            
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
            // Corregir la ruta del autoload
            $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
            if (!file_exists($autoloadPath)) {
                throw new Exception('El archivo autoload.php no existe. Por favor, ejecute: composer require phpoffice/phpspreadsheet');
            }
            require_once $autoloadPath;

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Establecer encabezados
            $headers = [
                'Fecha', 'Emisor', 'RFC Emisor', 'Receptor', 'RFC Receptor',
                'UUID', 'Subtotal', 'Total', 'Tasa o Cuota', 'Tipo Factor',
                'Impuestos Trasladados', 'Tipo'
            ];

            // Estilo para encabezados
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0066CC'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ];

            // Aplicar encabezados y estilos
            foreach (range('A', 'L') as $i => $column) {
                $sheet->setCellValue($column . '1', $headers[$i]);
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

            // Agregar datos
            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($item['fecha'] ?? '')));
                $sheet->setCellValue('B' . $row, $item['emisor_nombre'] ?? '');
                $sheet->setCellValue('C' . $row, $item['emisor_rfc'] ?? '');
                $sheet->setCellValue('D' . $row, $item['receptor_nombre'] ?? '');
                $sheet->setCellValue('E' . $row, $item['receptor_rfc'] ?? '');
                $sheet->setCellValue('F' . $row, $item['uuid'] ?? '');
                $sheet->setCellValue('G' . $row, ($item['subtotal'] ?? 0));
                $sheet->setCellValue('H' . $row, ($item['total'] ?? 0));
                $sheet->setCellValue('I' . $row, (($item['tasa_o_cuota'] ?? 0) * 100) . '%');
                $sheet->setCellValue('J' . $row, $item['tipo_factor'] ?? '');
                $sheet->setCellValue('K' . $row, ($item['total_impuestos_trasladados'] ?? 0));
                
                $tipos = [
                    'I' => 'Ingreso',
                    'E' => 'Egreso',
                    'P' => 'Pago'
                ];
                $tipoComprobante = $item['tipo_comprobante'] ?? '';
                $sheet->setCellValue('L' . $row, $tipos[$tipoComprobante] ?? $tipoComprobante);
                
                $row++;
            }

            // Formato para columnas numéricas (usando el formato correcto de moneda)
            $currencyFormat = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';
            $sheet->getStyle('G2:H' . ($row-1))->getNumberFormat()->setFormatCode($currencyFormat);
            $sheet->getStyle('K2:K' . ($row-1))->getNumberFormat()->setFormatCode($currencyFormat);

            // Configurar la respuesta
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="reporte_' . date('Y-m-d') . '.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            error_log("Error generando Excel: " . $e->getMessage());
            throw new Exception('Error al generar el archivo Excel: ' . $e->getMessage());
        }
    }

    public function generatePdfReport($data) {
        // Implementar la generación de PDF aquí
        throw new Exception('Generación de PDF no implementada aún');
    }

    public function getIvaResumen($filters) {
        try {
            $query = "
                SELECT 
                    CASE 
                        WHEN i.tasa = 0.16 THEN '16'
                        WHEN i.tasa = 0.08 THEN '8'
                        WHEN i.tasa = 0 THEN '0'
                        ELSE 'Otros'
                    END as tasa_grupo,
                    SUM(i.base) as base_total,
                    SUM(i.importe) as iva_total
                FROM facturas f
                JOIN ivas_factura i ON f.id = i.factura_id
                JOIN client_xmls cx ON f.uuid = cx.uuid
                WHERE 1=1
            ";

            $params = [];

            if (!empty($filters['client_id'])) {
                $query .= " AND cx.client_id = ?";
                $params[] = $filters['client_id'];
            }

            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $query .= " AND DATE(cx.fecha) BETWEEN ? AND ?";
                $params[] = $filters['start_date'];
                $params[] = $filters['end_date'];
            }

            $query .= " GROUP BY 
                CASE 
                    WHEN i.tasa = 0.16 THEN '16'
                    WHEN i.tasa = 0.08 THEN '8'
                    WHEN i.tasa = 0 THEN '0'
                    ELSE 'Otros'
                END";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getIvaResumen: " . $e->getMessage());
            throw new Exception("Error al obtener el resumen de IVAs");
        }
    }
} 