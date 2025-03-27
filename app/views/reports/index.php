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
                        // Consulta para obtener el resumen de IVAs usando las nuevas tablas
                        $query = "
                            SELECT 
                                CASE 
                                    WHEN i.tasa = 0.16 THEN '16'
                                    WHEN i.tasa = 0.08 THEN '8'
                                    WHEN i.tasa = 0 THEN '0'
                                    ELSE 'Otros'
                                END as tasa_grupo,
                                SUM(i.base) as base_total,
                                SUM(i.importe) as iva_total
                            FROM facturas f
                            JOIN ivas_factura i ON f.id = i.factura_id
                            WHERE f.client_id = :client_id
                            AND f.fecha BETWEEN :start_date AND :end_date
                            GROUP BY 
                                CASE 
                                    WHEN i.tasa = 0.16 THEN '16'
                                    WHEN i.tasa = 0.08 THEN '8'
                                    WHEN i.tasa = 0 THEN '0'
                                    ELSE 'Otros'
                                END
                        ";

                        $stmt = $db->prepare($query);
                        $stmt->execute([
                            ':client_id' => $filters['client_id'] ?? null,
                            ':start_date' => $filters['start_date'] . ' 00:00:00',
                            ':end_date' => $filters['end_date'] . ' 23:59:59'
                        ]);
                        $ivaResumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Definir los tipos de IVA y sus clases CSS
                        $tiposIVA = [
                            '16' => ['label' => 'IVA 16%', 'class' => 'bg-blue-100'],
                            '8' => ['label' => 'IVA 8%', 'class' => 'bg-green-100'],
                            '0' => ['label' => 'IVA 0%', 'class' => 'bg-yellow-100'],
                            'Otros' => ['label' => 'Otros', 'class' => 'bg-gray-100']
                        ];

                        foreach ($ivaResumen as $iva): 
                            $tipo = $tiposIVA[$iva['tasa_grupo']];
                        ?>
                            <div class="<?php echo $tipo['class']; ?> p-4 rounded-lg">
                                <h3 class="font-semibold text-gray-700"><?php echo $tipo['label']; ?></h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600">Base: $<?php echo number_format($iva['base_total'], 2); ?></p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        IVA: $<?php echo number_format($iva['iva_total'], 2); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Total: $<?php echo number_format($iva['base_total'] + $iva['iva_total'], 2); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tabla de resultados -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UUID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emisor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RFC Emisor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receptor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RFC Receptor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IVA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // Consulta para obtener los detalles de las facturas
                            $query = "
                                SELECT 
                                    cx.fecha,
                                    cx.uuid,
                                    cx.emisor_nombre,
                                    cx.emisor_rfc,
                                    cx.receptor_nombre,
                                    cx.receptor_rfc,
                                    cx.subtotal,
                                    f.total_iva,
                                    cx.total,
                                    cx.tipo_comprobante
                                FROM client_xmls cx
                                JOIN facturas f ON cx.uuid = f.uuid
                                WHERE cx.client_id = :client_id
                                AND cx.fecha BETWEEN :start_date AND :end_date
                                ORDER BY cx.fecha DESC
                            ";

                            $stmt = $db->prepare($query);
                            $stmt->execute([
                                ':client_id' => $filters['client_id'] ?? null,
                                ':start_date' => $filters['start_date'] . ' 00:00:00',
                                ':end_date' => $filters['end_date'] . ' 23:59:59'
                            ]);
                            $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (!empty($facturas)): 
                                foreach ($facturas as $factura): 
                            ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($factura['fecha'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($factura['uuid']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($factura['emisor_nombre']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($factura['emisor_rfc']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($factura['receptor_nombre']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($factura['receptor_rfc']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        $<?php echo number_format($factura['subtotal'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        $<?php echo number_format($factura['total_iva'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        $<?php echo number_format($factura['total'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php 
                                        $tipos = [
                                            'I' => 'Ingreso',
                                            'E' => 'Egreso',
                                            'P' => 'Pago',
                                            'N' => 'Nómina',
                                            'T' => 'Traslado'
                                        ];
                                        echo htmlspecialchars($tipos[$factura['tipo_comprobante']] ?? $factura['tipo_comprobante']); 
                                        ?>
                                    </td>
                                </tr>
                            <?php 
                                endforeach; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">
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
    // Definir BASE_URL y CSRF_TOKEN al inicio del script
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token']; ?>';
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('report-form');
        const exportExcel = document.getElementById('export-excel');
        const exportPdf = document.getElementById('export-pdf');
        const tipoComprobanteSelect = document.getElementById('tipo-comprobante');

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
            const clientId = form.querySelector('[name="client_id"]').value;

            if (!startDate || !endDate) {
                alert('Las fechas son obligatorias para exportar');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('format', format);
                formData.append('csrf_token', CSRF_TOKEN);
                formData.append('start_date', startDate);
                formData.append('end_date', endDate);
                formData.append('client_id', clientId);

                // Manejar tipos de comprobante seleccionados
                const selectedTypes = Array.from(tipoComprobanteSelect.selectedOptions).map(option => option.value);
                selectedTypes.forEach(type => {
                    formData.append('type[]', type);
                });

                console.log('Enviando solicitud a:', `${BASE_URL}/reports/export`);
                const response = await fetch(`${BASE_URL}/reports/export`, {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Error al exportar');
                }

                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const jsonResponse = await response.json();
                    throw new Error(jsonResponse.error || 'Error al exportar');
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `reporte_${format}_${new Date().toISOString().split('T')[0]}.${format === 'excel' ? 'xlsx' : 'pdf'}`;
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