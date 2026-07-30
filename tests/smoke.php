<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    'index.php', 'login.php', 'register.php', 'dashboard.php', 'courses.php', 'course.php',
    'lesson.php', 'quiz.php', 'playground.php', 'projects.php', 'progress.php', 'profile.php',
    'setup.php', 'system-status.php', 'admin/dashboard.php', 'admin/users.php', 'admin/content.php',
    'admin/questions.php', 'admin/settings.php', 'teacher/dashboard.php', 'teacher/learner.php',
    'app/Installation.php', 'app/Migrator.php', 'app/LanguageCatalog.php', 'app/CodeRunner.php',
    'api/save-project.php', 'api/run-code.php', 'assets/css/app.css', 'assets/css/app-v3.css',
    'assets/js/app.js', 'assets/js/playground.js', 'database/schema_mysql.sql',
    'database/schema_sqlite.sql', 'database/seed.php', 'README.md'
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

foreach (['assets/js/app.js', 'assets/js/playground.js', 'service-worker.js'] as $script) {
    $path = $root . DIRECTORY_SEPARATOR . $script;
    if (is_file($path) && trim((string) shell_exec('command -v node 2>/dev/null')) !== '') {
        $output = [];
        exec('node --check ' . escapeshellarg($path), $output, $code);
        if ($code !== 0) $failures[] = "JavaScript syntax failed: {$script}";
    }
}

foreach ($phpFiles as $file) {
    $contents = (string) file_get_contents($file);
    if (preg_match('/\bplaceholder\s*=/i', $contents)) $failures[] = 'Placeholder attribute found in ' . substr($file, strlen($root) + 1);
}

$mysqlSchema = (string) file_get_contents($root . '/database/schema_mysql.sql');
$sqliteSchema = (string) file_get_contents($root . '/database/schema_sqlite.sql');
foreach (['site_settings', 'course_enrollments', 'project_versions', 'announcements', 'login_attempts', 'programming_languages', 'code_runs'] as $table) {
    foreach (['MySQL' => $mysqlSchema, 'SQLite' => $sqliteSchema] as $label => $schema) {
        if (!str_contains($schema, 'CREATE TABLE IF NOT EXISTS ' . $table)) $failures[] = "{$label} schema is missing table: {$table}";
    }
}
foreach (['workspace_json', 'stdin'] as $column) {
    if (!str_contains($mysqlSchema, $column) || !str_contains($sqliteSchema, $column)) $failures[] = "Project schema is missing column: {$column}";
}

require_once $root . '/app/LanguageCatalog.php';
$languages = LanguageCatalog::definitions();
$slugs = array_column($languages, 'slug');
$expected = ['html', 'css', 'javascript', 'python', 'php', 'react', 'nextjs', 'go', 'c', 'cpp'];
if (count($languages) !== 10) $failures[] = 'Language catalogue must contain exactly ten mainstream languages.';
foreach ($expected as $slug) if (!in_array($slug, $slugs, true)) $failures[] = "Language catalogue is missing: {$slug}";

$index = (string) file_get_contents($root . '/index.php');
if (str_contains($index, "redirect('setup.php')")) $failures[] = 'index.php still contains a direct setup redirect.';
$installation = (string) file_get_contents($root . '/app/Installation.php');
foreach (['installed.lock', 'setup_exists', 'database_error'] as $declaration) {
    if (!str_contains($installation, $declaration)) $failures[] = "Intelligent installation service is missing: {$declaration}";
}

$responsiveCss = (string) file_get_contents($root . '/assets/css/app-v3.css');
foreach (['@media(max-width:1024px)', '@media(max-width:900px)', '@media(max-width:760px)', '@media(max-width:480px)', '.studio-mobile-tabs'] as $rule) {
    if (!str_contains($responsiveCss, $rule)) $failures[] = "Responsive stylesheet is missing rule: {$rule}";
}

$playground = (string) file_get_contents($root . '/playground.php');
foreach (['data-language-select', 'data-file-tree', 'data-stdin', 'data-preview-frame', 'api/run-code.php'] as $feature) {
    if (!str_contains($playground, $feature)) $failures[] = "Code Lab is missing feature declaration: {$feature}";
}

if ($failures) {
    fwrite(STDERR, "Smoke checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'Smoke checks passed for ' . count($phpFiles) . " PHP files, ten languages, responsive assets and intelligent installation state.\n";
