<?php

declare(strict_types=1);

final class Migrator
{
    public const VERSION = '3.0.0';

    public static function run(): void
    {
        self::ensureSchemaMeta();
        $current = (string) Database::scalar('SELECT schema_version FROM schema_meta ORDER BY id DESC LIMIT 1', [], '0.0.0');
        if (version_compare($current, self::VERSION, '>=')) {
            self::seedLanguages();
            return;
        }

        $driver = Database::driver();
        $pdo = Database::connection();

        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS programming_languages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                slug VARCHAR(40) NOT NULL UNIQUE,
                name VARCHAR(80) NOT NULL,
                short_name VARCHAR(20) NOT NULL,
                category VARCHAR(60) NOT NULL,
                description TEXT NOT NULL,
                editor_mode VARCHAR(30) NOT NULL,
                execution_mode VARCHAR(30) NOT NULL,
                runner_language VARCHAR(40) NULL,
                runner_version VARCHAR(30) NULL,
                main_file VARCHAR(120) NOT NULL,
                colour VARCHAR(20) NOT NULL,
                starter_files_json TEXT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS code_runs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                project_id INTEGER NULL,
                language_slug VARCHAR(40) NOT NULL,
                status VARCHAR(30) NOT NULL,
                stdin_text TEXT NULL,
                stdout_text TEXT NULL,
                stderr_text TEXT NULL,
                exit_code INT NULL,
                execution_time_ms INT NULL,
                memory_bytes INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_code_runs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_code_runs_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS programming_languages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(40) NOT NULL UNIQUE,
                name VARCHAR(80) NOT NULL,
                short_name VARCHAR(20) NOT NULL,
                category VARCHAR(60) NOT NULL,
                description TEXT NOT NULL,
                editor_mode VARCHAR(30) NOT NULL,
                execution_mode VARCHAR(30) NOT NULL,
                runner_language VARCHAR(40) NULL,
                runner_version VARCHAR(30) NULL,
                main_file VARCHAR(120) NOT NULL,
                colour VARCHAR(20) NOT NULL,
                starter_files_json JSON NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_languages_active_order (is_active, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $pdo->exec("CREATE TABLE IF NOT EXISTS code_runs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                project_id INT UNSIGNED NULL,
                language_slug VARCHAR(40) NOT NULL,
                status VARCHAR(30) NOT NULL,
                stdin_text TEXT NULL,
                stdout_text MEDIUMTEXT NULL,
                stderr_text MEDIUMTEXT NULL,
                exit_code INT NULL,
                execution_time_ms INT NULL,
                memory_bytes BIGINT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_code_runs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_code_runs_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
                INDEX idx_code_runs_user_date (user_id, created_at),
                INDEX idx_code_runs_language (language_slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        self::addColumn('projects', 'workspace_json', $driver === 'sqlite' ? 'TEXT NULL' : 'JSON NULL');
        self::addColumn('projects', 'stdin', 'TEXT NULL');
        self::addColumn('project_versions', 'language', "VARCHAR(40) NOT NULL DEFAULT 'mwanacode'");
        self::addColumn('project_versions', 'workspace_json', $driver === 'sqlite' ? 'TEXT NULL' : 'JSON NULL');
        self::addColumn('project_versions', 'stdin', 'TEXT NULL');

        self::seedLanguages();
        if (Database::tableExists('home_features')) {
            Database::query("UPDATE home_features SET title = ?, description = ? WHERE title = ?", ['Multi-language Code Lab', 'Ten mainstream language workspaces combine sandboxed browser previews with an optional isolated compiler runner.', 'Safe code execution']);
            Database::query("UPDATE home_features SET title = ?, description = ? WHERE title = ?", ['Multi-file project workspace', 'Learners create real project files, provide standard input, run code and retain database-backed version history.', 'Creative coding canvas']);
        }
        Database::query('UPDATE schema_meta SET schema_version = ?, updated_at = CURRENT_TIMESTAMP', [self::VERSION]);
        Installation::markInstalled(self::VERSION);
    }

    private static function ensureSchemaMeta(): void
    {
        if (Database::tableExists('schema_meta')) return;
        if (Database::driver() === 'sqlite') {
            Database::connection()->exec("CREATE TABLE schema_meta (id INTEGER PRIMARY KEY AUTOINCREMENT, schema_version VARCHAR(20) NOT NULL, installed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        } else {
            Database::connection()->exec("CREATE TABLE schema_meta (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, schema_version VARCHAR(20) NOT NULL, installed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        Database::query('INSERT INTO schema_meta (schema_version) VALUES (?)', ['2.0.0']);
    }

    public static function seedLanguages(): void
    {
        if (!Database::tableExists('programming_languages')) return;
        foreach (LanguageCatalog::definitions() as $language) {
            $json = json_encode($language['files'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $existing = Database::fetch('SELECT id FROM programming_languages WHERE slug = ?', [$language['slug']]);
            $params = [
                $language['name'], $language['short_name'], $language['category'], $language['description'],
                $language['editor_mode'], $language['execution_mode'], $language['runner_language'],
                $language['runner_version'], $language['main_file'], $language['colour'], $json,
                $language['sort_order'], 1,
            ];
            if ($existing) {
                Database::query('UPDATE programming_languages SET name=?, short_name=?, category=?, description=?, editor_mode=?, execution_mode=?, runner_language=?, runner_version=?, main_file=?, colour=?, starter_files_json=?, sort_order=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?', [...$params, $existing['id']]);
            } else {
                Database::query('INSERT INTO programming_languages (name, short_name, category, description, editor_mode, execution_mode, runner_language, runner_version, main_file, colour, starter_files_json, sort_order, is_active, slug) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [...$params, $language['slug']]);
            }
        }
    }

    private static function addColumn(string $table, string $column, string $definition): void
    {
        if (self::columnExists($table, $column)) return;
        Database::connection()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    private static function columnExists(string $table, string $column): bool
    {
        if (Database::driver() === 'sqlite') {
            foreach (Database::fetchAll("PRAGMA table_info({$table})") as $row) {
                if (($row['name'] ?? null) === $column) return true;
            }
            return false;
        }
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
            0
        ) > 0;
    }
}
