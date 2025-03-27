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
            $this->log("\n\n=== NUEVO PROCESAMIENTO DE XML ===");
            $this->log("Iniciando procesamiento para Factura ID: " . $facturaId);
            
            if (!$xml) {
                $this->log("ERROR: XML no válido");
                return false;
            }

            // Convertir el XML a string para buscar la posición específica
            $xmlString = $xml->asXML();
            
            // Buscar la posición del cierre de Conceptos
            $conceptosEndPos = strpos($xmlString, '</cfdi:Conceptos>');
            if ($conceptosEndPos === false) {
                $this->log("No se encontró el cierre de la sección Conceptos");
                return false;
            }

            // Buscar la sección de Impuestos después de Conceptos
            $impuestosStartPos = strpos($xmlString, '<cfdi:Impuestos', $conceptosEndPos);
            $impuestosEndPos = strpos($xmlString, '</cfdi:Impuestos>', $impuestosStartPos);
            
            if ($impuestosStartPos === false || $impuestosEndPos === false) {
                $this->log("No se encontró la sección de Impuestos después de Conceptos");
                return false;
            }

            // Extraer la sección de Impuestos relevante
            $impuestosSection = substr($xmlString, $impuestosStartPos, 
                $impuestosEndPos - $impuestosStartPos + strlen('</cfdi:Impuestos>'));
            
            $this->log("Sección de Impuestos encontrada:");
            $this->log($impuestosSection);

            // Crear un nuevo XML con solo la sección de impuestos
            $impuestosXml = new SimpleXMLElement($impuestosSection);
            
            // Obtener los traslados
            $traslados = $impuestosXml->xpath('//cfdi:Traslado');
            $this->log("\nNúmero de traslados encontrados: " . count($traslados));

            // Eliminar registros existentes para esta factura
            $deleteQuery = "DELETE FROM ivas_factura WHERE factura_id = ?";
            $stmt = $this->db->prepare($deleteQuery);
            $stmt->execute([$facturaId]);
            
            foreach ($traslados as $index => $traslado) {
                $attrs = $traslado->attributes();
                
                $this->log("\n--- Traslado #" . ($index + 1) . " ---");
                $this->log("Atributos encontrados:");
                foreach ($attrs as $name => $value) {
                    $this->log("$name => $value");
                }
                
                if (isset($attrs['Base']) && isset($attrs['TasaOCuota']) && isset($attrs['Importe'])) {
                    $base = (float)$attrs['Base'];
                    $tasaOCuota = (float)$attrs['TasaOCuota'];
                    $importe = (float)$attrs['Importe'];
                    
                    $this->log("Procesando valores:");
                    $this->log("Base: $base");
                    $this->log("TasaOCuota: $tasaOCuota");
                    $this->log("Importe: $importe");

                    try {
                        $query = "INSERT INTO ivas_factura (factura_id, base, tasa, importe) 
                                 VALUES (?, ?, ?, ?)";
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$facturaId, $base, $tasaOCuota, $importe]);
                        
                        $this->log("✓ Registro insertado correctamente");
                    } catch (Exception $e) {
                        $this->log("ERROR en inserción: " . $e->getMessage());
                        throw $e;
                    }
                } else {
                    $this->log("⚠ Faltan atributos requeridos en este traslado");
                }
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