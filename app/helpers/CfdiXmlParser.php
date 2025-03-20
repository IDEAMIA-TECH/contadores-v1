<?php
class CfdiXmlParser {
    public function parse($xmlContent) {
        try {
            libxml_use_internal_errors(true);
            
            $xml = new SimpleXMLElement($xmlContent);
            
            // Registrar los namespaces necesarios
            $namespaces = $xml->getNamespaces(true);
            error_log("Namespaces encontrados: " . print_r($namespaces, true));
            
            // Registrar el namespace principal de CFDI
            $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
            
            // Extraer datos del Timbre Fiscal Digital usando xpath
            $tfdNodes = $xml->xpath('//tfd:TimbreFiscalDigital');
            $tfd = !empty($tfdNodes) ? $tfdNodes[0] : null;
            
            $uuid = $tfd ? (string)$tfd['UUID'] : '';
            $fechaTimbrado = $tfd ? (string)$tfd['FechaTimbrado'] : '';
            
            // Extraer datos de Impuestos y Traslados usando xpath
            $impuestosTrasladados = 0;
            $impuesto = null;
            $tasaOCuota = null;
            $tipoFactor = null;
            
            $trasladoNodes = $xml->xpath('//cfdi:Comprobante/cfdi:Impuestos/cfdi:Traslados/cfdi:Traslado');
            if (!empty($trasladoNodes)) {
                $traslado = $trasladoNodes[0];
                $impuesto = (string)$traslado['Impuesto'];
                $tasaOCuota = (float)$traslado['TasaOCuota'];
                $tipoFactor = (string)$traslado['TipoFactor'];
                
                // Obtener total de impuestos trasladados
                $impuestosNodes = $xml->xpath('//cfdi:Comprobante/cfdi:Impuestos');
                if (!empty($impuestosNodes)) {
                    $impuestosTrasladados = (float)($impuestosNodes[0]['TotalImpuestosTrasladados'] ?? 0);
                }
            }
            
            // Extraer datos del Emisor y Receptor usando xpath
            $emisorNodes = $xml->xpath('//cfdi:Comprobante/cfdi:Emisor');
            $receptorNodes = $xml->xpath('//cfdi:Comprobante/cfdi:Receptor');
            
            $emisor = !empty($emisorNodes) ? $emisorNodes[0] : null;
            $receptor = !empty($receptorNodes) ? $receptorNodes[0] : null;
            
            error_log("Emisor raw data: " . print_r($emisor, true));
            error_log("Receptor raw data: " . print_r($receptor, true));
            
            // Construir array de datos
            $data = [
                'uuid' => strtoupper($uuid),
                'fecha_timbrado' => $fechaTimbrado,
                'fecha' => (string)$xml['Fecha'],
                'lugar_expedicion' => (string)$xml['LugarExpedicion'],
                'tipo_comprobante' => (string)$xml['TipoDeComprobante'],
                'forma_pago' => (string)$xml['FormaPago'],
                'metodo_pago' => (string)$xml['MetodoPago'],
                'moneda' => (string)$xml['Moneda'],
                'serie' => (string)$xml['Serie'],
                'folio' => (string)$xml['Folio'],
                'subtotal' => (float)$xml['SubTotal'],
                'total' => (float)$xml['Total'],
                
                // Datos de impuestos
                'total_impuestos_trasladados' => $impuestosTrasladados,
                'impuesto' => $impuesto,
                'tasa_o_cuota' => $tasaOCuota,
                'tipo_factor' => $tipoFactor,
                
                // Datos del emisor
                'emisor_rfc' => $emisor ? (string)$emisor['Rfc'] : '',
                'emisor_nombre' => $emisor ? (string)$emisor['Nombre'] : '',
                'emisor_regimen_fiscal' => $emisor ? (string)$emisor['RegimenFiscal'] : '',
                
                // Datos del receptor
                'receptor_rfc' => $receptor ? (string)$receptor['Rfc'] : '',
                'receptor_nombre' => $receptor ? (string)$receptor['Nombre'] : '',
                'receptor_regimen_fiscal' => $receptor ? (string)$receptor['RegimenFiscalReceptor'] : '',
                'receptor_domicilio_fiscal' => $receptor ? (string)$receptor['DomicilioFiscalReceptor'] : '',
                'receptor_uso_cfdi' => $receptor ? (string)$receptor['UsoCFDI'] : ''
            ];
            
            error_log("Datos extraídos del XML:");
            error_log("Emisor - RFC: {$data['emisor_rfc']}, Nombre: {$data['emisor_nombre']}, Régimen: {$data['emisor_regimen_fiscal']}");
            error_log("Receptor - RFC: {$data['receptor_rfc']}, Nombre: {$data['receptor_nombre']}, Régimen: {$data['receptor_regimen_fiscal']}");
            error_log("Domicilio Fiscal: {$data['receptor_domicilio_fiscal']}, Uso CFDI: {$data['receptor_uso_cfdi']}");
            error_log("Impuestos - Total: {$data['total_impuestos_trasladados']}, Impuesto: {$data['impuesto']}, Tasa: {$data['tasa_o_cuota']}");
            
            // Validar que los campos requeridos no estén vacíos
            $requiredFields = [
                'emisor_rfc', 'emisor_nombre', 'emisor_regimen_fiscal',
                'receptor_rfc', 'receptor_nombre', 'receptor_regimen_fiscal',
                'receptor_domicilio_fiscal', 'receptor_uso_cfdi'
            ];
            
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    error_log("Campo requerido vacío: $field - Valor actual: " . $data[$field]);
                    throw new Exception("El campo $field es requerido y está vacío");
                }
            }
            
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