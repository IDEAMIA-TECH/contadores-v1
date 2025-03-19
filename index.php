<?php
// Evitar cualquier salida antes de las redirecciones
ob_start();

// Definir la URL base
define('BASE_URL', '/contadores-v1');

// Definir la ruta base del sistema
define('ROOT_PATH', __DIR__);

// Definir la ruta para los assets
define('ASSETS_URL', BASE_URL . '/public');

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
        if (APP_DEBUG) {
            echo "<p>En archivo: $errfile:$errline</p>";
        }
    }
    return true;
});

// Procesar la URL
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = parse_url(BASE_URL, PHP_URL_PATH);

// Remover el base path de la URL si existe
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$requestUri = trim($requestUri, '/');
error_log("Request URI: " . $requestUri);

try {
    // Enrutamiento básico
    $route = $requestUri ?: 'dashboard';
    error_log("Ruta procesada: /" . $route);
    
    // Dividir la ruta en controlador/acción
    $parts = explode('/', $route);
    $controller = ucfirst($parts[0] ?? 'Dashboard') . 'Controller';
    $action = $parts[1] ?? 'index';
    
    // Cargar el controlador
    $controllerFile = __DIR__ . "/app/controllers/{$controller}.php";
    if (!file_exists($controllerFile)) {
        throw new Exception('Página no encontrada', 404);
    }
    
    require_once $controllerFile;
    $controllerInstance = new $controller();
    
    // Verificar si el método existe
    if (!method_exists($controllerInstance, $action)) {
        throw new Exception('Página no encontrada', 404);
    }
    
    // Ejecutar la acción
    $controllerInstance->$action();
    
} catch (Exception $e) {
    error_log("Error en enrutamiento: " . $e->getMessage());
    
    switch ($e->getCode()) {
        case 404:
            header("HTTP/1.0 404 Not Found");
            include __DIR__ . '/app/views/404.php';
            break;
        default:
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
    }
} finally {
    // Enviar la salida almacenada en el buffer
    ob_end_flush();
} 