<?php

class ReportsController {

    public function generateReport($filters) {
        $query = "
            SELECT 
                c.business_name as cliente,
                cx.fecha,
                cx.uuid,
                cx.total,
                cx.subtotal,
                f.total_iva,
                COALESCE(
                    (SELECT GROUP_CONCAT(DISTINCT tasa) 
                     FROM ivas_factura iv 
                     JOIN facturas ff ON iv.factura_id = ff.id 
                     WHERE ff.uuid = cx.uuid
                    ), 0
                ) as tasas_iva,
                'Tasa' as tipo_factor,
                f.total_iva as total_impuestos_trasladados,
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

        // Si hay un client_id específico
        if (!empty($filters['client_id'])) {
            $query .= " AND cx.client_id = :client_id";
        }

        // Agregar filtros de fecha
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query .= " AND DATE(cx.fecha) BETWEEN :start_date AND :end_date";
        }

        $query .= " ORDER BY cx.fecha DESC";

        try {
            $stmt = $db->prepare($query);
            
            // Bind de parámetros
            if (!empty($filters['client_id'])) {
                $stmt->bindValue(':client_id', $filters['client_id'], PDO::PARAM_INT);
            }
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $stmt->bindValue(':start_date', $filters['start_date']);
                $stmt->bindValue(':end_date', $filters['end_date']);
            }

            $stmt->execute();
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Agregar logging para debug
            error_log("Query ejecutada: " . $query);
            error_log("Parámetros: " . print_r($filters, true));
            error_log("Resultados encontrados: " . count($reportData));

            return $reportData;

        } catch (PDOException $e) {
            error_log("Error en generateReport: " . $e->getMessage());
            throw new Exception("Error al generar el reporte");
        }
    }
} 