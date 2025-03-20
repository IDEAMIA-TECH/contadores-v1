<?php
class CfdiXmlParser {
    public function parse($xmlContent) {
        try {
            libxml_use_internal_errors(true);
            
            $xml = new SimpleXMLElement($xmlContent);
            
            // Registrar los namespaces necesarios
            $namespaces = $xml->getNamespaces(true);
            error_log("Namespaces encontrados: " . print_r($namespaces, true));
            
            // Registrar todos los namespaces encontrados
            foreach ($namespaces as $prefix => $namespace) {
                $xml->registerXPathNamespace($prefix ?: 'cfdi', $namespace);
            }
            $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
            
            // Extraer datos del Timbre Fiscal Digital
            $tfd = $xml->xpath('//tfd:TimbreFiscalDigital')[0] ?? null;
            error_log("Timbre Fiscal Digital encontrado: " . ($tfd ? 'Sí' : 'No'));
            
            $uuid = $tfd ? (string)$tfd['UUID'] : '';
            $fechaTimbrado = $tfd ? (string)$tfd['FechaTimbrado'] : '';
            
            // Extraer datos de Impuestos y Traslados
            $impuestosTrasladados = 0;
            $impuesto = null;
            $tasaOCuota = null;
            $tipoFactor = null;
            
            if (isset($xml->Impuestos)) {
                $impuestosTrasladados = (float)($xml->Impuestos['TotalImpuestosTrasladados'] ?? 0);
                
                if (isset($xml->Impuestos->Traslados->Traslado)) {
                    $traslado = $xml->Impuestos->Traslados->Traslado;
                    $impuesto = (string)($traslado['Impuesto'] ?? '');
                    $tasaOCuota = (float)($traslado['TasaOCuota'] ?? 0);
                    $tipoFactor = (string)($traslado['TipoFactor'] ?? '');
                    
                    error_log("Datos de traslado encontrados - Impuesto: $impuesto, Tasa: $tasaOCuota, Tipo Factor: $tipoFactor");
                }
            }
            
            // Extraer datos del Emisor y Receptor directamente del XML
            $emisor = $xml->Emisor;
            $receptor = $xml->Receptor;
            
            error_log("Emisor encontrado: " . print_r($emisor, true));
            error_log("Receptor encontrado: " . print_r($receptor, true));
            
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
                'emisor_rfc' => (string)$emisor['Rfc'],
                'emisor_nombre' => (string)$emisor['Nombre'],
                'emisor_regimen_fiscal' => (string)$emisor['RegimenFiscal'],
                
                // Datos del receptor
                'receptor_rfc' => (string)$receptor['Rfc'],
                'receptor_nombre' => (string)$receptor['Nombre'],
                'receptor_regimen_fiscal' => (string)$receptor['RegimenFiscalReceptor'],
                'receptor_domicilio_fiscal' => (string)$receptor['DomicilioFiscalReceptor'],
                'receptor_uso_cfdi' => (string)$receptor['UsoCFDI']
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
                    error_log("Campo requerido vacío: $field");
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