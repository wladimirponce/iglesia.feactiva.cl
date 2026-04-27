<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'driver' => env('DB_DRIVER', 'mysql'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_NAME', 'feactiva_iglesia_saas'),
    'username' => env('DB_USER', ''),
    'password' => env('DB_PASS', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
];
