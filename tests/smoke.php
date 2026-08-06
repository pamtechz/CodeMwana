<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    '.htaccess', '.env.example', '.github/workflows/quality.yml',
    'index.php', 'about.php', 'about-us.php', 'about-app.php', 'developers.php',
    'contact.php', 'privacy.php', 'help.php', 'public-page.php', 'login.php', 'register.php',
    'dashboard.php', 'courses.php', 'course.php', 'lesson.php', 'quiz.php', 'playground.php',
    'projects.php', 'progress.php', 'profile.php', 'setup.php', 'system-status.php',
    'admin/dashboard.php', 'admin/users.php', 'admin/content.php', 'admin/settings.php',
    'admin/public-pages.php', 'admin/public-page-edit.php', 'teacher/dashboard.php',
    'app/bootstrap.php', 'app/Installation.php', 'app/Migrator.php', 'app/LanguageCatalog.php',
    'app/PublicPages.php', 'app/CodeRunner.php', 'api/save-project.php', 'api/run-code.php',
    'assets/css/app.css', 'assets/css/app-v3.css', 'assets/css/app-v4.css',
    'assets/css/remote-runner.css', 'assets/css/public-pages.css', 'assets/js/app.js',
    'assets/js/ui-v4.js', 'assets/js/managed-frame-compat.js', 'assets/js/remote-runner.js',
    'assets/js/playground.js', 'service-worker.js', 'database/schema_mysql.sql',
    'database/schema_sqlite.sql', 'tests/python_input_guard.php'
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

require_once $root . '/app/helpers.php';
require_once $root . '/app/LanguageCatalog.php';
require_once $root . '/app/PublicPages.php';

$languages = LanguageCatalog::definitions();
$expectedLanguages = ['html', 'css', 'javascript', 'python', 'php', 'react', 'nextjs', 'go', 'c', 'cpp'];
$languageSlugs = array_column($languages, 'slug');
if (count($languages) !== 10) $failures[] = 'Language catalogue must contain ten mainstream languages.';
foreach ($expectedLanguages as $slug) if (!in_array($slug, $languageSlugs, true)) $failures[] = "Language catalogue is missing: {$slug}";

$publicDefaults = PublicPages::defaults();
$publicSlugs = array_column($publicDefaults, 'slug');
$expectedPages = ['about', 'about-us', 'about-app', 'developers', 'contact', 'privacy', 'help'];
if (count($publicDefaults) !== count($expectedPages)) $failures[] = 'Public page catalogue must contain seven managed pages.';
foreach ($expectedPages as $slug) if (!in_array($slug, $publicSlugs, true)) $failures[] = "Public page catalogue is missing: {$slug}";
$defaultText = implode("\n", array_map(static fn (array $page): string => implode(' ', [(string) $page['hero_title'], (string) $page['hero_text'], (string) $page['content_html']]), $publicDefaults));
foreach (['Make the first steps in coding understandable', 'Learn by creating', 'Follow ordered learning paths', 'Pamtech I.T Solutions', 'one answer per line'] as $requiredText) {
    if (!str_contains($defaultText, $requiredText)) $failures[] = "Seeded public content is missing: {$requiredText}";
}

$sanitised = sanitize_public_html('<h2 onclick="bad()">Title</h2><script>alert(1)</script><a href="javascript:alert(1)" style="color:red">Unsafe</a><details open><summary>Help</summary><p>Text</p></details>');
foreach (['<script', 'onclick=', 'javascript:', 'style='] as $unsafe) {
    if (stripos($sanitised, $unsafe) !== false) $failures[] = "Public HTML sanitizer retained unsafe markup: {$unsafe}";
}
foreach (['<h2>', '<details open>', '<summary>'] as $safe) {
    if (!str_contains($sanitised, $safe)) $failures[] = "Public HTML sanitizer removed required content: {$safe}";
}

$bootstrap = (string) file_get_contents($root . '/app/bootstrap.php');
foreach ([
    "header_remove('X-Powered-By')",
    'X-Content-Type-Options: nosniff',
    'Strict-Transport-Security',
    'Content-Security-Policy:',
    "frame-src 'self' data: blob: https://onecompiler.com",
    "ini_set('session.use_strict_mode', '1')",
    'Cache-Control: no-store',
    "require_once __DIR__ . '/PublicPages.php'",
] as $feature) {
    if (!str_contains($bootstrap, $feature)) $failures[] = "Production bootstrap is missing: {$feature}";
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
    'PYTHON_INPUT_GUARD', '__codemwana_safe_input', 'except EOFError', 'injectPythonInputGuard',
    'sanitiseProgramOutput', "API_HOST = 'api.jdoodle.com'", "FALLBACK_HOST = 'onecompiler.com'", "'_provider' => 'managed'",
] as $feature) {
    if (!str_contains($codeRunner, $feature)) $failures[] = "Managed execution hardening is missing: {$feature}";
}
foreach (['runPiston', 'CODE_RUNNER_URL', "config('app.code_runner.url'", "config('app.code_runner.token'"] as $forbidden) {
    if (str_contains($codeRunner, $forbidden)) $failures[] = "Managed execution retains an unnecessary external runner path: {$forbidden}";
}

$config = (string) file_get_contents($root . '/config/app.php');
$environment = (string) file_get_contents($root . '/.env.example');
if (!str_contains($config, "'version' => '3.6.0'")) $failures[] = 'Application release version must be 3.6.0.';
foreach (['CODE_RUNNER_URL', 'CODE_RUNNER_TOKEN', 'CODE_RUNNER_PROVIDER'] as $forbidden) {
    if (str_contains($config, $forbidden) || str_contains($environment, $forbidden)) $failures[] = "Legacy runner configuration remains: {$forbidden}";
}

$runApi = (string) file_get_contents($root . '/api/run-code.php');
foreach (['CodeRunner::isManagedLanguage', 'RunnerFallbackException', 'code_runner_fallback'] as $feature) {
    if (!str_contains($runApi, $feature)) $failures[] = "Run API is missing: {$feature}";
}

$remoteRunner = (string) file_get_contents($root . '/assets/js/remote-runner.js');
foreach (["new Set(['python', 'php', 'c', 'cpp', 'go'])", "postMessage(message, managedOrigin)", 'event.origin !== managedOrigin', 'executionInput', 'payload.fallback'] as $feature) {
    if (!str_contains($remoteRunner, $feature)) $failures[] = "Managed editor is missing: {$feature}";
}
if (str_contains($remoteRunner, 'codapi-snippet') || str_contains($remoteRunner, "engine', 'wasi")) $failures[] = 'Retired browser runner code is still active.';

$frameCompat = (string) file_get_contents($root . '/assets/js/managed-frame-compat.js');
foreach (["removeAttribute('sandbox')", 'data-external-runner-frame'] as $feature) {
    if (!str_contains($frameCompat, $feature)) $failures[] = "Managed frame compatibility is missing: {$feature}";
}

$systemStatus = (string) file_get_contents($root . '/system-status.php');
if (!str_contains($systemStatus, "require_role('admin')")) $failures[] = 'System diagnostics must be restricted to administrators.';

$setup = (string) file_get_contents($root . '/setup.php');
foreach (["if (Installation::installed()) redirect('index.php')", 'Migrator::run()', "Database::tableExists('public_pages')"] as $feature) {
    if (!str_contains($setup, $feature)) $failures[] = "Initial setup is missing: {$feature}";
}
if (str_contains($setup, '<?= e(PHP_VERSION) ?>') || str_contains($setup, '<code>.env</code>')) $failures[] = 'The installer still renders implementation details.';

$installation = (string) file_get_contents($root . '/app/Installation.php');
foreach (['Service temporarily unavailable', 'logTechnical', 'meta name="robots" content="noindex,nofollow"'] as $feature) {
    if (!str_contains($installation, $feature)) $failures[] = "Neutral service failure handling is missing: {$feature}";
}
if (str_contains($installation, 'Open system status')) $failures[] = 'Public service failures still expose the diagnostics route.';

$migrator = (string) file_get_contents($root . '/app/Migrator.php');
foreach (["VERSION = '3.6.0'", 'ensurePublicPagesTable', 'CREATE TABLE public_pages', 'PublicPages::seedDefaults()', 'refreshPublicContent'] as $feature) {
    if (!str_contains($migrator, $feature)) $failures[] = "Public-page migration is missing: {$feature}";
}

$publicPageService = (string) file_get_contents($root . '/app/PublicPages.php');
foreach (['seedDefaults', 'navigation', 'routeMap', 'resolveHtml', 'resolveUrl', 'show_in_header', 'show_in_footer', 'is_published'] as $feature) {
    if (!str_contains($publicPageService, $feature)) $failures[] = "Public page service is missing: {$feature}";
}

$renderer = (string) file_get_contents($root . '/public-page.php');
foreach (['PublicPages::find', 'PublicPages::resolveHtml', 'PublicPages::resolveUrl', 'Administrator preview', "['public-pages.css']"] as $feature) {
    if (!str_contains($renderer, $feature)) $failures[] = "Public page renderer is missing: {$feature}";
}

$routeFiles = [
    'about.php' => 'about', 'about-us.php' => 'about-us', 'about-app.php' => 'about-app',
    'developers.php' => 'developers', 'contact.php' => 'contact', 'privacy.php' => 'privacy', 'help.php' => 'help',
];
foreach ($routeFiles as $file => $slug) {
    $source = (string) file_get_contents($root . '/' . $file);
    if (!str_contains($source, "\$publicPageSlug = '{$slug}'") || !str_contains($source, "require __DIR__ . '/public-page.php'")) {
        $failures[] = "Public route {$file} is not connected to {$slug}.";
    }
}

$adminList = (string) file_get_contents($root . '/admin/public-pages.php');
$adminEditor = (string) file_get_contents($root . '/admin/public-page-edit.php');
foreach ([$adminList, $adminEditor] as $adminSource) {
    if (!str_contains($adminSource, "require_role('admin')")) $failures[] = 'Public page administration must require the administrator role.';
    if (!str_contains($adminSource, 'verify_csrf()')) $failures[] = 'Public page administration must verify CSRF tokens.';
}
foreach (['sanitize_public_html', 'updated_by', 'show_in_header', 'show_in_footer', 'data-document-editor', '{{support_email}}'] as $feature) {
    if (!str_contains($adminEditor, $feature)) $failures[] = "Public page editor is missing: {$feature}";
}

$header = (string) file_get_contents($root . '/partials/header.php');
$footer = (string) file_get_contents($root . '/partials/footer.php');
foreach (['PublicPages::navigation(\'header\')', 'PublicPages::urlFor'] as $feature) {
    if (!str_contains($header, $feature)) $failures[] = "Public header navigation is missing: {$feature}";
}
foreach (['PublicPages::navigation(\'footer\')', 'PublicPages::urlFor'] as $feature) {
    if (!str_contains($footer, $feature)) $failures[] = "Public footer navigation is missing: {$feature}";
}
if (str_contains($footer, "config('app.version'")) $failures[] = 'The shared footer still discloses the software version.';

$adminDashboard = (string) file_get_contents($root . '/admin/dashboard.php');
$adminSettings = (string) file_get_contents($root . '/admin/settings.php');
foreach ([$adminDashboard, $adminSettings] as $source) {
    if (!str_contains($source, 'admin/public-pages.php')) $failures[] = 'Administrator navigation does not expose public page management.';
}

$workflow = (string) file_get_contents($root . '/.github/workflows/quality.yml');
foreach (['php tests/smoke.php', 'php tests/python_input_guard.php'] as $feature) {
    if (!str_contains($workflow, $feature)) $failures[] = "Quality workflow is missing: {$feature}";
}

$serviceWorker = (string) file_get_contents($root . '/service-worker.js');
foreach (['assets/js/managed-frame-compat.js', 'assets/js/remote-runner.js'] as $feature) {
    if (!str_contains($serviceWorker, $feature)) $failures[] = "Service worker is missing: {$feature}";
}
if (str_contains($serviceWorker, 'browser-runners.js')) $failures[] = 'Service worker still caches the retired browser runner.';

if ($failures) {
    fwrite(STDERR, "Smoke checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'Smoke checks passed for ' . count($phpFiles) . " PHP files, seven database-managed public pages, safe administration, dynamic navigation and managed code execution.\n";
