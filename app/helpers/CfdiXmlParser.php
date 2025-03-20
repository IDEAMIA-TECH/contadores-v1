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
            
            // Extraer UUID y fecha de timbrado del Timbre Fiscal Digital
            $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
            $uuid = isset($tfd[0]) ? (string)$tfd[0]['UUID'] : null;
            $fechaTimbrado = isset($tfd[0]) ? (string)$tfd[0]['FechaTimbrado'] : null;
            
            // Obtener impuestos trasladados
            $impuestosTrasladados = 0;
            $impuesto = null;
            $tasaOCuota = null;
            $tipoFactor = null;
            
            if (isset($xml->Impuestos) && isset($xml->Impuestos->Traslados)) {
                foreach ($xml->Impuestos->Traslados->Traslado as $traslado) {
                    $impuestosTrasladados += (float)($traslado['Importe'] ?? 0);
                    // Tomamos el primer impuesto y tasa como referencia
                    if ($impuesto === null) {
                        $impuesto = (string)$traslado['Impuesto'];
                        $tasaOCuota = (float)$traslado['TasaOCuota'];
                        $tipoFactor = (string)$traslado['TipoFactor'];
                    }
                }
            }
            
            // Extraer datos básicos del comprobante
            $data = [
                'uuid' => $uuid,
                'fecha_timbrado' => $fechaTimbrado,
                'fecha' => (string)$xml['Fecha'],
                'lugar_expedicion' => (string)$xml['LugarExpedicion'],
                'tipo_comprobante' => (string)$xml['TipoDeComprobante'],
                'forma_pago' => (string)$xml['FormaPago'],
                'metodo_pago' => (string)$xml['MetodoPago'],
                'moneda' => (string)$xml['Moneda'] ?: 'MXN',
                'serie' => (string)$xml['Serie'],
                'folio' => (string)$xml['Folio'],
                'subtotal' => (float)$xml['SubTotal'],
                'total' => (float)$xml['Total'],
                'total_impuestos_trasladados' => $impuestosTrasladados,
                'impuesto' => $impuesto,
                'tasa_o_cuota' => $tasaOCuota,
                'tipo_factor' => $tipoFactor,
                
                // Datos del emisor
                'emisor_rfc' => (string)$xml->Emisor['Rfc'],
                'emisor_nombre' => (string)$xml->Emisor['Nombre'],
                'emisor_regimen_fiscal' => (string)$xml->Emisor['RegimenFiscal'],
                
                // Datos del receptor
                'receptor_rfc' => (string)$xml->Receptor['Rfc'],
                'receptor_nombre' => (string)$xml->Receptor['Nombre'],
                'receptor_regimen_fiscal' => (string)$xml->Receptor['RegimenFiscalReceptor'],
                'receptor_domicilio_fiscal' => (string)$xml->Receptor['DomicilioFiscalReceptor'],
                'receptor_uso_cfdi' => (string)$xml->Receptor['UsoCFDI']
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