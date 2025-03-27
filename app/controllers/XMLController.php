<?php

class XMLController {
    private $db;
    private $logFile;

    public function __construct() {
        // Inicializar la conexión a la base de datos
        $this->db = Database::getInstance()->getConnection();
        
        // Definir la ruta absoluta del log
        $this->logFile = '/home/ideamiadev/public_html/contadores-v1/logs/xml_process.log';
        
        // Asegurarnos que el directorio existe
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Crear el archivo si no existe
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0777);
        }
        
        // Verificar permisos de escritura
        if (!is_writable($this->logFile)) {
            chmod($this->logFile, 0777);
        }
    }

    private function log($message) {
        try {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] $message" . PHP_EOL;
            
            // Intentar escribir directamente al archivo
            file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
            
            // También escribir al error_log como respaldo
            error_log($message);
            
        } catch (Exception $e) {
            error_log("Error escribiendo en el log: " . $e->getMessage());
        }
    }

    public function processXMLImpuestos($xml, $facturaId) {
        try {
            // Log inicial para verificar que la función se está ejecutando
            $this->log("\n\n=== NUEVO PROCESAMIENTO DE XML ===");
            $this->log("Iniciando procesamiento para Factura ID: " . $facturaId);
            
            // Verificar que el XML es válido
            if (!$xml) {
                $this->log("ERROR: XML no válido");
                return false;
            }

            // Obtener y loguear el XML completo para debug
            $this->log("XML Completo:");
            $this->log($xml->asXML());
            
            // Obtener la sección de impuestos
            $impuestos = $xml->xpath('//cfdi:Impuestos')[0] ?? null;
            
            if ($impuestos) {
                $this->log("\nSección de Impuestos encontrada:");
                $this->log($impuestos->asXML());
                
                // Obtener el total de impuestos trasladados
                $attrs = $impuestos->attributes();
                $totalTrasladados = isset($attrs['TotalImpuestosTrasladados']) ? 
                    (string)$attrs['TotalImpuestosTrasladados'] : 'No especificado';
                $this->log("Total Impuestos Trasladados: " . $totalTrasladados);
                
                // Obtener los traslados
                $traslados = $impuestos->xpath('./cfdi:Traslados/cfdi:Traslado');
                $this->log("\nNúmero de traslados encontrados: " . count($traslados));
                
                foreach ($traslados as $index => $traslado) {
                    $this->log("\n--- Traslado #" . ($index + 1) . " ---");
                    $this->log("XML del traslado:");
                    $this->log($traslado->asXML());
                    
                    $attrs = $traslado->attributes();
                    
                    // Loguear todos los atributos encontrados
                    $this->log("Atributos encontrados:");
                    foreach ($attrs as $name => $value) {
                        $this->log("$name => $value");
                    }
                    
                    if (isset($attrs['Base']) && isset($attrs['Importe']) && isset($attrs['TasaOCuota'])) {
                        $base = (float)$attrs['Base'];
                        $importe = (float)$attrs['Importe'];
                        $tasaOCuota = (float)$attrs['TasaOCuota'];
                        
                        try {
                            $query = "INSERT INTO ivas_factura (factura_id, base, importe, tasa) 
                                    VALUES (?, ?, ?, ?)";
                            $stmt = $this->db->prepare($query);
                            $stmt->execute([$facturaId, $base, $importe, $tasaOCuota]);
                            
                            $this->log("✓ Registro insertado correctamente");
                        } catch (Exception $e) {
                            $this->log("ERROR en inserción: " . $e->getMessage());
                        }
                    } else {
                        $this->log("⚠ Faltan atributos requeridos en este traslado");
                    }
                }
            } else {
                $this->log("❌ No se encontró la sección de Impuestos en el XML");
            }
            
            $this->log("=== FIN DEL PROCESAMIENTO ===\n");
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ ERROR GENERAL: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
            throw $e;
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