<?php
class XmlParser {
    public function parse($xmlPath) {
        if (!file_exists($xmlPath)) {
            throw new Exception("El archivo XML no existe");
        }
        
        try {
            $xml = new SimpleXMLElement(file_get_contents($xmlPath));
            $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
            
            // Obtener datos del CFDI
            $comprobante = $xml->xpath('//cfdi:Comprobante')[0];
            $timbreFiscal = $xml->xpath('//tfd:TimbreFiscalDigital')[0];
            
            return [
                'uuid' => (string)$timbreFiscal['UUID'],
                'xml_type' => $this->determineXmlType($comprobante),
                'emission_date' => (string)$comprobante['Fecha'],
                'certification_date' => (string)$timbreFiscal['FechaTimbrado'],
                'subtotal' => (float)$comprobante['SubTotal'],
                'total' => (float)$comprobante['Total'],
                'status' => 'active'
            ];
        } catch (Exception $e) {
            error_log("Error al parsear XML: " . $e->getMessage());
            throw new Exception("Error al procesar el archivo XML");
        }
    }
    
    private function determineXmlType($comprobante) {
        $tipoComprobante = (string)$comprobante['TipoDeComprobante'];
        switch (strtoupper($tipoComprobante)) {
            case 'I':
                return 'ingreso';
            case 'E':
                return 'egreso';
            case 'P':
                return 'pago';
            default:
                return 'otro';
        }
    }
} 