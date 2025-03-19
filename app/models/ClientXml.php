<?php
class ClientXml {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create($data) {
        try {
            // Obtener la estructura actual de la tabla
            $columns = $this->db->query("SHOW COLUMNS FROM client_xmls")->fetchAll(PDO::FETCH_COLUMN);
            
            // Preparar los campos base que siempre deben existir
            $baseFields = [
                'client_id', 'xml_path', 'uuid', 'serie', 'folio', 'fecha',
                'fecha_timbrado', 'subtotal', 'total', 'tipo_comprobante',
                'forma_pago', 'metodo_pago', 'moneda', 'lugar_expedicion',
                'emisor_rfc', 'emisor_nombre', 'emisor_regimen_fiscal',
                'receptor_rfc', 'receptor_nombre', 'receptor_regimen_fiscal',
                'receptor_domicilio_fiscal', 'receptor_uso_cfdi',
                'total_impuestos_trasladados', 'created_at', 'updated_at'
            ];
            
            // Campos nuevos que podrían no existir aún
            $newFields = ['impuesto', 'tasa_o_cuota', 'tipo_factor'];
            
            // Construir la consulta SQL dinámicamente
            $fields = [];
            $values = [];
            $params = [];
            
            // Agregar campos base
            foreach ($baseFields as $field) {
                $fields[] = $field;
                $values[] = ":{$field}";
                $params[":{$field}"] = $data[$field] ?? null;
            }
            
            // Agregar campos nuevos solo si existen en la tabla
            foreach ($newFields as $field) {
                if (in_array($field, $columns)) {
                    $fields[] = $field;
                    $values[] = ":{$field}";
                    $params[":{$field}"] = $data[$field] ?? null;
                }
            }
            
            // Construir la consulta SQL
            $sql = "INSERT INTO client_xmls (" . implode(", ", $fields) . ") 
                    VALUES (" . implode(", ", $values) . ")";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
            
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