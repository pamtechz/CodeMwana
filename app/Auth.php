<?php

declare(strict_types=1);

final class Auth
{
    private static ?array $cachedUser = null;

    public static function attempt(string $identifier, string $password): array
    {
        $identifier = strtolower(trim($identifier));
        $ipHash = self::ipHash();
        $limit = (int) config('app.login_limit', 5);
        $window = (int) config('app.login_window_minutes', 15);

        $threshold = date('Y-m-d H:i:s', time() - ($window * 60));
        $failed = (int) Database::scalar(
            'SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND ip_hash = ? AND was_successful = 0 AND attempted_at >= ?',
            [$identifier, $ipHash, $threshold]
        );
        if ($failed >= $limit) {
            return ['ok' => false, 'message' => "Too many unsuccessful attempts. Try again after {$window} minutes."];
        }

        $user = Database::fetch(
            'SELECT * FROM users WHERE (LOWER(email) = ? OR LOWER(username) = ?) LIMIT 1',
            [$identifier, $identifier]
        );
        $successful = $user && $user['status'] === 'active' && password_verify($password, $user['password']);
        Database::query(
            'INSERT INTO login_attempts (identifier, ip_hash, was_successful) VALUES (?, ?, ?)',
            [$identifier, $ipHash, $successful ? 1 : 0]
        );

        if (!$successful) {
            $message = $user && $user['status'] !== 'active'
                ? 'This account is not active. Contact a platform administrator.'
                : 'The email, username or password is incorrect.';
            return ['ok' => false, 'message' => $message];
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['last_activity_at'] = time();
        self::$cachedUser = null;
        Database::query('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?', [$user['id']]);
        activity('signed_in', ['method' => 'password'], (int) $user['id']);
        Learning::awardEligibleBadges((int) $user['id']);
        return ['ok' => true, 'message' => null];
    }

    public static function register(array $data): int
    {
        $userId = Database::insert(
            'INSERT INTO users (name, username, email, password, role, age_group, school_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                trim($data['name']),
                strtolower(trim($data['username'])),
                strtolower(trim($data['email'])),
                password_hash($data['password'], PASSWORD_DEFAULT),
                'learner',
                $data['age_group'],
                trim($data['school_name']),
                'active',
            ]
        );
        activity('account_created', ['role' => 'learner'], $userId);
        return $userId;
    }

    public static function createStaff(array $data): int
    {
        return Database::insert(
            'INSERT INTO users (name, username, email, password, role, age_group, school_name, status) VALUES (?, ?, ?, ?, ?, NULL, ?, ?)',
            [
                trim($data['name']),
                strtolower(trim($data['username'])),
                strtolower(trim($data['email'])),
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['role'],
                trim($data['school_name']),
                'active',
            ]
        );
    }

    public static function check(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        $idleLimit = 60 * 60 * 4;
        if (isset($_SESSION['last_activity_at']) && time() - (int) $_SESSION['last_activity_at'] > $idleLimit) {
            self::logout();
            return false;
        }
        $_SESSION['last_activity_at'] = time();
        return self::user() !== null;
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
            'SELECT id, name, username, email, role, age_group, school_name, avatar, points, streak_days, status, last_login_at, created_at FROM users WHERE id = ? AND status = ? LIMIT 1',
            [(int) $_SESSION['user_id'], 'active']
        );
        return self::$cachedUser;
    }

    public static function loginById(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity_at'] = time();
        self::$cachedUser = null;
    }

    public static function logout(): void
    {
        if (isset($_SESSION['user_id']) && Database::tableExists('activity_log')) {
            activity('signed_out', [], (int) $_SESSION['user_id']);
        }
        self::$cachedUser = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    private static function ipHash(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'local');
        return hash('sha256', $ip . '|' . (string) config('app.name', 'CodeMwana'));
    }
}
