<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'CodeMwana'),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'timezone' => env('APP_TIMEZONE', 'Africa/Lusaka'),
    'session_name' => env('APP_SESSION_NAME', 'codemwana_session'),
    'version' => '3.3.0',
    'login_limit' => 5,
    'login_window_minutes' => 15,
    'code_runner' => [
        'url' => env('CODE_RUNNER_URL', ''),
        'token' => env('CODE_RUNNER_TOKEN', ''),
        'timeout_seconds' => (int) env('CODE_RUNNER_TIMEOUT', 15),
    ],
];
