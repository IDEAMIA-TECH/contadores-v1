<?php
class PdfParser {
    public function parseCSF($filePath) {
        // Requiere la librería pdfparser
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            // Extraer datos usando expresiones regulares
            $data = [];
            
            // RFC
            if (preg_match('/RFC:\s*([A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3})/', $text, $matches)) {
                $data['rfc'] = $matches[1];
            }
            
            // Razón Social
            if (preg_match('/Denominación\/Razón Social:\s*([^\n]+)/', $text, $matches)) {
                $data['business_name'] = trim($matches[1]);
            }
            
            // Régimen Fiscal
            if (preg_match('/Régimen Fiscal:\s*([^\n]+)/', $text, $matches)) {
                $data['fiscal_regime'] = trim($matches[1]);
            }
            
            // Dirección
            $address = [];
            if (preg_match('/Calle:\s*([^\n]+)/', $text, $matches)) {
                $address[] = trim($matches[1]);
            }
            if (preg_match('/Número Exterior:\s*([^\n]+)/', $text, $matches)) {
                $address[] = 'Ext. ' . trim($matches[1]);
            }
            if (preg_match('/Número Interior:\s*([^\n]+)/', $text, $matches)) {
                $address[] = 'Int. ' . trim($matches[1]);
            }
            if (preg_match('/Colonia:\s*([^\n]+)/', $text, $matches)) {
                $address[] = trim($matches[1]);
            }
            if (preg_match('/Código Postal:\s*([0-9]{5})/', $text, $matches)) {
                $address[] = 'C.P. ' . $matches[1];
            }
            
            $data['address'] = implode(', ', array_filter($address));
            
            return $data;
            
        } catch (Exception $e) {
            error_log('Error al parsear PDF: ' . $e->getMessage());
            return null;
        }
    }
} 