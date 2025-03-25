// Agregar las rutas de reportes
$router->addRoute('reports', 'ReportController', 'index');
$router->addRoute('reports/export', 'ReportController', 'export');

// Agregar la ruta para la descarga de XMLs del SAT
$router->addRoute('clients/download-sat', 'ClientController', 'downloadSat');

// Agregar esta ruta a tu archivo de rutas
$router->addRoute('logout', 'AuthController', 'logout'); 