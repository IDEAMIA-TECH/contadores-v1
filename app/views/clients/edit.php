<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Editar Cliente</h1>
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

            <form action="<?php echo BASE_URL; ?>/clients/update/<?php echo htmlspecialchars($client['id']); ?>" 
                  method="POST" class="space-y-6" id="clientForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">RFC *</label>
                        <input type="text" name="rfc" id="rfc" required maxlength="13"
                               value="<?php echo htmlspecialchars($client['rfc']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Razón Social *</label>
                        <input type="text" name="business_name" id="business_name" required
                               value="<?php echo htmlspecialchars($client['business_name']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre Legal</label>
                        <input type="text" name="legal_name" id="legal_name"
                               value="<?php echo htmlspecialchars($client['legal_name'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Régimen Fiscal *</label>
                        <select name="fiscal_regime" id="fiscal_regime" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccione un régimen</option>
                            <?php
                            $regimenes = [
                                '601' => '601 - General de Ley',
                                '603' => '603 - Personas Morales con Fines no Lucrativos',
                                '605' => '605 - Sueldos y Salarios e Ingresos Asimilados',
                                '606' => '606 - Arrendamiento',
                                '607' => '607 - Régimen de Enajenación o Adquisición de Bienes',
                                '608' => '608 - Demás ingresos',
                                '609' => '609 - Consolidación',
                                '610' => '610 - Residentes en el Extranjero sin Establecimiento',
                                '611' => '611 - Ingresos por Dividendos',
                                '612' => '612 - Personas Físicas con Actividades Empresariales',
                                '614' => '614 - Ingresos por intereses',
                                '615' => '615 - Régimen de los ingresos por obtención de premios',
                                '616' => '616 - Sin obligaciones fiscales',
                                '620' => '620 - Sociedades Cooperativas de Producción',
                                '621' => '621 - Incorporación Fiscal',
                                '622' => '622 - Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
                                '623' => '623 - Opcional para Grupos de Sociedades',
                                '624' => '624 - Coordinados',
                                '625' => '625 - Régimen de las Actividades Empresariales',
                                '626' => '626 - Régimen Simplificado de Confianza'
                            ];
                            foreach ($regimenes as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo ($client['fiscal_regime'] == $value) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Dirección desglosada -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Calle *</label>
                        <input type="text" name="street" id="street" required
                               value="<?php echo htmlspecialchars($client['street']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número Exterior *</label>
                            <input type="text" name="exterior_number" id="exterior_number" required
                                   value="<?php echo htmlspecialchars($client['exterior_number']); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Número Interior</label>
                            <input type="text" name="interior_number" id="interior_number"
                                   value="<?php echo htmlspecialchars($client['interior_number'] ?? ''); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Colonia *</label>
                        <input type="text" name="neighborhood" id="neighborhood" required
                               value="<?php echo htmlspecialchars($client['neighborhood']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ciudad/Municipio *</label>
                        <input type="text" name="city" id="city" required
                               value="<?php echo htmlspecialchars($client['city']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estado *</label>
                        <input type="text" name="state" id="state" required
                               value="<?php echo htmlspecialchars($client['state']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Código Postal *</label>
                        <input type="text" name="zip_code" id="zip_code" required pattern="[0-9]{5}"
                               value="<?php echo htmlspecialchars($client['zip_code']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email *</label>
                        <input type="email" name="email" id="email" required
                               value="<?php echo htmlspecialchars($client['email']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Teléfono *</label>
                        <input type="tel" name="phone" id="phone" required
                               value="<?php echo htmlspecialchars($client['phone']); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Información de contacto -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Información de Contacto</h3>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre de Contacto</label>
                        <input type="text" name="contact_name" id="contact_name"
                               value="<?php echo htmlspecialchars($contact['contact_name'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email de Contacto</label>
                        <input type="email" name="contact_email" id="contact_email"
                               value="<?php echo htmlspecialchars($contact['contact_email'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Teléfono de Contacto</label>
                        <input type="tel" name="contact_phone" id="contact_phone"
                               value="<?php echo htmlspecialchars($contact['contact_phone'] ?? ''); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Agregar después de la sección de contacto y antes del botón de guardar -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Credenciales SAT</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Archivo .cer -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Certificado (.cer)
                            </label>
                            <div class="flex items-center">
                                <?php if (!empty($client['cer_path'])): ?>
                                    <span class="text-sm text-green-600 mr-2">
                                        <svg class="w-5 h-5 inline" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Archivo cargado
                                    </span>
                                <?php endif; ?>
                                <input type="file" 
                                       name="cer_file" 
                                       accept=".cer"
                                       class="block w-full text-sm text-gray-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-full file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-blue-50 file:text-blue-700
                                              hover:file:bg-blue-100">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Seleccione el archivo .cer del SAT</p>
                        </div>

                        <!-- Archivo .key -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Llave privada (.key)
                            </label>
                            <div class="flex items-center">
                                <?php if (!empty($client['key_path'])): ?>
                                    <span class="text-sm text-green-600 mr-2">
                                        <svg class="w-5 h-5 inline" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Archivo cargado
                                    </span>
                                <?php endif; ?>
                                <input type="file" 
                                       name="key_file" 
                                       accept=".key"
                                       class="block w-full text-sm text-gray-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-full file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-blue-50 file:text-blue-700
                                              hover:file:bg-blue-100">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">Seleccione el archivo .key del SAT</p>
                        </div>

                        <!-- Contraseña de la llave privada -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Contraseña FIEL
                            </label>
                            <input type="password" 
                                   name="key_password" 
                                   value="<?php echo htmlspecialchars($client['key_password'] ?? ''); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-1 text-sm text-gray-500">Contraseña de la llave privada del SAT</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('clientForm');
        
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
        });
    });
    </script>
</body>
</html> 