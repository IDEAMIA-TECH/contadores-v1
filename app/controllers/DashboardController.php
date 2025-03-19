<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/Security.php';
require_once __DIR__ . '/../models/Client.php';

class DashboardController {
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new Security();
    }
    
    public function index() {
        if (!$this->security->isAuthenticated()) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        
        // Obtener la ruta actual
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $route = str_starts_with($requestUri, BASE_URL) 
            ? substr($requestUri, strlen(BASE_URL)) 
            : $requestUri;
        
        // Aquí podrías agregar lógica para obtener estadísticas
        
        include __DIR__ . '/../views/dashboard/index.php';
    }
} 