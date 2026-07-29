<?php

declare(strict_types=1);

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__);
    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
}

function load_env(string $file): void
{
    if (!is_file($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '') {
            continue;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}

function config(string $key, mixed $default = null): mixed
{
    $segments = explode('.', $key);
    $file = array_shift($segments);
    $config = $GLOBALS['config'][$file] ?? [];

    foreach ($segments as $segment) {
        if (!is_array($config) || !array_key_exists($segment, $config)) {
            return $default;
        }
        $config = $config[$segment];
    }

    return $config;
}

function url(string $path = ''): string
{
    $base = (string) config('app.url', '');
    if ($base === '') {
        $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
        $projectRoot = realpath(base_path());
        if ($documentRoot && $projectRoot && str_starts_with($projectRoot, $documentRoot)) {
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($projectRoot, strlen($documentRoot)));
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = $scheme . '://' . $host . rtrim($relative, '/');
        }
    }
    $path = ltrim($path, '/');
    return rtrim($base, '/') . ($path === '' ? '' : '/' . $path);
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Your session expired. Please return to the previous page and try again.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function set_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function current_user(): ?array
{
    return Auth::user();
}

function require_auth(): void
{
    if (!Auth::check()) {
        flash('error', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function require_role(array|string $roles): void
{
    require_auth();
    $roles = (array) $roles;
    if (!in_array(Auth::user()['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

function request_int(string $key, int $default = 0): int
{
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
    return $value === false || $value === null ? $default : $value;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function page_title(string $title = ''): string
{
    $app = (string) config('app.name', 'CodeMwana');
    return $title === '' ? $app : $title . ' | ' . $app;
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return 'recently';
    }
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return 'recently';
    }
    $difference = time() - $timestamp;
    if ($difference < 60) return 'just now';
    if ($difference < 3600) return floor($difference / 60) . ' min ago';
    if ($difference < 86400) return floor($difference / 3600) . ' hr ago';
    if ($difference < 604800) return floor($difference / 86400) . ' days ago';
    return date('j M Y', $timestamp);
}
