<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal SAT - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Portal SAT</h1>
                <a href="<?php echo BASE_URL; ?>/clients/view/<?php echo $client_id; ?>" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    Volver
                </a>
            </div>

         
         

            <!-- Botón para subir XMLs -->
            <div class="mt-6 flex justify-end">
                <a href="<?php echo BASE_URL; ?>/clients/upload-xml?id=<?php echo $client_id; ?>" 
                   class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-md inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12"/>
                    </svg>
                    Subir XMLs Descargados
                </a>
            </div>

            <!-- Agregar sección de descarga masiva -->
            <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Descarga Masiva de CFDIs</h2>
                
                <?php if (empty($client['cer_path']) || empty($client['key_path'])): ?>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Para usar la descarga masiva, primero debe configurar la e.firma (CER y KEY) del cliente.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <form id="downloadForm" class="space-y-6">
                        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">

                        <!-- Tipo de Solicitud -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Tipo de Solicitud</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center space-x-2">
                                    <input type="radio" name="request_type" value="metadata" checked 
                                           class="form-radio text-blue-600">
                                    <span>Metadata (Recomendado)</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="radio" name="request_type" value="cfdi" 
                                           class="form-radio text-blue-600">
                                    <span>CFDI (XML Completo)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Tipo de Comprobantes -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Tipo de Comprobantes</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center space-x-2">
                                    <input type="radio" name="document_type" value="issued" checked 
                                           class="form-radio text-blue-600">
                                    <span>Emitidos</span>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <input type="radio" name="document_type" value="received" 
                                           class="form-radio text-blue-600">
                                    <span>Recibidos</span>
                                </label>
                            </div>
                        </div>

                        <!-- Periodo -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                                <input type="datetime-local" 
                                       name="fecha_inicio" 
                                       required 
                                       pattern="\d{4}-\d{2}-\d{2}T\d{2}:\d{2}"
                                       class="mt-1 form-input block w-full rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                                <input type="datetime-local" 
                                name="fecha_fin" 
                                required 
                                pattern="\d{4}-\d{2}-\d{2}T\d{2}:\d{2}"
                                       class="mt-1 form-input block w-full rounded-md">
                            </div>
                        </div>

                        <!-- Información de Límites -->
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 text-sm">
                            <ul class="list-disc list-inside space-y-1 text-blue-700">
                                <li>Podrás recuperar hasta 200,000 CFDIs por solicitud</li>
                                <li>Para metadata, el límite es de 1,000,000 de registros</li>
                                <li>El periodo máximo recomendado es de 72 horas</li>
                            </ul>
                        </div>

                        <div class="flex justify-end space-x-4">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md inline-flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Iniciar Descarga
                            </button>
                        </div>
                    </form>

                    <!-- Estado de la Solicitud -->
                    <div id="requestStatus" class="mt-6 hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Estado de la Solicitud</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="space-y-4">
                                <div id="statusMessage"></div>
                                <div id="progressBar" class="hidden">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div id="downloadLinks" class="hidden space-y-2"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const iframe = document.getElementById('sat-iframe');
        const fallbackMessage = document.getElementById('fallback-message');

        function showFallbackMessage() {
            iframe.style.display = 'none';
            fallbackMessage.classList.remove('hidden');
        }

        iframe.addEventListener('load', function() {
            try {
                const iframeContent = iframe.contentWindow.document;
                console.log('Iframe cargado correctamente');
            } catch (e) {
                console.log('No se puede acceder al contenido del iframe');
                showFallbackMessage();
            }
        });

        iframe.addEventListener('error', showFallbackMessage);

        setTimeout(() => {
            try {
                if (!iframe.contentWindow.document) {
                    showFallbackMessage();
                }
            } catch (e) {
                showFallbackMessage();
            }
        }, 5000);
    });

    document.getElementById('downloadForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const status = document.getElementById('requestStatus');
        const statusMessage = document.getElementById('statusMessage');
        const progressBar = document.getElementById('progressBar');
        
        try {
            status.classList.remove('hidden');
            statusMessage.innerHTML = `
                <div class="bg-blue-50 text-blue-800 p-4 rounded-md">
                    Iniciando solicitud de descarga...
                </div>
            `;
            
            const formData = new FormData(form);
            const response = await fetch('<?php echo BASE_URL; ?>/clients/download-sat-masivo', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                statusMessage.innerHTML = `
                    <div class="bg-green-50 text-green-800 p-4 rounded-md">
                        ${data.message}
                        <div class="mt-2 text-sm">
                            ID de Solicitud: ${data.requestId}
                        </div>
                    </div>
                `;
                
                // Iniciar verificación periódica
                checkStatus(data.requestId);
            } else {
                throw new Error(data.error || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error:', error);
            statusMessage.innerHTML = `
                <div class="bg-red-50 text-red-800 p-4 rounded-md">
                    ${error.message || 'Error al procesar la solicitud'}
                </div>
            `;
        }
    });

    async function checkStatus(requestId) {
        const statusMessage = document.getElementById('statusMessage');
        const progressBar = document.getElementById('progressBar');
        const downloadLinks = document.getElementById('downloadLinks');
        
        try {
            const response = await fetch('<?php echo BASE_URL; ?>/clients/check-download-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ requestId })
            });

            const data = await response.json();

            if (data.success) {
                if (data.status === 'PENDING') {
                    statusMessage.innerHTML = `
                        <div class="bg-blue-50 text-blue-800 p-4 rounded-md">
                            ${data.message}
                        </div>
                    `;
                    // Verificar nuevamente en 30 segundos
                    setTimeout(() => checkStatus(requestId), 30000);
                } else if (data.status === 'READY') {
                    statusMessage.innerHTML = `
                        <div class="bg-green-50 text-green-800 p-4 rounded-md">
                            ${data.message}
                            <div class="mt-2 text-sm">
                                Paquetes disponibles: ${data.packagesCount}
                            </div>
                        </div>
                    `;
                    downloadLinks.classList.remove('hidden');
                    // Mostrar enlaces de descarga
                    showDownloadLinks(requestId, data.packagesCount);
                }
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error:', error);
            statusMessage.innerHTML = `
                <div class="bg-red-50 text-red-800 p-4 rounded-md">
                    Error al verificar el estado: ${error.message}
                </div>
            `;
        }
    }

    function showDownloadLinks(requestId, packagesCount) {
        const downloadLinks = document.getElementById('downloadLinks');
        downloadLinks.innerHTML = '';
        
        for (let i = 1; i <= packagesCount; i++) {
            const link = document.createElement('a');
            link.href = `<?php echo BASE_URL; ?>/clients/download-package/${requestId}/${i}`;
            link.className = 'bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded inline-block mb-2 mr-2';
            link.textContent = `Descargar Paquete ${i}`;
            downloadLinks.appendChild(link);
        }
    }
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 