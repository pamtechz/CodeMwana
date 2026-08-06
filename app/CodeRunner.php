<?php

declare(strict_types=1);

final class RunnerFallbackException extends RuntimeException
{
    public function __construct(private readonly string $fallbackReason, string $message = 'The primary execution service is unavailable.')
    {
        parent::__construct($message);
    }

    public function reason(): string
    {
        return $this->fallbackReason;
    }
}

final class CodeRunner
{
    private const MANAGED_LANGUAGES = ['python', 'php', 'go', 'c', 'cpp'];
    private const PYTHON_INPUT_GUARD = <<<'PYTHON'
# CodeMwana input compatibility guard.
import builtins as __codemwana_builtins
__codemwana_original_input = __codemwana_builtins.input

def __codemwana_safe_input(prompt=''):
    try:
        return __codemwana_original_input(prompt)
    except EOFError:
        return ''

__codemwana_builtins.input = __codemwana_safe_input
PYTHON;

    public static function provider(): string
    {
        $provider = strtolower(trim((string) config('app.code_runner.provider', 'jdoodle')));
        return in_array($provider, ['jdoodle', 'piston'], true) ? $provider : 'jdoodle';
    }

    public static function configured(): bool
    {
        if (self::provider() === 'piston') {
            return trim((string) config('app.code_runner.url', '')) !== '';
        }

        return trim((string) config('app.code_runner.jdoodle.client_id', '')) !== ''
            && trim((string) config('app.code_runner.jdoodle.client_secret', '')) !== '';
    }

    public static function fallbackAvailable(): bool
    {
        $url = trim((string) config('app.code_runner.fallback.embed_url', ''));
        return $url !== '' && preg_match('#^https://#i', $url) === 1;
    }

    public static function fallbackUrl(): string
    {
        $url = rtrim(trim((string) config('app.code_runner.fallback.embed_url', '')), '/');
        if ($url === '' || !preg_match('#^https://#i', $url)) {
            throw new RuntimeException('The backup execution environment is not configured.');
        }
        return $url;
    }

    public static function isManagedLanguage(string $slug): bool
    {
        return in_array(strtolower(trim($slug)), self::MANAGED_LANGUAGES, true);
    }

    public static function run(array $language, array $files, string $stdin = ''): array
    {
        $slug = strtolower(trim((string) ($language['slug'] ?? $language['runner_language'] ?? '')));
        if (!self::isManagedLanguage($slug) && ($language['execution_mode'] ?? '') !== 'remote') {
            throw new RuntimeException('This language runs in the browser preview rather than the managed runner.');
        }
        if (!$files) throw new InvalidArgumentException('At least one source file is required.');

        return self::provider() === 'piston'
            ? self::runPiston($language, $files, $stdin)
            : self::runJdoodle($language, $files, $stdin);
    }

    private static function runJdoodle(array $language, array $files, string $stdin): array
    {
        $clientId = trim((string) config('app.code_runner.jdoodle.client_id', ''));
        $clientSecret = trim((string) config('app.code_runner.jdoodle.client_secret', ''));
        if ($clientId === '' || $clientSecret === '') {
            throw new RunnerFallbackException('not_configured');
        }

        [$runtime, $versionIndex] = self::jdoodleRuntime($language);
        $slug = strtolower(trim((string) ($language['slug'] ?? $language['runner_language'] ?? '')));
        $normalisedFiles = [];
        foreach ($files as $name => $content) {
            $cleanName = basename((string) $name);
            if ($cleanName !== '') $normalisedFiles[$cleanName] = (string) $content;
        }
        if (!$normalisedFiles) throw new InvalidArgumentException('At least one valid source file is required.');

        $mainFile = basename((string) ($language['main_file'] ?? array_key_first($normalisedFiles)));
        if (!array_key_exists($mainFile, $normalisedFiles)) $mainFile = (string) array_key_first($normalisedFiles);
        $normalisedFiles = self::prepareSourceFiles($slug, $normalisedFiles, $mainFile);

        $payload = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'language' => $runtime,
            'versionIndex' => (string) $versionIndex,
            'stdin' => mb_substr($stdin, 0, 10000),
            'compileOnly' => false,
        ];

        $endpoint = trim((string) config('app.code_runner.jdoodle.execute_url', 'https://api.jdoodle.com/v1/execute'));
        if (count($normalisedFiles) > 1) {
            $endpoint = trim((string) config('app.code_runner.jdoodle.multi_file_url', 'https://api.jdoodle.com/v1/engine/execute-api-multifile'));
            $payload['multiFile'] = true;
            $payload['mainFile'] = $mainFile;
            $payload['hasInputFiles'] = false;
            $payload['args'] = '';
            $payload['files'] = array_map(
                static fn (string $name, string $content): array => ['name' => $name, 'content' => $content],
                array_keys($normalisedFiles),
                array_values($normalisedFiles)
            );
        } else {
            $payload['script'] = (string) $normalisedFiles[$mainFile];
        }

        if (!preg_match('#^https://#i', $endpoint)) {
            throw new RunnerFallbackException('invalid_configuration');
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        try {
            [$httpStatus, $body] = self::post($endpoint, $json, ['Content-Type: application/json', 'Accept: application/json']);
        } catch (Throwable $exception) {
            error_log('CodeMwana managed runner connection failure: ' . $exception->getMessage());
            throw new RunnerFallbackException('network');
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            error_log('CodeMwana managed runner returned an invalid response. HTTP ' . $httpStatus);
            throw new RunnerFallbackException('invalid_response');
        }

        $apiStatus = (int) ($decoded['statusCode'] ?? $httpStatus);
        $providerMessage = trim(implode("\n", array_filter([
            (string) ($decoded['error'] ?? ''),
            (string) ($decoded['message'] ?? ''),
            (string) ($decoded['output'] ?? ''),
        ])));

        if ($httpStatus === 429 || $apiStatus === 429 || preg_match('/daily\s+limit|credit|quota|too\s+many\s+requests/i', $providerMessage)) {
            throw new RunnerFallbackException('quota');
        }
        if (in_array($httpStatus, [401, 403], true) || in_array($apiStatus, [401, 403], true)) {
            error_log('CodeMwana managed runner authentication was rejected.');
            throw new RunnerFallbackException('authentication');
        }
        if ($httpStatus >= 500 || $apiStatus >= 500) {
            throw new RunnerFallbackException('service');
        }
        if ($httpStatus < 200 || $httpStatus >= 300) {
            throw new RunnerFallbackException('request_rejected');
        }

        $output = self::sanitiseProgramOutput((string) ($decoded['output'] ?? ''), $mainFile);
        $explicitError = self::sanitiseProgramOutput(trim((string) ($decoded['error'] ?? '')), $mainFile);
        $success = array_key_exists('isExecutionSuccess', $decoded)
            ? (bool) $decoded['isExecutionSuccess']
            : ($apiStatus === 200 && $explicitError === '');
        if (isset($decoded['compilationStatus']) && $decoded['compilationStatus'] !== null && (int) $decoded['compilationStatus'] !== 0) {
            $success = false;
        }

        $stdout = $success ? $output : '';
        $stderr = $success ? $explicitError : trim($explicitError . ($explicitError !== '' && $output !== '' ? "\n" : '') . $output);
        $cpuSeconds = is_numeric($decoded['cpuTime'] ?? null) ? (float) $decoded['cpuTime'] : null;
        $memoryKilobytes = is_numeric($decoded['memory'] ?? null) ? (int) $decoded['memory'] : null;

        return [
            'status' => $success ? 'completed' : 'failed',
            'stdout' => mb_substr($stdout, 0, 50000),
            'stderr' => mb_substr($stderr, 0, 50000),
            'exit_code' => $success ? 0 : 1,
            'execution_time_ms' => $cpuSeconds !== null ? (int) round($cpuSeconds * 1000) : null,
            'memory_bytes' => $memoryKilobytes !== null ? $memoryKilobytes * 1024 : null,
            'runtime' => $slug,
            'version' => (string) $versionIndex,
            '_provider' => 'managed',
        ];
    }

    private static function prepareSourceFiles(string $slug, array $files, string $mainFile): array
    {
        if ($slug !== 'python' || !isset($files[$mainFile])) return $files;
        if (str_contains($files[$mainFile], '__codemwana_safe_input')) return $files;

        $files[$mainFile] = self::PYTHON_INPUT_GUARD . "\n\n" . $files[$mainFile];
        return $files;
    }

    private static function sanitiseProgramOutput(string $text, string $mainFile): string
    {
        if ($text === '') return '';
        $text = str_ireplace(['JDoodle', 'Piston', 'OneCompiler'], 'execution service', $text);
        $text = preg_replace('#/home/(?:[^\s/:]+/)*[^\s:]+#', $mainFile, $text) ?? $text;
        $text = preg_replace('#(?:[A-Za-z]:\\\\|/)(?:[^\s:]+[/\\\\])+([^/\\\\\s:]+)#', '$1', $text) ?? $text;
        return $text;
    }

    private static function jdoodleRuntime(array $language): array
    {
        $slug = strtolower(trim((string) ($language['slug'] ?? $language['runner_language'] ?? '')));
        $mapping = [
            'python' => 'python3',
            'php' => 'php',
            'go' => 'go',
            'c' => 'c',
            'cpp' => 'cpp17',
        ];
        if (!isset($mapping[$slug])) throw new RunnerFallbackException('unsupported_language');

        $version = (string) config('app.code_runner.jdoodle.versions.' . $slug, '0');
        return [$mapping[$slug], $version];
    }

    private static function runPiston(array $language, array $files, string $stdin): array
    {
        $runnerLanguage = trim((string) ($language['runner_language'] ?? ''));
        if ($runnerLanguage === '') throw new RuntimeException('No runtime has been assigned to this language.');

        $base = rtrim(trim((string) config('app.code_runner.url', '')), '/');
        if ($base === '') throw new RunnerFallbackException('not_configured');
        if (!preg_match('#^https?://#i', $base)) throw new RunnerFallbackException('invalid_configuration');
        $endpoint = str_ends_with($base, '/execute') ? $base : (str_ends_with($base, '/api/v2') ? $base . '/execute' : $base . '/api/v2/execute');

        $payloadFiles = [];
        foreach ($files as $name => $content) {
            $payloadFiles[] = ['name' => basename((string) $name), 'content' => (string) $content];
        }

        $payload = [
            'language' => $runnerLanguage,
            'version' => (string) ($language['runner_version'] ?: '*'),
            'files' => $payloadFiles,
            'stdin' => mb_substr($stdin, 0, 10000),
            'compile_timeout' => 10000,
            'run_timeout' => 5000,
            'compile_cpu_time' => 10000,
            'run_cpu_time' => 5000,
            'compile_memory_limit' => 268435456,
            'run_memory_limit' => 134217728,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $token = trim((string) config('app.code_runner.token', ''));
        if ($token !== '') $headers[] = 'Authorization: Bearer ' . $token;

        try {
            [$status, $body] = self::post($endpoint, $json, $headers);
        } catch (Throwable) {
            throw new RunnerFallbackException('network');
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded) || $status < 200 || $status >= 300) throw new RunnerFallbackException('service');

        $compile = is_array($decoded['compile'] ?? null) ? $decoded['compile'] : [];
        $run = is_array($decoded['run'] ?? null) ? $decoded['run'] : [];
        $stdout = self::sanitiseProgramOutput((string) ($run['stdout'] ?? $run['output'] ?? ''), basename((string) ($language['main_file'] ?? 'main')));
        $stderr = self::sanitiseProgramOutput(trim((string) ($compile['stderr'] ?? '')), basename((string) ($language['main_file'] ?? 'main')));
        if ($stderr !== '' && trim((string) ($run['stderr'] ?? '')) !== '') $stderr .= "\n";
        $stderr .= self::sanitiseProgramOutput((string) ($run['stderr'] ?? ''), basename((string) ($language['main_file'] ?? 'main')));
        $message = self::sanitiseProgramOutput(trim(implode("\n", array_filter([(string) ($compile['message'] ?? ''), (string) ($run['message'] ?? '')]))), basename((string) ($language['main_file'] ?? 'main')));
        if ($message !== '') $stderr = trim($stderr . "\n" . $message);

        return [
            'status' => ((int) ($run['code'] ?? 0) === 0 && trim($stderr) === '') ? 'completed' : 'failed',
            'stdout' => mb_substr($stdout, 0, 50000),
            'stderr' => mb_substr($stderr, 0, 50000),
            'exit_code' => isset($run['code']) ? (int) $run['code'] : null,
            'execution_time_ms' => isset($run['wall_time']) ? (int) $run['wall_time'] : null,
            'memory_bytes' => isset($run['memory']) ? (int) $run['memory'] : null,
            'runtime' => strtolower((string) ($language['slug'] ?? $runnerLanguage)),
            'version' => (string) ($decoded['version'] ?? $language['runner_version'] ?? '*'),
            '_provider' => 'managed',
        ];
    }

    private static function post(string $url, string $body, array $headers): array
    {
        $timeout = max(5, min(45, (int) config('app.code_runner.timeout_seconds', 20)));
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle === false) throw new RuntimeException('The HTTP client could not be initialised.');
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 6,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $response = curl_exec($handle);
            if ($response === false) {
                $error = curl_error($handle);
                curl_close($handle);
                throw new RuntimeException('The code runner could not be reached: ' . $error);
            }
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            curl_close($handle);
            return [$status, (string) $response];
        }

        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) throw new RuntimeException('The code runner could not be reached.');
        $status = 200;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#HTTP/\S+\s+(\d{3})#', $header, $match)) $status = (int) $match[1];
        }
        return [$status, (string) $response];
    }
}
