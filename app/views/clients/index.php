<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema de Cobranza</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/styles.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../partials/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Clientes</h1>
            <a href="<?php echo BASE_URL; ?>/clients/create" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Nuevo Cliente
            </a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php 
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RFC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Razón Social</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($client['rfc']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($client['business_name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($client['email']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($client['phone']); ?></td>
                        <td class="px-6 py-4">
                            <a href="<?php echo BASE_URL; ?>/clients/edit/<?php echo $client['id']; ?>" 
                               class="text-blue-600 hover:text-blue-900">Editar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html> 