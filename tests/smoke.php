<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    'index.php', 'login.php', 'register.php', 'dashboard.php', 'courses.php', 'course.php',
    'lesson.php', 'quiz.php', 'playground.php', 'projects.php', 'progress.php', 'profile.php',
    'setup.php', 'admin/dashboard.php', 'admin/users.php', 'admin/content.php',
    'admin/questions.php', 'admin/settings.php', 'teacher/dashboard.php', 'teacher/learner.php',
    'assets/css/app.css', 'assets/js/app.js', 'assets/js/playground.js',
    'database/schema_mysql.sql', 'database/schema_sqlite.sql', 'database/seed.php', 'README.md'
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

foreach (['assets/js/app.js', 'assets/js/playground.js'] as $script) {
    $path = $root . DIRECTORY_SEPARATOR . $script;
    if (is_file($path) && trim((string) shell_exec('command -v node 2>/dev/null')) !== '') {
        exec('node --check ' . escapeshellarg($path), $output, $code);
        if ($code !== 0) $failures[] = "JavaScript syntax failed: {$script}";
        $output = [];
    }
}

foreach ($phpFiles as $file) {
    $contents = (string) file_get_contents($file);
    if (preg_match('/\bplaceholder\s*=/i', $contents)) $failures[] = 'Placeholder attribute found in ' . substr($file, strlen($root) + 1);
}

$schema = (string) file_get_contents($root . '/database/schema_mysql.sql');
foreach (['site_settings', 'course_enrollments', 'project_versions', 'announcements', 'login_attempts'] as $table) {
    if (!str_contains($schema, 'CREATE TABLE IF NOT EXISTS ' . $table)) $failures[] = "Schema is missing table: {$table}";
}

if ($failures) {
    fwrite(STDERR, "Smoke checks failed:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'Smoke checks passed for ' . count($phpFiles) . " PHP files and all required platform assets.\n";
