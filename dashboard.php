<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user = current_user();
$stats = Learning::dashboardStats((int) $user['id']);
$courses = Learning::courses();
$badges = Learning::badges((int) $user['id']);
$earnedBadges = array_values(array_filter($badges, fn(array $badge): bool => (int) $badge['earned'] === 1));
$pageTitle = 'Dashboard';
$bodyClass = 'app-page';
require base_path('partials/header.php');
?>
<section class="dashboard-hero">
    <div class="container dashboard-hero-grid">
        <div>
            <span class="eyebrow light">Learner dashboard</span>
            <h1>Hello, <?= e(explode(' ', $user['name'])[0]) ?>! <span aria-hidden="true">👋🏾</span></h1>
            <p>One clear idea today can become a useful program tomorrow.</p>
        </div>
        <div class="points-card">
            <span aria-hidden="true">⭐</span>
            <div><strong><?= number_format((int) $user['points']) ?></strong><small>learning points</small></div>
            <div class="points-divider"></div>
            <div><strong><?= number_format((int) $user['streak_days']) ?></strong><small>day streak</small></div>
        </div>
    </div>
</section>
<section class="section dashboard-content">
    <div class="container app-layout">
        <div class="app-main">
            <?php if ($stats['next']): ?>
            <article class="continue-card">
                <div class="continue-icon" aria-hidden="true"><?= e($stats['next']['icon'] ?? '💡') ?></div>
                <div class="continue-copy">
                    <span>Continue learning</span>
                    <h2><?= e($stats['next']['title']) ?></h2>
                    <p><?= e($stats['next']['course_title']) ?> · <?= (int) $stats['next']['duration_minutes'] ?> minutes · <?= e(ucfirst($stats['next']['difficulty'])) ?></p>
                </div>
                <a class="button" href="<?= e(url('lesson.php?id=' . (int) $stats['next']['id'])) ?>">Open lesson <span aria-hidden="true">→</span></a>
            </article>
            <?php else: ?>
            <article class="continue-card completed-card">
                <div class="continue-icon" aria-hidden="true">🎉</div>
                <div class="continue-copy"><span>All current lessons completed</span><h2>You finished every published lesson!</h2><p>Use the code lab to create a new project or improve an existing one.</p></div>
                <a class="button" href="<?= e(url('playground.php')) ?>">Open Code Lab</a>
            </article>
            <?php endif; ?>

            <div class="dashboard-stat-grid">
                <article class="metric-card"><span class="metric-icon purple" aria-hidden="true">✓</span><div><strong><?= $stats['completed'] ?>/<?= $stats['lessons'] ?></strong><small>Lessons completed</small></div><progress value="<?= $stats['completed'] ?>" max="<?= max(1, $stats['lessons']) ?>"><?= $stats['completed'] ?></progress></article>
                <article class="metric-card"><span class="metric-icon orange" aria-hidden="true">⚡</span><div><strong><?= $stats['average'] ?>%</strong><small>Average best score</small></div><progress value="<?= $stats['average'] ?>" max="100"><?= $stats['average'] ?>%</progress></article>
                <article class="metric-card"><span class="metric-icon green" aria-hidden="true">{ }</span><div><strong><?= $stats['projects'] ?></strong><small>Saved code projects</small></div><a href="<?= e(url('projects.php')) ?>">View projects</a></article>
            </div>

            <div class="section-row">
                <div><span class="eyebrow">Learning paths</span><h2>Choose what to practise</h2></div>
                <a class="text-link" href="<?= e(url('courses.php')) ?>">View all lessons <span aria-hidden="true">→</span></a>
            </div>
            <div class="course-grid compact-course-grid">
                <?php foreach ($courses as $course): ?>
                <?php
                    $lessons = Learning::lessonsForCourse((int) $course['id'], (int) $user['id']);
                    $completed = count(array_filter($lessons, fn(array $l): bool => $l['progress_status'] === 'completed'));
                    $percent = count($lessons) ? (int) round(($completed / count($lessons)) * 100) : 0;
                ?>
                <article class="course-card" style="--course-accent: <?= e($course['colour']) ?>">
                    <div class="course-top"><span class="course-icon" aria-hidden="true"><?= e($course['icon']) ?></span><span class="level-pill"><?= e($course['level']) ?></span></div>
                    <h3><?= e($course['title']) ?></h3>
                    <p><?= e($course['short_description']) ?></p>
                    <div class="progress-label"><span><?= $completed ?> of <?= count($lessons) ?> lessons</span><strong><?= $percent ?>%</strong></div>
                    <progress value="<?= $percent ?>" max="100"><?= $percent ?>%</progress>
                    <a class="card-link" href="<?= e(url('courses.php?course=' . (int) $course['id'])) ?>">Explore path <span aria-hidden="true">→</span></a>
                </article>
                <?php endforeach; ?>
            </div>

            <div class="section-row"><div><span class="eyebrow">Recent activity</span><h2>Pick up where you stopped</h2></div></div>
            <div class="activity-list">
                <?php if (!$stats['recent']): ?><div class="empty-state small"><span aria-hidden="true">🌱</span><h3>Your learning history starts here</h3><p>Open a lesson and your activity will appear in this section.</p></div><?php endif; ?>
                <?php foreach ($stats['recent'] as $activity): ?>
                <a class="activity-item" href="<?= e(url('lesson.php?id=' . (int) $activity['id'])) ?>">
                    <span class="activity-icon <?= e($activity['status']) ?>" aria-hidden="true"><?= $activity['status'] === 'completed' ? '✓' : '▶' ?></span>
                    <span><strong><?= e($activity['title']) ?></strong><small><?= e(ucfirst(str_replace('_', ' ', $activity['status']))) ?> · Best score <?= (int) $activity['best_score'] ?>%</small></span>
                    <time><?= e(time_ago($activity['last_accessed_at'])) ?></time>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <aside class="app-sidebar">
            <div class="sidebar-card">
                <div class="sidebar-card-heading"><h2>Achievements</h2><a href="<?= e(url('progress.php#badges')) ?>">All badges</a></div>
                <?php if ($earnedBadges): ?>
                    <div class="badge-list">
                    <?php foreach (array_slice($earnedBadges, 0, 4) as $badge): ?>
                        <div class="badge-item"><span aria-hidden="true"><?= e($badge['icon']) ?></span><div><strong><?= e($badge['name']) ?></strong><small><?= e($badge['description']) ?></small></div></div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-badges"><span aria-hidden="true">🏅</span><p>Complete your first lesson to unlock a badge.</p></div>
                <?php endif; ?>
            </div>
            <div class="sidebar-card tip-card">
                <span class="tip-label">Programming tip</span>
                <blockquote>“When a program is not working, test one small part at a time.”</blockquote>
                <small>This process is called debugging.</small>
            </div>
            <a class="sidebar-action-card" href="<?= e(url('playground.php')) ?>"><span aria-hidden="true">💻</span><div><strong>Build something</strong><small>Open a blank MwanaCode project</small></div><b aria-hidden="true">→</b></a>
        </aside>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>
