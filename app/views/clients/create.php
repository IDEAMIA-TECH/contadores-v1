<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Cliente - Sistema de Cobranza</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-6">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Registrar Nuevo Cliente</h2>
                <p class="text-gray-600">Complete los datos del cliente</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert-error mb-4">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/clients/create" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                
                <div class="bg-blue-50 p-4 rounded-lg mb-6">
                    <h3 class="text-lg font-semibold text-blue-800 mb-2">Constancia de Situación Fiscal</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">
                            Cargar PDF de la Constancia
                        </label>
                        <input type="file" name="csf_pdf" accept=".pdf" 
                               class="mt-1 block w-full" 
                               onchange="extractCsfData(this)">
                        <p class="text-sm text-gray-500 mt-1">
                            Al cargar el PDF, se intentarán extraer los datos automáticamente
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Datos Fiscales -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-900">Datos Fiscales</h3>
                        
                        <div>
                            <label for="rfc" class="block text-sm font-medium text-gray-700">RFC *</label>
                            <input type="text" id="rfc" name="rfc" required 
                                   pattern="[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}"
                                   class="form-input uppercase"
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['rfc'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="business_name" class="block text-sm font-medium text-gray-700">Razón Social *</label>
                            <input type="text" id="business_name" name="business_name" required 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['business_name'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="legal_name" class="block text-sm font-medium text-gray-700">Nombre Comercial</label>
                            <input type="text" id="legal_name" name="legal_name" 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['legal_name'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="fiscal_regime" class="block text-sm font-medium text-gray-700">Régimen Fiscal</label>
                            <input type="text" id="fiscal_regime" name="fiscal_regime" 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['fiscal_regime'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700">Dirección Fiscal</label>
                            <textarea id="address" name="address" rows="3" 
                                    class="form-input"><?php echo htmlspecialchars($_SESSION['form_data']['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Datos de Contacto -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-900">Datos de Contacto</h3>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Principal</label>
                            <input type="email" id="email" name="email" 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono Principal</label>
                            <input type="tel" id="phone" name="phone" 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['phone'] ?? ''); ?>">
                        </div>

                        <div class="border-t pt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Contacto Adicional</h4>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="contact_name" class="block text-sm font-medium text-gray-700">Nombre</label>
                                    <input type="text" id="contact_name" name="contact_name" 
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['contact_name'] ?? ''); ?>">
                                </div>

                                <div>
                                    <label for="contact_email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" id="contact_email" name="contact_email" 
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['contact_email'] ?? ''); ?>">
                                </div>

                                <div>
                                    <label for="contact_phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                                    <input type="tel" id="contact_phone" name="contact_phone" 
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($_SESSION['form_data']['contact_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t">
                    <a href="/clients" class="btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        Registrar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function extractCsfData(input) {
            if (!input.files || !input.files[0]) return;
            
            const formData = new FormData();
            formData.append('csf_pdf', input.files[0]);
            
            try {
                const response = await fetch('/clients/extract-csf', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success && result.data) {
                    // Autocompletar campos
                    Object.keys(result.data).forEach(key => {
                        const input = document.getElementById(key);
                        if (input) {
                            input.value = result.data[key];
                        }
                    });
                }
            } catch (error) {
                console.error('Error al extraer datos:', error);
            }
        }
    </script>
</body>
</html> 