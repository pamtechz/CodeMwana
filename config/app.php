<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'CodeMwana'),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'timezone' => env('APP_TIMEZONE', 'Africa/Lusaka'),
    'session_name' => env('APP_SESSION_NAME', 'codemwana_session'),
    'version' => '3.5.0',
    'login_limit' => 5,
    'login_window_minutes' => 15,
    'code_runner' => [
        'provider' => strtolower(trim((string) env('CODE_RUNNER_PROVIDER', 'jdoodle'))),
        'url' => env('CODE_RUNNER_URL', ''),
        'token' => env('CODE_RUNNER_TOKEN', ''),
        'timeout_seconds' => (int) env('CODE_RUNNER_TIMEOUT', 20),
        'jdoodle' => [
            'execute_url' => env('JDOODLE_API_URL', 'https://api.jdoodle.com/v1/execute'),
            'multi_file_url' => env('JDOODLE_MULTI_FILE_URL', 'https://api.jdoodle.com/v1/engine/execute-api-multifile'),
            'client_id' => env('JDOODLE_CLIENT_ID', ''),
            'client_secret' => env('JDOODLE_CLIENT_SECRET', ''),
            'versions' => [
                'python' => env('JDOODLE_PYTHON_VERSION_INDEX', '6'),
                'php' => env('JDOODLE_PHP_VERSION_INDEX', '6'),
                'go' => env('JDOODLE_GO_VERSION_INDEX', '6'),
                'c' => env('JDOODLE_C_VERSION_INDEX', '7'),
                'cpp' => env('JDOODLE_CPP_VERSION_INDEX', '3'),
            ],
        ],
        'fallback' => [
            'provider' => 'onecompiler',
            'embed_url' => env('ONECOMPILER_EMBED_URL', 'https://onecompiler.com/embed'),
        ],
    ],
];
