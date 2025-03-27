<?php

class XMLController {
    private $db;
    private $logFile;

    public function __construct() {
        // Inicializar la conexión a la base de datos
        $this->db = Database::getInstance()->getConnection();
        
        // Configurar el directorio y archivo de logs
        $logDir = __DIR__ . '/../../logs';
        $this->logFile = $logDir . '/xml_process.log';

        // Crear directorio de logs si no existe y establecer permisos
        if (!file_exists($logDir)) {
            if (!mkdir($logDir, 0777, true)) {
                throw new Exception("No se pudo crear el directorio de logs");
            }
            chmod($logDir, 0777);
        }

        // Crear archivo de log si no existe y establecer permisos
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0666);
        }

        // Verificar si se puede escribir en el archivo
        if (!is_writable($this->logFile)) {
            throw new Exception("No se puede escribir en el archivo de log");
        }
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        // Intentar escribir en el archivo y verificar si fue exitoso
        if (file_put_contents($this->logFile, $logMessage, FILE_APPEND) === false) {
            error_log("Error escribiendo en el archivo de log: " . $this->logFile);
        }
    }

    public function processXMLImpuestos($xml, $facturaId) {
        try {
            $this->log("=== INICIO PROCESAMIENTO DE IMPUESTOS ===");
            $this->log("Factura ID: " . $facturaId);
            
            // Eliminar registros existentes
            $deleteQuery = "DELETE FROM ivas_factura WHERE factura_id = ?";
            $stmt = $this->db->prepare($deleteQuery);
            $stmt->execute([$facturaId]);
            
            // Obtener el nodo Impuestos principal
            $impuestos = $xml->xpath('//cfdi:Impuestos')[0] ?? null;
            
            if ($impuestos) {
                $this->log("XML completo siendo procesado:");
                $this->log($impuestos->asXML());
                
                $totalTrasladados = $impuestos->attributes()['TotalImpuestosTrasladados'] ?? 'No especificado';
                $this->log("TotalImpuestosTrasladados: " . $totalTrasladados);
                
                // Obtener solo los traslados dentro de la sección de Impuestos
                $traslados = $impuestos->xpath('./cfdi:Traslados/cfdi:Traslado');
                $this->log("Número de traslados encontrados: " . count($traslados));
                
                foreach ($traslados as $index => $traslado) {
                    $this->log("\n=== TRASLADO #" . ($index + 1) . " ===");
                    $this->log($traslado->asXML());
                    
                    $attributes = $traslado->attributes();
                    
                    if (isset($attributes['Base']) && 
                        isset($attributes['Importe']) && 
                        isset($attributes['TasaOCuota'])) {
                        
                        $base = (float)$attributes['Base'];
                        $importe = (float)$attributes['Importe'];
                        $tasaOCuota = (float)$attributes['TasaOCuota'];
                        
                        $this->log("Valores encontrados:");
                        $this->log("  Base: $base");
                        $this->log("  Importe: $importe");
                        $this->log("  TasaOCuota: $tasaOCuota");
                        
                        // Insertar en la base de datos
                        $query = "INSERT INTO ivas_factura (factura_id, base, importe, tasa) 
                                VALUES (?, ?, ?, ?)";
                        
                        $stmt = $this->db->prepare($query);
                        $stmt->execute([$facturaId, $base, $importe, $tasaOCuota]);
                        
                        $this->log("✓ Registro insertado correctamente");
                    }
                }
            } else {
                $this->log("❌ No se encontró la sección de Impuestos en el XML");
            }
            
            $this->log("=== FIN PROCESAMIENTO DE IMPUESTOS ===\n");
            return true;
            
        } catch (Exception $e) {
            $this->log("❌ ERROR: " . $e->getMessage());
            $this->log("Stack trace: " . $e->getTraceAsString());
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