<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user = current_user();
$data = Learning::dashboardData((int) $user['id']);
$courses = Learning::courses((int) $user['id']);
$badges = array_filter(Learning::badges((int) $user['id']), fn($badge) => (int) $badge['earned'] === 1);
$pageTitle = 'Overview';
$bodyClass = 'dashboard-page';
require base_path('partials/header.php');
$firstName = explode(' ', trim($user['name']))[0];
?>
<section class="workspace-section dashboard-welcome">
    <div class="welcome-copy"><span class="eyebrow">Learner workspace</span><h1>Welcome back, <?= e($firstName) ?>.</h1><p>Your next action is ready. Continue a lesson, review an announcement or build a program in the Code Lab.</p></div>
    <div class="points-pill"><?= icon('trophy') ?><span><strong><?= number_format((int) $user['points']) ?></strong><small>Total points</small></span></div>
</section>
<section class="metric-grid four">
    <article class="metric-card"><span class="metric-icon purple"><?= icon('book-open') ?></span><div><small>Lessons completed</small><strong><?= (int) $data['completed_lessons'] ?><span>/ <?= (int) $data['total_lessons'] ?></span></strong></div></article>
    <article class="metric-card"><span class="metric-icon green"><?= icon('chart') ?></span><div><small>Overall progress</small><strong><?= (int) $data['completion_percent'] ?>%</strong></div></article>
    <article class="metric-card"><span class="metric-icon orange"><?= icon('check-circle') ?></span><div><small>Average quiz score</small><strong><?= (int) $data['average_score'] ?>%</strong></div></article>
    <article class="metric-card"><span class="metric-icon blue"><?= icon('folder-code') ?></span><div><small>Saved projects</small><strong><?= (int) $data['project_count'] ?></strong></div></article>
</section>
<div class="dashboard-grid">
    <div class="dashboard-primary">
        <?php if ($data['next_lesson']): $next = $data['next_lesson']; ?>
        <section class="panel continue-panel" style="--course:<?= e($next['colour']) ?>">
            <div class="panel-heading"><div><span class="eyebrow">Continue learning</span><h2>Your next lesson</h2></div><a class="text-link" href="<?= e(url('courses.php')) ?>">View all paths<?= icon('arrow-right') ?></a></div>
            <div class="continue-card"><span class="continue-icon"><?= icon($next['icon']) ?></span><div><small><?= e($next['course_title']) ?></small><h3><?= e($next['title']) ?></h3><p><?= e($next['summary']) ?></p><div class="inline-meta"><span><?= icon('clock') ?><?= (int) $next['duration_minutes'] ?> minutes</span><span><?= icon($next['progress_status'] === 'in_progress' ? 'play' : 'book-open') ?><?= $next['progress_status'] === 'in_progress' ? 'In progress' : 'Ready to start' ?></span></div></div><a class="button" href="<?= e(url('lesson.php?lesson=' . urlencode($next['slug']))) ?>"><?= $next['progress_status'] === 'in_progress' ? 'Continue lesson' : 'Start lesson' ?><?= icon('arrow-right') ?></a></div>
        </section>
        <?php else: ?>
        <section class="panel empty-panel"><span class="empty-icon"><?= icon('book-open') ?></span><h2>Choose your first learning path</h2><p>Enrol in a path to receive an ordered next lesson on this dashboard.</p><a class="button" href="<?= e(url('courses.php')) ?>">Explore learning paths</a></section>
        <?php endif; ?>
        <section class="panel">
            <div class="panel-heading"><div><span class="eyebrow">Your learning paths</span><h2>Path progress</h2></div></div>
            <div class="path-list">
                <?php foreach ($courses as $course): $percent=(int)$course['lesson_count']>0?(int)round(((int)$course['completed_count']/(int)$course['lesson_count'])*100):0; ?>
                <a class="path-row" href="<?= e(url('course.php?course=' . urlencode($course['slug']))) ?>"><span class="course-icon compact" style="--course:<?= e($course['colour']) ?>"><?= icon($course['icon']) ?></span><span class="path-info"><strong><?= e($course['title']) ?></strong><small><?= (int)$course['completed_count'] ?> of <?= (int)$course['lesson_count'] ?> lessons complete</small><span class="progress-track"><i style="width:<?= $percent ?>%"></i></span></span><b><?= $percent ?>%</b><?= icon('arrow-right') ?></a>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="panel" id="announcements"><div class="panel-heading"><div><span class="eyebrow">Announcements</span><h2>What you need to know</h2></div></div><?php if ($data['announcements']): ?><div class="notice-list"><?php foreach ($data['announcements'] as $notice): ?><article><span class="notice-icon"><?= icon('bell') ?></span><div><div class="notice-top"><h3><?= e($notice['title']) ?></h3><time><?= e(time_ago($notice['published_at'])) ?></time></div><p><?= e($notice['body']) ?></p><small>Published by <?= e($notice['author_name']) ?></small></div></article><?php endforeach; ?></div><?php else: ?><div class="compact-empty">There are no current announcements for your account.</div><?php endif; ?></section>
    </div>
    <aside class="dashboard-aside">
        <section class="panel quick-actions"><div class="panel-heading"><h2>Quick actions</h2></div><a href="<?= e(url('playground.php')) ?>"><span class="action-icon purple"><?= icon('terminal') ?></span><span><strong>Open Code Lab</strong><small>Create or continue a program</small></span><?= icon('arrow-right') ?></a><a href="<?= e(url('projects.php')) ?>"><span class="action-icon blue"><?= icon('folder-code') ?></span><span><strong>Project library</strong><small>Manage saved programs</small></span><?= icon('arrow-right') ?></a><a href="<?= e(url('progress.php')) ?>"><span class="action-icon green"><?= icon('chart') ?></span><span><strong>Progress report</strong><small>Review scores and badges</small></span><?= icon('arrow-right') ?></a></section>
        <section class="panel"><div class="panel-heading"><div><span class="eyebrow">Achievements</span><h2>Recent badges</h2></div><a class="text-link" href="<?= e(url('progress.php#badges')) ?>">View all</a></div><?php if ($badges): ?><div class="badge-stack"><?php foreach (array_slice(array_reverse($badges),0,4) as $badge): ?><div><span><?= icon($badge['icon']) ?></span><div><strong><?= e($badge['name']) ?></strong><small><?= e($badge['description']) ?></small></div></div><?php endforeach; ?></div><?php else: ?><div class="compact-empty">Complete a lesson or save a project to earn your first badge.</div><?php endif; ?></section>
        <section class="panel activity-panel"><div class="panel-heading"><h2>Recent activity</h2></div><?php if ($data['recent_activity']): ?><ul class="activity-list"><?php foreach ($data['recent_activity'] as $activity): ?><li><span></span><div><strong><?= e(ucwords(str_replace('_',' ',$activity['action']))) ?></strong><small><?= e(time_ago($activity['created_at'])) ?></small></div></li><?php endforeach; ?></ul><?php else: ?><div class="compact-empty">Your activity timeline will appear here.</div><?php endif; ?></section>
    </aside>
</div>
<?php require base_path('partials/footer.php'); ?>
