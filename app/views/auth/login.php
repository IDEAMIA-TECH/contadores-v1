<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IDEAMIA TECH Contaduría</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white rounded-xl shadow-lg p-8">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-[#0047BA]">IDEAMIA TECH</h1>
                <p class="mt-2 text-xl text-[#00C4B3]">Contaduría</p>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">Iniciar Sesión</h2>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <?php echo htmlspecialchars($_SESSION['error']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/login" class="mt-8 space-y-6" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token ?? ''); ?>">
                
                <div class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            Usuario
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input type="text" id="username" name="username" required 
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg
                                          text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-[#0047BA] 
                                          focus:border-[#0047BA] transition duration-150 ease-in-out text-base"
                                   placeholder="Ingrese su usuario"
                                   autocomplete="username">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Contraseña
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required 
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg
                                          text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-[#0047BA] 
                                          focus:border-[#0047BA] transition duration-150 ease-in-out text-base"
                                   placeholder="Ingrese su contraseña"
                                   autocomplete="current-password">
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-3 px-4
                                   bg-white hover:bg-gray-50
                                   text-gray-900 font-medium text-base
                                   rounded-md 
                                   border border-gray-300
                                   shadow-sm
                                   transition-colors duration-200
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0047BA]">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                        </span>
                        <span>Iniciar Sesión</span>
                    </button>
                </div>

                <div class="flex items-center justify-center">
                    <a href="<?php echo BASE_URL; ?>/forgot-password" 
                       class="text-sm text-[#0047BA] hover:text-[#003A9E] transition duration-150 ease-in-out">
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
</body>
</html> 