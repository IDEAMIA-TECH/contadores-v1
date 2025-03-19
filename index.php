<?php
// Cargar configuración primero
require_once __DIR__ . '/app/config/config.php';

// Mostrar todos los errores en desarrollo
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? 1 : 0);
ini_set('display_startup_errors', APP_DEBUG ? 1 : 0);

// Configurar el manejador de errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno] $errstr en $errfile:$errline");
    if (APP_DEBUG) {
        echo "<h1>Error</h1>";
        echo "<p>$errstr</p>";
        echo "<p>Archivo: $errfile</p>";
        echo "<p>Línea: $errline</p>";
    }
});

// Configurar el manejador de excepciones
set_exception_handler(function($e) {
    error_log("Excepción no capturada: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    if (APP_DEBUG) {
        echo "<h1>Error</h1>";
        echo "<pre>";
        echo "Mensaje: " . $e->getMessage() . "\n\n";
        echo "Archivo: " . $e->getFile() . "\n";
        echo "Línea: " . $e->getLine() . "\n\n";
        echo "Stack trace:\n" . $e->getTraceAsString();
        echo "</pre>";
    } else {
        include __DIR__ . '/app/views/error.php';
    }
});

try {
    // Verificar mantenimiento
    require_once __DIR__ . '/maintenance.php';

    // Cargar clases necesarias
    require_once __DIR__ . '/app/config/database.php';
    require_once __DIR__ . '/app/config/security.php';
    require_once __DIR__ . '/app/controllers/AuthController.php';
    require_once __DIR__ . '/app/controllers/ClientController.php';

    // Iniciar o reanudar sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Debug: Mostrar información de la ruta
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (APP_DEBUG) {
        error_log("URI solicitada: " . $uri);
        error_log("Método HTTP: " . $_SERVER['REQUEST_METHOD']);
    }

    // Enrutador básico
    switch ($uri) {
        case BASE_URL . '/':
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            // Redirigir según el rol
            switch ($_SESSION['role']) {
                case 'contador':
                    header('Location: ' . BASE_URL . '/clients');
                    break;
                default:
                    header('Location: ' . BASE_URL . '/dashboard');
                    break;
            }
            exit;

        case BASE_URL . '/login':
            $controller = new AuthController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->login();
            } else {
                $controller->showLogin();
            }
            break;

        case BASE_URL . '/logout':
            $controller = new AuthController();
            $controller->logout();
            break;

        case BASE_URL . '/clients':
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            $controller = new ClientController();
            $controller->index();
            break;

        case BASE_URL . '/clients/create':
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            $controller = new ClientController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->create();
            } else {
                $controller->showCreateForm();
            }
            break;

        case BASE_URL . '/clients/extract-csf':
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'contador') {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            $controller = new ClientController();
            $controller->extractCsfData();
            break;

        case BASE_URL . '/dashboard':
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            include __DIR__ . '/app/views/dashboard/index.php';
            break;

        case BASE_URL . '/profile':
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            include __DIR__ . '/app/views/profile.php';
            break;

        case BASE_URL . '/forgot-password':
            $controller = new AuthController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->processForgotPassword();
            } else {
                $controller->showForgotPassword();
            }
            break;

        case BASE_URL . '/reset-password':
            $controller = new AuthController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->processResetPassword();
            } else {
                $controller->showResetPassword();
            }
            break;

        default:
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
            // Página 404
            header("HTTP/1.0 404 Not Found");
            include __DIR__ . '/app/views/404.php';
            break;
    }
} catch (Throwable $e) {
    // Log del error
    error_log("Error crítico: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (APP_DEBUG) {
        // Mostrar error detallado en desarrollo
        echo "<h1>Error Crítico</h1>";
        echo "<pre>";
        echo "Mensaje: " . $e->getMessage() . "\n\n";
        echo "Archivo: " . $e->getFile() . "\n";
        echo "Línea: " . $e->getLine() . "\n\n";
        echo "Stack trace:\n" . $e->getTraceAsString();
        echo "</pre>";
    } else {
        // Mostrar error amigable en producción
        include __DIR__ . '/app/views/error.php';
    }
} 