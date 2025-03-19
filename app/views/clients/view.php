<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Cliente - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <?php echo htmlspecialchars($client['business_name']); ?>
                </h1>
                <div class="space-x-4">
                    <a href="<?php echo BASE_URL; ?>/clients/edit/<?php echo $client['id']; ?>" 
                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Editar
                    </a>
                    <a href="<?php echo BASE_URL; ?>/clients" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Volver
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Información Fiscal -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Fiscal</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">RFC</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['rfc']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Razón Social</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['business_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nombre Legal</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['legal_name'] ?? 'No especificado'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Régimen Fiscal</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['fiscal_regime']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Dirección -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Dirección</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Calle</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['street']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Números</p>
                        <p class="mt-1">
                            Ext: <?php echo htmlspecialchars($client['exterior_number']); ?>
                            <?php if (!empty($client['interior_number'])): ?>
                                , Int: <?php echo htmlspecialchars($client['interior_number']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Colonia</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['neighborhood']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Ciudad/Municipio</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['city']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Estado</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['state']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Código Postal</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['zip_code']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Contacto Principal -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Contacto Principal</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Email</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['email']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Teléfono</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['phone']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Contacto Adicional -->
            <?php if ($contact): ?>
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Contacto Adicional</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nombre</p>
                        <p class="mt-1"><?php echo htmlspecialchars($contact['contact_name'] ?? 'No especificado'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Email</p>
                        <p class="mt-1"><?php echo htmlspecialchars($contact['contact_email'] ?? 'No especificado'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Teléfono</p>
                        <p class="mt-1"><?php echo htmlspecialchars($contact['contact_phone'] ?? 'No especificado'); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Documentos -->
            <?php if (!empty($documents)): ?>
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Documentos</h2>
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($documents as $doc): ?>
                        <li>
                            <div class="px-4 py-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($doc['type']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Subido el: <?php echo date('d/m/Y', strtotime($doc['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <a href="<?php echo BASE_URL; ?>/uploads/<?php echo $doc['file_path']; ?>" 
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-900">
                                        Ver documento
                                    </a>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Credenciales SAT -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Credenciales SAT</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Certificado -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">Certificado (.cer)</p>
                        <div class="mt-1 flex items-center">
                            <?php if (!empty($client['cer_path'])): ?>
                                <span class="text-sm text-green-600 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Archivo cargado
                                    <a href="<?php echo BASE_URL . '/uploads/' . htmlspecialchars($client['cer_path']); ?>" 
                                       class="ml-2 text-blue-600 hover:text-blue-800"
                                       target="_blank">
                                        Ver archivo
                                    </a>
                                </span>
                            <?php else: ?>
                                <span class="text-sm text-gray-500">No se ha cargado el certificado</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Llave Privada -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">Llave Privada (.key)</p>
                        <div class="mt-1 flex items-center">
                            <?php if (!empty($client['key_path'])): ?>
                                <span class="text-sm text-green-600 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Archivo cargado
                                    <span class="ml-2 text-gray-600">(Archivo protegido)</span>
                                </span>
                            <?php else: ?>
                                <span class="text-sm text-gray-500">No se ha cargado la llave privada</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contraseña FIEL -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">Contraseña FIEL</p>
                        <div class="mt-1">
                            <?php if (!empty($client['key_password'])): ?>
                                <span class="text-sm text-green-600 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Contraseña configurada
                                </span>
                            <?php else: ?>
                                <span class="text-sm text-gray-500">No se ha configurado la contraseña</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Estado de la configuración -->
                    <div>
                        <p class="text-sm font-medium text-gray-500">Estado de Configuración</p>
                        <div class="mt-1">
                            <?php 
                            $isConfigured = !empty($client['cer_path']) && 
                                           !empty($client['key_path']) && 
                                           !empty($client['key_password']);
                            ?>
                            <?php if ($isConfigured): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Configuración completa
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    Configuración incompleta
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Botón para editar credenciales -->
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>/clients/edit/<?php echo $client['id']; ?>#credenciales-sat" 
                       class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar credenciales
                    </a>
                </div>
            </div>

            <!-- Después de la sección de credenciales SAT -->
            <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Gestión de XMLs</h2>
                
                <!-- Formulario de carga -->
                <form id="xml-upload-form" class="mb-6" enctype="multipart/form-data">
                    <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="file" 
                                   name="xml_files[]" 
                                   multiple 
                                   accept=".xml"
                                   class="w-full text-sm text-gray-500
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-full file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-50 file:text-blue-700
                                          hover:file:bg-blue-100">
                        </div>
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12"/>
                            </svg>
                            Procesar XMLs
                        </button>
                    </div>
                </form>

                <!-- Resumen de Facturas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-blue-700">Facturas Emitidas</h3>
                        <p class="text-3xl font-bold text-blue-900" id="count-emitidas">0</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-green-700">Facturas Recibidas</h3>
                        <p class="text-3xl font-bold text-green-900" id="count-recibidas">0</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-purple-700">Total Facturado</h3>
                        <p class="text-3xl font-bold text-purple-900" id="total-amount">$0.00</p>
                    </div>
                </div>

                <!-- Tabla de Facturas -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UUID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Emisor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receptor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="facturas-list">
                            <!-- Se llenará dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Acciones adicionales -->
            <div class="flex justify-end space-x-4 mt-8">
                <a href="<?php echo BASE_URL; ?>/clients/upload-xml?id=<?php echo $client['id']; ?>" 
                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    Subir XML
                </a>
            </div>
        </div>
    </div>

    <script>
    // Definir BASE_URL al inicio del script
    const BASE_URL = '<?php echo BASE_URL; ?>';

    document.addEventListener('DOMContentLoaded', function() {
        const uploadForm = document.getElementById('xml-upload-form');
        
        async function handleUpload(e) {
            e.preventDefault();
            const submitBtn = uploadForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            
            try {
                const formData = new FormData(uploadForm);
                
                const response = await fetch(`${BASE_URL}/clients/process-xmls`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    alert(`Archivos procesados: ${result.processed}\n${
                        result.errors.length ? 'Errores: ' + result.errors.join('\n') : ''
                    }`);
                    loadFacturas();
                } else {
                    throw new Error(result.error || 'Error al procesar los archivos');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'Error al procesar los archivos');
            } finally {
                submitBtn.disabled = false;
                uploadForm.reset();
            }
        }

        async function loadFacturas() {
            try {
                const response = await fetch(
                    `${BASE_URL}/clients/get-facturas/<?php echo $client['id']; ?>`
                );
                const data = await response.json();
                
                // Actualizar contadores
                document.getElementById('count-emitidas').textContent = 
                    data.summary.emitidas || 0;
                document.getElementById('count-recibidas').textContent = 
                    data.summary.recibidas || 0;
                document.getElementById('total-amount').textContent = 
                    `$${(data.summary.total_amount || 0).toLocaleString('es-MX', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })}`;
                
                // Actualizar tabla
                const tbody = document.getElementById('facturas-list');
                tbody.innerHTML = data.facturas.map(factura => `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${factura.uuid}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ${new Date(factura.fecha).toLocaleString()}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${factura.emisor_nombre}<br>
                            <span class="text-xs text-gray-500">${factura.emisor_rfc}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${factura.receptor_nombre}<br>
                            <span class="text-xs text-gray-500">${factura.receptor_rfc}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            $${parseFloat(factura.total).toLocaleString('es-MX', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            })}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                ${factura.tipo === 'emitidas' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}">
                                ${factura.tipo}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="${BASE_URL}/uploads/xmls/${factura.file_name}" 
                               class="text-blue-600 hover:text-blue-900"
                               target="_blank">
                                Ver XML
                            </a>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error('Error al cargar facturas:', error);
            }
        }

        if (uploadForm) {
            uploadForm.addEventListener('submit', handleUpload);
        }

        // Cargar facturas iniciales
        loadFacturas();
    });
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 