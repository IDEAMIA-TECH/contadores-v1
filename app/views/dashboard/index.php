<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Dashboard</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Estadísticas -->
                <div class="bg-blue-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-800">Total Clientes</h3>
                    <p class="text-3xl font-bold text-blue-600">0</p>
                </div>
                
                <div class="bg-green-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-green-800">XMLs Procesados</h3>
                    <p class="text-3xl font-bold text-green-600">0</p>
                </div>
                
                <div class="bg-purple-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-purple-800">Reportes Generados</h3>
                    <p class="text-3xl font-bold text-purple-600">0</p>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="mt-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Acciones Rápidas</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="<?php echo BASE_URL; ?>/clients/create" 
                       class="bg-blue-500 hover:bg-blue-600 text-white rounded-lg p-4 text-center">
                        Agregar Cliente
                    </a>
                    <a href="<?php echo BASE_URL; ?>/clients" 
                       class="bg-green-500 hover:bg-green-600 text-white rounded-lg p-4 text-center">
                        Ver Clientes
                    </a>
                    <a href="<?php echo BASE_URL; ?>/reports" 
                       class="bg-purple-500 hover:bg-purple-600 text-white rounded-lg p-4 text-center">
                        Generar Reporte
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 