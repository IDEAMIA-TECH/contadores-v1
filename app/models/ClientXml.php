<?php
class ClientXml {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO client_xmls (
                    client_id,
                    xml_path,
                    uuid,
                    serie,
                    folio,
                    fecha,
                    subtotal,
                    total,
                    created_at,
                    updated_at
                ) VALUES (
                    :client_id,
                    :xml_path,
                    :uuid,
                    :serie,
                    :folio,
                    :fecha,
                    :subtotal,
                    :total,
                    :created_at,
                    :updated_at
                )
            ");

            return $stmt->execute([
                ':client_id' => $data['client_id'],
                ':xml_path' => $data['xml_path'],
                ':uuid' => $data['uuid'] ?? null,
                ':serie' => $data['serie'] ?? null,
                ':folio' => $data['folio'] ?? null,
                ':fecha' => $data['fecha'] ?? null,
                ':subtotal' => $data['subtotal'] ?? 0,
                ':total' => $data['total'] ?? 0,
                ':created_at' => $data['created_at'],
                ':updated_at' => $data['updated_at']
            ]);

        } catch (PDOException $e) {
            error_log("Error al crear XML: " . $e->getMessage());
            throw new Exception("Error al guardar el XML");
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