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
                <div class="relative pt-1 hidden mb-4" id="progress-container">
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200">
                        <div id="progress-bar" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500 w-0 transition-all duration-300"></div>
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
        const MAX_FILES = 500;
        let files = [];

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

        // Agregar función para procesar los IVAS del XML
        function processXmlIvas($xmlString) {
            $xml = new SimpleXMLElement($xmlString);
            $ns = $xml->getNamespaces(true);
            $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
            
            $ivas = [];
            
            // Procesar impuestos por concepto
            $conceptos = $xml->xpath('//cfdi:Concepto');
            foreach ($conceptos as $concepto) {
                $traslados = $concepto->xpath('.//cfdi:Traslado');
                foreach ($traslados as $traslado) {
                    $impuesto = (string)$traslado['Impuesto'];
                    $tipoFactor = (string)$traslado['TipoFactor'];
                    $tasaOCuota = (float)$traslado['TasaOCuota'];
                    $base = (float)$traslado['Base'];
                    $importe = (float)$traslado['Importe'];
                    
                    $tasaKey = "{$impuesto}_{$tasaOCuota}";
                    
                    if (!isset($ivas[$tasaKey])) {
                        $ivas[$tasaKey] = [
                            'impuesto' => $impuesto,
                            'tipoFactor' => $tipoFactor,
                            'tasaOCuota' => $tasaOCuota,
                            'base' => 0,
                            'importe' => 0
                        ];
                    }
                    
                    $ivas[$tasaKey]['base'] += $base;
                    $ivas[$tasaKey]['importe'] += $importe;
                }
            }
            
            return $ivas;
        }

        // Modificar el manejador del formulario para procesar los IVAS
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (files.length === 0) {
                alert('Por favor, seleccione al menos un archivo XML');
                return;
            }

            const formData = new FormData();
            const csrfToken = document.getElementById('csrf_token').value;
            const clientId = document.getElementById('client_id').value;
            
            formData.append('csrf_token', csrfToken);
            formData.append('client_id', clientId);
            
            // Procesar cada archivo XML
            for (let file of files) {
                const reader = new FileReader();
                reader.onload = async function(e) {
                    try {
                        const xmlContent = e.target.result;
                        const ivas = processXmlIvas(xmlContent);
                        
                        // Agregar los IVAS procesados al FormData
                        formData.append('ivas[]', JSON.stringify(ivas));
                        formData.append('xml_files[]', file);
                        
                        // Mostrar información de IVAS en la interfaz
                        const ivasInfo = document.createElement('div');
                        ivasInfo.className = 'mt-4 p-4 bg-gray-50 rounded';
                        ivasInfo.innerHTML = `
                            <h3 class="font-bold text-gray-700">IVAS en ${file.name}:</h3>
                            <ul class="mt-2">
                                ${Object.values(ivas).map(iva => `
                                    <li class="text-sm text-gray-600">
                                        Impuesto: ${iva.impuesto} - 
                                        Tasa: ${(iva.tasaOCuota * 100).toFixed(2)}% - 
                                        Base: $${iva.base.toFixed(2)} - 
                                        Importe: $${iva.importe.toFixed(2)}
                                    </li>
                                `).join('')}
                            </ul>
                        `;
                        fileList.appendChild(ivasInfo);
                        
                    } catch (error) {
                        console.error('Error procesando XML:', error);
                        alert(`Error procesando el archivo ${file.name}: ${error.message}`);
                    }
                };
                reader.readAsText(file);
            }

            try {
                submitBtn.disabled = true;
                progressContainer.classList.remove('hidden');

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

        // Agregar función para mostrar el progreso
        function updateProgress(processed, total) {
            const progress = (processed / total) * 100;
            const progressBar = document.getElementById('progress-bar');
            progressBar.style.width = `${progress}%`;
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
    });
    </script>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 