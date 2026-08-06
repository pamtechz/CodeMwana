<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    '.htaccess', '.env.example', '.github/workflows/quality.yml',
    'index.php', 'about.php', 'privacy.php', 'help.php', 'login.php', 'register.php',
    'dashboard.php', 'courses.php', 'course.php', 'lesson.php', 'quiz.php', 'playground.php',
    'projects.php', 'progress.php', 'profile.php', 'setup.php', 'system-status.php',
    'admin/dashboard.php', 'admin/users.php', 'admin/content.php', 'teacher/dashboard.php',
    'app/bootstrap.php', 'app/Installation.php', 'app/Migrator.php', 'app/LanguageCatalog.php',
    'app/CodeRunner.php', 'api/save-project.php', 'api/run-code.php', 'assets/css/app.css',
    'assets/css/app-v3.css', 'assets/css/app-v4.css', 'assets/css/remote-runner.css',
    'assets/js/app.js', 'assets/js/ui-v4.js', 'assets/js/managed-frame-compat.js',
    'assets/js/remote-runner.js', 'assets/js/playground.js', 'service-worker.js',
    'database/schema_mysql.sql', 'database/schema_sqlite.sql', 'tests/python_input_guard.php'
];

$failures = [];
foreach ($required as $file) {
    if (!is_file($root . DIRECTORY_SEPARATOR . $file)) $failures[] = "Missing required file: {$file}";
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$phpFiles = [];
foreach ($iterator as $file) {
    $path = $file->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)) continue;
    if ($file->isFile() && $file->getExtension() === 'php') $phpFiles[] = $path;
}

foreach ($phpFiles as $file) {
    $output = [];
    exec('php -l ' . escapeshellarg($file), $output, $code);
    if ($code !== 0) $failures[] = implode("\n", $output);
}

$node = trim((string) shell_exec('command -v node 2>/dev/null'));
foreach (['assets/js/app.js', 'assets/js/ui-v4.js', 'assets/js/managed-frame-compat.js', 'assets/js/remote-runner.js', 'assets/js/playground.js', 'service-worker.js'] as $script) {
    $path = $root . DIRECTORY_SEPARATOR . $script;
    if ($node !== '' && is_file($path)) {
        $output = [];
        exec('node --check ' . escapeshellarg($path), $output, $code);
        if ($code !== 0) $failures[] = "JavaScript syntax failed: {$script}";
    }
}

$mysqlSchema = (string) file_get_contents($root . '/database/schema_mysql.sql');
$sqliteSchema = (string) file_get_contents($root . '/database/schema_sqlite.sql');
foreach (['site_settings', 'course_enrollments', 'project_versions', 'login_attempts', 'programming_languages', 'code_runs'] as $table) {
    foreach (['MySQL' => $mysqlSchema, 'SQLite' => $sqliteSchema] as $label => $schema) {
        if (!str_contains($schema, 'CREATE TABLE IF NOT EXISTS ' . $table)) $failures[] = "{$label} schema is missing table: {$table}";
    }
}

require_once $root . '/app/LanguageCatalog.php';
$languages = LanguageCatalog::definitions();
$expected = ['html', 'css', 'javascript', 'python', 'php', 'react', 'nextjs', 'go', 'c', 'cpp'];
$slugs = array_column($languages, 'slug');
if (count($languages) !== 10) $failures[] = 'Language catalogue must contain ten mainstream languages.';
foreach ($expected as $slug) if (!in_array($slug, $slugs, true)) $failures[] = "Language catalogue is missing: {$slug}";

$bootstrap = (string) file_get_contents($root . '/app/bootstrap.php');
foreach ([
    "header_remove('X-Powered-By')",
    'X-Content-Type-Options: nosniff',
    'Strict-Transport-Security',
    'Content-Security-Policy:',
    "frame-src 'self' data: blob: https://onecompiler.com",
    "ini_set('session.use_strict_mode', '1')",
    'Cache-Control: no-store',
] as $feature) {
    if (!str_contains($bootstrap, $feature)) $failures[] = "Production response hardening is missing: {$feature}";
}

$apache = (string) file_get_contents($root . '/.htaccess');
foreach (['Options -Indexes', 'app|config|database|docs|partials|storage|tests', 'Require all denied'] as $feature) {
    if (!str_contains($apache, $feature)) $failures[] = "Apache public-surface protection is missing: {$feature}";
}
if (stripos($apache, 'Content-Security-Policy') !== false || stripos($apache, 'Header always set') !== false) {
    $failures[] = 'Apache must not duplicate PHP-owned response headers.';
}

$codeRunner = (string) file_get_contents($root . '/app/CodeRunner.php');
foreach ([
    'PYTHON_INPUT_GUARD',
    '__codemwana_safe_input',
    'except EOFError',
    'injectPythonInputGuard',
    'sanitiseProgramOutput',
    "API_HOST = 'api.jdoodle.com'",
    "FALLBACK_HOST = 'onecompiler.com'",
    "'_provider' => 'managed'",
] as $feature) {
    if (!str_contains($codeRunner, $feature)) $failures[] = "Managed execution hardening is missing: {$feature}";
}
foreach (['runPiston', 'CODE_RUNNER_URL', "config('app.code_runner.url'", "config('app.code_runner.token'"] as $forbidden) {
    if (str_contains($codeRunner, $forbidden)) $failures[] = "Managed execution retains an unnecessary external runner path: {$forbidden}";
}

$config = (string) file_get_contents($root . '/config/app.php');
$environment = (string) file_get_contents($root . '/.env.example');
foreach (['CODE_RUNNER_URL', 'CODE_RUNNER_TOKEN', 'CODE_RUNNER_PROVIDER'] as $forbidden) {
    if (str_contains($config, $forbidden) || str_contains($environment, $forbidden)) {
        $failures[] = "Legacy runner configuration remains: {$forbidden}";
    }
}

$runApi = (string) file_get_contents($root . '/api/run-code.php');
foreach (['CodeRunner::isManagedLanguage', 'RunnerFallbackException', 'code_runner_fallback'] as $feature) {
    if (!str_contains($runApi, $feature)) $failures[] = "Run API is missing: {$feature}";
}

$remoteRunner = (string) file_get_contents($root . '/assets/js/remote-runner.js');
foreach ([
    "new Set(['python', 'php', 'c', 'cpp', 'go'])",
    "postMessage(message, managedOrigin)",
    'event.origin !== managedOrigin',
    'executionInput',
    'payload.fallback',
] as $feature) {
    if (!str_contains($remoteRunner, $feature)) $failures[] = "Managed editor is missing: {$feature}";
}
if (str_contains($remoteRunner, 'codapi-snippet') || str_contains($remoteRunner, "engine', 'wasi")) {
    $failures[] = 'Retired browser runner code is still active.';
}

$frameCompat = (string) file_get_contents($root . '/assets/js/managed-frame-compat.js');
foreach (["removeAttribute('sandbox')", 'data-external-runner-frame'] as $feature) {
    if (!str_contains($frameCompat, $feature)) $failures[] = "Managed frame compatibility is missing: {$feature}";
}

$systemStatus = (string) file_get_contents($root . '/system-status.php');
if (!str_contains($systemStatus, "require_role('admin')")) $failures[] = 'System diagnostics must be restricted to administrators.';

$setup = (string) file_get_contents($root . '/setup.php');
if (!str_contains($setup, "if (Installation::installed()) redirect('index.php')")) {
    $failures[] = 'The installer must be disabled after installation.';
}
if (str_contains($setup, '<?= e(PHP_VERSION) ?>') || str_contains($setup, '<code>.env</code>')) {
    $failures[] = 'The installer still renders implementation details.';
}

$installation = (string) file_get_contents($root . '/app/Installation.php');
foreach (['Service temporarily unavailable', 'logTechnical', 'meta name="robots" content="noindex,nofollow"'] as $feature) {
    if (!str_contains($installation, $feature)) $failures[] = "Neutral service failure handling is missing: {$feature}";
}
if (str_contains($installation, 'Open system status')) $failures[] = 'Public service failures still expose the diagnostics route.';

$help = (string) file_get_contents($root . '/help.php');
foreach (['Learning::courses', 'Learning::languages', "setting('support_email'", "setting('registration_open'", 'one answer per line'] as $feature) {
    if (!str_contains($help, $feature)) $failures[] = "Dynamic Help centre is missing: {$feature}";
}
foreach (['CODE_RUNNER_URL', '.env', 'setup.php', 'JDoodle', 'OneCompiler', 'Piston', 'administrator operations', 'Installation'] as $forbidden) {
    if (stripos($help, $forbidden) !== false) $failures[] = "Public Help centre exposes internal terminology: {$forbidden}";
}

$about = (string) file_get_contents($root . '/about.php');
foreach (['PHP 8.1', 'PDO', 'ICT4410', 'CSRF', 'database-backed', 'relational database'] as $forbidden) {
    if (stripos($about, $forbidden) !== false) $failures[] = "Public About page exposes internal terminology: {$forbidden}";
}

$privacy = (string) file_get_contents($root . '/privacy.php');
foreach (['setup.php', '.env', 'CSRF', 'PDO', 'password hashes'] as $forbidden) {
    if (stripos($privacy, $forbidden) !== false) $failures[] = "Public Privacy page exposes unnecessary implementation details: {$forbidden}";
}

$index = (string) file_get_contents($root . '/index.php');
foreach (['database-backed', 'curriculum database', 'platform administrator', 'Loaded from the curriculum database'] as $forbidden) {
    if (stripos($index, $forbidden) !== false) $failures[] = "Landing page exposes internal terminology: {$forbidden}";
}

$header = (string) file_get_contents($root . '/partials/header.php');
foreach (['meta name="robots"', 'noindex,nofollow', "'setup.php', 'system-status.php'"] as $feature) {
    if (!str_contains($header, $feature)) $failures[] = "Private-page indexing protection is missing: {$feature}";
}

$footer = (string) file_get_contents($root . '/partials/footer.php');
if (str_contains($footer, "config('app.version'")) $failures[] = 'The shared footer still discloses the software version.';

$migrator = (string) file_get_contents($root . '/app/Migrator.php');
foreach (["VERSION = '3.5.0'", 'refreshPublicContent', 'Multi-language Code Lab', 'Multi-file project workspace'] as $feature) {
    if (!str_contains($migrator, $feature)) $failures[] = "Production content migration is missing: {$feature}";
}

$workflow = (string) file_get_contents($root . '/.github/workflows/quality.yml');
foreach (['php tests/smoke.php', 'php tests/python_input_guard.php'] as $feature) {
    if (!str_contains($workflow, $feature)) $failures[] = "Quality workflow is missing: {$feature}";
}

$serviceWorker = (string) file_get_contents($root . '/service-worker.js');
foreach (['codemwana-static-v9', 'assets/js/managed-frame-compat.js', 'assets/js/remote-runner.js'] as $feature) {
    if (!str_contains($serviceWorker, $feature)) $failures[] = "Service worker is missing: {$feature}";
}
if (str_contains($serviceWorker, 'browser-runners.js')) $failures[] = 'Service worker still caches the retired browser runner.';

if ($failures) {
    fwrite(STDERR, "Smoke checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'Smoke checks passed for ' . count($phpFiles) . " PHP files, production headers, protected internals, secure sessions, public content, dynamic help and managed language execution.\n";
