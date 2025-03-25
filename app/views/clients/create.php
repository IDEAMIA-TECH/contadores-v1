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

            <form action="<?php echo BASE_URL; ?>/clients/store" method="POST" class="space-y-6" id="clientForm">
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

                <!-- Credenciales SAT -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Credenciales SAT 
                        <span class="text-sm font-normal text-gray-500">(Opcional)</span>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Archivo .cer -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Certificado (.cer)
                                <span class="text-gray-500 font-normal">(Opcional)</span>
                            </label>
                            <input type="file" 
                                   name="cer_file" 
                                   accept=".cer"
                                   class="block w-full text-sm text-gray-500
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-full file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-50 file:text-blue-700
                                          hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500">Seleccione el archivo .cer del SAT (opcional)</p>
                        </div>

                        <!-- Archivo .key -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Llave privada (.key)
                                <span class="text-gray-500 font-normal">(Opcional)</span>
                            </label>
                            <input type="file" 
                                   name="key_file" 
                                   accept=".key"
                                   class="block w-full text-sm text-gray-500
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-full file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-50 file:text-blue-700
                                          hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500">Seleccione el archivo .key del SAT (opcional)</p>
                        </div>

                        <!-- Contraseña de la llave privada -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Contraseña FIEL
                                <span class="text-gray-500 font-normal">(Opcional)</span>
                            </label>
                            <input type="password" 
                                   name="key_password" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500">Contraseña de la llave privada del SAT (opcional)</p>
                        </div>
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
        const form = document.getElementById('clientForm');
        const csfFile = document.getElementById('csf_file');
        const uploadButton = document.getElementById('upload_csf');
        
        // Prevenir envío del formulario si faltan campos requeridos
        form.addEventListener('submit', function(e) {
            const requiredFields = [
                'rfc', 'business_name', 'fiscal_regime', 
                'street', 'exterior_number', 'neighborhood',
                'city', 'state', 'zip_code', 'email', 'phone'
            ];
            
            let hasError = false;
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    hasError = true;
                    input.classList.add('border-red-500');
                } else {
                    input.classList.remove('border-red-500');
                }
            });
            
            if (hasError) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios');
            }
            
            // Actualizar token CSRF antes de enviar
            const csrfToken = document.querySelector('input[name="csrf_token"]');
            csrfToken.value = '<?php echo $_SESSION['csrf_token']; ?>';
        });
        
        uploadButton.addEventListener('click', function() {
            if (!csfFile.files.length) {
                alert('Por favor seleccione un archivo PDF');
                return;
            }

            const formData = new FormData();
            formData.append('csf_file', csfFile.files[0]);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            uploadButton.disabled = true;
            uploadButton.textContent = 'Procesando...';

            fetch('<?php echo BASE_URL; ?>/clients/process-csf', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Actualizar los campos del formulario
                    const formFields = {
                        'rfc': data.data.rfc || '',
                        'business_name': data.data.business_name || '',
                        'legal_name': data.data.legal_name || '',
                        'fiscal_regime': data.data.fiscal_regime || '',
                        'street': data.data.street || '',
                        'exterior_number': data.data.exterior_number || '',
                        'interior_number': data.data.interior_number || '',
                        'neighborhood': data.data.neighborhood || '',
                        'city': data.data.city || '',
                        'state': data.data.state || '',
                        'zip_code': data.data.zip_code || ''
                    };

                    // Actualizar cada campo del formulario
                    Object.keys(formFields).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.value = formFields[key];
                            // Disparar un evento de cambio para activar cualquier listener
                            input.dispatchEvent(new Event('change'));
                        }
                    });
                    
                    // Actualizar el campo oculto con la ruta del archivo
                    document.getElementById('csf_path').value = data.data.csf_path;
                    
                    alert('Constancia procesada correctamente');
                } else {
                    throw new Error(data.message || 'Error al procesar el archivo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            })
            .finally(() => {
                uploadButton.disabled = false;
                uploadButton.textContent = 'Cargar y Procesar';
            });
        });
        
        // Restaurar datos del formulario si hay error
        <?php if (isset($_SESSION['form_data'])): ?>
        const formData = <?php echo json_encode($_SESSION['form_data']); ?>;
        Object.keys(formData).forEach(key => {
            const input = document.getElementById(key);
            if (input) {
                input.value = formData[key];
            }
        });
        <?php 
        unset($_SESSION['form_data']);
        endif; 
        ?>
    });
    </script>
</body>
</html> 