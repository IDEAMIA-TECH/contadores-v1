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
            $uuid = isset($tfd[0]) ? (string)$tfd[0]['UUID'] : '';
            $fechaTimbrado = isset($tfd[0]) ? (string)$tfd[0]['FechaTimbrado'] : '';
            
            // Obtener impuestos trasladados con validación más estricta
            $impuestosTrasladados = 0;
            $impuesto = null;
            $tasaOCuota = null;
            $tipoFactor = null;
            
            if (isset($xml->Impuestos)) {
                $impuestosTrasladados = (float)($xml->Impuestos['TotalImpuestosTrasladados'] ?? 0);
                
                if (isset($xml->Impuestos->Traslados) && isset($xml->Impuestos->Traslados->Traslado)) {
                    $traslado = $xml->Impuestos->Traslados->Traslado[0]; // Tomamos el primer traslado
                    $impuesto = (string)($traslado['Impuesto'] ?? '');
                    $tasaOCuota = (float)($traslado['TasaOCuota'] ?? 0);
                    $tipoFactor = (string)($traslado['TipoFactor'] ?? '');
                }
            }
            
            // Validar y extraer datos del emisor
            $emisorRfc = (string)($xml->Emisor['Rfc'] ?? '');
            $emisorNombre = (string)($xml->Emisor['Nombre'] ?? '');
            $emisorRegimenFiscal = (string)($xml->Emisor['RegimenFiscal'] ?? '');
            
            if (empty($emisorRfc) || empty($emisorNombre) || empty($emisorRegimenFiscal)) {
                throw new Exception("Datos del emisor incompletos o inválidos");
            }
            
            // Validar y extraer datos del receptor
            $receptorRfc = (string)($xml->Receptor['Rfc'] ?? '');
            $receptorNombre = (string)($xml->Receptor['Nombre'] ?? '');
            $receptorRegimenFiscal = (string)($xml->Receptor['RegimenFiscalReceptor'] ?? '');
            $receptorDomicilioFiscal = (string)($xml->Receptor['DomicilioFiscalReceptor'] ?? '');
            $receptorUsoCfdi = (string)($xml->Receptor['UsoCFDI'] ?? '');
            
            if (empty($receptorRfc) || empty($receptorNombre) || empty($receptorRegimenFiscal) || 
                empty($receptorDomicilioFiscal) || empty($receptorUsoCfdi)) {
                throw new Exception("Datos del receptor incompletos o inválidos");
            }
            
            // Extraer datos básicos del comprobante con validación
            $data = [
                'uuid' => $uuid,
                'fecha_timbrado' => $fechaTimbrado,
                'fecha' => (string)$xml['Fecha'],
                'lugar_expedicion' => (string)($xml['LugarExpedicion'] ?? ''),
                'tipo_comprobante' => (string)($xml['TipoDeComprobante'] ?? ''),
                'forma_pago' => (string)($xml['FormaPago'] ?? ''),
                'metodo_pago' => (string)($xml['MetodoPago'] ?? ''),
                'moneda' => (string)($xml['Moneda'] ?? 'MXN'),
                'serie' => (string)($xml['Serie'] ?? ''),
                'folio' => (string)($xml['Folio'] ?? ''),
                'subtotal' => (float)($xml['SubTotal'] ?? 0),
                'total' => (float)($xml['Total'] ?? 0),
                
                // Datos de impuestos
                'total_impuestos_trasladados' => $impuestosTrasladados,
                'impuesto' => $impuesto,
                'tasa_o_cuota' => $tasaOCuota,
                'tipo_factor' => $tipoFactor,
                
                // Datos del emisor
                'emisor_rfc' => $emisorRfc,
                'emisor_nombre' => $emisorNombre,
                'emisor_regimen_fiscal' => $emisorRegimenFiscal,
                
                // Datos del receptor
                'receptor_rfc' => $receptorRfc,
                'receptor_nombre' => $receptorNombre,
                'receptor_regimen_fiscal' => $receptorRegimenFiscal,
                'receptor_domicilio_fiscal' => $receptorDomicilioFiscal,
                'receptor_uso_cfdi' => $receptorUsoCfdi
            ];
            
            // Validar campos requeridos
            $requiredFields = [
                'uuid', 'fecha', 'fecha_timbrado', 'lugar_expedicion', 'tipo_comprobante',
                'emisor_rfc', 'emisor_nombre', 'emisor_regimen_fiscal',
                'receptor_rfc', 'receptor_nombre', 'receptor_regimen_fiscal',
                'receptor_domicilio_fiscal', 'receptor_uso_cfdi'
            ];
            
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Campo requerido faltante: {$field}");
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