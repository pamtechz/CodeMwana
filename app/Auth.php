<?php

declare(strict_types=1);

final class Auth
{
    private static ?array $cachedUser = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetch('SELECT * FROM users WHERE email = ? AND status = ? LIMIT 1', [strtolower(trim($email)), 'active']);
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        self::$cachedUser = $user;
        Database::query('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?', [$user['id']]);
        return true;
    }

    public static function register(array $data): int
    {
        return Database::insert(
            'INSERT INTO users (name, username, email, password, role, age_group, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                trim($data['name']),
                strtolower(trim($data['username'])),
                strtolower(trim($data['email'])),
                password_hash($data['password'], PASSWORD_DEFAULT),
                'learner',
                $data['age_group'],
                'active',
            ]
        );
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        self::$cachedUser = Database::fetch(
            'SELECT id, name, username, email, role, age_group, avatar, points, streak_days, last_login_at, created_at FROM users WHERE id = ? LIMIT 1',
            [(int) $_SESSION['user_id']]
        );
        return self::$cachedUser;
    }

    public static function loginById(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        self::$cachedUser = null;
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
