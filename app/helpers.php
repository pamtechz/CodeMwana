<?php

declare(strict_types=1);

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__);
    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
}

function load_env(string $file): void
{
    if (!is_file($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '') continue;
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
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
    if ($value === false) return $default;
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
        if (!is_array($config) || !array_key_exists($segment, $config)) return $default;
        $config = $config[$segment];
    }
    return $config;
}

function setting(string $key, mixed $default = null): mixed
{
    try {
        return Settings::get($key, $default);
    } catch (Throwable) {
        return $default;
    }
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

function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $path): never { header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path))); exit; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('Your secure session expired. Return to the previous page and submit the form again.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) { $_SESSION['_flash'][$key] = $message; return null; }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}
function old(string $key, string $default = ''): string { return e($_SESSION['_old'][$key] ?? $default); }
function set_old(array $data): void { $_SESSION['_old'] = $data; }
function clear_old(): void { unset($_SESSION['_old']); }
function current_user(): ?array { return Auth::user(); }

function remember_intended_url(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? null;
    }
}
function intended_url(string $fallback = 'dashboard.php'): string
{
    $target = $_SESSION['_intended'] ?? null;
    unset($_SESSION['_intended']);
    return is_string($target) && str_starts_with($target, '/') ? $target : url($fallback);
}
function require_auth(): void
{
    if (!Auth::check()) {
        remember_intended_url();
        flash('error', 'Sign in to continue to your learning workspace.');
        redirect('login.php');
    }
}
function require_role(array|string $roles): void
{
    require_auth();
    if (!in_array(Auth::user()['role'] ?? '', (array) $roles, true)) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        require base_path('partials/header.php');
        echo '<section class="state-page"><div class="state-card"><span class="state-icon">403</span><h1>Access denied</h1><p>Your account does not have permission to use this area.</p><a class="button" href="' . e(url('dashboard.php')) . '">Return to dashboard</a></div></section>';
        require base_path('partials/footer.php');
        exit;
    }
}
function request_int(string $key, int $default = 0): int
{
    $source = $_GET[$key] ?? $_POST[$key] ?? null;
    $value = filter_var($source, FILTER_VALIDATE_INT);
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
    $app = (string) setting('site_name', config('app.name', 'CodeMwana'));
    return $title === '' ? $app : $title . ' | ' . $app;
}
function time_ago(?string $datetime): string
{
    if (!$datetime || ($timestamp = strtotime($datetime)) === false) return 'Not recorded';
    $difference = max(0, time() - $timestamp);
    if ($difference < 60) return 'Just now';
    if ($difference < 3600) return floor($difference / 60) . ' min ago';
    if ($difference < 86400) return floor($difference / 3600) . ' hr ago';
    if ($difference < 604800) return floor($difference / 86400) . ' days ago';
    return date('j M Y', $timestamp);
}
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    return $letters ?: 'CM';
}
function activity(string $action, array $details = [], ?int $userId = null): void
{
    if (!Database::tableExists('activity_log')) return;
    $userId ??= isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'local');
    Database::query(
        'INSERT INTO activity_log (user_id, action, details_json, ip_hash) VALUES (?, ?, ?, ?)',
        [$userId, $action, json_encode($details, JSON_UNESCAPED_UNICODE), hash('sha256', $ip . '|CodeMwana')]
    );
}
function validation_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<small class="field-error">' . e($errors[$key]) . '</small>' : '';
}
function active_nav(array|string $pages): string
{
    $current = basename($_SERVER['PHP_SELF'] ?? '');
    return in_array($current, (array) $pages, true) ? 'is-active' : '';
}

function icon(string $name, string $class = ''): string
{
    $paths = [
        'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5"/><path d="M9.5 20v-6h5v6"/>',
        'book-open' => '<path d="M3 5.5A3.5 3.5 0 0 1 6.5 2H11v17H6.5A3.5 3.5 0 0 0 3 22Z"/><path d="M21 5.5A3.5 3.5 0 0 0 17.5 2H13v17h4.5A3.5 3.5 0 0 1 21 22Z"/>',
        'terminal' => '<path d="m5 7 4 4-4 4"/><path d="M12 17h7"/><rect x="3" y="3" width="18" height="18" rx="3"/>',
        'chart' => '<path d="M4 19V9"/><path d="M10 19V5"/><path d="M16 19v-7"/><path d="M22 19H2"/>',
        'trophy' => '<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v4a5 5 0 0 1-10 0Z"/><path d="M7 6H4v2a4 4 0 0 0 4 4"/><path d="M17 6h3v2a4 4 0 0 1-4 4"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users' => '<path d="M16 21a7 7 0 0 0-14 0"/><circle cx="9" cy="8" r="4"/><path d="M22 21a6 6 0 0 0-4.5-5.8"/><path d="M16 4.3a4 4 0 0 1 0 7.4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21h-4v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H3v-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1L7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V3h4v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1H21v4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
        'log-out' => '<path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'x' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'play' => '<path d="m8 5 11 7-11 7Z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'route' => '<circle cx="6" cy="19" r="2"/><circle cx="18" cy="5" r="2"/><path d="M8 19h3a3 3 0 0 0 3-3V8a3 3 0 0 1 3-3"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>',
        'shield-check' => '<path d="M12 22s8-3.5 8-10V5l-8-3-8 3v7c0 6.5 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
        'map' => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/><path d="M9 3v15M15 6v15"/>',
        'palette' => '<path d="M12 3a9 9 0 0 0 0 18h1.5a2 2 0 0 0 0-4H12a2 2 0 0 1 0-4h3a6 6 0 0 0 0-12Z"/><circle cx="7.5" cy="10" r=".8"/><circle cx="9.5" cy="6.5" r=".8"/><circle cx="14" cy="6" r=".8"/>',
        'wifi' => '<path d="M5 12.5a10 10 0 0 1 14 0"/><path d="M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r="1"/>',
        'folder-code' => '<path d="M3 6h7l2 2h9v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="m9 13-2 2 2 2M15 13l2 2-2 2M13 12l-2 6"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/>',
        'trash' => '<path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6M14 11v6"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'school' => '<path d="m3 10 9-6 9 6"/><path d="M5 9v11h14V9M9 20v-6h6v6"/><path d="M8 10h.01M12 10h.01M16 10h.01"/>',
        'alert-circle' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'rocket' => '<path d="M14 5c3-3 6-2 6-2s1 3-2 6l-5 5-4-4Z"/><path d="m9 10-4 1-2 3 6 1M13 14l-1 4-3 2-1-6"/><path d="M5 19c1-3 3-4 5-5"/>',
        'blocks' => '<rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="8" rx="1"/><rect x="8" y="13" width="8" height="8" rx="1"/>',
        'footprints' => '<path d="M8 3c2 0 3 2 2 4S7 10 6 9 6 3 8 3ZM16 12c2 0 3 2 2 4s-3 3-4 2-1-6 2-6Z"/><path d="M4 12c1.5 0 2.5 1 2 2.5S4 17 3 16s-.5-4 1-4ZM13 4c1.5 0 2.5 1 2 2.5S13 9 12 8s-.5-4 1-4Z"/>',
        'hammer' => '<path d="m14 4 6 6-3 3-6-6Z"/><path d="m12 8-8 8-1 5 5-1 8-8"/>',
        'pen-tool' => '<path d="m12 19 7-7 3 3-7 7Z"/><path d="m18 6-3-3-7 7 3 3Z"/><path d="m2 22 7-2-5-5Z"/>',
        'save' => '<path d="M4 4h13l3 3v13H4Z"/><path d="M8 4v6h8V4M8 20v-6h8v6"/>',
        'more-horizontal' => '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
    ];
    $path = $paths[$name] ?? $paths['info'];
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function sanitize_lesson_html(string $html): string
{
    $allowed = '<h2><h3><h4><p><ol><ul><li><strong><em><pre><code><blockquote><a><br><table><thead><tbody><tr><th><td>';
    $clean = strip_tags($html, $allowed);
    $clean = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? '';
    $clean = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? '';
    $clean = preg_replace('/\s+href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i', '', $clean) ?? '';
    return trim($clean);
}
