<?php

require_once __DIR__ . '/../utils/Logger.php';

class ClientXml {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create($data) {
        try {
            Logger::debug("Iniciando creación de registro XML", [
                'uuid' => $data['uuid'] ?? 'no-uuid',
                'client_id' => $data['client_id'] ?? 'no-client'
            ]);

            // Verificar si el UUID ya existe para este cliente
            $stmt = $this->db->prepare("
                SELECT id FROM client_xml 
                WHERE uuid = ? AND client_id = ?
            ");
            $stmt->execute([$data['uuid'], $data['client_id']]);
            
            if ($stmt->fetch()) {
                Logger::info("XML duplicado, omitiendo", [
                    'uuid' => $data['uuid'],
                    'client_id' => $data['client_id']
                ]);
                return true; // Consideramos esto como éxito ya que el documento ya existe
            }

            // Preparar la consulta SQL
            $sql = "INSERT INTO client_xml (
                client_id, xml_path, uuid, serie, folio,
                fecha, fecha_timbrado, subtotal, total,
                tipo_comprobante, forma_pago, metodo_pago,
                moneda, lugar_expedicion, emisor_rfc,
                emisor_nombre, emisor_regimen_fiscal,
                receptor_rfc, receptor_nombre,
                receptor_regimen_fiscal,
                receptor_domicilio_fiscal,
                receptor_uso_cfdi,
                total_impuestos_trasladados,
                impuesto, tasa_o_cuota, tipo_factor,
                created_at
            ) VALUES (
                :client_id, :xml_path, :uuid, :serie, :folio,
                :fecha, :fecha_timbrado, :subtotal, :total,
                :tipo_comprobante, :forma_pago, :metodo_pago,
                :moneda, :lugar_expedicion, :emisor_rfc,
                :emisor_nombre, :emisor_regimen_fiscal,
                :receptor_rfc, :receptor_nombre,
                :receptor_regimen_fiscal,
                :receptor_domicilio_fiscal,
                :receptor_uso_cfdi,
                :total_impuestos_trasladados,
                :impuesto, :tasa_o_cuota, :tipo_factor,
                NOW()
            )";

            Logger::debug("Preparando consulta SQL para inserción", [
                'sql' => $sql
            ]);

            $stmt = $this->db->prepare($sql);

            // Asignar valores a los parámetros
            $params = [
                ':client_id' => $data['client_id'],
                ':xml_path' => $data['xml_path'],
                ':uuid' => $data['uuid'],
                ':serie' => $data['serie'] ?? '',
                ':folio' => $data['folio'] ?? '',
                ':fecha' => $data['fecha'],
                ':fecha_timbrado' => $data['fecha_timbrado'],
                ':subtotal' => $data['subtotal'],
                ':total' => $data['total'],
                ':tipo_comprobante' => $data['tipo_comprobante'],
                ':forma_pago' => $data['forma_pago'] ?? '',
                ':metodo_pago' => $data['metodo_pago'] ?? '',
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
                ':impuesto' => $data['impuesto'] ?? '',
                ':tasa_o_cuota' => $data['tasa_o_cuota'] ?? 0,
                ':tipo_factor' => $data['tipo_factor'] ?? ''
            ];

            Logger::debug("Ejecutando inserción con parámetros", [
                'params' => $params
            ]);

            $result = $stmt->execute($params);

            if ($result) {
                Logger::info("XML guardado exitosamente", [
                    'uuid' => $data['uuid'],
                    'client_id' => $data['client_id']
                ]);
            } else {
                Logger::error("Error al guardar XML", [
                    'uuid' => $data['uuid'],
                    'error' => $stmt->errorInfo()
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Logger::error("Error en create de ClientXml", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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