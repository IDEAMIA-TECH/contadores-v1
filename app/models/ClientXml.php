<?php
class ClientXml {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function exists($uuid, $clientId) {
        $stmt = $this->db->prepare("SELECT id FROM client_xmls WHERE uuid = ? AND client_id = ?");
        $stmt->bind_param("si", $uuid, $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    public function create($data) {
        $sql = "INSERT INTO client_xmls (client_id, uuid, xml_content, emisor_rfc, emisor_nombre, 
                receptor_rfc, receptor_nombre, fecha, tipo_comprobante, total, file_name, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("issssssssdss",
            $data['client_id'],
            $data['uuid'],
            $data['xml_content'],
            $data['emisor_rfc'],
            $data['emisor_nombre'],
            $data['receptor_rfc'],
            $data['receptor_nombre'],
            $data['fecha'],
            $data['tipo_comprobante'],
            $data['total'],
            $data['file_name'],
            $data['created_at']
        );
        
        return $stmt->execute();
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