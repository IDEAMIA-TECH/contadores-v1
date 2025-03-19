<?php
require_once __DIR__ . '/../config/database.php';

class SatService {
    private $db;
    private $uploadDir;
    
    public function __construct($db) {
        $this->db = $db;
        $this->uploadDir = dirname(__DIR__) . '/uploads/xmls/';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function processXmlFiles($clientId, $files) {
        try {
            $results = [
                'processed' => 0,
                'errors' => [],
                'summary' => [
                    'emitidas' => 0,
                    'recibidas' => 0,
                    'total_amount' => 0
                ]
            ];

            foreach ($files['tmp_name'] as $index => $tmpFile) {
                try {
                    if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $xmlContent = file_get_contents($tmpFile);
                    if (!$this->isValidXml($xmlContent)) {
                        $results['errors'][] = "Archivo inválido: " . $files['name'][$index];
                        continue;
                    }

                    $xmlData = $this->parseXmlData($xmlContent);
                    if (!$xmlData) {
                        $results['errors'][] = "No se pudo procesar el XML: " . $files['name'][$index];
                        continue;
                    }

                    // Guardar el archivo
                    $fileName = $this->saveXmlFile($clientId, $files['name'][$index], $tmpFile);
                    
                    // Guardar en la base de datos
                    $this->saveXmlData($clientId, $fileName, $xmlData);

                    // Actualizar estadísticas
                    $results['processed']++;
                    if ($xmlData['tipo'] === 'emitidas') {
                        $results['summary']['emitidas']++;
                    } else {
                        $results['summary']['recibidas']++;
                    }
                    $results['summary']['total_amount'] += $xmlData['total'];

                } catch (Exception $e) {
                    $results['errors'][] = "Error procesando " . $files['name'][$index] . ": " . $e->getMessage();
                }
            }

            return $results;

        } catch (Exception $e) {
            error_log("Error en SatService::processXmlFiles: " . $e->getMessage());
            throw new Exception('Error al procesar los archivos XML: ' . $e->getMessage());
        }
    }

    private function isValidXml($content) {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($content);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        return $doc !== false && empty($errors);
    }

    private function parseXmlData($xmlContent) {
        $xml = simplexml_load_string($xmlContent);
        $ns = $xml->getNamespaces(true);

        // Registrar los namespaces comunes en CFDIs
        $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        // Extraer datos básicos
        $data = [
            'uuid' => '',
            'fecha' => '',
            'emisor_rfc' => '',
            'emisor_nombre' => '',
            'receptor_rfc' => '',
            'receptor_nombre' => '',
            'total' => 0,
            'tipo' => ''
        ];

        // Obtener UUID del Timbre Fiscal Digital
        $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
        if (!empty($tfd)) {
            $data['uuid'] = (string)$tfd[0]['UUID'];
        }

        // Datos básicos del CFDI
        $data['fecha'] = (string)$xml['Fecha'];
        $data['total'] = (float)$xml['Total'];

        // Datos del emisor y receptor
        $data['emisor_rfc'] = (string)$xml->xpath('//cfdi:Emisor')[0]['Rfc'];
        $data['emisor_nombre'] = (string)$xml->xpath('//cfdi:Emisor')[0]['Nombre'];
        $data['receptor_rfc'] = (string)$xml->xpath('//cfdi:Receptor')[0]['Rfc'];
        $data['receptor_nombre'] = (string)$xml->xpath('//cfdi:Receptor')[0]['Nombre'];

        // Determinar si es emitida o recibida basado en el RFC del cliente
        $clientRfc = $this->getClientRfc($data['emisor_rfc']);
        $data['tipo'] = ($clientRfc === $data['emisor_rfc']) ? 'emitidas' : 'recibidas';

        return $data;
    }

    private function saveXmlFile($clientId, $originalName, $tmpFile) {
        $fileName = $clientId . '_' . time() . '_' . uniqid() . '_' . $originalName;
        $filePath = $this->uploadDir . $fileName;
        
        if (!move_uploaded_file($tmpFile, $filePath)) {
            throw new Exception('No se pudo guardar el archivo XML');
        }
        
        return $fileName;
    }

    private function saveXmlData($clientId, $fileName, $xmlData) {
        $sql = "INSERT INTO facturas (
            client_id, file_name, uuid, fecha, emisor_rfc, 
            emisor_nombre, receptor_rfc, receptor_nombre, 
            total, tipo
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, 
            ?, ?
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $clientId, $fileName, $xmlData['uuid'], $xmlData['fecha'],
            $xmlData['emisor_rfc'], $xmlData['emisor_nombre'],
            $xmlData['receptor_rfc'], $xmlData['receptor_nombre'],
            $xmlData['total'], $xmlData['tipo']
        ]);
    }

    private function getClientRfc($rfc) {
        $stmt = $this->db->prepare("SELECT rfc FROM clients WHERE rfc = ?");
        $stmt->execute([$rfc]);
        return $stmt->fetchColumn();
    }

    public function getClientXmls($clientId, $tipo = null, $fechaInicio = null, $fechaFin = null) {
        $sql = "SELECT * FROM facturas WHERE client_id = ?";
        $params = [$clientId];

        if ($tipo) {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
        }

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND fecha BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }

        $sql .= " ORDER BY fecha DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 