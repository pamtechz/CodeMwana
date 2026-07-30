<?php

declare(strict_types=1);

final class Installation
{
    private static ?array $cachedState = null;

    public static function lockPath(): string
    {
        return base_path('storage/installed.lock');
    }

    public static function state(bool $refresh = false): array
    {
        if (!$refresh && self::$cachedState !== null) return self::$cachedState;

        $state = [
            'installed' => false,
            'setup_exists' => is_file(base_path('setup.php')),
            'lock_exists' => is_file(self::lockPath()),
            'database_error' => false,
            'message' => null,
            'schema_version' => null,
        ];

        try {
            Database::connection();
            $required = ['users', 'site_settings'];
            foreach ($required as $table) {
                if (!Database::tableExists($table)) {
                    self::$cachedState = $state;
                    return $state;
                }
            }

            $userCount = (int) Database::scalar('SELECT COUNT(*) FROM users', [], 0);
            $state['schema_version'] = Database::tableExists('schema_meta')
                ? (string) Database::scalar('SELECT schema_version FROM schema_meta ORDER BY id DESC LIMIT 1', [], '')
                : 'legacy';
            $state['installed'] = $userCount > 0;

            if ($state['installed'] && !$state['lock_exists']) {
                self::markInstalled($state['schema_version'] ?: (string) config('app.version', '3.0.0'));
                $state['lock_exists'] = is_file(self::lockPath());
            }
        } catch (Throwable $exception) {
            $state['database_error'] = true;
            $state['message'] = $exception->getMessage();
        }

        self::$cachedState = $state;
        return $state;
    }

    public static function installed(): bool
    {
        return (bool) self::state()['installed'];
    }

    public static function markInstalled(string $schemaVersion = '3.0.0'): void
    {
        $directory = dirname(self::lockPath());
        if (!is_dir($directory)) @mkdir($directory, 0775, true);
        $payload = json_encode([
            'installed_at' => date(DATE_ATOM),
            'schema_version' => $schemaVersion,
            'database_driver' => (string) config('database.driver', 'mysql'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($payload !== false) @file_put_contents(self::lockPath(), $payload . PHP_EOL, LOCK_EX);
        self::$cachedState = null;
    }

    public static function enforce(): void
    {
        if (PHP_SAPI === 'cli') return;

        $script = basename((string) ($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
        if (in_array($script, ['setup.php', 'system-status.php'], true)) return;

        $state = self::state();
        if ($state['installed']) return;

        if ($state['database_error'] && $state['lock_exists']) {
            self::renderFailure(
                'The platform database is temporarily unavailable',
                'CodeMwana has already been installed, so the application will not redirect to the installer. Check the database service and the values in .env, then reload this page.',
                $state['message'],
                503
            );
        }

        if ($state['setup_exists']) redirect('setup.php');

        self::renderFailure(
            'Installation could not be confirmed',
            'The database does not contain a complete CodeMwana installation and setup.php is not present. Restore setup.php from the release package, confirm the database settings in .env, and run the installer once.',
            $state['message'],
            503
        );
    }

    public static function migrationFailure(Throwable $exception): never
    {
        self::renderFailure(
            'The database upgrade could not be completed',
            'CodeMwana detected an existing installation but could not apply the required schema upgrade. Back up the database, confirm that the database user can alter tables, then reload the platform.',
            $exception->getMessage(),
            503
        );
    }

    private static function renderFailure(string $title, string $message, ?string $technical, int $status): never
    {
        http_response_code($status);
        $showTechnical = (bool) config('app.debug', false);
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeTechnical = htmlspecialchars((string) $technical, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $statusUrl = htmlspecialchars(url('system-status.php'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CodeMwana system status</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:#f4f5fa;color:#172033;font-family:system-ui,sans-serif}.card{width:min(100%,680px);padding:clamp(24px,5vw,46px);border:1px solid #e0e3ec;border-radius:24px;background:#fff;box-shadow:0 24px 80px rgba(27,31,55,.12)}.mark{display:grid;place-items:center;width:54px;height:54px;border-radius:16px;background:#efedff;color:#5b4bdb;font-weight:900}.eyebrow{margin:24px 0 8px;color:#5b4bdb;font-size:.72rem;font-weight:850;letter-spacing:.12em;text-transform:uppercase}h1{margin:0;font-size:clamp(1.8rem,5vw,3rem);line-height:1.08}p{color:#667085;line-height:1.7}.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:24px}a,button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 16px;border:1px solid #5b4bdb;border-radius:12px;background:#5b4bdb;color:#fff;text-decoration:none;font-weight:800}button{background:#fff;color:#172033;border-color:#dfe2ea;cursor:pointer}code{display:block;overflow:auto;padding:13px;border-radius:12px;background:#151725;color:#f4f4fa;font-size:.78rem}</style></head><body><main class="card"><div class="mark">CM</div><p class="eyebrow">Intelligent installation check</p><h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p>' . ($showTechnical && $safeTechnical !== '' ? '<code>' . $safeTechnical . '</code>' : '') . '<div class="actions"><button type="button" onclick="location.reload()">Check again</button><a href="' . $statusUrl . '">Open system status</a></div></main></body></html>';
        exit;
    }
}
