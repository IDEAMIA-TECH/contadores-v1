<?php

class Controller {
    protected function view($view, $data = []) {
        // Extraer los datos para que estén disponibles en la vista
        extract($data);
        
        // Construir la ruta completa a la vista
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        
        // Verificar si el archivo existe
        if (!file_exists($viewPath)) {
            // Log del error
            error_log("Vista no encontrada: " . $viewPath);
            throw new Exception("Vista no encontrada: " . $view);
        }
        
        // Incluir la vista
        require $viewPath;
    }
} 