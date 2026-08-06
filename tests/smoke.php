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
    'api/log-browser-run.php', 'assets/css/app.css', 'assets/css/app-v3.css', 'assets/css/app-v4.css',
    'assets/css/curriculum.css', 'assets/css/remote-runner.css', 'assets/js/app.js',
    'assets/js/ui-v4.js', 'assets/js/remote-runner.js', 'assets/js/playground.js',
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

foreach (['assets/js/app.js', 'assets/js/ui-v4.js', 'assets/js/remote-runner.js', 'assets/js/playground.js', 'assets/js/curriculum.js', 'service-worker.js'] as $script) {
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
foreach (['data-language-select', 'data-file-tree', 'data-stdin', 'data-preview-frame', 'api/run-code.php', 'remote-runner.js', 'data-external-runner-frame', "'browserRunners' => []", "'remoteRunners' => ['python', 'php', 'c', 'cpp', 'go']", 'sandbox="allow-scripts allow-forms allow-modals"'] as $feature) {
    if (!str_contains($playground, $feature)) $failures[] = "Code Lab is missing feature declaration: {$feature}";
}
foreach (['browser-runners.js', 'allow-scripts allow-same-origin', '@antonz/codapi', 'engine="wasi"'] as $retiredFeature) {
    if (str_contains($playground, $retiredFeature)) $failures[] = "Code Lab still contains retired or unsafe declaration: {$retiredFeature}";
}

$remoteRunner = (string) file_get_contents($root . '/assets/js/remote-runner.js');
foreach (["new Set(['python', 'php', 'c', 'cpp', 'go'])", 'populateCode', 'triggerRun', 'payload.fallback', 'data-managed-editor', 'codeChangeEvent', "event.origin !== 'null'", 'scheduleSync'] as $feature) {
    if (!str_contains($remoteRunner, $feature)) $failures[] = "Managed runner module is missing feature: {$feature}";
}
if (str_contains($remoteRunner, "setAttribute('engine', 'wasi')") || str_contains($remoteRunner, 'codapi-snippet')) {
    $failures[] = 'Managed runner must not use the retired WASI widget.';
}

$codeRunner = (string) file_get_contents($root . '/app/CodeRunner.php');
foreach (['RunnerFallbackException', 'MANAGED_LANGUAGES', "['python', 'php', 'go', 'c', 'cpp']", 'isManagedLanguage', 'runJdoodle', 'jdoodleRuntime', "'_provider' => 'jdoodle'", 'fallbackAvailable'] as $feature) {
    if (!str_contains($codeRunner, $feature)) $failures[] = "Code runner service is missing feature: {$feature}";
}

$runApi = (string) file_get_contents($root . '/api/run-code.php');
foreach (['RunnerFallbackException', 'CodeRunner::isManagedLanguage', "'fallback' => CodeRunner::fallbackAvailable()", 'code_runner_fallback'] as $feature) {
    if (!str_contains($runApi, $feature)) $failures[] = "Run API is missing managed fallback declaration: {$feature}";
}

$remoteCss = (string) file_get_contents($root . '/assets/css/remote-runner.css');
foreach (['.external-runner-shell', 'data-managed-editor', '.studio-editor > .external-runner-shell', '@media (max-width: 520px)'] as $feature) {
    if (!str_contains($remoteCss, $feature)) $failures[] = "Managed runner stylesheet is missing: {$feature}";
}

$serviceWorker = (string) file_get_contents($root . '/service-worker.js');
foreach (['codemwana-static-v8', 'assets/js/remote-runner.js', 'assets/css/remote-runner.css'] as $feature) {
    if (!str_contains($serviceWorker, $feature)) $failures[] = "Service worker is missing managed runner cache declaration: {$feature}";
}
if (str_contains($serviceWorker, 'assets/js/browser-runners.js')) $failures[] = 'Service worker still caches the retired browser runner.';

$environmentExample = (string) file_get_contents($root . '/.env.example');
foreach (['CODE_RUNNER_PROVIDER=jdoodle', 'JDOODLE_CLIENT_ID=', 'JDOODLE_CLIENT_SECRET=', 'ONECOMPILER_EMBED_URL=https://onecompiler.com/embed'] as $feature) {
    if (!str_contains($environmentExample, $feature)) $failures[] = "Environment example is missing runner setting: {$feature}";
}

$footerSource = (string) file_get_contents($root . '/partials/footer.php');
foreach (['$pageScripts', 'array_unique', 'asset(\'js/\' . $script)'] as $feature) {
    if (!str_contains($footerSource, $feature)) $failures[] = "Shared footer is missing multi-script support: {$feature}";
}

if ($failures) {
    fwrite(STDERR, "Smoke checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'Smoke checks passed for ' . count($phpFiles) . " PHP files, ten languages, five managed editor runtimes, secure sandboxing, JDoodle execution, automatic embedded fallback, responsive curriculum pages and intelligent installation state.\n";
