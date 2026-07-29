<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user = current_user();
$stats = Learning::dashboardStats((int) $user['id']);
$badges = Learning::badges((int) $user['id']);
$courseProgress = [];
foreach (Learning::courses() as $course) {
    $lessons = Learning::lessonsForCourse((int) $course['id'], (int) $user['id']);
    $completed = count(array_filter($lessons, fn(array $lesson): bool => $lesson['progress_status'] === 'completed'));
    $courseProgress[] = ['course' => $course, 'lessons' => $lessons, 'completed' => $completed, 'percent' => count($lessons) ? (int) round($completed / count($lessons) * 100) : 0];
}
$attempts = Database::fetchAll('SELECT qa.*, l.title FROM quiz_attempts qa JOIN lessons l ON l.id = qa.lesson_id WHERE qa.user_id = ? ORDER BY qa.created_at DESC LIMIT 10', [$user['id']]);
$pageTitle = 'Progress';
$bodyClass = 'app-page';
require base_path('partials/header.php');
?>
<section class="page-hero progress-hero"><div class="container"><span class="eyebrow light">Learning record</span><h1>Your progress tells a story</h1><p>Review completed lessons, best quiz scores and milestones earned through consistent practice.</p></div></section>
<section class="section"><div class="container app-layout">
    <div class="app-main">
        <div class="dashboard-stat-grid four"><article class="metric-card"><span class="metric-icon purple">✓</span><div><strong><?= $stats['completed'] ?></strong><small>Completed lessons</small></div></article><article class="metric-card"><span class="metric-icon orange">⭐</span><div><strong><?= $stats['average'] ?>%</strong><small>Average best score</small></div></article><article class="metric-card"><span class="metric-icon green">{ }</span><div><strong><?= $stats['projects'] ?></strong><small>Saved projects</small></div></article><article class="metric-card"><span class="metric-icon blue">🏅</span><div><strong><?= count(array_filter($badges, fn(array $b): bool => (int) $b['earned'] === 1)) ?></strong><small>Badges earned</small></div></article></div>
        <div class="section-row"><div><span class="eyebrow">By learning path</span><h2>Course completion</h2></div></div>
        <div class="progress-course-list"><?php foreach ($courseProgress as $item): ?><article class="progress-course-card" style="--course-accent: <?= e($item['course']['colour']) ?>"><div class="course-icon" aria-hidden="true"><?= e($item['course']['icon']) ?></div><div class="progress-course-main"><div><h3><?= e($item['course']['title']) ?></h3><span><?= $item['completed'] ?> of <?= count($item['lessons']) ?> lessons complete</span></div><progress value="<?= $item['percent'] ?>" max="100"><?= $item['percent'] ?>%</progress></div><strong><?= $item['percent'] ?>%</strong></article><?php endforeach; ?></div>
        <div class="section-row"><div><span class="eyebrow">Assessment history</span><h2>Recent quiz attempts</h2></div></div>
        <div class="table-card"><div class="responsive-table"><table><thead><tr><th>Lesson</th><th>Score</th><th>Result</th><th>Date</th></tr></thead><tbody><?php if (!$attempts): ?><tr><td colspan="4" class="empty-cell">No quiz attempts yet.</td></tr><?php endif; ?><?php foreach ($attempts as $attempt): ?><tr><td><a href="<?= e(url('lesson.php?id=' . (int) $attempt['lesson_id'])) ?>"><?= e($attempt['title']) ?></a></td><td><strong><?= (int) $attempt['score'] ?>%</strong></td><td><span class="status-pill <?= (int) $attempt['passed'] ? 'success' : 'warning' ?>"><?= (int) $attempt['passed'] ? 'Passed' : 'Review' ?></span></td><td><?= e(date('j M Y, H:i', strtotime($attempt['created_at']))) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
    </div>
    <aside class="app-sidebar" id="badges"><div class="sidebar-card"><span class="eyebrow">Achievement cabinet</span><h2>Badges</h2><div class="all-badges"><?php foreach ($badges as $badge): ?><div class="full-badge <?= (int) $badge['earned'] ? 'earned' : 'locked' ?>"><span aria-hidden="true"><?= (int) $badge['earned'] ? e($badge['icon']) : '🔒' ?></span><div><strong><?= e($badge['name']) ?></strong><small><?= e($badge['description']) ?></small></div><b>+<?= (int) $badge['points'] ?></b></div><?php endforeach; ?></div></div></aside>
</div></section>
<?php require base_path('partials/footer.php'); ?>
