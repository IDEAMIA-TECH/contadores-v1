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

    public function processXML($xmlContent, $clientId) {
        try {
            $xml = new SimpleXMLElement($xmlContent);
            
            // Procesar datos básicos de la factura y obtener el facturaId
            // ... código existente para insertar en facturas ...

            // Procesar los impuestos usando los datos del frontend
            $fileName = basename($xmlContent);
            if (isset($_POST['traslados_' . $fileName])) {
                $this->log("Procesando traslados para archivo: " . $fileName);
                
                $trasladosJson = $_POST['traslados_' . $fileName];
                $traslados = json_decode($trasladosJson, true);
                
                $this->log("Traslados recibidos del frontend: " . print_r($traslados, true));

                // Eliminar registros existentes
                $deleteQuery = "DELETE FROM ivas_factura WHERE factura_id = ?";
                $stmt = $this->db->prepare($deleteQuery);
                $stmt->execute([$facturaId]);
                
                // Insertar los nuevos traslados
                foreach ($traslados as $traslado) {
                    $this->log("Insertando traslado: " . print_r($traslado, true));
                    
                    $query = "INSERT INTO ivas_factura (factura_id, base, tasa, importe) 
                             VALUES (?, ?, ?, ?)";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        $facturaId,
                        $traslado['base'],
                        $traslado['tasa'],
                        $traslado['importe']
                    ]);
                }
                
                $this->log("Traslados insertados correctamente");
            } else {
                $this->log("No se encontraron traslados en POST para el archivo: " . $fileName);
            }

            return true;
        } catch (Exception $e) {
            $this->log("Error procesando XML: " . $e->getMessage());
            throw new Exception("Error al procesar el XML");
        }
    }
} 