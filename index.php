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
    case '/':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        // Redirigir según el rol
        switch ($_SESSION['role']) {
            case 'contador':
                header('Location: /clients');
                break;
            default:
                header('Location: /dashboard');
                break;
        }
        exit;

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

    case '/clients':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
            header('Location: /login');
            exit;
        }
        $controller = new ClientController();
        $controller->index();
        break;

    case '/clients/create':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
            header('Location: /login');
            exit;
        }
        $controller = new ClientController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->create();
        } else {
            $controller->showCreateForm();
        }
        break;

    case '/clients/extract-csf':
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
            header('Location: /login');
            exit;
        }
        $controller = new ClientController();
        $controller->extractCsfData();
        break;

    case '/dashboard':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        // TODO: Implementar dashboard
        echo "Dashboard - Próximamente";
        break;

    case '/profile':
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        // TODO: Implementar perfil
        echo "Perfil - Próximamente";
        break;

    case '/reset-password':
        $controller = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->resetPassword();
        } else {
            $controller->showResetForm();
        }
        break;

    default:
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        // Página 404
        header("HTTP/1.0 404 Not Found");
        include __DIR__ . '/app/views/404.php';
        break;
} 