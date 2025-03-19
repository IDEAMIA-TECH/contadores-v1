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
                    :created_at,
                    :updated_at
                )
            ");

            return $stmt->execute([
                ':client_id' => $data['client_id'],
                ':xml_path' => $data['xml_path'],
                ':uuid' => $data['uuid'],
                ':serie' => $data['serie'],
                ':folio' => $data['folio'],
                ':fecha' => $data['fecha'],
                ':fecha_timbrado' => $data['fecha_timbrado'],
                ':subtotal' => $data['subtotal'],
                ':total' => $data['total'],
                ':tipo_comprobante' => $data['tipo_comprobante'],
                ':forma_pago' => $data['forma_pago'],
                ':metodo_pago' => $data['metodo_pago'],
                ':moneda' => $data['moneda'],
                ':lugar_expedicion' => $data['lugar_expedicion'],
                ':emisor_rfc' => $data['emisor_rfc'],
                ':emisor_nombre' => $data['emisor_nombre'],
                ':emisor_regimen_fiscal' => $data['emisor_regimen_fiscal'],
                ':receptor_rfc' => $data['receptor_rfc'],
                ':receptor_nombre' => $data['receptor_nombre'],
                ':receptor_regimen_fiscal' => $data['receptor_regimen_fiscal'],
                ':receptor_domicilio_fiscal' => $data['receptor_domicilio_fiscal'],
                ':receptor_uso_cfdi' => $data['receptor_uso_cfdi'],
                ':total_impuestos_trasladados' => $data['total_impuestos_trasladados'],
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