<?php
// Rutas de autenticación
$router->addRoute('login', 'LoginController', 'index');
$router->addRoute('login', 'LoginController', 'login', 'POST');
$router->addRoute('logout', 'LoginController', 'logout');
$router->addRoute('forgot-password', 'AuthController', 'showForgotPassword');
$router->addRoute('reset-password', 'AuthController', 'showResetPassword');

// Rutas de clientes
$router->addRoute('clients', 'ClientsController', 'index');
$router->addRoute('clients/download-sat', 'ClientsController', 'downloadSat');

// Rutas de reportes
$router->addRoute('reports', 'ReportController', 'index');
$router->addRoute('reports/export', 'ReportController', 'export'); 