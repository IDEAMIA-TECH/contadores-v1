<?php
define('BASE_PATH', dirname(__DIR__, 1));
define('APP_PATH', __DIR__ . '/../app');
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('DEBUG', getenv('APP_DEBUG') ?: true);

// Configuración de rutas
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

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

// Configuración de sesión
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200); 