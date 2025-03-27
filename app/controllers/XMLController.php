<?php

class XMLController {
    public function processXMLImpuestos($xml, $facturaId) {
        try {
            error_log("Procesando impuestos para factura ID: " . $facturaId);
            
            // Eliminar registros existentes
            $deleteQuery = "DELETE FROM ivas_factura WHERE factura_id = ?";
            $stmt = $this->db->prepare($deleteQuery);
            $stmt->execute([$facturaId]);
            
            // Modificamos el xpath para ser más específico
            // Buscamos solo los Traslados que están directamente dentro de cfdi:Impuestos
            $traslados = $xml->xpath('//cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado');
            
            if ($traslados) {
                error_log("Número de traslados encontrados: " . count($traslados));
                
                foreach ($traslados as $traslado) {
                    $attributes = $traslado->attributes();
                    
                    // Verificar que tengamos todos los atributos necesarios
                    if (isset($attributes['Base']) && 
                        isset($attributes['Importe']) && 
                        isset($attributes['TasaOCuota'])) {
                        
                        error_log("Procesando traslado - Base: " . $attributes['Base'] . 
                                 ", Importe: " . $attributes['Importe'] . 
                                 ", Tasa: " . $attributes['TasaOCuota']);
                        
                        // Convertir la tasa de porcentaje a decimal
                        $tasaOCuota = (float)$attributes['TasaOCuota'];
                        $base = (float)$attributes['Base'];
                        $importe = (float)$attributes['Importe'];
                        
                        // Verificar si ya existe un registro con estos valores
                        $checkQuery = "SELECT id FROM ivas_factura 
                                     WHERE factura_id = ? 
                                     AND base = ? 
                                     AND tasa = ? 
                                     AND importe = ?";
                        
                        $checkStmt = $this->db->prepare($checkQuery);
                        $checkStmt->execute([$facturaId, $base, $tasaOCuota, $importe]);
                        
                        if (!$checkStmt->fetch()) {
                            // Solo insertar si no existe
                            $query = "INSERT INTO ivas_factura (
                                factura_id, 
                                base, 
                                importe, 
                                tasa
                            ) VALUES (?, ?, ?, ?)";
                            
                            $stmt = $this->db->prepare($query);
                            $stmt->execute([
                                $facturaId,
                                $base,
                                $importe,
                                $tasaOCuota
                            ]);
                            
                            error_log("Registro insertado correctamente");
                        } else {
                            error_log("Registro duplicado encontrado, saltando inserción");
                        }
                    }
                }
            } else {
                error_log("No se encontraron traslados en la sección de impuestos");
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