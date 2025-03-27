<?php

class XMLController {
    public function processXMLImpuestos($xml, $facturaId) {
        try {
            error_log("=== INICIO PROCESAMIENTO DE IMPUESTOS ===");
            error_log("Factura ID: " . $facturaId);
            
            // Eliminar registros existentes
            $deleteQuery = "DELETE FROM ivas_factura WHERE factura_id = ?";
            $stmt = $this->db->prepare($deleteQuery);
            $stmt->execute([$facturaId]);
            
            // Primero, obtener el nodo Impuestos principal
            $impuestos = $xml->xpath('//cfdi:Impuestos')[0] ?? null;
            
            if ($impuestos) {
                // Log del TotalImpuestosTrasladados
                $totalTrasladados = $impuestos->attributes()['TotalImpuestosTrasladados'] ?? 'No especificado';
                error_log("TotalImpuestosTrasladados encontrado: " . $totalTrasladados);
                
                // Log del XML completo de la sección de Impuestos
                error_log("=== SECCION COMPLETA DE IMPUESTOS ===");
                error_log($impuestos->asXML());
                
                // Obtener los traslados
                $traslados = $impuestos->xpath('./cfdi:Traslados/cfdi:Traslado');
                error_log("Número de traslados encontrados: " . count($traslados));
                
                foreach ($traslados as $index => $traslado) {
                    error_log("=== PROCESANDO TRASLADO #" . ($index + 1) . " ===");
                    error_log("XML del traslado:");
                    error_log($traslado->asXML());
                    
                    $attributes = $traslado->attributes();
                    
                    // Log de todos los atributos disponibles
                    error_log("Atributos encontrados:");
                    foreach ($attributes as $name => $value) {
                        error_log("  $name => $value");
                    }
                    
                    if (isset($attributes['Base']) && 
                        isset($attributes['Importe']) && 
                        isset($attributes['TasaOCuota'])) {
                        
                        $base = (float)$attributes['Base'];
                        $importe = (float)$attributes['Importe'];
                        $tasaOCuota = (float)$attributes['TasaOCuota'];
                        
                        error_log("Valores procesados:");
                        error_log("  Base: $base");
                        error_log("  Importe: $importe");
                        error_log("  TasaOCuota: $tasaOCuota");
                        
                        // Verificar duplicados
                        $checkQuery = "SELECT id FROM ivas_factura 
                                     WHERE factura_id = ? 
                                     AND base = ? 
                                     AND tasa = ? 
                                     AND importe = ?";
                        
                        $checkStmt = $this->db->prepare($checkQuery);
                        $checkStmt->execute([$facturaId, $base, $tasaOCuota, $importe]);
                        
                        if (!$checkStmt->fetch()) {
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
                            
                            error_log("✓ Registro insertado correctamente");
                        } else {
                            error_log("⚠ Registro duplicado encontrado, saltando inserción");
                        }
                    } else {
                        error_log("⚠ Faltan atributos requeridos en este traslado");
                    }
                    
                    error_log("=== FIN TRASLADO #" . ($index + 1) . " ===");
                }
            } else {
                error_log("❌ No se encontró la sección de Impuestos en el XML");
            }
            
            error_log("=== FIN PROCESAMIENTO DE IMPUESTOS ===");
            return true;
            
        } catch (Exception $e) {
            error_log("❌ ERROR: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
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