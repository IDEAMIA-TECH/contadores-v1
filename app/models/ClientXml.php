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
                tipo_comprobante,
                serie,
                folio,
                subtotal,
                total,
                emisor_rfc,
                emisor_nombre,
                receptor_rfc,
                receptor_nombre,
                created_at
            ) VALUES (
                :client_id,
                :xml_path,
                :uuid,
                :fecha,
                :fecha_timbrado,
                :tipo_comprobante,
                :serie,
                :folio,
                :subtotal,
                :total,
                :emisor_rfc,
                :emisor_nombre,
                :receptor_rfc,
                :receptor_nombre,
                NOW()
            )";

            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([
                ':client_id' => $data['client_id'],
                ':xml_path' => $data['xml_path'],
                ':uuid' => $data['uuid'] ?? null,
                ':fecha' => $data['fecha'] ?? null,
                ':fecha_timbrado' => $data['fecha_timbrado'] ?? $data['fecha'] ?? null,
                ':tipo_comprobante' => $data['tipo_comprobante'] ?? null,
                ':serie' => $data['serie'] ?? null,
                ':folio' => $data['folio'] ?? null,
                ':subtotal' => $data['subtotal'] ?? 0,
                ':total' => $data['total'] ?? 0,
                ':emisor_rfc' => $data['emisor_rfc'] ?? null,
                ':emisor_nombre' => $data['emisor_nombre'] ?? null,
                ':receptor_rfc' => $data['receptor_rfc'] ?? null,
                ':receptor_nombre' => $data['receptor_nombre'] ?? null
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