<?php

declare(strict_types=1);

final class CodeRunner
{
    public static function configured(): bool
    {
        return trim((string) config('app.code_runner.url', '')) !== '';
    }

    public static function endpoint(): string
    {
        $base = rtrim(trim((string) config('app.code_runner.url', '')), '/');
        if ($base === '') throw new RuntimeException('The remote code runner is not configured. Add CODE_RUNNER_URL to .env.');
        if (!preg_match('#^https?://#i', $base)) throw new RuntimeException('CODE_RUNNER_URL must use HTTP or HTTPS.');
        if (str_ends_with($base, '/execute')) return $base;
        if (str_ends_with($base, '/api/v2')) return $base . '/execute';
        return $base . '/api/v2/execute';
    }

    public static function run(array $language, array $files, string $stdin = ''): array
    {
        if (($language['execution_mode'] ?? '') !== 'remote') {
            throw new RuntimeException('This language runs in the browser preview rather than the remote runner.');
        }
        $runnerLanguage = trim((string) ($language['runner_language'] ?? ''));
        if ($runnerLanguage === '') throw new RuntimeException('No runtime has been assigned to this language.');

        $payloadFiles = [];
        foreach ($files as $name => $content) {
            $payloadFiles[] = ['name' => basename((string) $name), 'content' => (string) $content];
        }
        if (!$payloadFiles) throw new InvalidArgumentException('At least one source file is required.');

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

        [$status, $body] = self::post(self::endpoint(), $json, $headers);
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) throw new RuntimeException('The code runner returned an invalid response.');
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException((string) ($decoded['message'] ?? 'The code runner rejected the request.'));
        }

        $compile = is_array($decoded['compile'] ?? null) ? $decoded['compile'] : [];
        $run = is_array($decoded['run'] ?? null) ? $decoded['run'] : [];
        $stdout = (string) ($run['stdout'] ?? $run['output'] ?? '');
        $stderr = trim((string) ($compile['stderr'] ?? ''));
        if ($stderr !== '' && trim((string) ($run['stderr'] ?? '')) !== '') $stderr .= "\n";
        $stderr .= (string) ($run['stderr'] ?? '');
        $message = trim(implode("\n", array_filter([(string) ($compile['message'] ?? ''), (string) ($run['message'] ?? '')])));
        if ($message !== '') $stderr = trim($stderr . "\n" . $message);

        return [
            'status' => ((int) ($run['code'] ?? 0) === 0 && trim($stderr) === '') ? 'completed' : 'failed',
            'stdout' => mb_substr($stdout, 0, 50000),
            'stderr' => mb_substr($stderr, 0, 50000),
            'exit_code' => isset($run['code']) ? (int) $run['code'] : null,
            'execution_time_ms' => isset($run['wall_time']) ? (int) $run['wall_time'] : null,
            'memory_bytes' => isset($run['memory']) ? (int) $run['memory'] : null,
            'runtime' => (string) ($decoded['language'] ?? $runnerLanguage),
            'version' => (string) ($decoded['version'] ?? $language['runner_version'] ?? '*'),
        ];
    }

    private static function post(string $url, string $body, array $headers): array
    {
        $timeout = max(5, min(30, (int) config('app.code_runner.timeout_seconds', 15)));
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle === false) throw new RuntimeException('The HTTP client could not be initialised.');
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
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
        if ($response === false) throw new RuntimeException('The code runner could not be reached. Enable cURL for clearer connection diagnostics.');
        $status = 200;
        foreach ($http_response_header ?? [] as $header) {
            if (preg_match('#HTTP/\S+\s+(\d{3})#', $header, $match)) $status = (int) $match[1];
        }
        return [$status, (string) $response];
    }
}
