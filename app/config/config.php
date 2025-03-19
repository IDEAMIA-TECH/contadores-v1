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
define('APP_ENV', getenv('APP_ENV') ?: 'development');
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

// Iniciar sesión al principio del archivo
if (session_status() === PHP_SESSION_NONE) {
    error_log("Iniciando sesión desde config.php");
    session_start();
    
    // Generar token CSRF si no existe
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        error_log("Generado token CSRF inicial en config: " . $_SESSION['csrf_token']);
    }
} else {
    error_log("Sesión ya iniciada en config.php - ID: " . session_id());
    error_log("Token CSRF actual: " . ($_SESSION['csrf_token'] ?? 'no existe'));
}

// Debug: Verificar estado de la sesión
error_log("=== Estado de la sesión ===");
error_log("Session ID: " . session_id());
error_log("Session status: " . session_status());
error_log("Session name: " . session_name());

// Configuración de archivos
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// Configuración de logs
define('LOG_PATH', BASE_PATH . '/logs');
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0777, true);
}

$logFile = LOG_PATH . '/error.log';
if (!file_exists($logFile)) {
    touch($logFile);
    chmod($logFile, 0666);
}

ini_set('error_log', $logFile);
ini_set('log_errors', 1);
error_log("=== Inicio de sesión de logs ===");

// Debug: Mostrar valores de conexión si estamos en modo debug
if (APP_DEBUG) {
    error_log("Configuración de BD:");
    error_log("Host: " . DB_HOST);
    error_log("Base de datos: " . DB_NAME);
    error_log("Usuario: " . DB_USER);
}

// Crear directorio de uploads si no existe
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
} 