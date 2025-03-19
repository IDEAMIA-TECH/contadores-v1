<?php
class Client {
    private $db;
    private $tablePrefix;
    
    public function __construct($db, $isTest = false) {
        $this->db = $db;
        $this->tablePrefix = $isTest ? '_test' : '';
    }
    
    public function getAllClients() {
        try {
            $stmt = $this->db->query("
                SELECT * FROM clients{$this->tablePrefix} 
                WHERE status = 'active' 
                ORDER BY business_name ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error en getAllClients: " . $e->getMessage());
            throw new Exception("Error al obtener los clientes");
        }
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Preparar la consulta SQL base
            $sql = "INSERT INTO clients (
                rfc, business_name, legal_name, fiscal_regime,
                street, exterior_number, interior_number, neighborhood,
                city, state, zip_code, email, phone, csf_path";
            
            $values = "VALUES (
                :rfc, :business_name, :legal_name, :fiscal_regime,
                :street, :exterior_number, :interior_number, :neighborhood,
                :city, :state, :zip_code, :email, :phone, :csf_path";
            
            // Agregar campos de SAT si están presentes
            if (isset($data['cer_path'])) {
                $sql .= ", cer_path";
                $values .= ", :cer_path";
            }
            if (isset($data['key_path'])) {
                $sql .= ", key_path";
                $values .= ", :key_path";
            }
            if (isset($data['key_password'])) {
                $sql .= ", key_password";
                $values .= ", :key_password";
            }
            
            $sql .= ") " . $values . ")";
            
            $stmt = $this->db->prepare($sql);
            
            // Preparar parámetros base
            $params = [
                ':rfc' => $data['rfc'],
                ':business_name' => $data['business_name'],
                ':legal_name' => $data['legal_name'],
                ':fiscal_regime' => $data['fiscal_regime'],
                ':street' => $data['street'],
                ':exterior_number' => $data['exterior_number'],
                ':interior_number' => $data['interior_number'],
                ':neighborhood' => $data['neighborhood'],
                ':city' => $data['city'],
                ':state' => $data['state'],
                ':zip_code' => $data['zip_code'],
                ':email' => $data['email'],
                ':phone' => $data['phone'],
                ':csf_path' => $data['csf_path'] ?? null
            ];
            
            // Agregar parámetros de SAT si están presentes
            if (isset($data['cer_path'])) {
                $params[':cer_path'] = $data['cer_path'];
            }
            if (isset($data['key_path'])) {
                $params[':key_path'] = $data['key_path'];
            }
            if (isset($data['key_password'])) {
                $params[':key_password'] = $data['key_password'];
            }
            
            $stmt->execute($params);
            $clientId = $this->db->lastInsertId();
            
            // Insertar en la tabla client_contacts si hay datos de contacto
            if (!empty($data['contact_name']) || !empty($data['contact_email']) || !empty($data['contact_phone'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO client_contacts (
                        client_id, contact_name, contact_email, contact_phone
                    ) VALUES (
                        :client_id, :contact_name, :contact_email, :contact_phone
                    )
                ");
                
                $contactData = [
                    ':client_id' => $clientId,
                    ':contact_name' => $data['contact_name'] ?? null,
                    ':contact_email' => $data['contact_email'] ?? null,
                    ':contact_phone' => $data['contact_phone'] ?? null
                ];
                
                $stmt->execute($contactData);
            }
            
            // Insertar relación contador-cliente
            $stmt = $this->db->prepare("
                INSERT INTO accountant_clients (
                    accountant_id, client_id
                ) VALUES (
                    :accountant_id, :client_id
                )
            ");
            
            $stmt->execute([
                ':accountant_id' => $data['accountant_id'],
                ':client_id' => $clientId
            ]);
            
            // Guardar documento CSF si existe
            if (!empty($data['csf_path'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO client_documents (
                        client_id, type, file_path
                    ) VALUES (
                        :client_id, 'csf', :file_path
                    )
                ");
                
                $stmt->execute([
                    ':client_id' => $clientId,
                    ':file_path' => $data['csf_path']
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error en create: " . $e->getMessage());
            throw new Exception("Error al crear el cliente");
        }
    }

    public function getClientById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM clients 
                WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getClientById: " . $e->getMessage());
            throw new Exception("Error al obtener el cliente");
        }
    }

    public function getClientDocuments($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM client_documents 
                WHERE client_id = :client_id 
                ORDER BY created_at DESC
            ");
            $stmt->execute(['client_id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getClientDocuments: " . $e->getMessage());
            throw new Exception("Error al obtener los documentos del cliente");
        }
    }

    public function getClientContact($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM client_contacts 
                WHERE client_id = :client_id 
                LIMIT 1
            ");
            $stmt->execute(['client_id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getClientContact: " . $e->getMessage());
            throw new Exception("Error al obtener el contacto del cliente");
        }
    }

    public function update($data) {
        try {
            $sql = "UPDATE clients SET 
                    rfc = ?,
                    business_name = ?,
                    legal_name = ?,
                    fiscal_regime = ?,
                    street = ?,
                    exterior_number = ?,
                    interior_number = ?,
                    neighborhood = ?,
                    city = ?,
                    state = ?,
                    zip_code = ?,
                    email = ?,
                    phone = ?";
            
            $params = [
                $data['rfc'],
                $data['business_name'],
                $data['legal_name'],
                $data['fiscal_regime'],
                $data['street'],
                $data['exterior_number'],
                $data['interior_number'],
                $data['neighborhood'],
                $data['city'],
                $data['state'],
                $data['zip_code'],
                $data['email'],
                $data['phone']
            ];

            // Agregar campos de SAT si están presentes
            if (isset($data['cer_path'])) {
                $sql .= ", cer_path = ?";
                $params[] = $data['cer_path'];
            }
            
            if (isset($data['key_path'])) {
                $sql .= ", key_path = ?";
                $params[] = $data['key_path'];
            }
            
            if (isset($data['key_password'])) {
                $sql .= ", key_password = ?";
                $params[] = $data['key_password'];
            }

            $sql .= " WHERE id = ?";
            $params[] = $data['id'];

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Error en Client::update: " . $e->getMessage());
            return false;
        }
    }
} 