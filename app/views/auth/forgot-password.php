<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Recuperar Contraseña</h2>
                <p class="text-gray-600">Ingrese su correo electrónico</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error">
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/forgot-password" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" id="email" name="email" required 
                           class="form-input">
                </div>

                <div>
                    <button type="submit" class="btn-primary">
                        Enviar Instrucciones
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