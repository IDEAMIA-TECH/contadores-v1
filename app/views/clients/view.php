<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Cliente - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <?php echo htmlspecialchars($client['business_name']); ?>
                </h1>
                <div class="space-x-4">
                    <a href="<?php echo BASE_URL; ?>/clients/edit/<?php echo $client['id']; ?>" 
                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Editar
                    </a>
                    <a href="<?php echo BASE_URL; ?>/clients" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                        Volver
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                    echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Información Fiscal -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Fiscal</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">RFC</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['rfc']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Razón Social</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['business_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nombre Legal</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['legal_name'] ?? 'No especificado'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Régimen Fiscal</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['fiscal_regime']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Dirección -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Dirección</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Calle</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['street']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Números</p>
                        <p class="mt-1">
                            Ext: <?php echo htmlspecialchars($client['exterior_number']); ?>
                            <?php if (!empty($client['interior_number'])): ?>
                                , Int: <?php echo htmlspecialchars($client['interior_number']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Colonia</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['neighborhood']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Ciudad/Municipio</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['city']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Estado</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['state']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Código Postal</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['zip_code']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Contacto Principal -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Contacto Principal</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Email</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['email']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Teléfono</p>
                        <p class="mt-1"><?php echo htmlspecialchars($client['phone']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Contacto Adicional -->
            <?php if ($contact): ?>
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Contacto Adicional</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Nombre</p>
                        <p class="mt-1"><?php echo htmlspecialchars($contact['contact_name'] ?? 'No especificado'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Email</p>
                        <p class="mt-1"><?php echo htmlspecialchars($contact['contact_email'] ?? 'No especificado'); ?></p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Teléfono</p>
                        <p class="mt-1"><?php echo htmlspecialchars($contact['contact_phone'] ?? 'No especificado'); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Documentos -->
            <?php if (!empty($documents)): ?>
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Documentos</h2>
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($documents as $doc): ?>
                        <li>
                            <div class="px-4 py-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($doc['type']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            Subido el: <?php echo date('d/m/Y', strtotime($doc['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <a href="<?php echo BASE_URL; ?>/uploads/<?php echo $doc['file_path']; ?>" 
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-900">
                                        Ver documento
                                    </a>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <!-- Acciones adicionales -->
            <div class="flex justify-end space-x-4 mt-8">
                <a href="<?php echo BASE_URL; ?>/clients/upload-xml?id=<?php echo $client['id']; ?>" 
                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    Subir XML
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 