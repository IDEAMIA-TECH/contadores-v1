<?php
class ClientXml {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create($data) {
        try {
            // Validar datos requeridos y establecer valores por defecto
            $xmlData = [
                ':client_id' => $data['client_id'] ?? null,
                ':xml_path' => $data['xml_path'] ?? null,
                ':uuid' => $data['uuid'] ?? null,
                ':serie' => $data['serie'] ?? null,
                ':folio' => $data['folio'] ?? null,
                ':fecha' => $data['fecha'] ?? null,
                ':fecha_timbrado' => $data['fecha_timbrado'] ?? null,
                ':subtotal' => $data['subtotal'] ?? 0.00,
                ':total' => $data['total'] ?? 0.00,
                ':tipo_comprobante' => $data['tipo_comprobante'] ?? 'I',
                ':forma_pago' => $data['forma_pago'] ?? null,
                ':metodo_pago' => $data['metodo_pago'] ?? null,
                ':moneda' => $data['moneda'] ?? 'MXN',
                ':lugar_expedicion' => $data['lugar_expedicion'] ?? '',
                ':emisor_rfc' => $data['emisor_rfc'] ?? '',
                ':emisor_nombre' => $data['emisor_nombre'] ?? '',
                ':emisor_regimen_fiscal' => $data['emisor_regimen_fiscal'] ?? '',
                ':receptor_rfc' => $data['receptor_rfc'] ?? '',
                ':receptor_nombre' => $data['receptor_nombre'] ?? '',
                ':receptor_regimen_fiscal' => $data['receptor_regimen_fiscal'] ?? '',
                ':receptor_domicilio_fiscal' => $data['receptor_domicilio_fiscal'] ?? '',
                ':receptor_uso_cfdi' => $data['receptor_uso_cfdi'] ?? '',
                ':total_impuestos_trasladados' => $data['total_impuestos_trasladados'] ?? 0.00,
                ':impuesto' => $data['impuesto'] ?? null,
                ':tasa_o_cuota' => $data['tasa_o_cuota'] ?? null,
                ':tipo_factor' => $data['tipo_factor'] ?? null,
                ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
                ':updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s')
            ];

            // Validar campos requeridos
            $requiredFields = [
                'client_id', 'xml_path', 'uuid', 'fecha', 'fecha_timbrado',
                'emisor_rfc', 'emisor_nombre', 'emisor_regimen_fiscal',
                'receptor_rfc', 'receptor_nombre', 'receptor_regimen_fiscal'
            ];

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("El campo {$field} es requerido");
                }
            }

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
                    :created_at,
                    :updated_at
                )
            ");

            return $stmt->execute($xmlData);

        } catch (PDOException $e) {
            error_log("Error al crear XML: " . $e->getMessage());
            throw new Exception("Error al guardar el XML");
        } catch (Exception $e) {
            error_log("Error de validación: " . $e->getMessage());
            throw $e;
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