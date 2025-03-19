<?php
// Evitar cualquier salida antes de las redirecciones
ob_start();

// Cargar configuración y dependencias comunes
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/middleware/Security.php';

// Mostrar todos los errores en desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configurar el manejador de errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Registrar el error en el log
    error_log("Error [$errno] $errstr en $errfile:$errline");
    
    // Solo mostrar errores en modo debug
    if (APP_DEBUG) {
        ob_clean(); // Limpiar cualquier salida anterior
        echo "<h1>Error</h1>";
        echo "<p>$errstr</p>";
        echo "<p>Archivo: $errfile</p>";
        echo "<p>Línea: $errline</p>";
    }
    return true; // Evitar que PHP maneje el error
});

// Configurar el manejador de excepciones
set_exception_handler(function($e) {
    // Registrar la excepción en el log
    error_log("Excepción no capturada: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (APP_DEBUG) {
        ob_clean(); // Limpiar cualquier salida anterior
        echo "<h1>Error</h1>";
        echo "<pre>";
        echo "Mensaje: " . $e->getMessage() . "\n\n";
        echo "Archivo: " . $e->getFile() . "\n";
        echo "Línea: " . $e->getLine() . "\n\n";
        echo "Stack trace:\n" . $e->getTraceAsString();
        echo "</pre>";
    } else {
        ob_clean(); // Limpiar cualquier salida anterior
        include __DIR__ . '/app/views/error.php';
    }
});

try {
    // Obtener la ruta actual
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    error_log("Request URI: " . $requestUri);
    
    // Remover BASE_URL del inicio de la ruta si existe
    $route = str_starts_with($requestUri, BASE_URL) 
        ? substr($requestUri, strlen(BASE_URL)) 
        : $requestUri;
    
    error_log("Ruta procesada: " . $route);

    // Rutas públicas (no requieren autenticación)
    $publicRoutes = [
        '/login',
        '/forgot-password',
        '/reset-password'
    ];

    // Si no es una ruta pública y no hay sesión, redirigir al login
    if (!in_array($route, $publicRoutes) && !isset($_SESSION['user_id'])) {
        error_log("Ruta protegida sin autenticación, redirigiendo a login");
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    // Enrutamiento principal
    switch ($route) {
        case '/':
        case '':
            if (isset($_SESSION['user_id'])) {
                header('Location: ' . BASE_URL . '/dashboard');
            } else {
                header('Location: ' . BASE_URL . '/login');
            }
            exit;

        case '/login':
            require_once __DIR__ . '/app/controllers/AuthController.php';
            $controller = new AuthController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->login();
            } else {
                $controller->showLogin();
            }
            break;

        case '/logout':
            require_once __DIR__ . '/app/controllers/AuthController.php';
            $controller = new AuthController();
            $controller->logout();
            break;

        case '/dashboard':
            require_once __DIR__ . '/app/controllers/DashboardController.php';
            $controller = new DashboardController();
            $controller->index();
            break;

        case '/clients':
            require_once __DIR__ . '/app/controllers/ClientController.php';
            $controller = new ClientController();
            $controller->index();
            break;

        case '/clients/create':
            require_once __DIR__ . '/app/controllers/ClientController.php';
            $controller = new ClientController();
            $controller->create();
            break;

        case '/clients/store':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ' . BASE_URL . '/clients/create');
                exit;
            }
            require_once __DIR__ . '/app/controllers/ClientController.php';
            $controller = new ClientController();
            $controller->store();
            break;

        case '/clients/upload-xml':
            require_once __DIR__ . '/app/controllers/ClientController.php';
            $controller = new ClientController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->uploadXml();
            } else {
                $controller->showUploadXml();
            }
            break;

        case '/clients/process-csf':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('HTTP/1.1 405 Method Not Allowed');
                exit;
            }
            require_once __DIR__ . '/app/controllers/ClientController.php';
            $controller = new ClientController();
            $controller->processCSF();
            break;

        case '/reports':
            require_once __DIR__ . '/app/controllers/ReportController.php';
            $controller = new ReportController();
            $controller->index();
            break;

        case '/forgot-password':
            require_once __DIR__ . '/app/controllers/AuthController.php';
            $controller = new AuthController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->processForgotPassword();
            } else {
                $controller->showForgotPassword();
            }
            break;

        case '/reset-password':
            require_once __DIR__ . '/app/controllers/AuthController.php';
            $controller = new AuthController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->processResetPassword();
            } else {
                $controller->showResetPassword();
            }
            break;

        default:
            // Página 404
            header("HTTP/1.0 404 Not Found");
            include __DIR__ . '/app/views/404.php';
            break;
    }

} catch (Throwable $e) {
    error_log("Error crítico: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (APP_DEBUG) {
        echo "<h1>Error Crítico</h1>";
        echo "<pre>";
        echo "Mensaje: " . $e->getMessage() . "\n\n";
        echo "Archivo: " . $e->getFile() . "\n";
        echo "Línea: " . $e->getLine() . "\n\n";
        echo "Stack trace:\n" . $e->getTraceAsString();
        echo "</pre>";
    } else {
        include __DIR__ . '/app/views/error.php';
    }
}

// Enviar la salida almacenada en el buffer
ob_end_flush(); 