<?php
// Agregar estos logs al inicio del archivo, antes del DOCTYPE
error_log("=== Inicio de carga de login.php ===");
error_log("Session status: " . session_status());
error_log("BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'no definido'));
error_log("Token disponible: " . (isset($token) ? 'sí' : 'no'));
error_log("Variables disponibles: " . print_r(get_defined_vars(), true));

// Asegurarnos que la sesión esté iniciada
if (session_status() === PHP_SESSION_NONE) {
    error_log("Iniciando sesión en login.php");
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Iniciar Sesión</h2>
            </div>

            <?php error_log("Verificando errores de sesión: " . (isset($_SESSION['error']) ? $_SESSION['error'] : 'no hay errores')); ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php error_log("Iniciando renderizado del formulario"); ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/login" class="space-y-6" id="loginForm">
                <?php 
                error_log("=== Renderizando formulario de login ===");
                error_log("Token CSRF en vista: " . ($token ?? 'no definido'));
                ?>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token ?? ''); ?>">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Usuario</label>
                    <input type="text" id="username" name="username" required 
                           class="form-input" autocomplete="username">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input type="password" id="password" name="password" required 
                           class="form-input" autocomplete="current-password">
                </div>

                <div>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        Iniciar Sesión
                    </button>
                </div>

                <div class="text-center">
                    <a href="<?php echo BASE_URL; ?>/forgot-password" class="text-sm text-blue-600 hover:text-blue-800">
                        ¿Olvidó su contraseña?
                    </a>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            console.log('Form submitted');
            console.log('CSRF Token:', document.querySelector('input[name="csrf_token"]').value);
        });
    </script>
    <?php error_log("=== Fin de carga de login.php ==="); ?>
</body>
</html> 