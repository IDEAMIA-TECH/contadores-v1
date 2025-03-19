<?php
// Configuración de base de datos
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'ideamiadev_contadores');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'ideamiadev_contadores');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '?y#rPKn59xyretAN');

// Rutas
define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', __DIR__ . '/..');
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// Configuración de la aplicación
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', getenv('APP_DEBUG') ?: true);

// Configuración de URLs
define('APP_URL', getenv('APP_URL') ?: 'https://contadores.ideamia.dev');

// Definir la URL base del proyecto
define('BASE_URL', '/contadores-v1');

// Configuración de errores
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de sesión
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);
session_start();

// Configuración de archivos
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Configuración de logs
define('LOG_PATH', BASE_PATH . '/logs');
ini_set('error_log', LOG_PATH . '/error.log');
ini_set('log_errors', 1);

// Debug: Mostrar valores de conexión si estamos en modo debug
if (APP_DEBUG) {
    error_log("Configuración de BD:");
    error_log("Host: " . DB_HOST);
    error_log("Base de datos: " . DB_NAME);
    error_log("Usuario: " . DB_USER);
} 