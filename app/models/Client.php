<?php
class Client {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Insertar cliente
            $stmt = $this->db->prepare('
                INSERT INTO clients (
                    rfc, business_name, legal_name, fiscal_regime, 
                    address, email, phone, status
                ) VALUES (
                    :rfc, :business_name, :legal_name, :fiscal_regime,
                    :address, :email, :phone, "active"
                )
            ');
            
            $stmt->execute([
                'rfc' => $data['rfc'],
                'business_name' => $data['business_name'],
                'legal_name' => $data['legal_name'],
                'fiscal_regime' => $data['fiscal_regime'],
                'address' => $data['address'],
                'email' => $data['email'],
                'phone' => $data['phone']
            ]);
            
            $clientId = $this->db->lastInsertId();
            
            // Insertar contacto
            if (!empty($data['contact_name'])) {
                $stmt = $this->db->prepare('
                    INSERT INTO client_contacts (
                        client_id, name, email, phone
                    ) VALUES (
                        :client_id, :name, :email, :phone
                    )
                ');
                
                $stmt->execute([
                    'client_id' => $clientId,
                    'name' => $data['contact_name'],
                    'email' => $data['contact_email'],
                    'phone' => $data['contact_phone']
                ]);
            }
            
            // Insertar relación contador-cliente
            $stmt = $this->db->prepare('
                INSERT INTO accountant_clients (
                    accountant_id, client_id
                ) VALUES (
                    :accountant_id, :client_id
                )
            ');
            
            $stmt->execute([
                'accountant_id' => $data['accountant_id'],
                'client_id' => $clientId
            ]);
            
            // Guardar ruta del PDF de la CSF si existe
            if (!empty($data['csf_path'])) {
                $stmt = $this->db->prepare('
                    INSERT INTO client_documents (
                        client_id, type, file_path
                    ) VALUES (
                        :client_id, "csf", :file_path
                    )
                ');
                
                $stmt->execute([
                    'client_id' => $clientId,
                    'file_path' => $data['csf_path']
                ]);
            }
            
            $this->db->commit();
            return $clientId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
} 