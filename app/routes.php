// Agregar las rutas de reportes
$router->addRoute('reports', 'ReportController', 'index');
$router->addRoute('reports/export', 'ReportController', 'export'); 