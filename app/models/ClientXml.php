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
                serie,
                folio,
                fecha,
                fecha_timbrado,
                subtotal,
                total,
                tipo_comprobante,
                forma_pago,
                metodo_pago,
                moneda,
                lugar_expedicion,
                emisor_rfc,
                emisor_nombre,
                emisor_regimen_fiscal,
                receptor_rfc,
                receptor_nombre,
                receptor_regimen_fiscal,
                receptor_domicilio_fiscal,
                receptor_uso_cfdi,
                total_impuestos_trasladados,
                impuesto,
                tasa_o_cuota,
                tipo_factor,
                created_at,
                updated_at
            ) VALUES (
                :client_id,
                :xml_path,
                :uuid,
                :serie,
                :folio,
                :fecha,
                :fecha_timbrado,
                :subtotal,
                :total,
                :tipo_comprobante,
                :forma_pago,
                :metodo_pago,
                :moneda,
                :lugar_expedicion,
                :emisor_rfc,
                :emisor_nombre,
                :emisor_regimen_fiscal,
                :receptor_rfc,
                :receptor_nombre,
                :receptor_regimen_fiscal,
                :receptor_domicilio_fiscal,
                :receptor_uso_cfdi,
                :total_impuestos_trasladados,
                :impuesto,
                :tasa_o_cuota,
                :tipo_factor,
                NOW(),
                NOW()
            )";

            $stmt = $this->db->prepare($sql);
            
            // Asegurar que todos los valores numéricos sean del tipo correcto
            $stmt->bindValue(':client_id', $data['client_id'], PDO::PARAM_INT);
            $stmt->bindValue(':xml_path', $data['xml_path'], PDO::PARAM_STR);
            $stmt->bindValue(':uuid', $data['uuid'], PDO::PARAM_STR);
            $stmt->bindValue(':serie', $data['serie'], PDO::PARAM_STR);
            $stmt->bindValue(':folio', $data['folio'], PDO::PARAM_STR);
            $stmt->bindValue(':fecha', $data['fecha'], PDO::PARAM_STR);
            $stmt->bindValue(':fecha_timbrado', $data['fecha_timbrado'], PDO::PARAM_STR);
            $stmt->bindValue(':subtotal', $data['subtotal'], PDO::PARAM_STR);
            $stmt->bindValue(':total', $data['total'], PDO::PARAM_STR);
            $stmt->bindValue(':tipo_comprobante', $data['tipo_comprobante'], PDO::PARAM_STR);
            $stmt->bindValue(':forma_pago', $data['forma_pago'], PDO::PARAM_STR);
            $stmt->bindValue(':metodo_pago', $data['metodo_pago'], PDO::PARAM_STR);
            $stmt->bindValue(':moneda', $data['moneda'], PDO::PARAM_STR);
            $stmt->bindValue(':lugar_expedicion', $data['lugar_expedicion'], PDO::PARAM_STR);
            $stmt->bindValue(':emisor_rfc', $data['emisor_rfc'], PDO::PARAM_STR);
            $stmt->bindValue(':emisor_nombre', $data['emisor_nombre'], PDO::PARAM_STR);
            $stmt->bindValue(':emisor_regimen_fiscal', $data['emisor_regimen_fiscal'], PDO::PARAM_STR);
            $stmt->bindValue(':receptor_rfc', $data['receptor_rfc'], PDO::PARAM_STR);
            $stmt->bindValue(':receptor_nombre', $data['receptor_nombre'], PDO::PARAM_STR);
            $stmt->bindValue(':receptor_regimen_fiscal', $data['receptor_regimen_fiscal'], PDO::PARAM_STR);
            $stmt->bindValue(':receptor_domicilio_fiscal', $data['receptor_domicilio_fiscal'], PDO::PARAM_STR);
            $stmt->bindValue(':receptor_uso_cfdi', $data['receptor_uso_cfdi'], PDO::PARAM_STR);
            $stmt->bindValue(':total_impuestos_trasladados', $data['total_impuestos_trasladados'], PDO::PARAM_STR);
            $stmt->bindValue(':impuesto', $data['impuesto'], PDO::PARAM_STR);
            $stmt->bindValue(':tasa_o_cuota', $data['tasa_o_cuota'], PDO::PARAM_STR);
            $stmt->bindValue(':tipo_factor', $data['tipo_factor'], PDO::PARAM_STR);

            Logger::debug("Ejecutando consulta SQL para insertar XML", [
                'uuid' => $data['uuid'],
                'client_id' => $data['client_id']
            ]);

            $stmt->execute();
            return $this->db->lastInsertId();

        } catch (PDOException $e) {
            Logger::error("Error en ClientXml::create: " . $e->getMessage(), [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw new Exception("Error al guardar los datos del XML en la base de datos");
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