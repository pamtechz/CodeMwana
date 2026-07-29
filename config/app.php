<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'CodeMwana'),
    'url' => rtrim(env('APP_URL', ''), '/'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'timezone' => env('APP_TIMEZONE', 'Africa/Lusaka'),
    'session_name' => 'codemwana_session',
    'version' => '1.0.0',
];
