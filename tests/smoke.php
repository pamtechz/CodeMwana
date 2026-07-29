<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    'index.php','login.php','register.php','dashboard.php','courses.php','lesson.php','quiz.php',
    'playground.php','projects.php','progress.php','leaderboard.php','profile.php','setup.php',
    'assets/css/app.css','assets/js/app.js','assets/js/playground.js',
    'database/schema_mysql.sql','database/seed.php','README.md'
];
$missing = [];
foreach ($required as $file) {
    if (!is_file($root . DIRECTORY_SEPARATOR . $file)) $missing[] = $file;
}
if ($missing) {
    fwrite(STDERR, "Missing files:\n- " . implode("\n- ", $missing) . "\n");
    exit(1);
}
$phpFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') $phpFiles[] = $file->getPathname();
}
foreach ($phpFiles as $file) {
    exec('php -l ' . escapeshellarg($file), $output, $code);
    if ($code !== 0) {
        fwrite(STDERR, implode("\n", $output) . "\n");
        exit(1);
    }
    $output = [];
}
echo 'Smoke checks passed for ' . count($phpFiles) . " PHP files.\n";
