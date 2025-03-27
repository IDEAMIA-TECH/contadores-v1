<?php

class XMLController {
    public function __construct() {
        // Crear directorio de logs si no existe
        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    public function processXMLImpuestos($xml, $facturaId) {
        try {
            // Definir archivo de log
            $logFile = __DIR__ . '/../logs/xml_process.log';
            
            // Función helper para logging
            $logMessage = function($message) use ($logFile) {
                $timestamp = date('Y-m-d H:i:s');
                file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
            };

            $logMessage("=== INICIO PROCESAMIENTO DE IMPUESTOS ===");
            $logMessage("Factura ID: " . $facturaId);
            
            // Eliminar registros existentes
            $deleteQuery = "DELETE FROM ivas_factura WHERE factura_id = ?";
            $stmt = $this->db->prepare($deleteQuery);
            $stmt->execute([$facturaId]);
            
            // Primero, obtener el nodo Impuestos principal
            $impuestos = $xml->xpath('//cfdi:Impuestos')[0] ?? null;
            
            if ($impuestos) {
                // Log del TotalImpuestosTrasladados
                $totalTrasladados = $impuestos->attributes()['TotalImpuestosTrasladados'] ?? 'No especificado';
                $logMessage("TotalImpuestosTrasladados encontrado: " . $totalTrasladados);
                
                // Log del XML completo de la sección de Impuestos
                $logMessage("=== SECCION COMPLETA DE IMPUESTOS ===");
                $logMessage($impuestos->asXML());
                
                // Obtener los traslados
                $traslados = $impuestos->xpath('./cfdi:Traslados/cfdi:Traslado');
                $logMessage("Número de traslados encontrados: " . count($traslados));
                
                foreach ($traslados as $index => $traslado) {
                    $logMessage("=== PROCESANDO TRASLADO #" . ($index + 1) . " ===");
                    $logMessage("XML del traslado:");
                    $logMessage($traslado->asXML());
                    
                    $attributes = $traslado->attributes();
                    
                    // Log de todos los atributos disponibles
                    $logMessage("Atributos encontrados:");
                    foreach ($attributes as $name => $value) {
                        $logMessage("  $name => $value");
                    }
                    
                    if (isset($attributes['Base']) && 
                        isset($attributes['Importe']) && 
                        isset($attributes['TasaOCuota'])) {
                        
                        $base = (float)$attributes['Base'];
                        $importe = (float)$attributes['Importe'];
                        $tasaOCuota = (float)$attributes['TasaOCuota'];
                        
                        $logMessage("Valores procesados:");
                        $logMessage("  Base: $base");
                        $logMessage("  Importe: $importe");
                        $logMessage("  TasaOCuota: $tasaOCuota");
                        
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
                            
                            $logMessage("✓ Registro insertado correctamente");
                        } else {
                            $logMessage("⚠ Registro duplicado encontrado, saltando inserción");
                        }
                    } else {
                        $logMessage("⚠ Faltan atributos requeridos en este traslado");
                    }
                    
                    $logMessage("=== FIN TRASLADO #" . ($index + 1) . " ===");
                }
            } else {
                $logMessage("❌ No se encontró la sección de Impuestos en el XML");
            }
            
            $logMessage("=== FIN PROCESAMIENTO DE IMPUESTOS ===");
            return true;
            
        } catch (Exception $e) {
            $logMessage("❌ ERROR: " . $e->getMessage());
            $logMessage("Stack trace: " . $e->getTraceAsString());
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