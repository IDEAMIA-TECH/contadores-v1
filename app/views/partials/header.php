<header class="bg-white shadow">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center">
                <a href="<?php echo BASE_URL; ?>/" class="text-xl font-bold text-gray-800">Sistema de Cobranza</a>
                <nav class="ml-8">
                    <ul class="flex space-x-4">
                        <?php if ($_SESSION['role'] === 'contador'): ?>
                            <li><a href="<?php echo BASE_URL; ?>/clients" class="text-gray-600 hover:text-gray-900">Clientes</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>/dashboard" class="text-gray-600 hover:text-gray-900">Dashboard</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/profile" class="text-gray-600 hover:text-gray-900">Perfil</a></li>
                    </ul>
                </nav>
            </div>
            <div class="flex items-center">
                <span class="text-gray-600 mr-4"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="<?php echo BASE_URL; ?>/logout" class="text-red-600 hover:text-red-800">Cerrar Sesión</a>
            </div>
        </div>
    </div>
</header> 