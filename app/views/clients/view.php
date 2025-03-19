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

            <!-- Acciones adicionales -->
            <div class="flex justify-end space-x-4 mt-8">
                <a href="<?php echo BASE_URL; ?>/clients/upload-xml?id=<?php echo $client['id']; ?>" 
                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    Subir XML
                </a>
            </div>
        </div>

        <!-- Agregar después de la sección de carga de archivos y antes del footer -->
        <div class="bg-white rounded-lg shadow-lg p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Descarga de XMLs desde el SAT</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Descarga de Facturas Emitidas -->
                <div class="border rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Facturas Emitidas</h3>
                    <form id="form-emitidas" class="space-y-4">
                        <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                        <input type="hidden" name="tipo" value="emitidas">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" required 
                                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                            <input type="date" name="fecha_fin" required 
                                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Descargar Emitidas
                            </button>
                            <div id="progress-emitidas" class="hidden flex-1">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Descarga de Facturas Recibidas -->
                <div class="border rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Facturas Recibidas</h3>
                    <form id="form-recibidas" class="space-y-4">
                        <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                        <input type="hidden" name="tipo" value="recibidas">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" required 
                                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                            <input type="date" name="fecha_fin" required 
                                   class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <button type="submit" 
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Descargar Recibidas
                            </button>
                            <div id="progress-recibidas" class="hidden flex-1">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-green-600 h-2.5 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Actualizar el script de manejo de descargas -->
    <script>
    // Definir BASE_URL al inicio del script
    const BASE_URL = '<?php echo BASE_URL; ?>';

    document.addEventListener('DOMContentLoaded', function() {
        const formEmitidas = document.getElementById('form-emitidas');
        const formRecibidas = document.getElementById('form-recibidas');
        const progressEmitidas = document.getElementById('progress-emitidas');
        const progressRecibidas = document.getElementById('progress-recibidas');

        async function handleDownload(e, form, progressElement) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const progressBar = progressElement.querySelector('.bg-blue-600, .bg-green-600');
            
            try {
                // Validar fechas
                const fechaInicio = form.querySelector('[name="fecha_inicio"]').value;
                const fechaFin = form.querySelector('[name="fecha_fin"]').value;
                
                if (!fechaInicio || !fechaFin) {
                    alert('Por favor, seleccione las fechas de inicio y fin');
                    return;
                }

                if (new Date(fechaInicio) > new Date(fechaFin)) {
                    alert('La fecha de inicio no puede ser posterior a la fecha final');
                    return;
                }

                submitBtn.disabled = true;
                progressElement.classList.remove('hidden');
                
                const formData = new FormData(form);
                formData.append('csrf_token', '<?php echo $token; ?>');

                console.log('Enviando solicitud a:', `${BASE_URL}/clients/download-sat`);
                console.log('Datos del formulario:', Object.fromEntries(formData));

                const response = await fetch(`${BASE_URL}/clients/download-sat`, {
                    method: 'POST',
                    body: formData
                });

                console.log('Respuesta recibida:', response);

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Error en la descarga');
                }

                // Verificar si es una respuesta JSON (error) o un archivo
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const jsonResponse = await response.json();
                    if (!jsonResponse.success) {
                        throw new Error(jsonResponse.error || 'Error en la descarga');
                    }
                } else {
                    // Es un archivo, proceder con la descarga
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    const fileName = `facturas_${formData.get('tipo')}_${fechaInicio}_${fechaFin}.zip`;
                    
                    a.href = url;
                    a.download = fileName;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }

            } catch (error) {
                console.error('Error:', error);
                alert(error.message || 'Error al descargar los XMLs');
            } finally {
                submitBtn.disabled = false;
                progressElement.classList.add('hidden');
                progressBar.style.width = '0%';
            }
        }

        if (formEmitidas) {
            formEmitidas.addEventListener('submit', (e) => handleDownload(e, formEmitidas, progressEmitidas));
        }
        
        if (formRecibidas) {
            formRecibidas.addEventListener('submit', (e) => handleDownload(e, formRecibidas, progressRecibidas));
        }
    });
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 