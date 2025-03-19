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

            <!-- Actualizar el formulario -->
            <form id="report-form" method="GET" action="<?php echo BASE_URL; ?>/reports" class="mb-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                    <select name="client_id" class="w-full rounded-md border-gray-300">
                        <option value="">Todos los clientes</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client['id']); ?>" 
                                    <?php echo (isset($filters['client_id']) && $filters['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['business_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="start_date" required class="w-full rounded-md border-gray-300" 
                           value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="end_date" required class="w-full rounded-md border-gray-300"
                           value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Comprobante</label>
                    <select name="type[]" multiple class="w-full rounded-md border-gray-300 h-32" id="tipo-comprobante">
                        <option value="">Todos</option>
                        <?php 
                        $tiposComprobante = [
                            'I' => 'Ingreso',
                            'E' => 'Egreso',
                            'P' => 'Pago',
                            'N' => 'Nómina',
                            'T' => 'Traslado',
                            'R' => 'Recepción de pagos',
                            'D' => 'Nota de débito',
                            'C' => 'Nota de crédito'
                        ];
                        
                        $selectedTypes = isset($filters['type']) ? (array)$filters['type'] : [];
                        
                        foreach ($tiposComprobante as $value => $label): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" 
                                    <?php echo in_array($value, $selectedTypes) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Mantén presionado Ctrl (Windows) o Cmd (Mac) para seleccionar múltiples opciones</p>
                </div>

                <div class="col-span-full flex justify-end space-x-4">
                    <input type="hidden" name="search" value="1">
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

            <!-- Solo mostrar resultados si se realizó una búsqueda -->
            <?php if (isset($_GET['search']) && !empty($reportData)): ?>
                <!-- Sección de resumen de impuestos -->
                <div class="mb-8 bg-gray-50 p-6 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Resumen de Impuestos</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <?php
                        // Inicializar el resumen de IVA con totales en 0
                        $resumenIVA = [
                            '16' => ['tasa' => '16%', 'total' => 0, 'base' => 0, 'label' => 'IVA 16%', 'class' => 'bg-blue-100'],
                            '8' => ['tasa' => '8%', 'total' => 0, 'base' => 0, 'label' => 'IVA 8%', 'class' => 'bg-green-100'],
                            '0' => ['tasa' => '0%', 'total' => 0, 'base' => 0, 'label' => 'IVA 0%', 'class' => 'bg-yellow-100'],
                            'Exento' => ['tasa' => 'Exento', 'total' => 0, 'base' => 0, 'label' => 'IVA Exento', 'class' => 'bg-purple-100'],
                            'Otros' => ['tasa' => 'Otros', 'total' => 0, 'base' => 0, 'label' => 'Otros', 'class' => 'bg-gray-100']
                        ];

                        // Calcular totales por tipo de IVA
                        foreach ($reportData as $row) {
                            $tasa = $row['tasa_o_cuota'];
                            $tasaPorcentaje = (string)round($tasa * 100);
                            $subtotal = (float)$row['subtotal'];
                            
                            error_log("Procesando registro - Tasa: $tasa, TasaPorcentaje: $tasaPorcentaje, Subtotal: $subtotal, TipoFactor: {$row['tipo_factor']}");
                            
                            if ($row['tipo_factor'] === 'Exento') {
                                $resumenIVA['Exento']['total'] += 0; // Exento no genera IVA
                                $resumenIVA['Exento']['base'] += $subtotal;
                            } elseif (isset($resumenIVA[$tasaPorcentaje])) {
                                // Calcular el IVA basado en el subtotal y la tasa
                                $ivaCalculado = $subtotal * ($tasa); // tasa ya viene en decimal (0.16)
                                $resumenIVA[$tasaPorcentaje]['total'] += $ivaCalculado;
                                $resumenIVA[$tasaPorcentaje]['base'] += $subtotal;
                                
                                error_log("IVA Calculado para tasa $tasaPorcentaje%: Base: $subtotal, IVA: $ivaCalculado");
                            } else {
                                $resumenIVA['Otros']['total'] += ($subtotal * $tasa);
                                $resumenIVA['Otros']['base'] += $subtotal;
                            }
                        }

                        // Mostrar cada tipo de IVA con información detallada
                        foreach ($resumenIVA as $key => $info): 
                            if ($info['total'] > 0 || $info['base'] > 0): 
                        ?>
                            <div class="<?php echo htmlspecialchars($info['class']); ?> p-4 rounded-lg">
                                <h3 class="font-semibold text-gray-700"><?php echo htmlspecialchars($info['label']); ?></h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600">Base: $<?php echo number_format($info['base'], 2); ?></p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        IVA: $<?php echo number_format($info['total'], 2); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Total: $<?php echo number_format($info['base'] + $info['total'], 2); ?>
                                    </p>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>

                <!-- Tabla de resultados -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emisor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RFC Emisor</th>
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
                                            <?php echo htmlspecialchars($row['emisor_nombre']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($row['emisor_rfc']); ?>
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
                                    <td colspan="11" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No se encontraron resultados
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (isset($_GET['search'])): ?>
                <div class="text-center py-8 text-gray-600">
                    No se encontraron resultados para los filtros seleccionados
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-600">
                    Selecciona los filtros y haz clic en "Generar Reporte" para ver los resultados
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('report-form');
        const exportExcel = document.getElementById('export-excel');
        const exportPdf = document.getElementById('export-pdf');

        // Validar fechas antes de enviar el formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const startDate = form.querySelector('[name="start_date"]').value;
            const endDate = form.querySelector('[name="end_date"]').value;

            if (!startDate || !endDate) {
                alert('Las fechas son obligatorias');
                return;
            }

            if (startDate > endDate) {
                alert('La fecha de inicio no puede ser posterior a la fecha final');
                return;
            }

            this.submit();
        });

        // Función común para exportar
        async function exportReport(format) {
            const startDate = form.querySelector('[name="start_date"]').value;
            const endDate = form.querySelector('[name="end_date"]').value;

            if (!startDate || !endDate) {
                alert('Las fechas son obligatorias para exportar');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('format', format);
                formData.append('csrf_token', '<?php echo $token; ?>');
                formData.append('start_date', startDate);
                formData.append('end_date', endDate);
                formData.append('client_id', form.querySelector('[name="client_id"]').value);
                formData.append('type', form.querySelector('[name="type"]').value);

                const response = await fetch(`${BASE_URL}/reports/export`, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }

                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const jsonResponse = await response.json();
                    throw new Error(jsonResponse.message || 'Error al exportar');
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `reporte_${format === 'excel' ? 'xlsx' : 'pdf'}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'Error al exportar el reporte');
            }
        }

        exportExcel.addEventListener('click', () => exportReport('excel'));
        exportPdf.addEventListener('click', () => exportReport('pdf'));
    });
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 