<?php

declare(strict_types=1);

final class Learning
{
    public static function publicStatistics(): array
    {
        if (!Database::tableExists('courses')) {
            return ['courses' => 0, 'lessons' => 0, 'learners' => 0, 'projects' => 0, 'languages' => 10];
        }
        return [
            'courses' => (int) Database::scalar("SELECT COUNT(*) FROM courses WHERE is_published = 1"),
            'lessons' => (int) Database::scalar("SELECT COUNT(*) FROM lessons WHERE is_published = 1"),
            'learners' => (int) Database::scalar("SELECT COUNT(*) FROM users WHERE role = 'learner' AND status = 'active'"),
            'projects' => (int) Database::scalar('SELECT COUNT(*) FROM projects'),
            'languages' => Database::tableExists('programming_languages') ? (int) Database::scalar('SELECT COUNT(*) FROM programming_languages WHERE is_active = 1') : count(LanguageCatalog::definitions()),
        ];
    }

    public static function homeFeatures(): array
    {
        if (!Database::tableExists('home_features')) return [];
        return Database::fetchAll('SELECT * FROM home_features WHERE is_active = 1 ORDER BY sort_order, id');
    }

    public static function courses(?int $userId = null): array
    {
        if ($userId) {
            return Database::fetchAll(
                "SELECT c.*, COUNT(DISTINCT l.id) AS lesson_count,
                        COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN p.lesson_id END) AS completed_count,
                        MAX(CASE WHEN e.user_id IS NULL THEN 0 ELSE 1 END) AS is_enrolled
                 FROM courses c
                 LEFT JOIN lessons l ON l.course_id = c.id AND l.is_published = 1
                 LEFT JOIN progress p ON p.lesson_id = l.id AND p.user_id = ?
                 LEFT JOIN course_enrollments e ON e.course_id = c.id AND e.user_id = ? AND e.status = 'active'
                 WHERE c.is_published = 1
                 GROUP BY c.id
                 ORDER BY c.sort_order, c.id",
                [$userId, $userId]
            );
        }
        return Database::fetchAll(
            "SELECT c.*, COUNT(l.id) AS lesson_count
             FROM courses c
             LEFT JOIN lessons l ON l.course_id = c.id AND l.is_published = 1
             WHERE c.is_published = 1
             GROUP BY c.id
             ORDER BY c.sort_order, c.id"
        );
    }

    public static function course(int|string $identifier): ?array
    {
        $field = is_int($identifier) || ctype_digit((string) $identifier) ? 'c.id' : 'c.slug';
        return Database::fetch(
            "SELECT c.*, COUNT(l.id) AS lesson_count
             FROM courses c LEFT JOIN lessons l ON l.course_id = c.id AND l.is_published = 1
             WHERE {$field} = ? AND c.is_published = 1 GROUP BY c.id",
            [$identifier]
        );
    }

    public static function enroll(int $userId, int $courseId): void
    {
        $existing = Database::fetch('SELECT id FROM course_enrollments WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
        if ($existing) {
            Database::query("UPDATE course_enrollments SET status = 'active', enrolled_at = CURRENT_TIMESTAMP WHERE id = ?", [$existing['id']]);
        } else {
            Database::query('INSERT INTO course_enrollments (user_id, course_id, status) VALUES (?, ?, ?)', [$userId, $courseId, 'active']);
        }
        activity('course_enrolled', ['course_id' => $courseId], $userId);
    }

    public static function lessonsForCourse(int $courseId, ?int $userId = null): array
    {
        if ($userId) {
            return Database::fetchAll(
                "SELECT l.*, COALESCE(p.status, 'not_started') AS progress_status,
                        COALESCE(p.best_score, 0) AS best_score, p.last_accessed_at
                 FROM lessons l
                 LEFT JOIN progress p ON p.lesson_id = l.id AND p.user_id = ?
                 WHERE l.course_id = ? AND l.is_published = 1
                 ORDER BY l.sort_order, l.id",
                [$userId, $courseId]
            );
        }
        return Database::fetchAll('SELECT * FROM lessons WHERE course_id = ? AND is_published = 1 ORDER BY sort_order, id', [$courseId]);
    }

    public static function lesson(int|string $identifier): ?array
    {
        $field = is_int($identifier) || ctype_digit((string) $identifier) ? 'l.id' : 'l.slug';
        return Database::fetch(
            "SELECT l.*, c.title AS course_title, c.slug AS course_slug, c.icon AS course_icon,
                    c.colour AS course_colour, c.id AS course_id
             FROM lessons l JOIN courses c ON c.id = l.course_id
             WHERE {$field} = ? AND l.is_published = 1 AND c.is_published = 1",
            [$identifier]
        );
    }

    public static function lessonNavigation(int $courseId, int $sortOrder): array
    {
        return [
            'previous' => Database::fetch(
                'SELECT id, title, slug FROM lessons WHERE course_id = ? AND is_published = 1 AND sort_order < ? ORDER BY sort_order DESC LIMIT 1',
                [$courseId, $sortOrder]
            ),
            'next' => Database::fetch(
                'SELECT id, title, slug FROM lessons WHERE course_id = ? AND is_published = 1 AND sort_order > ? ORDER BY sort_order ASC LIMIT 1',
                [$courseId, $sortOrder]
            ),
        ];
    }

    public static function questions(int $lessonId, bool $withAnswers = false): array
    {
        $columns = $withAnswers
            ? 'id, question, option_a, option_b, option_c, option_d, correct_option, explanation'
            : 'id, question, option_a, option_b, option_c, option_d';
        return Database::fetchAll("SELECT {$columns} FROM quiz_questions WHERE lesson_id = ? ORDER BY sort_order, id", [$lessonId]);
    }

    public static function markLessonStarted(int $userId, int $lessonId): void
    {
        $lesson = self::lesson($lessonId);
        if (!$lesson) return;
        self::enrolSilently($userId, (int) $lesson['course_id']);
        $existing = Database::fetch('SELECT id, status FROM progress WHERE user_id = ? AND lesson_id = ?', [$userId, $lessonId]);
        if ($existing) {
            Database::query(
                "UPDATE progress SET status = CASE WHEN status = 'not_started' THEN 'in_progress' ELSE status END, last_accessed_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$existing['id']]
            );
        } else {
            Database::query("INSERT INTO progress (user_id, lesson_id, status, last_accessed_at) VALUES (?, ?, 'in_progress', CURRENT_TIMESTAMP)", [$userId, $lessonId]);
            activity('lesson_started', ['lesson_id' => $lessonId], $userId);
        }
    }

    public static function submitQuiz(int $userId, int $lessonId, array $answers): array
    {
        $questions = self::questions($lessonId, true);
        if (!$questions) {
            return ['score' => 0, 'passed' => false, 'correct' => 0, 'total' => 0, 'feedback' => []];
        }
        $correct = 0;
        $feedback = [];
        foreach ($questions as $question) {
            $selected = strtoupper(trim((string) ($answers[$question['id']] ?? '')));
            $isCorrect = $selected === strtoupper((string) $question['correct_option']);
            if ($isCorrect) $correct++;
            $feedback[(int) $question['id']] = [
                'correct' => $isCorrect,
                'selected' => $selected,
                'correct_option' => $question['correct_option'],
                'explanation' => $question['explanation'],
            ];
        }
        $total = count($questions);
        $score = (int) round(($correct / $total) * 100);
        $passed = $score >= 60;
        $wasCompleted = (bool) Database::fetch("SELECT id FROM progress WHERE user_id = ? AND lesson_id = ? AND status = 'completed'", [$userId, $lessonId]);
        $hadPerfect = (bool) Database::fetch('SELECT id FROM quiz_attempts WHERE user_id = ? AND lesson_id = ? AND score = 100', [$userId, $lessonId]);

        Database::transaction(function () use ($userId, $lessonId, $answers, $score, $passed, $wasCompleted, $hadPerfect): void {
            Database::query(
                'INSERT INTO quiz_attempts (user_id, lesson_id, score, answers_json, passed) VALUES (?, ?, ?, ?, ?)',
                [$userId, $lessonId, $score, json_encode($answers, JSON_UNESCAPED_UNICODE), $passed ? 1 : 0]
            );
            $progress = Database::fetch('SELECT id, best_score FROM progress WHERE user_id = ? AND lesson_id = ?', [$userId, $lessonId]);
            if ($progress) {
                Database::query(
                    "UPDATE progress SET status = ?, best_score = ?, completed_at = CASE WHEN ? = 1 THEN COALESCE(completed_at, CURRENT_TIMESTAMP) ELSE completed_at END, last_accessed_at = CURRENT_TIMESTAMP WHERE id = ?",
                    [$passed ? 'completed' : 'in_progress', max((int) $progress['best_score'], $score), $passed ? 1 : 0, $progress['id']]
                );
            } else {
                Database::query(
                    'INSERT INTO progress (user_id, lesson_id, status, best_score, completed_at, last_accessed_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)',
                    [$userId, $lessonId, $passed ? 'completed' : 'in_progress', $score, $passed ? date('Y-m-d H:i:s') : null]
                );
            }
            $points = 0;
            if ($passed && !$wasCompleted) $points += 25;
            if ($score === 100 && !$hadPerfect) $points += 10;
            if ($points > 0) Database::query('UPDATE users SET points = points + ? WHERE id = ?', [$points, $userId]);
        });

        activity('quiz_submitted', ['lesson_id' => $lessonId, 'score' => $score, 'passed' => $passed], $userId);
        self::awardEligibleBadges($userId);
        return compact('score', 'passed', 'correct', 'total', 'feedback');
    }

    public static function dashboardData(int $userId): array
    {
        $totalLessons = (int) Database::scalar("SELECT COUNT(*) FROM lessons WHERE is_published = 1");
        $completed = (int) Database::scalar("SELECT COUNT(*) FROM progress WHERE user_id = ? AND status = 'completed'", [$userId]);
        $average = (int) Database::scalar('SELECT COALESCE(ROUND(AVG(best_score)), 0) FROM progress WHERE user_id = ? AND best_score > 0', [$userId]);
        $projects = (int) Database::scalar('SELECT COUNT(*) FROM projects WHERE user_id = ?', [$userId]);
        $nextLesson = Database::fetch(
            "SELECT l.id, l.slug, l.title, l.summary, l.duration_minutes, l.icon, c.title AS course_title, c.colour,
                    COALESCE(p.status, 'not_started') AS progress_status
             FROM lessons l
             JOIN courses c ON c.id = l.course_id
             JOIN course_enrollments e ON e.course_id = c.id AND e.user_id = ? AND e.status = 'active'
             LEFT JOIN progress p ON p.lesson_id = l.id AND p.user_id = ?
             WHERE l.is_published = 1 AND c.is_published = 1 AND COALESCE(p.status, 'not_started') <> 'completed'
             ORDER BY CASE WHEN p.status = 'in_progress' THEN 0 ELSE 1 END, p.last_accessed_at DESC, c.sort_order, l.sort_order
             LIMIT 1",
            [$userId, $userId]
        );
        if (!$nextLesson) {
            $nextLesson = Database::fetch(
                "SELECT l.id, l.slug, l.title, l.summary, l.duration_minutes, l.icon, c.title AS course_title, c.colour, 'not_started' AS progress_status
                 FROM lessons l JOIN courses c ON c.id = l.course_id
                 WHERE l.is_published = 1 AND c.is_published = 1 ORDER BY c.sort_order, l.sort_order LIMIT 1"
            );
        }
        return [
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completed,
            'completion_percent' => $totalLessons > 0 ? (int) round(($completed / $totalLessons) * 100) : 0,
            'average_score' => $average,
            'project_count' => $projects,
            'next_lesson' => $nextLesson,
            'recent_activity' => self::recentActivity($userId, 6),
            'announcements' => self::announcements(4, $userId),
        ];
    }

    public static function recentActivity(int $userId, int $limit = 10): array
    {
        $limit = max(1, min(30, $limit));
        return Database::fetchAll("SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}", [$userId]);
    }

    public static function announcements(int $limit = 10, ?int $userId = null): array
    {
        $limit = max(1, min(30, $limit));
        $role = $userId ? (Database::fetch('SELECT role FROM users WHERE id = ?', [$userId])['role'] ?? 'learner') : 'public';
        return Database::fetchAll(
            "SELECT a.*, u.name AS author_name FROM announcements a JOIN users u ON u.id = a.author_id
             WHERE a.status = 'published' AND (a.audience = 'all' OR a.audience = ? OR (? = 'public' AND a.audience = 'public'))
             AND (a.expires_at IS NULL OR a.expires_at >= CURRENT_TIMESTAMP)
             ORDER BY a.is_pinned DESC, a.published_at DESC LIMIT {$limit}",
            [$role, $role]
        );
    }

    public static function badges(int $userId): array
    {
        return Database::fetchAll(
            'SELECT b.*, ub.awarded_at, CASE WHEN ub.id IS NULL THEN 0 ELSE 1 END AS earned FROM badges b LEFT JOIN user_badges ub ON ub.badge_id = b.id AND ub.user_id = ? ORDER BY b.sort_order, b.id',
            [$userId]
        );
    }

    public static function awardEligibleBadges(int $userId): void
    {
        if (!Database::tableExists('badges')) return;
        $metrics = [
            'completed' => (int) Database::scalar("SELECT COUNT(*) FROM progress WHERE user_id = ? AND status = 'completed'", [$userId]),
            'projects' => (int) Database::scalar('SELECT COUNT(*) FROM projects WHERE user_id = ?', [$userId]),
            'perfect' => (int) Database::scalar('SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ? AND score = 100', [$userId]),
            'enrolments' => (int) Database::scalar("SELECT COUNT(*) FROM course_enrollments WHERE user_id = ? AND status = 'active'", [$userId]),
        ];
        $eligible = [];
        if ($metrics['enrolments'] >= 1) $eligible[] = 'first_path';
        if ($metrics['completed'] >= 1) $eligible[] = 'first_lesson';
        if ($metrics['completed'] >= 5) $eligible[] = 'five_lessons';
        if ($metrics['completed'] >= 12) $eligible[] = 'learning_runner';
        if ($metrics['projects'] >= 1) $eligible[] = 'first_project';
        if ($metrics['projects'] >= 5) $eligible[] = 'project_builder';
        if ($metrics['perfect'] >= 1) $eligible[] = 'perfect_score';
        foreach ($eligible as $code) {
            $badge = Database::fetch('SELECT id, points FROM badges WHERE code = ?', [$code]);
            if (!$badge) continue;
            $existing = Database::fetch('SELECT id FROM user_badges WHERE user_id = ? AND badge_id = ?', [$userId, $badge['id']]);
            if ($existing) continue;
            Database::transaction(function () use ($userId, $badge, $code): void {
                Database::query('INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)', [$userId, $badge['id']]);
                Database::query('UPDATE users SET points = points + ? WHERE id = ?', [(int) $badge['points'], $userId]);
                activity('badge_earned', ['badge_code' => $code], $userId);
            });
        }
    }

    public static function languages(bool $includeGuided = true): array
    {
        return LanguageCatalog::all($includeGuided);
    }

    public static function language(string $slug): ?array
    {
        return LanguageCatalog::find($slug);
    }

    public static function projects(int $userId): array
    {
        $projects = Database::fetchAll(
            "SELECT p.*, (SELECT COUNT(*) FROM project_versions pv WHERE pv.project_id = p.id) AS version_count
             FROM projects p WHERE p.user_id = ? ORDER BY p.updated_at DESC",
            [$userId]
        );
        return array_map([self::class, 'hydrateProject'], $projects);
    }

    public static function project(int $projectId, int $userId): ?array
    {
        $project = Database::fetch('SELECT * FROM projects WHERE id = ? AND user_id = ?', [$projectId, $userId]);
        return $project ? self::hydrateProject($project) : null;
    }

    public static function saveProject(int $userId, array $data): int
    {
        $projectId = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $languageSlug = strtolower(trim((string) ($data['language'] ?? 'mwanacode')));
        $language = self::language($languageSlug);
        if (!$language) throw new InvalidArgumentException('Select a supported programming language.');

        $workspace = LanguageCatalog::normalizeWorkspace((array) ($data['files'] ?? []), $language);
        $mainFile = (string) ($language['main_file'] ?? array_key_first($workspace));
        $code = (string) ($workspace[$mainFile] ?? reset($workspace) ?: '');
        $stdin = mb_substr((string) ($data['stdin'] ?? ''), 0, 10000);
        $workspaceJson = json_encode($workspace, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if ($projectId > 0) {
            $project = self::project($projectId, $userId);
            if (!$project) throw new RuntimeException('The project could not be found.');
            $changed = $project['title'] !== $title
                || $project['language'] !== $languageSlug
                || $project['workspace_json'] !== $workspaceJson
                || (string) ($project['stdin'] ?? '') !== $stdin;
            Database::transaction(function () use ($projectId, $project, $title, $languageSlug, $code, $workspaceJson, $stdin, $changed, $userId): void {
                if ($changed) {
                    Database::query(
                        'INSERT INTO project_versions (project_id, title, language, code, workspace_json, stdin) VALUES (?, ?, ?, ?, ?, ?)',
                        [$projectId, $project['title'], $project['language'], $project['code'], $project['workspace_json'], $project['stdin']]
                    );
                }
                Database::query(
                    'UPDATE projects SET title = ?, language = ?, code = ?, workspace_json = ?, stdin = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?',
                    [$title, $languageSlug, $code, $workspaceJson, $stdin, $projectId, $userId]
                );
            });
            activity('project_updated', ['project_id' => $projectId, 'language' => $languageSlug], $userId);
            return $projectId;
        }

        $projectId = Database::insert(
            'INSERT INTO projects (user_id, title, language, code, workspace_json, stdin) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $title, $languageSlug, $code, $workspaceJson, $stdin]
        );
        activity('project_created', ['project_id' => $projectId, 'language' => $languageSlug], $userId);
        self::awardEligibleBadges($userId);
        return $projectId;
    }

    public static function logCodeRun(int $userId, ?int $projectId, string $languageSlug, array $result, string $stdin): void
    {
        if (!Database::tableExists('code_runs')) return;
        Database::query(
            'INSERT INTO code_runs (user_id, project_id, language_slug, status, stdin_text, stdout_text, stderr_text, exit_code, execution_time_ms, memory_bytes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                $projectId ?: null,
                $languageSlug,
                (string) ($result['status'] ?? 'failed'),
                mb_substr($stdin, 0, 10000),
                (string) ($result['stdout'] ?? ''),
                (string) ($result['stderr'] ?? ''),
                $result['exit_code'] ?? null,
                $result['execution_time_ms'] ?? null,
                $result['memory_bytes'] ?? null,
            ]
        );
    }

    public static function deleteProject(int $projectId, int $userId): bool
    {
        $project = self::project($projectId, $userId);
        if (!$project) return false;
        Database::query('DELETE FROM projects WHERE id = ? AND user_id = ?', [$projectId, $userId]);
        activity('project_deleted', ['project_title' => $project['title'], 'language' => $project['language']], $userId);
        return true;
    }

    private static function hydrateProject(array $project): array
    {
        $language = self::language((string) ($project['language'] ?? 'mwanacode')) ?? LanguageCatalog::guided();
        $workspace = json_decode((string) ($project['workspace_json'] ?? ''), true);
        if (!is_array($workspace) || !$workspace) {
            $workspace = [(string) ($language['main_file'] ?? 'main.mwana') => (string) ($project['code'] ?? '')];
        }
        $workspace = LanguageCatalog::normalizeWorkspace($workspace, $language);
        $project['workspace'] = $workspace;
        $project['workspace_json'] = json_encode($workspace, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $project['language_meta'] = $language;
        $project['stdin'] = (string) ($project['stdin'] ?? '');
        return $project;
    }

    public static function leaderboard(int $limit = 50): array
    {
        $limit = max(3, min(100, $limit));
        return Database::fetchAll(
            "SELECT u.id, u.name, u.username, u.school_name, u.points,
                    COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN p.lesson_id END) AS completed_lessons,
                    COUNT(DISTINCT pr.id) AS project_count
             FROM users u
             LEFT JOIN progress p ON p.user_id = u.id
             LEFT JOIN projects pr ON pr.user_id = u.id
             WHERE u.role = 'learner' AND u.status = 'active'
             GROUP BY u.id ORDER BY u.points DESC, completed_lessons DESC, u.name ASC LIMIT {$limit}"
        );
    }

    public static function teacherOverview(): array
    {
        return [
            'learners' => (int) Database::scalar("SELECT COUNT(*) FROM users WHERE role = 'learner' AND status = 'active'"),
            'active_week' => (int) Database::scalar("SELECT COUNT(DISTINCT user_id) FROM activity_log WHERE created_at >= ?", [date('Y-m-d H:i:s', strtotime('-7 days'))]),
            'completed' => (int) Database::scalar("SELECT COUNT(*) FROM progress WHERE status = 'completed'"),
            'average' => (int) Database::scalar('SELECT COALESCE(ROUND(AVG(score)), 0) FROM quiz_attempts'),
        ];
    }

    private static function enrolSilently(int $userId, int $courseId): void
    {
        $existing = Database::fetch('SELECT id FROM course_enrollments WHERE user_id = ? AND course_id = ?', [$userId, $courseId]);
        if (!$existing) {
            Database::query("INSERT INTO course_enrollments (user_id, course_id, status) VALUES (?, ?, 'active')", [$userId, $courseId]);
        }
    }
}
