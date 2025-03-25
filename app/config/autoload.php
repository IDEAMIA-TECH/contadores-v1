<?php

// Agregar esta línea si no existe
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../controllers/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}); 