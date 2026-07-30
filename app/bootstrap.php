<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
load_env(base_path('.env'));

$GLOBALS['config'] = [
    'app' => require base_path('config/app.php'),
    'database' => require base_path('config/database.php'),
];

date_default_timezone_set((string) config('app.timezone', 'Africa/Lusaka'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name((string) config('app.session_name', 'codemwana_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Installation.php';
require_once __DIR__ . '/LanguageCatalog.php';
require_once __DIR__ . '/Migrator.php';
require_once __DIR__ . '/CodeRunner.php';

Installation::enforce();
if (Installation::installed()) {
    try {
        Migrator::run();
    } catch (Throwable $exception) {
        if (PHP_SAPI === 'cli') throw $exception;
        Installation::migrationFailure($exception);
    }
}

require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/Learning.php';
require_once __DIR__ . '/Auth.php';

if ((bool) config('app.debug', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}
