<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir XML - <?php echo htmlspecialchars($client['business_name']); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    Subir XML - <?php echo htmlspecialchars($client['business_name']); ?>
                </h1>
                <a href="<?php echo BASE_URL; ?>/clients/view/<?php echo $client['id']; ?>" 
                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    Volver
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Área de Drop -->
            <div id="drop-area" class="border-2 border-dashed border-gray-300 rounded-lg p-8 mb-6 text-center transition-all duration-300">
                <p class="text-gray-600 mb-4">Arrastra y suelta hasta 500 archivos XML aquí o</p>
                <label for="xml_files" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md cursor-pointer inline-block mb-4">
                    Seleccionar archivos
                </label>
                <p class="text-sm text-gray-500">Máximo 500 archivos XML</p>
                <div id="file-list" class="mt-4 text-left max-h-60 overflow-y-auto"></div>
            </div>

            <form id="uploadForm" action="<?= BASE_URL ?>/clients/upload-xml" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?= Security::getCSRFToken() ?>">
                <input type="hidden" name="client_id" id="client_id" value="<?= $clientId ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="xml_files">
                        Seleccionar archivos XML
                    </label>
                    <input type="file" 
                           name="xml_files[]" 
                           id="xml_files" 
                           accept=".xml"
                           multiple
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div id="progressContainer" class="hidden mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                </div>

                <button type="submit" 
                        id="submitBtn"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Subir archivos
                </button>
            </form>
        </div>
    </div>

    <style>
    #drop-area.dragover {
        @apply bg-blue-50 border-blue-500;
    }

    .file-item {
        @apply flex justify-between items-center p-2 my-1 bg-gray-50 rounded;
    }

    .remove-file {
        @apply text-red-500 hover:text-red-700 cursor-pointer font-bold text-xl;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('xml_files');
        const fileList = document.getElementById('file-list');
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('uploadForm');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const MAX_FILES = 500;
        let files = [];

        // Agregar el HTML de la barra de progreso si no existe
        if (!progressContainer) {
            const progressHTML = `
                <div id="progressContainer" class="hidden mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                        <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            `;
            form.insertAdjacentHTML('beforeend', progressHTML);
        }

        // Prevenir comportamiento por defecto del drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Efectos visuales durante el drag
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });

        // Manejar archivos soltados
        dropArea.addEventListener('drop', handleDrop, false);
        fileInput.addEventListener('change', handleFiles);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            dropArea.classList.add('dragover');
        }

        function unhighlight(e) {
            dropArea.classList.remove('dragover');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const newFiles = [...dt.files];
            handleFileSelection(newFiles);
        }

        function handleFiles(e) {
            const newFiles = [...e.target.files];
            handleFileSelection(newFiles);
        }

        function handleFileSelection(newFiles) {
            // Filtrar solo archivos XML
            const xmlFiles = newFiles.filter(file => file.name.toLowerCase().endsWith('.xml'));
            
            // Verificar límite de archivos
            if (files.length + xmlFiles.length > MAX_FILES) {
                alert(`Solo se permiten ${MAX_FILES} archivos XML`);
                return;
            }

            // Validar tamaño de archivos
            if (!validateFiles(xmlFiles)) {
                return;
            }

            // Agregar nuevos archivos
            files = [...files, ...xmlFiles];
            updateFileList();
            updateSubmitButton();
        }

        function updateFileList() {
            fileList.innerHTML = '';
            files.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'file-item';
                div.innerHTML = `
                    <span>${file.name}</span>
                    <span class="remove-file" data-index="${index}">&times;</span>
                `;
                fileList.appendChild(div);
            });

            // Actualizar el input de archivos
            const dt = new DataTransfer();
            files.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }

        function updateSubmitButton() {
            submitBtn.disabled = files.length === 0;
        }

        // Remover archivos
        fileList.addEventListener('click', e => {
            if (e.target.classList.contains('remove-file')) {
                const index = parseInt(e.target.dataset.index);
                files.splice(index, 1);
                updateFileList();
                updateSubmitButton();
            }
        });

        // Agregar función de logging
        function logXmlData(message, data) {
            console.log('%c XML Data Log ', 'background: #0047BA; color: white; padding: 2px 5px; border-radius: 3px;', 
                '\n', message, '\n', data);
        }

        // Modificar la función processXmlIvas para extraer todos los datos necesarios
        function processXmlIvas(xmlString) {
            try {
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlString, "text/xml");
                
                // Obtener el nodo principal
                const comprobante = xmlDoc.getElementsByTagName('cfdi:Comprobante')[0];
                const tfd = xmlDoc.getElementsByTagName('tfd:TimbreFiscalDigital')[0];
                
                // Extraer datos básicos del comprobante
                const datosComprobante = {
                    serie: comprobante.getAttribute('Serie') || '',
                    folio: comprobante.getAttribute('Folio') || '',
                    fecha: comprobante.getAttribute('Fecha'),
                    fecha_timbrado: tfd ? tfd.getAttribute('FechaTimbrado') : '',
                    subtotal: parseFloat(comprobante.getAttribute('SubTotal')),
                    total: parseFloat(comprobante.getAttribute('Total')),
                    tipo_comprobante: comprobante.getAttribute('TipoDeComprobante'),
                    forma_pago: comprobante.getAttribute('FormaPago'),
                    metodo_pago: comprobante.getAttribute('MetodoPago'),
                    moneda: comprobante.getAttribute('Moneda'),
                    lugar_expedicion: comprobante.getAttribute('LugarExpedicion')
                };

                // Extraer datos del emisor
                const emisor = xmlDoc.getElementsByTagName('cfdi:Emisor')[0];
                const datosEmisor = {
                    rfc: emisor.getAttribute('Rfc'),
                    nombre: emisor.getAttribute('Nombre'),
                    regimen_fiscal: emisor.getAttribute('RegimenFiscal')
                };

                // Extraer datos del receptor
                const receptor = xmlDoc.getElementsByTagName('cfdi:Receptor')[0];
                const datosReceptor = {
                    rfc: receptor.getAttribute('Rfc'),
                    nombre: receptor.getAttribute('Nombre'),
                    regimen_fiscal: receptor.getAttribute('RegimenFiscalReceptor'),
                    domicilio_fiscal: receptor.getAttribute('DomicilioFiscalReceptor'),
                    uso_cfdi: receptor.getAttribute('UsoCFDI')
                };

                // Procesar impuestos
                let totalImpuestosTrasladados = 0;
                let impuestoGeneral = '';
                let tasaGeneral = 0;
                let tipoFactorGeneral = '';

                const impuestos = xmlDoc.getElementsByTagName('cfdi:Impuestos');
                if (impuestos.length > 0) {
                    totalImpuestosTrasladados = parseFloat(impuestos[0].getAttribute('TotalImpuestosTrasladados') || '0');
                    
                    const traslados = impuestos[0].getElementsByTagName('cfdi:Traslado');
                    if (traslados.length > 0) {
                        impuestoGeneral = traslados[0].getAttribute('Impuesto');
                        tasaGeneral = parseFloat(traslados[0].getAttribute('TasaOCuota'));
                        tipoFactorGeneral = traslados[0].getAttribute('TipoFactor');
                    }
                }

                // Obtener UUID
                const uuid = tfd ? tfd.getAttribute('UUID') : '';

                // Asegurarnos de que los valores numéricos sean números válidos
                const safeParseFloat = (value) => {
                    const parsed = parseFloat(value);
                    return isNaN(parsed) ? 0 : parsed;
                };

                // Crear objeto con la estructura exacta que espera el servidor
                const xmlData = {
                    client_id: document.getElementById('client_id').value,
                    xml_path: '', // Se llenará en el servidor
                    uuid: uuid,
                    serie: datosComprobante.serie,
                    folio: datosComprobante.folio,
                    fecha: datosComprobante.fecha,
                    fecha_timbrado: datosComprobante.fecha_timbrado,
                    subtotal: safeParseFloat(datosComprobante.subtotal),
                    total: safeParseFloat(datosComprobante.total),
                    tipo_comprobante: datosComprobante.tipo_comprobante,
                    forma_pago: datosComprobante.forma_pago,
                    metodo_pago: datosComprobante.metodo_pago,
                    moneda: datosComprobante.moneda,
                    lugar_expedicion: datosComprobante.lugar_expedicion,
                    emisor_rfc: datosEmisor.rfc,
                    emisor_nombre: datosEmisor.nombre,
                    emisor_regimen_fiscal: datosEmisor.regimen_fiscal,
                    receptor_rfc: datosReceptor.rfc,
                    receptor_nombre: datosReceptor.nombre,
                    receptor_regimen_fiscal: datosReceptor.regimen_fiscal,
                    receptor_domicilio_fiscal: datosReceptor.domicilio_fiscal,
                    receptor_uso_cfdi: datosReceptor.uso_cfdi,
                    total_impuestos_trasladados: safeParseFloat(totalImpuestosTrasladados),
                    impuesto: impuestoGeneral,
                    tasa_o_cuota: safeParseFloat(tasaGeneral),
                    tipo_factor: tipoFactorGeneral
                };

                logXmlData('Datos extraídos del XML', xmlData);
                return xmlData;

            } catch (error) {
                logXmlData('ERROR procesando XML', {
                    message: error.message,
                    stack: error.stack
                });
                throw error;
            }
        }

        // Modificar el manejador del formulario
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const files = document.querySelector('input[type="file"]').files;
                
                logXmlData('Iniciando proceso de subida', {
                    numeroArchivos: files.length,
                    nombresArchivos: Array.from(files).map(f => f.name)
                });

                if (files.length === 0) {
                    alert('Por favor, seleccione al menos un archivo XML');
                    return;
                }

                submitBtn.disabled = true;
                progressContainer.classList.remove('hidden');

                const formData = new FormData();
                const csrfToken = document.getElementById('csrf_token').value;
                const clientId = document.getElementById('client_id').value;
                
                formData.append('csrf_token', csrfToken);
                formData.append('client_id', clientId);

                // Agregar cada archivo al FormData
                Array.from(files).forEach((file, index) => {
                    formData.append(`xml_files[]`, file);
                    logXmlData(`Agregando archivo ${index + 1}`, {
                        nombre: file.name,
                        tamaño: file.size
                    });
                });

                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const result = await response.json();
                
                if (result.success) {
                    alert('Archivos procesados correctamente');
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                    }
                } else {
                    throw new Error(result.message || 'Error al procesar los archivos');
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Error al subir los archivos: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                progressContainer.classList.add('hidden');
                progressBar.style.width = '0%';
            }
        });

        // Función para mostrar el progreso
        function updateProgress(percent) {
            progressBar.style.width = `${percent}%`;
            progressBar.textContent = `${Math.round(percent)}%`;
        }

        // Agregar validación de tamaño de archivo
        function validateFiles(fileList) {
            const maxSize = 50 * 1024 * 1024; // 50MB
            const invalidFiles = [];
            
            for (let file of fileList) {
                if (file.size > maxSize) {
                    invalidFiles.push(`${file.name} (${(file.size / 1024 / 1024).toFixed(2)}MB)`);
                }
            }
            
            if (invalidFiles.length > 0) {
                alert(`Los siguientes archivos son demasiado grandes (máximo 50MB):\n${invalidFiles.join('\n')}`);
                return false;
            }
            
            return true;
        }

        // Función auxiliar para leer archivos
        function readFileAsync(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = () => reject(new Error(`Error al leer el archivo ${file.name}`));
                reader.readAsText(file);
            });
        }

        // Modificar la función displayIvasInfo para usar la estructura correcta de datos
        function displayIvasInfo(fileName, xmlData) {
            const ivasInfo = document.createElement('div');
            ivasInfo.className = 'mt-4 p-4 bg-gray-50 rounded';
            
            let ivasHtml = `
                <h3 class="font-bold text-gray-700">Información del XML: ${fileName}</h3>
                <div class="mt-2 space-y-2">
                    <div class="text-sm text-gray-600">
                        <strong>UUID:</strong> ${xmlData.uuid}<br>
                        <strong>Serie-Folio:</strong> ${xmlData.serie}-${xmlData.folio}<br>
                        <strong>Emisor:</strong> ${xmlData.emisor_nombre} (${xmlData.emisor_rfc})<br>
                        <strong>Receptor:</strong> ${xmlData.receptor_nombre} (${xmlData.receptor_rfc})<br>
                        <strong>Fecha:</strong> ${xmlData.fecha}<br>
                        <strong>Total:</strong> $${xmlData.total.toFixed(2)}
                    </div>
                    
                    <div class="mt-3">
                        <h4 class="font-semibold text-gray-700">Información de Impuestos:</h4>
                        <ul class="mt-1 space-y-1">
                            <li class="text-sm text-gray-600">
                                <strong>Total Impuestos Trasladados:</strong> $${xmlData.total_impuestos_trasladados.toFixed(2)}
                            </li>
            `;

            // Agregar información del impuesto si existe
            if (xmlData.impuesto) {
                ivasHtml += `
                    <li class="text-sm text-gray-600">
                        <strong>Impuesto:</strong> ${xmlData.impuesto === '002' ? 'IVA' : xmlData.impuesto}
                        <strong>Tasa:</strong> ${(xmlData.tasa_o_cuota * 100).toFixed(2)}%
                        <strong>Tipo Factor:</strong> ${xmlData.tipo_factor}
                    </li>
                `;
            }

            ivasHtml += `
                        </ul>
                    </div>
                    <div class="mt-2 text-xs text-gray-500">
                        <strong>Método de Pago:</strong> ${xmlData.metodo_pago || 'No especificado'}<br>
                        <strong>Forma de Pago:</strong> ${xmlData.forma_pago || 'No especificada'}<br>
                        <strong>Moneda:</strong> ${xmlData.moneda}<br>
                        <strong>Tipo de Comprobante:</strong> ${xmlData.tipo_comprobante}
                    </div>
                </div>
            `;

            ivasInfo.innerHTML = ivasHtml;
            fileList.appendChild(ivasInfo);

            // Log para debugging
            logXmlData(`Mostrando información del XML: ${fileName}`, {
                uuid: xmlData.uuid,
                total: xmlData.total,
                impuestos: {
                    total: xmlData.total_impuestos_trasladados,
                    impuesto: xmlData.impuesto,
                    tasa: xmlData.tasa_o_cuota,
                    tipoFactor: xmlData.tipo_factor
                }
            });
        }

        // Agregar función para mostrar errores de manera más amigable
        function showError(message, details = '') {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4';
            errorDiv.innerHTML = `
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline">${message}</span>
                ${details ? `<div class="mt-2 text-sm">${details}</div>` : ''}
            `;
            fileList.insertBefore(errorDiv, fileList.firstChild);
        }
    });
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 