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

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/login" class="space-y-6">
                <?php 
                error_log("=== Renderizando formulario de login ===");
                error_log("Token disponible en la vista: " . (isset($token) ? $token : 'no definido'));
                
                if (!isset($token)) {
                    error_log("ADVERTENCIA: Token no definido en la vista");
                    $token = bin2hex(random_bytes(32));
                    $_SESSION['csrf_token'] = $token;
                    error_log("Generado token de emergencia: " . $token);
                }
                ?>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Usuario</label>
                    <input type="text" id="username" name="username" required 
                           class="form-input">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input type="password" id="password" name="password" required 
                           class="form-input">
                </div>

                <div>
                    <button type="submit" class="btn-primary">
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
</body>
</html> 