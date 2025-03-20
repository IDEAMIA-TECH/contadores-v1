<?php
class CfdiXmlParser {
    public function parse($xmlContent) {
        try {
            // Suprimir advertencias de XML mal formado
            libxml_use_internal_errors(true);
            
            $xml = new SimpleXMLElement($xmlContent);
            
            // Registrar los namespaces necesarios
            $namespaces = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
            
            // Extraer UUID del Timbre Fiscal Digital
            $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
            $uuid = isset($tfd[0]) ? (string)$tfd[0]['UUID'] : null;
            
            // Extraer datos básicos del comprobante
            $data = [
                'uuid' => $uuid,
                'fecha' => (string)$xml['Fecha'],
                'tipo_comprobante' => (string)$xml['TipoDeComprobante'],
                'serie' => (string)$xml['Serie'],
                'folio' => (string)$xml['Folio'],
                'subtotal' => (float)$xml['SubTotal'],
                'total' => (float)$xml['Total'],
                'emisor_rfc' => (string)$xml->Emisor['Rfc'],
                'emisor_nombre' => (string)$xml->Emisor['Nombre'],
                'receptor_rfc' => (string)$xml->Receptor['Rfc'],
                'receptor_nombre' => (string)$xml->Receptor['Nombre']
            ];
            
            return $data;

        } catch (Exception $e) {
            error_log("Error parseando XML: " . $e->getMessage());
            throw new Exception("Error al parsear el XML: " . $e->getMessage());
        } finally {
            libxml_clear_errors();
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