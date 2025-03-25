<?php
// Obtener la ruta actual
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = str_starts_with($requestUri, BASE_URL) 
    ? substr($requestUri, strlen(BASE_URL)) 
    : $requestUri;
?>
<nav class="bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <a href="<?php echo BASE_URL; ?>" class="text-white font-bold">Sistema de Cobranza</a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="<?php echo BASE_URL; ?>/clients" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Clientes</a>
                        <a href="<?php echo BASE_URL; ?>/reports" 
                           class="<?php echo $route === '/reports' ? 'border-blue-500' : 'border-transparent'; ?> 
                                  inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium text-gray-900">
                            Reportes
                        </a>
                        <a href="<?php echo BASE_URL; ?>/profile" 
                           class="<?php echo $route === '/profile' ? 'border-blue-500' : 'border-transparent'; ?> 
                                  inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium text-gray-900">
                            Mi Perfil
                        </a>
                    </div>
                </div>
            </div>
            <div class="flex items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="ml-4 flex items-center md:ml-6">
                        <span class="text-gray-300 mr-4"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></span>
                        <a href="<?php echo BASE_URL; ?>/logout" 
                           class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Cerrar Sesión
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav> 