<?php

declare(strict_types=1);

return [
    'driver' => env('DB_DRIVER', 'mysql'),
    'mysql' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'codemwana'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    'sqlite' => [
        'path' => base_path(env('DB_SQLITE_PATH', 'storage/codemwana.sqlite')),
    ],
];
