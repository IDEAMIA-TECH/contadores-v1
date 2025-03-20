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
                <!--<div id="fallback-message" class="hidden absolute inset-0 flex items-center justify-center bg-gray-50 rounded-lg">
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
                </div>-->
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
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 