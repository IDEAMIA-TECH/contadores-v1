<?php
// Obtener la ruta actual
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = str_starts_with($requestUri, BASE_URL) 
    ? substr($requestUri, strlen(BASE_URL)) 
    : $requestUri;
?>
<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center">
                        <!-- Logo y nombre con los colores de CONTPAQi -->
                        <span class="text-[#0046BE] text-2xl font-bold">CONTPAQ</span>
                        <span class="text-[#00B3A7] text-2xl">i</span>
                        <span class="ml-2 bg-[#00B3A7] text-white px-3 py-1 text-sm rounded">
                            Contabilidad
                        </span>
                    </a>
                </div>
                
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="<?php echo BASE_URL; ?>/dashboard" 
                       class="<?php echo $route === '/dashboard' ? 'border-[#00B3A7] text-[#0046BE]' : 'border-transparent text-gray-700'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:text-[#0046BE]">
                        Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>/clients" 
                       class="<?php echo str_starts_with($route, '/clients') ? 'border-[#00B3A7] text-[#0046BE]' : 'border-transparent text-gray-700'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:text-[#0046BE]">
                        Clientes
                    </a>
                    <a href="<?php echo BASE_URL; ?>/reports" 
                       class="<?php echo $route === '/reports' ? 'border-[#00B3A7] text-[#0046BE]' : 'border-transparent text-gray-700'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:text-[#0046BE]">
                        Reportes
                    </a>
                    <a href="<?php echo BASE_URL; ?>/profile" 
                       class="<?php echo $route === '/profile' ? 'border-[#00B3A7] text-[#0046BE]' : 'border-transparent text-gray-700'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:text-[#0046BE]">
                        Mi Perfil
                    </a>
                </div>
            </div>
            
            <div class="flex items-center">
                <div class="hidden sm:ml-6 sm:flex sm:items-center">
                    <div class="ml-3 relative">
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <button 
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                               class="bg-[#0046BE] hover:bg-[#003899] text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                Cerrar Sesión
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Formulario oculto para el logout -->
<form id="logout-form" action="<?php echo BASE_URL; ?>/auth/logout" method="POST" class="hidden">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
</form>

<!-- Agregar estilos globales -->
<style>
    :root {
        --contpaqi-blue: #0046BE;
        --contpaqi-turquoise: #00B3A7;
    }

    .btn-primary {
        @apply bg-[#0046BE] hover:bg-[#003899] text-white px-4 py-2 rounded-md transition-colors duration-200;
    }

    .btn-secondary {
        @apply bg-[#00B3A7] hover:bg-[#009990] text-white px-4 py-2 rounded-md transition-colors duration-200;
    }

    .input-primary {
        @apply border-gray-300 focus:border-[#0046BE] focus:ring-[#0046BE] rounded-md shadow-sm;
    }

    .link-primary {
        @apply text-[#0046BE] hover:text-[#003899] transition-colors duration-200;
    }
</style> 