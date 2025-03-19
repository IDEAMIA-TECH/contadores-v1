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
                    xml_type,
                    uuid,
                    xml_path,
                    emission_date,
                    certification_date,
                    subtotal,
                    total,
                    status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $data['client_id'],
                $data['xml_type'],
                $data['uuid'],
                $data['xml_path'],
                $data['emission_date'],
                $data['certification_date'],
                $data['subtotal'],
                $data['total'],
                $data['status']
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
                ORDER BY emission_date DESC
            ");
            $stmt->execute([$clientId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error al obtener XMLs: " . $e->getMessage());
            throw new Exception("Error al obtener los XMLs");
        }
    }
} 