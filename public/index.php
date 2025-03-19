<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/ClientController.php';

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
        
    case '/logout':
        $controller = new AuthController();
        $controller->logout();
        break;
        
    case '/forgot-password':
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->processForgotPassword();
        } else {
            $controller->showForgotPassword();
        }
        break;
        
    case '/reset-password':
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->processResetPassword();
        } else {
            $controller->showResetPassword();
        }
        break;
        
    case '/clients/create':
        $controller = new ClientController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->create();
        } else {
            $controller->showCreateForm();
        }
        break;
        
    case '/clients/extract-csf':
        $controller = new ClientController();
        $controller->extractCsfData();
        break;
        
    default:
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        // Aquí irían las demás rutas protegidas
        break;
} 