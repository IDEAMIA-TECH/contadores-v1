<!-- Agregar después del área de drop y antes del formulario -->
<div id="iva-summary" class="mb-6 hidden">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Resumen de IVAs</h3>
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div id="iva-details" class="space-y-4">
            <!-- Los detalles de IVA se insertarán aquí dinámicamente -->
        </div>
    </div>
</div>

<!-- Modificar el script existente -->
<script>
    // ... código existente hasta handleFileSelection ...

    async function handleFileSelection(newFiles) {
        // Filtrar solo archivos XML
        const xmlFiles = newFiles.filter(file => file.name.toLowerCase().endsWith('.xml'));
        
        if (files.length + xmlFiles.length > MAX_FILES) {
            alert(`Solo se permiten ${MAX_FILES} archivos XML`);
            return;
        }

        // Validar tamaño de archivos
        if (!validateFiles(xmlFiles)) {
            return;
        }

        // Analizar los XMLs para extraer información de IVAs
        for (const file of xmlFiles) {
            try {
                const content = await readFileContent(file);
                const ivaInfo = await analyzeXMLIvas(content);
                file.ivaInfo = ivaInfo; // Guardar la información de IVA con el archivo
            } catch (error) {
                console.error(`Error al analizar ${file.name}:`, error);
            }
        }

        // Agregar nuevos archivos
        files = [...files, ...xmlFiles];
        updateFileList();
        updateIvaSummary();
        updateSubmitButton();
    }

    // Función para leer el contenido del archivo
    function readFileContent(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => resolve(e.target.result);
            reader.onerror = (e) => reject(e);
            reader.readAsText(file);
        });
    }

    // Función para analizar los IVAs del XML
    function analyzeXMLIvas(xmlContent) {
        return new Promise((resolve, reject) => {
            try {
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlContent, "text/xml");

                // Obtener todos los traslados de impuestos
                const traslados = xmlDoc.querySelectorAll('cfdi\\:Traslado');
                const ivaDetails = new Map();

                traslados.forEach(traslado => {
                    const impuesto = traslado.getAttribute('Impuesto');
                    const tasaStr = traslado.getAttribute('TasaOCuota');
                    const tasa = parseFloat(tasaStr) * 100; // Convertir a porcentaje
                    const importe = parseFloat(traslado.getAttribute('Importe'));
                    const base = parseFloat(traslado.getAttribute('Base'));

                    // Crear una clave única para cada tipo de impuesto y tasa
                    const key = `${impuesto}_${tasa}`;
                    
                    if (!ivaDetails.has(key)) {
                        ivaDetails.set(key, {
                            impuesto: impuesto,
                            tasa: tasa,
                            base: 0,
                            importe: 0
                        });
                    }

                    const detail = ivaDetails.get(key);
                    detail.base += base;
                    detail.importe += importe;
                });

                resolve(Array.from(ivaDetails.values()));
            } catch (error) {
                reject(error);
            }
        });
    }

    // Función para actualizar el resumen de IVAs
    function updateIvaSummary() {
        const ivaSummaryDiv = document.getElementById('iva-summary');
        const ivaDetailsDiv = document.getElementById('iva-details');
        
        if (files.length === 0) {
            ivaSummaryDiv.classList.add('hidden');
            return;
        }

        // Combinar todos los IVAs de todos los archivos
        const totalIvas = new Map();
        
        files.forEach(file => {
            if (file.ivaInfo) {
                file.ivaInfo.forEach(iva => {
                    const key = `${iva.impuesto}_${iva.tasa}`;
                    if (!totalIvas.has(key)) {
                        totalIvas.set(key, {
                            impuesto: iva.impuesto,
                            tasa: iva.tasa,
                            base: 0,
                            importe: 0
                        });
                    }
                    const total = totalIvas.get(key);
                    total.base += iva.base;
                    total.importe += iva.importe;
                });
            }
        });

        // Mostrar el resumen
        ivaDetailsDiv.innerHTML = '';
        totalIvas.forEach(iva => {
            const impuestoName = iva.impuesto === '002' ? 'IVA' : 
                               iva.impuesto === '003' ? 'IEPS' : 
                               `Impuesto ${iva.impuesto}`;
            
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center border-b border-gray-200 py-2';
            div.innerHTML = `
                <div class="text-gray-700">
                    <span class="font-medium">${impuestoName} ${iva.tasa}%</span>
                    <p class="text-sm text-gray-500">Base: $${iva.base.toFixed(2)}</p>
                </div>
                <div class="text-right">
                    <span class="font-medium text-gray-900">$${iva.importe.toFixed(2)}</span>
                </div>
            `;
            ivaDetailsDiv.appendChild(div);
        });

        ivaSummaryDiv.classList.remove('hidden');
    }

    // ... resto del código existente ...
</script> 