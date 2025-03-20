<?php
class CfdiXmlParser {
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
    
    public function isValidXml($content) {
        try {
            $dom = new DOMDocument();
            $dom->loadXML($content, LIBXML_NOWARNING);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function parse($content) {
        $dom = new DOMDocument();
        $dom->loadXML($content, LIBXML_NOWARNING);
        return $dom;
    }
    
    public function isCfdi($dom) {
        $root = $dom->documentElement;
        return $root->nodeName === 'cfdi:Comprobante';
    }
    
    public function getUuid($dom) {
        $timbreFiscal = $dom->getElementsByTagNameNS('http://www.sat.gob.mx/TimbreFiscalDigital', 'TimbreFiscalDigital')->item(0);
        return $timbreFiscal ? $timbreFiscal->getAttribute('UUID') : null;
    }
    
    public function getEmisorRfc($dom) {
        $emisor = $dom->getElementsByTagName('Emisor')->item(0);
        return $emisor ? $emisor->getAttribute('Rfc') : null;
    }
    
    public function getEmisorNombre($dom) {
        $emisor = $dom->getElementsByTagName('Emisor')->item(0);
        return $emisor ? $emisor->getAttribute('Nombre') : null;
    }
    
    public function getReceptorRfc($dom) {
        $receptor = $dom->getElementsByTagName('Receptor')->item(0);
        return $receptor ? $receptor->getAttribute('Rfc') : null;
    }
    
    public function getReceptorNombre($dom) {
        $receptor = $dom->getElementsByTagName('Receptor')->item(0);
        return $receptor ? $receptor->getAttribute('Nombre') : null;
    }
    
    public function getFecha($dom) {
        return $dom->documentElement->getAttribute('Fecha');
    }
    
    public function getTipoComprobante($dom) {
        return $dom->documentElement->getAttribute('TipoDeComprobante');
    }
    
    public function getTotal($dom) {
        return $dom->documentElement->getAttribute('Total');
    }
} 