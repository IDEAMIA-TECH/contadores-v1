<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?php echo BASE_URL; ?>/dashboard" class="text-xl font-bold text-blue-600">
                        IDEAMIA Tech
                    </a>
                </div>
                
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="<?php echo BASE_URL; ?>/dashboard" 
                       class="<?php echo $_SERVER['REQUEST_URI'] === BASE_URL . '/dashboard' ? 'border-blue-500' : 'border-transparent'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium text-gray-900">
                        Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>/client" 
                       class="<?php echo strpos($_SERVER['REQUEST_URI'], '/client') !== false ? 'border-blue-500' : 'border-transparent'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium text-gray-900">
                        Clientes
                    </a>
                    <a href="<?php echo BASE_URL; ?>/report" 
                       class="<?php echo strpos($_SERVER['REQUEST_URI'], '/report') !== false ? 'border-blue-500' : 'border-transparent'; ?> 
                              inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium text-gray-900">
                        Reportes
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
                            <a href="<?php echo BASE_URL; ?>/logout" 
                               class="text-sm text-red-600 hover:text-red-800">
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav> 