<?php
// Mostrar todos los errores en desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar mantenimiento
require_once __DIR__ . '/maintenance.php';

// Cargar configuración y clases necesarias
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/Database.php';
require_once __DIR__ . '/app/config/Security.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/ClientController.php';

// Iniciar o reanudar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Mostrar información de la ruta
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
error_log("URI solicitada: " . $uri);
error_log("Método HTTP: " . $_SERVER['REQUEST_METHOD']);

try {
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
} catch (Exception $e) {
    // Log del error
    error_log("Error en la aplicación: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Mostrar error amigable en producción
    if (APP_ENV === 'production') {
        include __DIR__ . '/app/views/error.php';
    } else {
        // Mostrar error detallado en desarrollo
        echo "<h1>Error</h1>";
        echo "<pre>";
        echo "Mensaje: " . $e->getMessage() . "\n\n";
        echo "Archivo: " . $e->getFile() . "\n";
        echo "Línea: " . $e->getLine() . "\n\n";
        echo "Stack trace:\n" . $e->getTraceAsString();
        echo "</pre>";
    }
} 