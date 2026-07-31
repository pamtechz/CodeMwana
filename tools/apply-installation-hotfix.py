from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def replace_once(path: str, old: str, new: str) -> None:
    target = ROOT / path
    text = target.read_text(encoding='utf-8')
    if old not in text:
        raise SystemExit(f'Expected source block not found in {path}')
    target.write_text(text.replace(old, new, 1), encoding='utf-8')


database_old = '''    public static function tableExists(string $table): bool
    {
        try {
            if (self::driver() === 'sqlite') {
                return (bool) self::fetch("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?", [$table]);
            }
            return (bool) self::fetch('SHOW TABLES LIKE ?', [$table]);
        } catch (Throwable) {
            return false;
        }
    }
'''

database_new = '''    public static function tableExists(string $table): bool
    {
        if ($table === '' || !preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
        try {
            if (self::driver() === 'sqlite') {
                return (bool) self::scalar(
                    "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1",
                    [$table],
                    false
                );
            }
            return (bool) self::scalar(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$table],
                false
            );
        } catch (Throwable) {
            return false;
        }
    }
'''
replace_once('app/Database.php', database_old, database_new)

setup_old = '''            require_once base_path('database/seed.php');
            seed_database($pdo, $data);
            Installation::markInstalled(Migrator::VERSION);
            clear_old();
'''
setup_new = '''            require_once base_path('database/seed.php');
            seed_database($pdo, $data);

            // Reconnect before reporting success. This catches wrong database
            // selection, failed persistence and host-specific table probes.
            Database::reset();
            $verifiedAdministrator = Database::fetch(
                "SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND role = 'admin' AND status = 'active' LIMIT 1",
                [$data['email']]
            );
            if (!Database::tableExists('users') || !Database::tableExists('site_settings') || !$verifiedAdministrator) {
                throw new RuntimeException('Installation data could not be verified after reconnecting to the configured database.');
            }

            Installation::markInstalled(Migrator::VERSION);
            clear_old();
'''
replace_once('setup.php', setup_old, setup_new)

smoke_anchor = '''$installation = (string) file_get_contents($root . '/app/Installation.php');
foreach (['installed.lock', 'setup_exists', 'database_error'] as $declaration) {
    if (!str_contains($installation, $declaration)) $failures[] = "Intelligent installation service is missing: {$declaration}";
}
'''
smoke_new = smoke_anchor + '''
$database = (string) file_get_contents($root . '/app/Database.php');
if (!str_contains($database, 'information_schema.tables')) $failures[] = 'MySQL table detection must use information_schema.tables.';
if (str_contains($database, "SHOW TABLES LIKE ?")) $failures[] = 'Native prepared SHOW TABLES detection must not be used.';
if (!str_contains($database, 'table_schema = DATABASE()')) $failures[] = 'MySQL table detection must be scoped to the configured database.';

$setupSource = (string) file_get_contents($root . '/setup.php');
foreach (['Database::reset()', 'verifiedAdministrator', 'Installation data could not be verified'] as $declaration) {
    if (!str_contains($setupSource, $declaration)) $failures[] = "Post-install database verification is missing: {$declaration}";
}
'''
replace_once('tests/smoke.php', smoke_anchor, smoke_new)

for unwanted in ['app/_installation_probe.tmp', 'app/DatabaseFixed.php']:
    path = ROOT / unwanted
    if path.exists():
        path.unlink()

print('Installation detection hotfix applied.')
