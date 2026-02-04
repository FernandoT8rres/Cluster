<?php
// database_config.php - Configuración segura de la base de datos
// NOTA: Este archivo debe estar fuera del directorio público en producción

return [
    'host' => '127.0.0.1', 
    'username' => 'u695712029_claut_fer',
    'password' => 'CLAUT@admin_fernando!7',
    'database' => 'u695712029_claut_intranet',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 30
    ]
];
?>