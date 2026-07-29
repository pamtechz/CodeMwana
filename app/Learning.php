<?php

declare(strict_types=1);

final class Learning
{
    public static function courses(): array
    {
        return Database::fetchAll(
            'SELECT c.*, COUNT(l.id) AS lesson_count FROM courses c LEFT JOIN lessons l ON l.course_id = c.id AND l.is_published = 1 WHERE c.is_published = 1 GROUP BY c.id ORDER BY c.sort_order, c.id'
        );
    }

    public static function course(int $courseId): ?array
    {
        return Database::fetch('SELECT * FROM courses WHERE id = ? AND is_published = 1', [$courseId]);
    }

    public static function lessonsForCourse(int $courseId, ?int $userId = null): array
    {
        if ($userId) {
            return Database::fetchAll(
                'SELECT l.*, COALESCE(p.status, ?) AS progress_status, COALESCE(p.best_score, 0) AS best_score
                 FROM lessons l
                 LEFT JOIN progress p ON p.lesson_id = l.id AND p.user_id = ?
                 WHERE l.course_id = ? AND l.is_published = 1
                 ORDER BY l.sort_order, l.id',
                ['not_started', $userId, $courseId]
            );
        }
        return Database::fetchAll('SELECT * FROM lessons WHERE course_id = ? AND is_published = 1 ORDER BY sort_order, id', [$courseId]);
    }

    public static function lesson(int $lessonId): ?array
    {
        return Database::fetch(
            'SELECT l.*, c.title AS course_title, c.slug AS course_slug, c.icon AS course_icon FROM lessons l JOIN courses c ON c.id = l.course_id WHERE l.id = ? AND l.is_published = 1',
            [$lessonId]
        );
    }

    public static function questions(int $lessonId): array
    {
        return Database::fetchAll('SELECT id, question, option_a, option_b, option_c, option_d FROM quiz_questions WHERE lesson_id = ? ORDER BY sort_order, id', [$lessonId]);
    }

    public static function markLessonStarted(int $userId, int $lessonId): void
    {
        $existing = Database::fetch('SELECT id FROM progress WHERE user_id = ? AND lesson_id = ?', [$userId, $lessonId]);
        if ($existing) {
            Database::query('UPDATE progress SET status = CASE WHEN status = ? THEN ? ELSE status END, last_accessed_at = CURRENT_TIMESTAMP WHERE id = ?', ['not_started', 'in_progress', $existing['id']]);
            return;
        }
        Database::query('INSERT INTO progress (user_id, lesson_id, status, last_accessed_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)', [$userId, $lessonId, 'in_progress']);
    }

    public static function submitQuiz(int $userId, int $lessonId, array $answers): array
    {
        $questions = Database::fetchAll('SELECT id, correct_option, explanation FROM quiz_questions WHERE lesson_id = ? ORDER BY sort_order, id', [$lessonId]);
        $correct = 0;
        $feedback = [];
        foreach ($questions as $question) {
            $answer = strtoupper((string) ($answers[$question['id']] ?? ''));
            $isCorrect = $answer === strtoupper((string) $question['correct_option']);
            if ($isCorrect) {
                $correct++;
            }
            $feedback[(int) $question['id']] = [
                'correct' => $isCorrect,
                'selected' => $answer,
                'correct_option' => $question['correct_option'],
                'explanation' => $question['explanation'],
            ];
        }
        $total = count($questions);
        $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
        $passed = $score >= 60;

        Database::transaction(function () use ($userId, $lessonId, $answers, $score, $passed): void {
            Database::query(
                'INSERT INTO quiz_attempts (user_id, lesson_id, score, answers_json, passed) VALUES (?, ?, ?, ?, ?)',
                [$userId, $lessonId, $score, json_encode($answers, JSON_UNESCAPED_UNICODE), $passed ? 1 : 0]
            );
            $existing = Database::fetch('SELECT id, best_score FROM progress WHERE user_id = ? AND lesson_id = ?', [$userId, $lessonId]);
            if ($existing) {
                Database::query(
                    'UPDATE progress SET status = ?, best_score = ?, completed_at = CASE WHEN ? = 1 THEN CURRENT_TIMESTAMP ELSE completed_at END, last_accessed_at = CURRENT_TIMESTAMP WHERE id = ?',
                    [$passed ? 'completed' : 'in_progress', max((int) $existing['best_score'], $score), $passed ? 1 : 0, $existing['id']]
                );
            } else {
                Database::query(
                    'INSERT INTO progress (user_id, lesson_id, status, best_score, completed_at, last_accessed_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)',
                    [$userId, $lessonId, $passed ? 'completed' : 'in_progress', $score, $passed ? date('Y-m-d H:i:s') : null]
                );
            }
            if ($passed) {
                Database::query('UPDATE users SET points = points + 20 WHERE id = ?', [$userId]);
            }
        });

        self::awardBadges($userId);
        return compact('score', 'correct', 'total', 'passed', 'feedback');
    }

    public static function dashboardStats(int $userId): array
    {
        $completed = Database::fetch('SELECT COUNT(*) AS total FROM progress WHERE user_id = ? AND status = ?', [$userId, 'completed']);
        $lessons = Database::fetch('SELECT COUNT(*) AS total FROM lessons WHERE is_published = 1');
        $projects = Database::fetch('SELECT COUNT(*) AS total FROM projects WHERE user_id = ?', [$userId]);
        $average = Database::fetch('SELECT ROUND(AVG(best_score)) AS average FROM progress WHERE user_id = ? AND best_score > 0', [$userId]);
        $recent = Database::fetchAll(
            'SELECT l.id, l.title, l.difficulty, p.status, p.best_score, p.last_accessed_at FROM progress p JOIN lessons l ON l.id = p.lesson_id WHERE p.user_id = ? ORDER BY p.last_accessed_at DESC LIMIT 5',
            [$userId]
        );
        $next = Database::fetch(
            'SELECT l.*, c.title AS course_title FROM lessons l JOIN courses c ON c.id = l.course_id WHERE l.is_published = 1 AND NOT EXISTS (SELECT 1 FROM progress p WHERE p.lesson_id = l.id AND p.user_id = ? AND p.status = ?) ORDER BY c.sort_order, l.sort_order LIMIT 1',
            [$userId, 'completed']
        );
        return [
            'completed' => (int) ($completed['total'] ?? 0),
            'lessons' => (int) ($lessons['total'] ?? 0),
            'projects' => (int) ($projects['total'] ?? 0),
            'average' => (int) ($average['average'] ?? 0),
            'recent' => $recent,
            'next' => $next,
        ];
    }

    public static function saveProject(int $userId, array $data): int
    {
        $title = trim((string) ($data['title'] ?? 'My MwanaCode Project'));
        $code = (string) ($data['code'] ?? '');
        $projectId = (int) ($data['id'] ?? 0);
        if ($projectId > 0) {
            $project = Database::fetch('SELECT id FROM projects WHERE id = ? AND user_id = ?', [$projectId, $userId]);
            if (!$project) {
                throw new RuntimeException('Project not found.');
            }
            Database::query('UPDATE projects SET title = ?, code = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$title, $code, $projectId]);
            return $projectId;
        }
        $id = Database::insert('INSERT INTO projects (user_id, title, language, code) VALUES (?, ?, ?, ?)', [$userId, $title, 'mwanacode', $code]);
        Database::query('UPDATE users SET points = points + 5 WHERE id = ?', [$userId]);
        self::awardBadges($userId);
        return $id;
    }

    public static function projects(int $userId): array
    {
        return Database::fetchAll('SELECT * FROM projects WHERE user_id = ? ORDER BY updated_at DESC, id DESC', [$userId]);
    }

    public static function project(int $projectId, int $userId): ?array
    {
        return Database::fetch('SELECT * FROM projects WHERE id = ? AND user_id = ?', [$projectId, $userId]);
    }

    public static function leaderboard(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        return Database::fetchAll("SELECT id, name, username, avatar, points, streak_days FROM users WHERE role = 'learner' AND status = 'active' ORDER BY points DESC, name ASC LIMIT {$limit}");
    }

    public static function badges(int $userId): array
    {
        return Database::fetchAll(
            'SELECT b.*, ub.awarded_at, CASE WHEN ub.id IS NULL THEN 0 ELSE 1 END AS earned FROM badges b LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = ? ORDER BY b.id',
            [$userId]
        );
    }

    public static function awardBadges(int $userId): void
    {
        $stats = [
            'lessons' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM progress WHERE user_id = ? AND status = ?', [$userId, 'completed'])['total'] ?? 0),
            'projects' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM projects WHERE user_id = ?', [$userId])['total'] ?? 0),
            'perfect' => (int) (Database::fetch('SELECT COUNT(*) AS total FROM quiz_attempts WHERE user_id = ? AND score = 100', [$userId])['total'] ?? 0),
        ];
        $badges = Database::fetchAll('SELECT * FROM badges');
        foreach ($badges as $badge) {
            $earned = match ($badge['code']) {
                'FIRST_STEP' => $stats['lessons'] >= 1,
                'CODE_EXPLORER' => $stats['projects'] >= 1,
                'QUIZ_STAR' => $stats['perfect'] >= 1,
                'LESSON_MASTER' => $stats['lessons'] >= 5,
                default => false,
            };
            if ($earned) {
                $exists = Database::fetch('SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?', [$userId, $badge['id']]);
                if (!$exists) {
                    Database::query('INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)', [$userId, $badge['id']]);
                    Database::query('UPDATE users SET points = points + ? WHERE id = ?', [(int) $badge['points'], $userId]);
                }
            }
        }
    }
}
