<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Smalot\PdfParser\Parser;

class PdfParser {
    private $parser;
    
    public function __construct() {
        $this->parser = new Parser();
    }
    
    private function getLocationDataByZipCode($zipCode) {
        try {
            // Usar la API de Códigos Postales de México
            $url = "https://api.copomex.com/query/info_cp/{$zipCode}?token=pruebas";
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if (isset($data[0])) {
                return [
                    'estado' => $data[0]['estado'] ?? '',
                    'municipio' => $data[0]['municipio'] ?? '',
                    'colonia' => $data[0]['asentamiento'] ?? ''
                ];
            }
        } catch (Exception $e) {
            error_log("Error al obtener datos de ubicación: " . $e->getMessage());
        }
        return null;
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
            
            // Nombre/Razón Social
            if (preg_match('/Nombre, denominación o razón\s+social\s+([^\n]+)/i', $text, $matches)) {
                $data['razon_social'] = trim($matches[1]);
            }
            
            // Régimen Fiscal
            if (preg_match('/Régimen de Sueldos y Salarios e Ingresos Asimilados a Salarios/', $text)) {
                $data['regimen_fiscal'] = '605';
            } elseif (preg_match('/Régimen de Incorporación Fiscal/', $text)) {
                $data['regimen_fiscal'] = '621';
            }
            
            // Código Postal y datos de ubicación
            if (preg_match('/CódigoPostal:(\d{5})/', $text, $matches)) {
                $zipCode = $matches[1];
                $data['codigo_postal'] = $zipCode;
                
                // Obtener datos de ubicación por CP
                $locationData = $this->getLocationDataByZipCode($zipCode);
                if ($locationData) {
                    $data['estado'] = $locationData['estado'];
                    $data['municipio'] = $locationData['municipio'];
                    $data['colonia'] = $locationData['colonia'];
                } else {
                    // Si falla la API, usar los datos del PDF
                    if (preg_match('/NombredelaColonia:([^N]+)/', $text, $matches)) {
                        $data['colonia'] = trim($matches[1]);
                    }
                    if (preg_match('/NombredelMunicipiooDemarcaciónTerritorial:([^N]+)/', $text, $matches)) {
                        $data['municipio'] = trim($matches[1]);
                    }
                    if (preg_match('/NombredelaEntidadFederativa:([^E]+)/', $text, $matches)) {
                        $data['estado'] = trim($matches[1]);
                    }
                }
            }
            
            // Calle
            if (preg_match('/NombredeVialidad:([^N]+)/', $text, $matches)) {
                $data['calle'] = trim($matches[1]);
            }
            
            // Número Exterior
            if (preg_match('/NúmeroExterior:(\d+)/', $text, $matches)) {
                $data['numero_exterior'] = $matches[1];
            }
            
            // Número Interior
            if (preg_match('/NúmeroInterior:(\d+)/', $text, $matches)) {
                $data['numero_interior'] = $matches[1];
            }
            
            // Nombre Legal (compuesto por nombre y apellidos)
            $nombreCompleto = [];
            if (preg_match('/Nombre\(s\):\s*([^\n]+)/', $text, $matches)) {
                $nombreCompleto[] = trim($matches[1]);
            }
            if (preg_match('/PrimerApellido:\s*([^\n]+)/', $text, $matches)) {
                $nombreCompleto[] = trim($matches[1]);
            }
            if (preg_match('/SegundoApellido:\s*([^\n]+)/', $text, $matches)) {
                $nombreCompleto[] = trim($matches[1]);
            }
            if (!empty($nombreCompleto)) {
                $data['nombre_legal'] = implode(' ', $nombreCompleto);
            }
            
            error_log("Datos extraídos: " . print_r($data, true));
            
            return $data;
            
        } catch (Exception $e) {
            error_log("Error al parsear PDF: " . $e->getMessage());
            throw new Exception("Error al procesar el archivo PDF: " . $e->getMessage());
        }
    }
} 