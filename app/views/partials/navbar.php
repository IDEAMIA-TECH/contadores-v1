<?php
// Obtener la ruta actual
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = str_starts_with($requestUri, BASE_URL) 
    ? substr($requestUri, strlen(BASE_URL)) 
    : $requestUri;
?>
<nav class="bg-white shadow-lg border-b-4 border-teal-400">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center">
                        <span class="text-2xl font-bold text-[#0047BA]">IDEAMIA</span>
                        <span class="text-2xl font-light text-[#0047BA] ml-2">TECH</span>
                        <span class="ml-2 text-[#00C4B3] text-lg">Contaduría</span>
                    </a>
                </div>
                
                <div class="hidden sm:ml-8 sm:flex sm:space-x-8">
                    <a href="<?php echo BASE_URL; ?>/dashboard" 
                       class="<?php echo $route === '/dashboard' ? 'border-[#00C4B3]' : 'border-transparent'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium text-gray-900 hover:border-[#00C4B3] transition-colors">
                        Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>/clients" 
                       class="<?php echo str_starts_with($route, '/clients') ? 'border-[#00C4B3]' : 'border-transparent'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium text-gray-900 hover:border-[#00C4B3] transition-colors">
                        Clientes
                    </a>
                    <a href="<?php echo BASE_URL; ?>/reports" 
                       class="<?php echo $route === '/reports' ? 'border-[#00C4B3]' : 'border-transparent'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium text-gray-900 hover:border-[#00C4B3] transition-colors">
                        Reportes
                    </a>
                    <a href="<?php echo BASE_URL; ?>/profile" 
                       class="<?php echo $route === '/profile' ? 'border-[#00C4B3]' : 'border-transparent'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium text-gray-900 hover:border-[#00C4B3] transition-colors">
                        Mi Perfil
                    </a>
                </div>
            </div>
            
            <div class="flex items-center">
                <div class="flex items-center space-x-4">
                    <!-- Nombre de usuario -->
                    <span class="text-sm text-gray-700 font-medium hidden md:block">
                        Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                    
                    <!-- Botón de Cerrar Sesión -->
                    <button type="button"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                            class="inline-flex items-center px-4 py-2
                                   bg-[#0047BA] hover:bg-[#003A9E] 
                                   text-white font-medium text-sm
                                   rounded-md shadow-sm
                                   border-0
                                   transition-colors duration-200
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0047BA]">
                        <svg class="w-4 h-4 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="text-white">Cerrar Sesión</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Formulario oculto para el logout -->
<form id="logout-form" action="<?php echo BASE_URL; ?>/auth/logout" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
</form> 