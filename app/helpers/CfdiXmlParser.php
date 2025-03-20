<?php
class CfdiXmlParser {
    public function parse($xmlContent) {
        try {
            libxml_use_internal_errors(true);
            
            $xml = new SimpleXMLElement($xmlContent);
            
            // Registrar los namespaces necesarios
            $namespaces = $xml->getNamespaces(true);
            error_log("Namespaces encontrados: " . print_r($namespaces, true));
            
            $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
            $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
            
            // Log del XML completo para debug
            error_log("Contenido completo del XML: " . $xmlContent);
            
            // Debug de la estructura completa
            error_log("Estructura completa del XML: " . print_r($xml, true));
            
            // Extraer y loggear datos del comprobante
            error_log("Atributos del Comprobante: " . print_r($xml->attributes(), true));
            
            // Extraer y loggear datos del emisor
            error_log("Datos del Emisor: " . print_r($xml->Emisor, true));
            error_log("Atributos del Emisor: " . print_r($xml->Emisor->attributes(), true));
            
            // Extraer y loggear datos del receptor
            error_log("Datos del Receptor: " . print_r($xml->Receptor, true));
            error_log("Atributos del Receptor: " . print_r($xml->Receptor->attributes(), true));
            
            // Extraer y loggear datos de impuestos
            error_log("Datos de Impuestos: " . print_r($xml->Impuestos, true));
            
            // Extraer UUID y fecha de timbrado
            $tfd = $xml->xpath('//tfd:TimbreFiscalDigital');
            error_log("Datos del Timbre Fiscal Digital: " . print_r($tfd, true));
            
            $uuid = isset($tfd[0]) ? (string)$tfd[0]['UUID'] : '';
            $fechaTimbrado = isset($tfd[0]) ? (string)$tfd[0]['FechaTimbrado'] : '';
            
            // Obtener impuestos trasladados
            $impuestosTrasladados = 0;
            $impuesto = null;
            $tasaOCuota = null;
            $tipoFactor = null;
            
            if (isset($xml->Impuestos)) {
                error_log("Procesando impuestos...");
                $impuestosTrasladados = (float)($xml->Impuestos['TotalImpuestosTrasladados'] ?? 0);
                
                if (isset($xml->Impuestos->Traslados) && isset($xml->Impuestos->Traslados->Traslado)) {
                    $traslado = $xml->Impuestos->Traslados->Traslado[0];
                    error_log("Datos del primer traslado: " . print_r($traslado, true));
                    
                    $impuesto = (string)($traslado['Impuesto'] ?? '');
                    $tasaOCuota = (float)($traslado['TasaOCuota'] ?? 0);
                    $tipoFactor = (string)($traslado['TipoFactor'] ?? '');
                }
            }
            
            // Extraer datos del emisor
            $emisorRfc = (string)($xml->Emisor['Rfc'] ?? '');
            $emisorNombre = (string)($xml->Emisor['Nombre'] ?? '');
            $emisorRegimenFiscal = (string)($xml->Emisor['RegimenFiscal'] ?? '');
            
            error_log("Datos extraídos del Emisor - RFC: $emisorRfc, Nombre: $emisorNombre, Régimen: $emisorRegimenFiscal");
            
            // Extraer datos del receptor
            $receptorRfc = (string)($xml->Receptor['Rfc'] ?? '');
            $receptorNombre = (string)($xml->Receptor['Nombre'] ?? '');
            $receptorRegimenFiscal = (string)($xml->Receptor['RegimenFiscalReceptor'] ?? '');
            $receptorDomicilioFiscal = (string)($xml->Receptor['DomicilioFiscalReceptor'] ?? '');
            $receptorUsoCfdi = (string)($xml->Receptor['UsoCFDI'] ?? '');
            
            error_log("Datos extraídos del Receptor - RFC: $receptorRfc, Nombre: $receptorNombre, Régimen: $receptorRegimenFiscal");
            
            // Extraer datos básicos del comprobante
            $data = [
                'uuid' => $uuid,
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
                'total_impuestos_trasladados' => $impuestosTrasladados,
                'impuesto' => $impuesto,
                'tasa_o_cuota' => $tasaOCuota,
                'tipo_factor' => $tipoFactor,
                'emisor_rfc' => $emisorRfc,
                'emisor_nombre' => $emisorNombre,
                'emisor_regimen_fiscal' => $emisorRegimenFiscal,
                'receptor_rfc' => $receptorRfc,
                'receptor_nombre' => $receptorNombre,
                'receptor_regimen_fiscal' => $receptorRegimenFiscal,
                'receptor_domicilio_fiscal' => $receptorDomicilioFiscal,
                'receptor_uso_cfdi' => $receptorUsoCfdi
            ];
            
            error_log("Datos finales extraídos: " . print_r($data, true));
            
            // Solo usar valores por defecto si realmente no se encontraron los datos
            if (empty($data['emisor_rfc'])) $data['emisor_rfc'] = 'XAXX010101000';
            if (empty($data['emisor_nombre'])) $data['emisor_nombre'] = 'NO IDENTIFICADO';
            if (empty($data['emisor_regimen_fiscal'])) $data['emisor_regimen_fiscal'] = '616';
            if (empty($data['receptor_rfc'])) $data['receptor_rfc'] = 'XAXX010101000';
            if (empty($data['receptor_nombre'])) $data['receptor_nombre'] = 'PÚBLICO EN GENERAL';
            if (empty($data['receptor_regimen_fiscal'])) $data['receptor_regimen_fiscal'] = '616';
            if (empty($data['receptor_domicilio_fiscal'])) $data['receptor_domicilio_fiscal'] = '00000';
            if (empty($data['receptor_uso_cfdi'])) $data['receptor_uso_cfdi'] = 'P01';
            if (empty($data['lugar_expedicion'])) $data['lugar_expedicion'] = '00000';
            
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