<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/app/CodeRunner.php';

$method = new ReflectionMethod(CodeRunner::class, 'injectPythonInputGuard');
$method->setAccessible(true);
$source = <<<'PYTHON'
from __future__ import annotations

name = input().strip() or 'Learner'
print(name)
PYTHON;

$prepared = (string) $method->invoke(null, $source);
if (!str_contains($prepared, '__codemwana_safe_input')) {
    fwrite(STDERR, "Python input guard was not inserted.\n");
    exit(1);
}

$futurePosition = strpos($prepared, 'from __future__ import annotations');
$guardPosition = strpos($prepared, 'import builtins as __codemwana_builtins');
if ($futurePosition === false || $guardPosition === false || $guardPosition < $futurePosition) {
    fwrite(STDERR, "Python input guard was inserted before a future import.\n");
    exit(1);
}

$python = '';
if (PHP_OS_FAMILY === 'Windows') {
    $result = trim((string) shell_exec('where python 2>NUL'));
    $python = strtok($result, "\r\n") ?: '';
} else {
    $python = trim((string) shell_exec('command -v python3 2>/dev/null'));
}

if ($python === '') {
    echo "Python interpreter not available; source transformation checks passed.\n";
    exit(0);
}

$temp = tempnam(sys_get_temp_dir(), 'codemwana-python-');
if ($temp === false) {
    fwrite(STDERR, "Could not create a temporary Python file.\n");
    exit(1);
}
file_put_contents($temp, $prepared);

$process = proc_open([$python, $temp], [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
], $pipes);

if (!is_resource($process)) {
    @unlink($temp);
    fwrite(STDERR, "Could not start the Python interpreter.\n");
    exit(1);
}

fclose($pipes[0]);
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);
@unlink($temp);

if ($exitCode !== 0 || trim((string) $stdout) !== 'Learner') {
    fwrite(STDERR, "Python EOF compatibility failed.\n" . trim((string) $stderr) . "\n");
    exit(1);
}

echo "Python EOF compatibility passed.\n";
