<?php

class XMLController {
    public function processXMLImpuestos($xml, $facturaId) {
        try {
            // Agregar logging para depuración
            error_log("Procesando impuestos para factura ID: " . $facturaId);
            
            // Eliminar registros existentes
            $deleteQuery = "DELETE FROM ivas_factura WHERE factura_id = ?";
            $stmt = $this->db->prepare($deleteQuery);
            $stmt->execute([$facturaId]);
            error_log("Registros anteriores eliminados");
            
            $impuestos = $xml->xpath('//cfdi:Impuestos[1]')[0] ?? null;
            error_log("Impuestos encontrados: " . ($impuestos ? "Sí" : "No"));
            
            if ($impuestos) {
                $traslados = $impuestos->xpath('.//cfdi:Traslado');
                error_log("Número de traslados encontrados: " . count($traslados));
                
                foreach ($traslados as $traslado) {
                    $attributes = $traslado->attributes();
                    error_log("Procesando traslado - Base: " . $attributes['Base'] . 
                             ", Importe: " . $attributes['Importe'] . 
                             ", Tasa: " . $attributes['TasaOCuota']);
                    
                    // Convertir la tasa de porcentaje a decimal
                    $tasaOCuota = (float)$attributes['TasaOCuota'];
                    
                    // Preparar la consulta de inserción
                    $query = "INSERT INTO ivas_factura (
                        factura_id, 
                        base, 
                        importe, 
                        tasa
                    ) VALUES (?, ?, ?, ?)";
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        $facturaId,
                        (float)$attributes['Base'],
                        (float)$attributes['Importe'],
                        $tasaOCuota
                    ]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error procesando impuestos del XML: " . $e->getMessage());
            throw new Exception("Error al procesar los impuestos del XML");
        }
    }

    public function processXML($xmlContent, $clientId) {
        try {
            // ... código existente ...

            // Después de insertar en la tabla facturas y obtener el facturaId
            $this->processXMLImpuestos($xml, $facturaId);

            // ... resto del código ...
        } catch (Exception $e) {
            error_log("Error procesando XML: " . $e->getMessage());
            throw new Exception("Error al procesar el XML");
        }
    }
} 