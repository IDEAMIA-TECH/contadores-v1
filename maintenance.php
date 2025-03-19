<?php
// Si el sitio está en mantenimiento, mostrar página de mantenimiento
if (file_exists(__DIR__ . '/maintenance.flag')) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600');
    include __DIR__ . '/app/views/maintenance.php';
    exit;
} 