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

            <!-- Instrucciones -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Instrucciones</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Ingrese al portal del SAT con su e.firma o Contraseña</li>
                                <li>Seleccione el período de descarga de los XMLs</li>
                                <li>Descargue los archivos</li>
                                <li>Una vez descargados, puede subirlos al sistema usando la opción "Subir XML"</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenedor del iframe -->
            <div class="relative" style="height: 800px;">
                <iframe 
                    src="https://portalcfdi.facturaelectronica.sat.gob.mx/"
                    class="w-full h-full border-0 rounded-lg"
                    id="sat-iframe"
                    sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
                >
                </iframe>
                
                <!-- Mensaje de fallback -->
                <div id="fallback-message" class="hidden absolute inset-0 flex items-center justify-center bg-gray-50 rounded-lg">
                    <div class="text-center p-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No se puede mostrar el portal del SAT en esta ventana</h3>
                        <p class="mt-1 text-sm text-gray-500">Por favor, acceda directamente al portal del SAT</p>
                        <div class="mt-6">
                            <a href="https://portalcfdi.facturaelectronica.sat.gob.mx/" 
                               target="_blank"
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Ir al Portal del SAT
                                <svg class="ml-2 -mr-1 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
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
                                <input type="datetime-local" name="fecha_inicio" required 
                                       class="mt-1 form-input block w-full rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                                <input type="datetime-local" name="fecha_fin" required 
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
        const downloadLinks = document.getElementById('downloadLinks');
        
        try {
            status.classList.remove('hidden');
            statusMessage.innerHTML = `
                <div class="bg-blue-50 text-blue-800 p-4 rounded-md">
                    Iniciando solicitud de descarga...
                </div>
            `;
            
            const response = await fetch('<?php echo BASE_URL; ?>/clients/download-sat-masivo', {
                method: 'POST',
                body: new FormData(form)
            });
            
            const data = await response.json();
            
            if (data.success) {
                statusMessage.innerHTML = `
                    <div class="bg-green-50 text-green-800 p-4 rounded-md">
                        ${data.message}
                        <div class="mt-2 text-sm">
                            ID de Solicitud: ${data.requestId || 'No disponible'}
                        </div>
                    </div>
                `;
                
                // Iniciar verificación periódica
                checkRequestStatus(data.requestId);
            } else {
                statusMessage.innerHTML = `
                    <div class="bg-red-50 text-red-800 p-4 rounded-md">
                        ${data.error}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
            statusMessage.innerHTML = `
                <div class="bg-red-50 text-red-800 p-4 rounded-md">
                    Error al procesar la solicitud
                </div>
            `;
        }
    });

    async function checkRequestStatus(requestId) {
        // Implementar verificación periódica del estado
        // Esta función se llamará cada cierto tiempo para verificar el estado de la solicitud
    }
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 