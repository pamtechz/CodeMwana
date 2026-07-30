<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $driver = (string) config('database.driver', 'mysql');
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($driver === 'sqlite') {
            $path = (string) config('database.sqlite.path');
            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('The SQLite storage directory could not be created.');
            }
            self::$connection = new PDO('sqlite:' . $path, null, null, $options);
            self::$connection->exec('PRAGMA foreign_keys = ON');
            self::$connection->exec('PRAGMA busy_timeout = 5000');
            return self::$connection;
        }

        $mysql = config('database.mysql', []);
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $mysql['host'],
            $mysql['port'],
            $mysql['database'],
            $mysql['charset']
        );
        self::$connection = new PDO($dsn, $mysql['username'], $mysql['password'], $options);
        return self::$connection;
    }

    public static function driver(): string
    {
        return (string) self::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function scalar(string $sql, array $params = [], mixed $default = 0): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? $default : $value;
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::query($sql, $params);
        return (int) self::connection()->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function tableExists(string $table): bool
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

    public static function reset(): void
    {
        self::$connection = null;
    }
}
