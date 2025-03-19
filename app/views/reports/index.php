<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema Contable</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Reportes</h1>

            <!-- Filtros -->
            <form id="report-form" class="mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                    <select name="client_id" class="w-full rounded-md border-gray-300">
                        <option value="">Todos los clientes</option>
                        <!-- Agregar opciones de clientes dinámicamente -->
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="start_date" class="w-full rounded-md border-gray-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="end_date" class="w-full rounded-md border-gray-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Comprobante</label>
                    <select name="type" class="w-full rounded-md border-gray-300">
                        <option value="">Todos</option>
                        <option value="I">Ingreso</option>
                        <option value="E">Egreso</option>
                        <option value="P">Pago</option>
                    </select>
                </div>

                <div class="col-span-full flex justify-end space-x-4">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Generar Reporte
                    </button>
                    <button type="button" id="export-excel" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                        Exportar Excel
                    </button>
                    <button type="button" id="export-pdf" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        Exportar PDF
                    </button>
                </div>
            </form>

            <!-- Tabla de Resultados -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UUID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impuestos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Implementar la lógica del frontend
    });
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 