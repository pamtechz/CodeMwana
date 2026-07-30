<?php

declare(strict_types=1);

final class Settings
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function all(): array
    {
        if (self::$loaded) {
            return self::$cache;
        }
        self::$loaded = true;
        if (!Database::tableExists('site_settings')) {
            return self::$cache;
        }
        foreach (Database::fetchAll('SELECT setting_key, setting_value FROM site_settings') as $row) {
            self::$cache[$row['setting_key']] = $row['setting_value'];
        }
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::all();
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    public static function set(string $key, string $value): void
    {
        $existing = Database::fetch('SELECT id FROM site_settings WHERE setting_key = ?', [$key]);
        if ($existing) {
            Database::query('UPDATE site_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$value, $existing['id']]);
        } else {
            Database::query('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)', [$key, $value]);
        }
        self::$cache[$key] = $value;
        self::$loaded = true;
    }

    public static function reset(): void
    {
        self::$cache = [];
        self::$loaded = false;
    }
}
