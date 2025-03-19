<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Restablecer Contraseña</h2>
                <p class="text-gray-600">Ingrese su nueva contraseña</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/reset-password" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                    <input type="password" id="password" name="password" required 
                           class="form-input"
                           minlength="8">
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                    <input type="password" id="password_confirm" name="password_confirm" required 
                           class="form-input"
                           minlength="8">
                </div>

                <div>
                    <button type="submit" class="btn-primary">
                        Restablecer Contraseña
                    </button>
                </div>

                <div class="text-center">
                    <a href="<?php echo BASE_URL; ?>/login" class="text-sm text-blue-600 hover:text-blue-800">
                        Volver al inicio de sesión
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 