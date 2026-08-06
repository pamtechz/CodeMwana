<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
load_env(base_path('.env'));

$GLOBALS['config'] = [
    'app' => require base_path('config/app.php'),
    'database' => require base_path('config/database.php'),
];

date_default_timezone_set((string) config('app.timezone', 'Africa/Lusaka'));

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header_remove('X-Powered-By');
    header_remove('Content-Security-Policy');

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    header('Cross-Origin-Resource-Policy: same-site');
    header('Cache-Control: no-store, private, max-age=0');
    header('Pragma: no-cache');

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    $scriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptName === 'playground.php') {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com; script-src-elem 'self' 'unsafe-inline' https://unpkg.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob: https://onecompiler.com; font-src 'self' data: https://onecompiler.com https://unpkg.com; connect-src 'self' https://unpkg.com https://onecompiler.com; worker-src 'self' blob:; child-src 'self' blob:; frame-src 'self' data: blob: https://onecompiler.com; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'");
    } else {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; script-src-elem 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; worker-src 'self'; frame-src 'self'; frame-ancestors 'self'; form-action 'self'; base-uri 'self'; object-src 'none'");
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

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
    ini_set('log_errors', '1');
}
