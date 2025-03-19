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
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client['id']); ?>" 
                                    <?php echo ($filters['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['business_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="start_date" class="w-full rounded-md border-gray-300" 
                           value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="end_date" class="w-full rounded-md border-gray-300"
                           value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Comprobante</label>
                    <select name="type" class="w-full rounded-md border-gray-300">
                        <option value="">Todos</option>
                        <option value="I" <?php echo ($filters['type'] == 'I') ? 'selected' : ''; ?>>Ingreso</option>
                        <option value="E" <?php echo ($filters['type'] == 'E') ? 'selected' : ''; ?>>Egreso</option>
                        <option value="P" <?php echo ($filters['type'] == 'P') ? 'selected' : ''; ?>>Pago</option>
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

            <!-- Después del form y antes de la tabla, agregamos el resumen de impuestos -->
            <?php if (!empty($reportData)): ?>
                <div class="mb-8 bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Resumen de Impuestos</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <?php
                        $resumenIVA = [
                            '16' => ['tasa' => '16%', 'total' => 0, 'label' => 'IVA 16%', 'class' => 'bg-blue-100'],
                            '8' => ['tasa' => '8%', 'total' => 0, 'label' => 'IVA 8%', 'class' => 'bg-green-100'],
                            '0' => ['tasa' => '0%', 'total' => 0, 'label' => 'IVA 0%', 'class' => 'bg-yellow-100'],
                            'Exento' => ['tasa' => 'Exento', 'total' => 0, 'label' => 'IVA Exento', 'class' => 'bg-purple-100'],
                            'Otros' => ['tasa' => 'Otros', 'total' => 0, 'label' => 'Otros', 'class' => 'bg-gray-100']
                        ];

                        // Calcular totales por tipo de IVA
                        foreach ($reportData as $row) {
                            $tasaPorcentaje = (float)$row['tasa_o_cuota'] * 100;
                            $tasaKey = (string)round($tasaPorcentaje); // Convertir a string después de redondear
                            $impuesto = (float)$row['total_impuestos_trasladados'];
                            
                            if ($row['tipo_factor'] === 'Exento') {
                                $resumenIVA['Exento']['total'] += (float)$row['subtotal'];
                            } elseif (isset($resumenIVA[$tasaKey])) {
                                $resumenIVA[$tasaKey]['total'] += $impuesto;
                            } else {
                                $resumenIVA['Otros']['total'] += $impuesto;
                            }
                        }

                        // Mostrar cada tipo de IVA
                        foreach ($resumenIVA as $info): ?>
                            <div class="<?php echo htmlspecialchars($info['class']); ?> p-4 rounded-lg">
                                <h3 class="font-semibold text-gray-700"><?php echo htmlspecialchars($info['label']); ?></h3>
                                <p class="text-2xl font-bold text-gray-900">
                                    $<?php echo number_format($info['total'], 2); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Actualizar la tabla para incluir los nuevos campos -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UUID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tasa o Cuota</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo Factor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impuestos Trasladados</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($reportData)): ?>
                            <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['cliente']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($row['fecha'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['uuid']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        $<?php echo number_format($row['subtotal'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        $<?php echo number_format($row['total'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo ($row['tasa_o_cuota'] * 100) . '%'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['tipo_factor']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        $<?php echo number_format($row['total_impuestos_trasladados'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php 
                                        $tipos = [
                                            'I' => 'Ingreso',
                                            'E' => 'Egreso',
                                            'P' => 'Pago'
                                        ];
                                        echo htmlspecialchars($tipos[$row['tipo_comprobante']] ?? $row['tipo_comprobante']); 
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No se encontraron resultados
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('report-form');
        const exportExcel = document.getElementById('export-excel');
        const exportPdf = document.getElementById('export-pdf');

        // Manejar la búsqueda
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            window.location.href = `${BASE_URL}/reports?${params.toString()}`;
        });

        // Manejar exportación a Excel
        exportExcel.addEventListener('click', function() {
            const formData = new FormData(form);
            formData.append('format', 'excel');
            submitExport(formData);
        });

        // Manejar exportación a PDF
        exportPdf.addEventListener('click', function() {
            const formData = new FormData(form);
            formData.append('format', 'pdf');
            submitExport(formData);
        });

        function submitExport(formData) {
            formData.append('csrf_token', '<?php echo $token; ?>');
            
            fetch(`${BASE_URL}/reports/export`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `reporte.${formData.get('format')}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al exportar el reporte');
            });
        }
    });
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 