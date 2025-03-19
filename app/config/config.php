<?php
// Configuración de base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'ideamiadev_contadores');
define('DB_USER', getenv('DB_USER') ?: 'ideamiadev_contadores');
define('DB_PASS', getenv('DB_PASS') ?: '?y#rPKn59xyretAN');

// Rutas
define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', __DIR__ . '/..');
define('UPLOAD_PATH', BASE_PATH . '/uploads');

// Configuración de la aplicación
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') ?: false);

// Configuración de sesión
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);

// Inicializar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración de URLs
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');

// Configuración de errores
if (DEBUG) {
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

// Configuración de rutas
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB 