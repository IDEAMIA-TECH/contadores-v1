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

            <form id="upload-form" action="<?php echo BASE_URL; ?>/clients/upload-xml" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" id="csrf_token">
                <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client['id']); ?>" id="client_id">
                <input type="file" id="xml_files" name="xml_files[]" accept=".xml" multiple class="hidden">

                <!-- Barra de progreso -->
                <div id="progress-container" class="relative pt-1 hidden mb-4">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                                Progreso
                            </span>
                        </div>
                        <div class="text-right">
                            <span id="progress-percentage" class="text-xs font-semibold inline-block text-blue-600">
                                0%
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                        <div id="progress-bar" 
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500 w-0 transition-all duration-300">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="submit" id="submit-btn" disabled
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md disabled:opacity-50 disabled:cursor-not-allowed">
                        Subir XMLs
                    </button>
                    <a href="<?php echo BASE_URL; ?>/clients/view/<?php echo $client['id']; ?>" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md">
                        Cancelar
                    </a>
                </div>
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
        const submitBtn = document.getElementById('submit-btn');
        const form = document.getElementById('upload-form');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const MAX_FILES = 500;
        let files = [];

        // Agregar el HTML de la barra de progreso si no existe
        if (!progressContainer) {
            const progressHTML = `
                <div id="progress-container" class="relative pt-1 hidden mb-4">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                                Progreso
                            </span>
                        </div>
                        <div class="text-right">
                            <span id="progress-percentage" class="text-xs font-semibold inline-block text-blue-600">
                                0%
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                        <div id="progress-bar" 
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500 w-0 transition-all duration-300">
                        </div>
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

        // Modificar la función processXmlIvas para manejar el desglose de IVAS
        function processXmlIvas(xmlString) {
            try {
                logXmlData('Procesando nuevo XML', xmlString.substring(0, 150) + '...');
                
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlString, "text/xml");
                
                // Extraer datos del comprobante
                const comprobante = xmlDoc.getElementsByTagName('cfdi:Comprobante')[0];
                const datosComprobante = {
                    serie: comprobante.getAttribute('Serie') || '',
                    folio: comprobante.getAttribute('Folio') || '',
                    fecha: comprobante.getAttribute('Fecha'),
                    total: parseFloat(comprobante.getAttribute('Total')),
                    subtotal: parseFloat(comprobante.getAttribute('SubTotal')),
                    tipoComprobante: comprobante.getAttribute('TipoDeComprobante')
                };

                // Extraer datos del emisor
                const emisor = xmlDoc.getElementsByTagName('cfdi:Emisor')[0];
                const datosEmisor = {
                    rfc: emisor.getAttribute('Rfc'),
                    nombre: emisor.getAttribute('Nombre'),
                    regimenFiscal: emisor.getAttribute('RegimenFiscal')
                };

                // Extraer datos del receptor
                const receptor = xmlDoc.getElementsByTagName('cfdi:Receptor')[0];
                const datosReceptor = {
                    rfc: receptor.getAttribute('Rfc'),
                    nombre: receptor.getAttribute('Nombre'),
                    regimenFiscal: receptor.getAttribute('RegimenFiscalReceptor'),
                    domicilioFiscal: receptor.getAttribute('DomicilioFiscalReceptor'),
                    usoCFDI: receptor.getAttribute('UsoCFDI')
                };

                // Procesar impuestos
                const impuestos = {
                    iva_16_base: 0,
                    iva_16_impuesto: 0,
                    iva_8_base: 0,
                    iva_8_impuesto: 0,
                    iva_0_base: 0,
                    iva_0_impuesto: 0,
                    desglose: []
                };

                let impuestosTotal = 0;

                const conceptos = xmlDoc.getElementsByTagName('cfdi:Concepto');
                Array.from(conceptos).forEach((concepto, index) => {
                    const traslados = concepto.getElementsByTagName('cfdi:Traslado');
                    Array.from(traslados).forEach(traslado => {
                        const impuesto = traslado.getAttribute('Impuesto');
                        const tipoFactor = traslado.getAttribute('TipoFactor');
                        const tasaOCuota = parseFloat(traslado.getAttribute('TasaOCuota'));
                        const base = parseFloat(traslado.getAttribute('Base'));
                        const importe = parseFloat(traslado.getAttribute('Importe'));
                        
                        impuestosTotal += importe;

                        // Acumular en el desglose correspondiente
                        if (impuesto === '002') { // IVA
                            if (tasaOCuota === 0.16) {
                                impuestos.iva_16_base += base;
                                impuestos.iva_16_impuesto += importe;
                            } else if (tasaOCuota === 0.08) {
                                impuestos.iva_8_base += base;
                                impuestos.iva_8_impuesto += importe;
                            } else if (tasaOCuota === 0) {
                                impuestos.iva_0_base += base;
                                impuestos.iva_0_impuesto += importe;
                            }
                        }

                        // Guardar el desglose detallado
                        impuestos.desglose.push({
                            concepto_index: index,
                            impuesto,
                            tipo_factor: tipoFactor,
                            tasa_o_cuota: tasaOCuota,
                            base,
                            importe
                        });
                    });
                });

                // Extraer UUID del TimbreFiscalDigital
                const tfd = xmlDoc.getElementsByTagName('tfd:TimbreFiscalDigital')[0];
                const uuid = tfd ? tfd.getAttribute('UUID') : '';

                // Crear objeto con todos los datos
                const xmlData = {
                    comprobante: datosComprobante,
                    emisor: datosEmisor,
                    receptor: datosReceptor,
                    impuestos: {
                        total: impuestosTotal,
                        desglose: impuestos
                    },
                    uuid: uuid
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

        // Modificar el manejador del formulario para incluir logs adicionales
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            logXmlData('Iniciando proceso de subida', {
                numeroArchivos: files.length,
                nombresArchivos: files.map(f => f.name)
            });

            if (files.length === 0) {
                alert('Por favor, seleccione al menos un archivo XML');
                return;
            }

            const formData = new FormData();
            const csrfToken = document.getElementById('csrf_token').value;
            const clientId = document.getElementById('client_id').value;
            
            formData.append('csrf_token', csrfToken);
            formData.append('client_id', clientId);
            
            // Reiniciar la barra de progreso
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '0%';
            
            let processedFiles = 0;
            let processedIvas = [];
            
            // Primero procesar todos los archivos
            for (let file of files) {
                try {
                    const xmlContent = await readFileAsync(file);
                    const xmlData = processXmlIvas(xmlContent);
                    
                    // Guardar los datos procesados
                    processedIvas.push({
                        fileName: file.name,
                        data: xmlData
                    });
                    
                    // Agregar el archivo al FormData
                    formData.append('xml_files[]', file);
                    
                    // Actualizar progreso
                    processedFiles++;
                    updateProgress(processedFiles, files.length);
                    
                    // Mostrar información del XML
                    displayIvasInfo(file.name, xmlData);
                    
                    logXmlData(`Archivo procesado exitosamente: ${file.name}`, {
                        uuid: xmlData.uuid,
                        impuestos: xmlData.impuestos
                    });
                } catch (error) {
                    logXmlData(`Error procesando archivo: ${file.name}`, {
                        error: error.message,
                        stack: error.stack
                    });
                    alert(`Error procesando el archivo ${file.name}: ${error.message}`);
                }
            }
            
            // Agregar todos los IVAS procesados al FormData
            formData.append('xml_data', JSON.stringify({
                xmlFiles: processedIvas.map(item => ({
                    fileName: item.fileName,
                    data: item.data
                }))
            }));

            // Asegurarnos de que los archivos XML se envíen correctamente
            files.forEach(file => {
                formData.append('xml_files[]', file);
            });

            try {
                submitBtn.disabled = true;
                
                logXmlData('Enviando datos al servidor', {
                    numeroArchivos: files.length,
                    datosFormulario: {
                        csrf_token: csrfToken,
                        client_id: clientId,
                        archivos: files.map(f => ({nombre: f.name, tamaño: f.size})),
                        ivasProcesados: processedIvas
                    }
                });

                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const result = await response.json();
                
                if (result.success) {
                    alert(`Se procesaron ${result.files_processed} archivos correctamente.`);
                    if (result.redirect_url) {
                        window.location.href = result.redirect_url;
                    }
                } else {
                    alert(result.message || 'Error al procesar los archivos');
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

        // Función para actualizar la barra de progreso
        function updateProgress(processed, total) {
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const progressPercentage = document.getElementById('progress-percentage');
            const percentage = Math.round((processed / total) * 100);

            progressContainer.classList.remove('hidden');
            progressBar.style.width = `${percentage}%`;
            progressPercentage.textContent = `${percentage}%`;
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

        // Función auxiliar para leer archivos como Promise
        function readFileAsync(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.onerror = (e) => reject(new Error(`Error leyendo archivo ${file.name}: ${reader.error}`));
                reader.readAsText(file);
            });
        }

        // Modificar la función displayIvasInfo para manejar correctamente la estructura de datos
        function displayIvasInfo(fileName, xmlData) {
            const ivasInfo = document.createElement('div');
            ivasInfo.className = 'mt-4 p-4 bg-gray-50 rounded';
            
            // Extraer el desglose de impuestos
            const impuestos = xmlData.impuestos.desglose;
            
            let ivasHtml = `
                <h3 class="font-bold text-gray-700">Información del XML: ${fileName}</h3>
                <div class="mt-2 space-y-2">
                    <div class="text-sm text-gray-600">
                        <strong>UUID:</strong> ${xmlData.uuid}<br>
                        <strong>Emisor:</strong> ${xmlData.emisor.nombre} (${xmlData.emisor.rfc})<br>
                        <strong>Receptor:</strong> ${xmlData.receptor.nombre} (${xmlData.receptor.rfc})<br>
                        <strong>Total:</strong> $${xmlData.comprobante.total.toFixed(2)}
                    </div>
                    
                    <div class="mt-3">
                        <h4 class="font-semibold text-gray-700">Desglose de IVAS:</h4>
                        <ul class="mt-1 space-y-1">
            `;

            // Agregar IVA 16% si existe
            if (impuestos.iva_16_base > 0) {
                ivasHtml += `
                    <li class="text-sm text-gray-600">
                        <strong>IVA 16%:</strong>
                        Base: $${impuestos.iva_16_base.toFixed(2)} - 
                        Impuesto: $${impuestos.iva_16_impuesto.toFixed(2)}
                    </li>
                `;
            }

            // Agregar IVA 8% si existe
            if (impuestos.iva_8_base > 0) {
                ivasHtml += `
                    <li class="text-sm text-gray-600">
                        <strong>IVA 8%:</strong>
                        Base: $${impuestos.iva_8_base.toFixed(2)} - 
                        Impuesto: $${impuestos.iva_8_impuesto.toFixed(2)}
                    </li>
                `;
            }

            // Agregar IVA 0% si existe
            if (impuestos.iva_0_base > 0) {
                ivasHtml += `
                    <li class="text-sm text-gray-600">
                        <strong>IVA 0%:</strong>
                        Base: $${impuestos.iva_0_base.toFixed(2)} - 
                        Impuesto: $${impuestos.iva_0_impuesto.toFixed(2)}
                    </li>
                `;
            }

            // Agregar desglose detallado
            if (impuestos.desglose && impuestos.desglose.length > 0) {
                ivasHtml += `
                    <li class="mt-2">
                        <strong class="text-sm text-gray-700">Desglose por concepto:</strong>
                        <ul class="ml-4 mt-1">
                            ${impuestos.desglose.map(item => `
                                <li class="text-xs text-gray-600">
                                    Concepto ${item.concepto_index + 1}: 
                                    ${item.impuesto === '002' ? 'IVA' : 'Otro'} 
                                    ${(item.tasa_o_cuota * 100).toFixed(2)}% - 
                                    Base: $${item.base.toFixed(2)} - 
                                    Impuesto: $${item.importe.toFixed(2)}
                                </li>
                            `).join('')}
                        </ul>
                    </li>
                `;
            }

            ivasHtml += `
                        </ul>
                    </div>
                </div>
            `;

            ivasInfo.innerHTML = ivasHtml;
            fileList.appendChild(ivasInfo);
        }
    });
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 