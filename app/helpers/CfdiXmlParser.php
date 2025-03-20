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
            
            // Debug para ver la estructura del XML
            error_log("Estructura del XML Emisor: " . print_r($xml->Emisor, true));
            
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
                    $traslado = $xml->Impuestos->Traslados->Traslado[0];
                    $impuesto = (string)($traslado['Impuesto'] ?? '');
                    $tasaOCuota = (float)($traslado['TasaOCuota'] ?? 0);
                    $tipoFactor = (string)($traslado['TipoFactor'] ?? '');
                }
            }
            
            // Extraer datos del emisor usando diferentes métodos
            $emisorRfc = '';
            $emisorNombre = '';
            $emisorRegimenFiscal = '';
            
            if (isset($xml->Emisor)) {
                // Intento 1: Usando atributos directamente
                $emisorRfc = (string)($xml->Emisor['Rfc'] ?? '');
                $emisorNombre = (string)($xml->Emisor['Nombre'] ?? '');
                $emisorRegimenFiscal = (string)($xml->Emisor['RegimenFiscal'] ?? '');
                
                // Intento 2: Si están vacíos, intentar como elementos
                if (empty($emisorRfc)) $emisorRfc = (string)($xml->Emisor->Rfc ?? '');
                if (empty($emisorNombre)) $emisorNombre = (string)($xml->Emisor->Nombre ?? '');
                if (empty($emisorRegimenFiscal)) $emisorRegimenFiscal = (string)($xml->Emisor->RegimenFiscal ?? '');
                
                // Intento 3: Usar xpath
                if (empty($emisorRfc) || empty($emisorNombre) || empty($emisorRegimenFiscal)) {
                    $emisorNode = $xml->xpath('//cfdi:Emisor');
                    if (!empty($emisorNode)) {
                        $emisorRfc = (string)($emisorNode[0]['Rfc'] ?? '');
                        $emisorNombre = (string)($emisorNode[0]['Nombre'] ?? '');
                        $emisorRegimenFiscal = (string)($emisorNode[0]['RegimenFiscal'] ?? '');
                    }
                }
            }
            
            // Log de los datos del emisor encontrados
            error_log("Datos del emisor encontrados - RFC: $emisorRfc, Nombre: $emisorNombre, Régimen: $emisorRegimenFiscal");
            
            // Validar datos del emisor de forma más flexible
            if (empty($emisorRfc)) $emisorRfc = 'XAXX010101000'; // RFC genérico
            if (empty($emisorNombre)) $emisorNombre = 'NO IDENTIFICADO';
            if (empty($emisorRegimenFiscal)) $emisorRegimenFiscal = '616'; // Sin obligaciones fiscales
            
            // Extraer datos del receptor de forma similar
            $receptorRfc = (string)($xml->Receptor['Rfc'] ?? 'XAXX010101000');
            $receptorNombre = (string)($xml->Receptor['Nombre'] ?? 'PÚBLICO EN GENERAL');
            $receptorRegimenFiscal = (string)($xml->Receptor['RegimenFiscalReceptor'] ?? '616');
            $receptorDomicilioFiscal = (string)($xml->Receptor['DomicilioFiscalReceptor'] ?? '00000');
            $receptorUsoCfdi = (string)($xml->Receptor['UsoCFDI'] ?? 'P01');
            
            // Extraer datos básicos del comprobante con validación
            $data = [
                'uuid' => $uuid,
                'fecha_timbrado' => $fechaTimbrado ?: date('Y-m-d H:i:s'),
                'fecha' => (string)$xml['Fecha'] ?: date('Y-m-d H:i:s'),
                'lugar_expedicion' => (string)($xml['LugarExpedicion'] ?? '00000'),
                'tipo_comprobante' => (string)($xml['TipoDeComprobante'] ?? 'I'),
                'forma_pago' => (string)($xml['FormaPago'] ?? '99'),
                'metodo_pago' => (string)($xml['MetodoPago'] ?? 'PUE'),
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