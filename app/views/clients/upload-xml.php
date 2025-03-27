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
        // Definir todas las referencias DOM al inicio
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('xml_files');
        const fileList = document.getElementById('file-list');
        const submitBtn = document.getElementById('submit-btn');
        const form = document.getElementById('upload-form');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
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

        // Agregar función para validar la estructura del XML
        async function validateXMLStructure(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const xmlContent = e.target.result;
                    
                    // Buscar la posición del tag de cierre de Conceptos
                    const conceptosEndPos = xmlContent.indexOf('</cfdi:Conceptos>');
                    if (conceptosEndPos === -1) {
                        reject('No se encontró la sección de Conceptos en el XML');
                        return;
                    }
                    
                    // Extraer solo la sección después de </cfdi:Conceptos>
                    const contentAfterConceptos = xmlContent.substring(conceptosEndPos);

                    // Buscar específicamente la sección de Impuestos que viene después de Conceptos
                    const impuestosMatch = contentAfterConceptos.match(/<cfdi:Impuestos[^>]*TotalImpuestosTrasladados="[^"]*">([\s\S]*?)<\/cfdi:Impuestos>/);
                    
                    if (!impuestosMatch) {
                        reject('No se encontró la sección de Impuestos después de Conceptos');
                        return;
                    }

                    const impuestosSection = impuestosMatch[0];
                    console.log('Sección de Impuestos encontrada:', impuestosSection);

                    // Extraer solo los traslados dentro de esta sección
                    const trasladosData = [];
                    const trasladosRegex = /<cfdi:Traslado\s+([^>]+)>/g;
                    let trasladoMatch;

                    while ((trasladoMatch = trasladosRegex.exec(impuestosSection)) !== null) {
                        const atributosStr = trasladoMatch[1];
                        
                        // Extraer los atributos usando expresiones regulares
                        const base = atributosStr.match(/Base="([^"]+)"/)?.[1];
                        const tasaOCuota = atributosStr.match(/TasaOCuota="([^"]+)"/)?.[1];
                        const importe = atributosStr.match(/Importe="([^"]+)"/)?.[1];

                        if (base && tasaOCuota && importe) {
                            const traslado = {
                                base: parseFloat(base),
                                tasa: parseFloat(tasaOCuota),
                                importe: parseFloat(importe)
                            };
                            trasladosData.push(traslado);
                            console.log('Traslado procesado:', traslado);
                        }
                    }

                    console.log('Total de traslados encontrados:', trasladosData.length);
                    console.log('Datos de traslados:', trasladosData);

                    // Resolver con los datos extraídos
                    resolve({
                        fileName: file.name,
                        traslados: trasladosData
                    });
                };
                reader.onerror = () => reject('Error al leer el archivo XML');
                reader.readAsText(file);
            });
        }

        // Modificar la función handleFileSelection para incluir la validación
        async function handleFileSelection(newFiles) {
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

            // Validar estructura de cada XML
            for (const file of xmlFiles) {
                try {
                    await validateXMLStructure(file);
                } catch (error) {
                    alert(`Error en el archivo ${file.name}: ${error}`);
                    return;
                }
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

        // Modificar el envío del formulario
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (files.length === 0) {
                alert('Por favor, seleccione al menos un archivo XML');
                return;
            }

            const formData = new FormData();
            formData.append('csrf_token', document.getElementById('csrf_token').value);
            formData.append('client_id', document.getElementById('client_id').value);
            
            try {
                submitBtn.disabled = true;
                if (progressContainer) progressContainer.classList.remove('hidden');
                
                // Procesar cada archivo
                for (const file of files) {
                    const xmlData = await validateXMLStructure(file);
                    console.log('Enviando datos para', file.name, ':', xmlData.traslados);
                    
                    // Enviar el archivo y sus traslados
                    formData.append('xml_files[]', file);
                    formData.append(`traslados_${file.name}`, JSON.stringify(xmlData.traslados));
                }

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
                alert('Error al procesar los archivos: ' + error.message);
            } finally {
                submitBtn.disabled = false;
                if (progressContainer) {
                    progressContainer.classList.add('hidden');
                    if (progressBar) progressBar.style.width = '0%';
                }
            }
        });

        // Función para actualizar la barra de progreso
        function updateProgress(processed, total) {
            if (progressBar) {
                const progress = (processed / total) * 100;
                progressBar.style.width = `${progress}%`;
            }
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