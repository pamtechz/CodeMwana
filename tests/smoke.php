<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    'index.php', 'login.php', 'register.php', 'dashboard.php', 'courses.php', 'course.php',
    'lesson.php', 'quiz.php', 'playground.php', 'projects.php', 'progress.php', 'profile.php',
    'setup.php', 'system-status.php', 'admin/dashboard.php', 'admin/users.php', 'admin/content.php',
    'admin/course-edit.php', 'admin/lesson-edit.php', 'admin/questions.php', 'admin/settings.php',
    'teacher/dashboard.php', 'teacher/learner.php', 'app/Installation.php', 'app/Migrator.php',
    'app/LanguageCatalog.php', 'app/CodeRunner.php', 'api/save-project.php', 'api/run-code.php',
    'assets/css/app.css', 'assets/css/app-v3.css', 'assets/css/app-v4.css', 'assets/css/curriculum.css',
    'assets/js/app.js', 'assets/js/ui-v4.js', 'assets/js/playground.js', 'assets/js/browser-runners.js',
    'assets/js/curriculum.js', 'database/schema_mysql.sql', 'database/schema_sqlite.sql',
    'database/seed.php', 'README.md'
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

foreach (['assets/js/app.js', 'assets/js/ui-v4.js', 'assets/js/playground.js', 'assets/js/browser-runners.js', 'assets/js/curriculum.js', 'service-worker.js'] as $script) {
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

$database = (string) file_get_contents($root . '/app/Database.php');
if (!str_contains($database, 'information_schema.tables')) $failures[] = 'MySQL table detection must use information_schema.tables.';
if (str_contains($database, "SHOW TABLES LIKE ?")) $failures[] = 'Native prepared SHOW TABLES detection must not be used.';
if (!str_contains($database, 'table_schema = DATABASE()')) $failures[] = 'MySQL table detection must be scoped to the configured database.';

$setupSource = (string) file_get_contents($root . '/setup.php');
foreach (['Database::reset()', 'verifiedAdministrator', 'Installation data could not be verified'] as $declaration) {
    if (!str_contains($setupSource, $declaration)) $failures[] = "Post-install database verification is missing: {$declaration}";
}

$responsiveCss = (string) file_get_contents($root . '/assets/css/app-v3.css');
foreach (['@media(max-width:1024px)', '@media(max-width:900px)', '@media(max-width:760px)', '@media(max-width:480px)', '.studio-mobile-tabs'] as $rule) {
    if (!str_contains($responsiveCss, $rule)) $failures[] = "Responsive stylesheet is missing rule: {$rule}";
}

$modernCss = (string) file_get_contents($root . '/assets/css/app-v4.css');
foreach (['@media(max-width:1200px)', '@media(max-width:1024px)', '@media(max-width:900px)', '@media(max-width:760px)', '@media(max-width:520px)', '@media(max-width:380px)', '.page-scroll-dock', '.scroll-edge-button', 'prefers-reduced-motion'] as $rule) {
    if (!str_contains($modernCss, $rule)) $failures[] = "Modern responsive stylesheet is missing rule: {$rule}";
}

$curriculumCss = (string) file_get_contents($root . '/assets/css/curriculum.css');
foreach (['.curriculum-dashboard-grid', '.curriculum-editor-layout', '.curriculum-record-actions', '.document-editor', '@media(max-width:620px)', '@media(max-width:390px)'] as $rule) {
    if (!str_contains($curriculumCss, $rule)) $failures[] = "Curriculum stylesheet is missing rule: {$rule}";
}

$curriculumJs = (string) file_get_contents($root . '/assets/js/curriculum.js');
foreach (['setupDocumentEditor', 'setupDrafts', 'data-editor-command', 'localStorage', 'slugify'] as $feature) {
    if (!str_contains($curriculumJs, $feature)) $failures[] = "Curriculum editor script is missing feature: {$feature}";
}

$contentPage = (string) file_get_contents($root . '/admin/content.php');
foreach (['admin/course-edit.php', 'admin/lesson-edit.php', 'curriculum-dashboard-grid', 'curriculum-record-list'] as $feature) {
    if (!str_contains($contentPage, $feature)) $failures[] = "Curriculum management page is missing: {$feature}";
}
foreach (['course-modal', 'lesson-modal', 'data-modal-open'] as $modalDeclaration) {
    if (str_contains($contentPage, $modalDeclaration)) $failures[] = "Curriculum management must not contain modal declaration: {$modalDeclaration}";
}

$courseEditor = (string) file_get_contents($root . '/admin/course-edit.php');
foreach (['data-curriculum-form', 'course_id', 'Create learning path', 'Edit learning path'] as $feature) {
    if (!str_contains($courseEditor, $feature)) $failures[] = "Learning-path editor is missing: {$feature}";
}

$lessonEditor = (string) file_get_contents($root . '/admin/lesson-edit.php');
foreach (['data-document-editor', 'data-editor-surface', 'content_html', 'Curriculum lesson maker'] as $feature) {
    if (!str_contains($lessonEditor, $feature)) $failures[] = "Lesson editor is missing: {$feature}";
}

$playground = (string) file_get_contents($root . '/playground.php');
foreach (['data-language-select', 'data-file-tree', 'data-stdin', 'data-preview-frame', 'api/run-code.php', 'browser-runners.js', "'browserRunners' => ['python', 'php']"] as $feature) {
    if (!str_contains($playground, $feature)) $failures[] = "Code Lab is missing feature declaration: {$feature}";
}

$browserRunners = (string) file_get_contents($root . '/assets/js/browser-runners.js');
foreach (['@antonz/codapi@0.20.0', "new Set(['python', 'php'])", "setAttribute('engine', 'wasi')", 'pythonSource', 'phpSource', 'codapi-snippet'] as $feature) {
    if (!str_contains($browserRunners, $feature)) $failures[] = "Browser runner module is missing feature: {$feature}";
}

$serviceWorker = (string) file_get_contents($root . '/service-worker.js');
foreach (['codemwana-static-v6', 'assets/js/browser-runners.js'] as $feature) {
    if (!str_contains($serviceWorker, $feature)) $failures[] = "Service worker is missing browser runtime cache declaration: {$feature}";
}

$footerSource = (string) file_get_contents($root . '/partials/footer.php');
foreach (['$pageScripts', 'array_unique', "asset('js/' . $script)"] as $feature) {
    if (!str_contains($footerSource, $feature)) $failures[] = "Shared footer is missing multi-script support: {$feature}";
}

if ($failures) {
    fwrite(STDERR, "Smoke checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'Smoke checks passed for ' . count($phpFiles) . " PHP files, ten languages, no-install Python/PHP runtimes, responsive curriculum pages, modern scrolling and intelligent installation state.\n";
