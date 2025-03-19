<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cliente - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Crear Nuevo Cliente</h1>
                <a href="<?php echo BASE_URL; ?>/clients" 
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

            <!-- Sección para cargar CSF -->
            <div class="mb-8 p-4 bg-gray-50 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Cargar Constancia de Situación Fiscal</h2>
                <div class="flex items-center space-x-4">
                    <input type="file" id="csf_file" accept=".pdf"
                           class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-full file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700
                                  hover:file:bg-blue-100">
                    <button type="button" id="upload_csf" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Cargar y Procesar
                    </button>
                </div>
            </div>

            <form action="<?php echo BASE_URL; ?>/clients/store" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="csf_path" id="csf_path" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">RFC *</label>
                        <input type="text" name="rfc" id="rfc" required maxlength="13"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razón Social *</label>
                        <input type="text" name="business_name" id="business_name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre Legal</label>
                        <input type="text" name="legal_name" id="legal_name"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Régimen Fiscal *</label>
                        <select name="fiscal_regime" id="fiscal_regime" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccione un régimen</option>
                            <option value="601">601 - General de Ley</option>
                            <option value="603">603 - Personas Morales con Fines no Lucrativos</option>
                            <option value="605">605 - Sueldos y Salarios e Ingresos Asimilados</option>
                            <option value="606">606 - Arrendamiento</option>
                            <option value="607">607 - Régimen de Enajenación o Adquisición de Bienes</option>
                            <option value="608">608 - Demás ingresos</option>
                            <option value="609">609 - Consolidación</option>
                            <option value="610">610 - Residentes en el Extranjero sin Establecimiento</option>
                            <option value="611">611 - Ingresos por Dividendos</option>
                            <option value="612">612 - Personas Físicas con Actividades Empresariales</option>
                            <option value="614">614 - Ingresos por intereses</option>
                            <option value="615">615 - Régimen de los ingresos por obtención de premios</option>
                            <option value="616">616 - Sin obligaciones fiscales</option>
                            <option value="620">620 - Sociedades Cooperativas de Producción</option>
                            <option value="621">621 - Incorporación Fiscal</option>
                            <option value="622">622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras</option>
                            <option value="623">623 - Opcional para Grupos de Sociedades</option>
                            <option value="624">624 - Coordinados</option>
                            <option value="625">625 - Régimen de las Actividades Empresariales</option>
                            <option value="626">626 - Régimen Simplificado de Confianza</option>
                        </select>
                    </div>

                    <!-- Dirección desglosada -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Calle *</label>
                        <input type="text" name="street" id="street" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número Exterior *</label>
                            <input type="text" name="exterior_number" id="exterior_number" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número Interior</label>
                            <input type="text" name="interior_number" id="interior_number"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Colonia *</label>
                        <input type="text" name="neighborhood" id="neighborhood" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ciudad/Municipio *</label>
                        <input type="text" name="city" id="city" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado *</label>
                        <input type="text" name="state" id="state" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código Postal *</label>
                        <input type="text" name="zip_code" id="zip_code" required pattern="[0-9]{5}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email *</label>
                        <input type="email" name="email" id="email" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Teléfono *</label>
                        <input type="tel" name="phone" id="phone" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Información de contacto -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Información de Contacto</h3>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre de Contacto</label>
                        <input type="text" name="contact_name" id="contact_name"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email de Contacto</label>
                        <input type="email" name="contact_email" id="contact_email"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Teléfono de Contacto</label>
                        <input type="tel" name="contact_phone" id="contact_phone"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md">
                        Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const csfFile = document.getElementById('csf_file');
        const uploadButton = document.getElementById('upload_csf');
        
        uploadButton.addEventListener('click', function() {
            if (!csfFile.files.length) {
                console.error('No se seleccionó ningún archivo');
                alert('Por favor seleccione un archivo PDF');
                return;
            }

            console.log('Archivo seleccionado:', csfFile.files[0].name);
            
            const formData = new FormData();
            formData.append('csf_file', csfFile.files[0]);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            console.log('Iniciando carga del archivo...');
            console.log('CSRF Token:', document.querySelector('input[name="csrf_token"]').value);

            // Mostrar indicador de carga
            uploadButton.disabled = true;
            uploadButton.textContent = 'Procesando...';

            fetch('<?php echo BASE_URL; ?>/clients/process-csf', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Respuesta del servidor:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data);
                if (data.success) {
                    // Actualizar los campos del formulario
                    console.log('Actualizando campos del formulario...');
                    document.getElementById('rfc').value = data.data.rfc || '';
                    document.getElementById('business_name').value = data.data.business_name || '';
                    document.getElementById('legal_name').value = data.data.legal_name || '';
                    document.getElementById('fiscal_regime').value = data.data.fiscal_regime || '';
                    document.getElementById('street').value = data.data.street || '';
                    document.getElementById('exterior_number').value = data.data.exterior_number || '';
                    document.getElementById('interior_number').value = data.data.interior_number || '';
                    document.getElementById('neighborhood').value = data.data.neighborhood || '';
                    document.getElementById('city').value = data.data.city || '';
                    document.getElementById('state').value = data.data.state || '';
                    document.getElementById('zip_code').value = data.data.zip_code || '';
                    document.getElementById('csf_path').value = data.data.csf_path || '';
                    
                    console.log('Formulario actualizado correctamente');
                    alert('Constancia procesada correctamente');
                } else {
                    console.error('Error en la respuesta:', data);
                    alert(data.message || 'Error al procesar el archivo');
                }
            })
            .catch(error => {
                console.error('Error detallado:', {
                    message: error.message,
                    name: error.name,
                    stack: error.stack
                });
                alert('Error al procesar el archivo: ' + error.message);
            })
            .finally(() => {
                console.log('Proceso finalizado');
                uploadButton.disabled = false;
                uploadButton.textContent = 'Cargar y Procesar';
            });
        });

        // Actualizar el token CSRF antes de enviar el formulario
        const form = document.querySelector('form');
        form.addEventListener('submit', function() {
            const csrfToken = document.querySelector('input[name="csrf_token"]');
            csrfToken.value = '<?php echo $_SESSION['csrf_token']; ?>';
        });
    });
    </script>
</body>
</html> 