<?php

declare(strict_types=1);

final class DatabaseFixed
{
    public static function tableExists(string $table): bool
    {
        if ($table === '' || !preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
        try {
            if (Database::driver() === 'sqlite') {
                return (bool) Database::scalar("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1", [$table], false);
            }
            return (bool) Database::scalar('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1', [$table], false);
        } catch (Throwable) {
            return false;
        }
    }
}
