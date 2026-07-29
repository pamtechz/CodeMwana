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
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
            self::$connection = new PDO('sqlite:' . $path, null, null, $options);
            self::$connection->exec('PRAGMA foreign_keys = ON');
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
}
