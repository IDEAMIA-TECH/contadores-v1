<?php
// Verificar mantenimiento
require_once __DIR__ . '/maintenance.php';

// Cargar configuración
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/ClientController.php';

// Obtener la ruta actual
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Enrutador básico
switch ($uri) {
    case '/login':
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->login();
        } else {
            $controller->showLogin();
        }
        break;
        
    // ... resto de las rutas ...
} 