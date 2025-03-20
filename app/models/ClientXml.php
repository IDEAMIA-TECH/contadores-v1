<?php
class ClientXml {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create($data) {
        try {
            $sql = "INSERT INTO client_xmls (
                client_id,
                xml_path,
                uuid,
                fecha,
                fecha_timbrado,
                lugar_expedicion,
                tipo_comprobante,
                serie,
                folio,
                forma_pago,
                metodo_pago,
                moneda,
                subtotal,
                total,
                total_impuestos_trasladados,
                emisor_rfc,
                emisor_nombre,
                emisor_regimen_fiscal,
                receptor_rfc,
                receptor_nombre,
                receptor_regimen_fiscal,
                receptor_domicilio_fiscal,
                receptor_uso_cfdi,
                impuesto,
                tasa_cuota,
                created_at
            ) VALUES (
                :client_id,
                :xml_path,
                :uuid,
                :fecha,
                :fecha_timbrado,
                :lugar_expedicion,
                :tipo_comprobante,
                :serie,
                :folio,
                :forma_pago,
                :metodo_pago,
                :moneda,
                :subtotal,
                :total,
                :total_impuestos_trasladados,
                :emisor_rfc,
                :emisor_nombre,
                :emisor_regimen_fiscal,
                :receptor_rfc,
                :receptor_nombre,
                :receptor_regimen_fiscal,
                :receptor_domicilio_fiscal,
                :receptor_uso_cfdi,
                :impuesto,
                :tasa_cuota,
                NOW()
            )";

            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':client_id' => $data['client_id'],
                ':xml_path' => $data['xml_path'],
                ':uuid' => $data['uuid'] ?? null,
                ':fecha' => $data['fecha'] ?? null,
                ':fecha_timbrado' => $data['fecha_timbrado'] ?? $data['fecha'] ?? null,
                ':lugar_expedicion' => $data['lugar_expedicion'] ?? '',
                ':tipo_comprobante' => $data['tipo_comprobante'] ?? null,
                ':serie' => $data['serie'] ?? null,
                ':folio' => $data['folio'] ?? null,
                ':forma_pago' => $data['forma_pago'] ?? null,
                ':metodo_pago' => $data['metodo_pago'] ?? null,
                ':moneda' => $data['moneda'] ?? null,
                ':subtotal' => $data['subtotal'] ?? 0,
                ':total' => $data['total'] ?? 0,
                ':total_impuestos_trasladados' => $data['total_impuestos_trasladados'] ?? 0,
                ':emisor_rfc' => $data['emisor_rfc'] ?? null,
                ':emisor_nombre' => $data['emisor_nombre'] ?? null,
                ':emisor_regimen_fiscal' => $data['emisor_regimen_fiscal'] ?? null,
                ':receptor_rfc' => $data['receptor_rfc'] ?? null,
                ':receptor_nombre' => $data['receptor_nombre'] ?? null,
                ':receptor_regimen_fiscal' => $data['receptor_regimen_fiscal'] ?? null,
                ':receptor_domicilio_fiscal' => $data['receptor_domicilio_fiscal'] ?? null,
                ':receptor_uso_cfdi' => $data['receptor_uso_cfdi'] ?? null,
                ':impuesto' => $data['impuesto'] ?? null,
                ':tasa_cuota' => $data['tasa_cuota'] ?? null
            ]);

        } catch (PDOException $e) {
            error_log("Error en ClientXml::create: " . $e->getMessage());
            return false;
        }
    }
    
    public function getByClient($clientId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM client_xmls 
                WHERE client_id = ? 
                ORDER BY fecha DESC
            ");
            $stmt->execute([$clientId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener XMLs: " . $e->getMessage());
            throw new Exception("Error al obtener los XMLs");
        }
    }
} 