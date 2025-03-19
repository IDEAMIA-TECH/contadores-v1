<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Smalot\PdfParser\Parser;

class PdfParser {
    private $parser;
    
    public function __construct() {
        $this->parser = new Parser();
    }
    
    public function parseCSF($filePath) {
        try {
            // Parsear el PDF
            $pdf = $this->parser->parseFile($filePath);
            $text = $pdf->getText();
            
            error_log("Texto extraído del PDF: " . $text);
            
            // Extraer datos usando expresiones regulares
            $data = [];
            
            // RFC
            if (preg_match('/RFC:\s*([A-Z0-9]{12,13})/', $text, $matches)) {
                $data['rfc'] = $matches[1];
            }
            
            // Razón Social
            if (preg_match('/DENOMINACIÓN/RAZÓN SOCIAL:\s*([^\n]+)/', $text, $matches)) {
                $data['razon_social'] = trim($matches[1]);
            }
            
            // Régimen Fiscal
            if (preg_match('/RÉGIMEN\s+(\d{3})/', $text, $matches)) {
                $data['regimen_fiscal'] = $matches[1];
            }
            
            // Dirección
            if (preg_match('/DOMICILIO:\s*([^\n]+)/', $text, $matches)) {
                $direccion = $matches[1];
                
                // Calle y número
                if (preg_match('/CALLE\s+([^,]+),?\s*(?:NO\.\s*(\d+))?(?:\s*INT\.\s*([^,]+))?/', $direccion, $matches)) {
                    $data['calle'] = trim($matches[1]);
                    $data['numero_exterior'] = $matches[2] ?? '';
                    $data['numero_interior'] = $matches[3] ?? '';
                }
                
                // Colonia
                if (preg_match('/COLONIA\s+([^,]+)/', $direccion, $matches)) {
                    $data['colonia'] = trim($matches[1]);
                }
                
                // Municipio
                if (preg_match('/(?:MUNICIPIO|ALCALDÍA)\s+([^,]+)/', $direccion, $matches)) {
                    $data['municipio'] = trim($matches[1]);
                }
                
                // Estado
                if (preg_match('/ESTADO\s+([^,]+)/', $direccion, $matches)) {
                    $data['estado'] = trim($matches[1]);
                }
                
                // Código Postal
                if (preg_match('/C\.P\.\s*(\d{5})/', $direccion, $matches)) {
                    $data['codigo_postal'] = $matches[1];
                }
            }
            
            error_log("Datos extraídos: " . print_r($data, true));
            
            return $data;
            
        } catch (Exception $e) {
            error_log("Error al parsear PDF: " . $e->getMessage());
            throw new Exception("Error al procesar el archivo PDF: " . $e->getMessage());
        }
    }
} 