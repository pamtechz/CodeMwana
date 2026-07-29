<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user = current_user();
$courses = Learning::courses();
$selectedId = request_int('course');
$pageTitle = 'Learning paths';
$bodyClass = 'app-page';
require base_path('partials/header.php');
?>
<section class="page-hero page-hero-lessons">
    <div class="container"><span class="eyebrow light">Learning paths</span><h1>Build strong programming foundations</h1><p>Move from logical thinking to written code through guided, practical activities.</p></div>
</section>
<section class="section">
    <div class="container">
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
            <?php
                $lessons = Learning::lessonsForCourse((int) $course['id'], (int) $user['id']);
                $completedCount = count(array_filter($lessons, fn(array $lesson): bool => $lesson['progress_status'] === 'completed'));
                $percent = count($lessons) ? (int) round(($completedCount / count($lessons)) * 100) : 0;
            ?>
            <article class="course-card large-course-card <?= $selectedId === (int) $course['id'] ? 'selected' : '' ?>" style="--course-accent: <?= e($course['colour']) ?>">
                <div class="course-top"><span class="course-icon large" aria-hidden="true"><?= e($course['icon']) ?></span><span class="level-pill"><?= e($course['level']) ?></span></div>
                <h2><?= e($course['title']) ?></h2><p><?= e($course['description']) ?></p>
                <div class="course-meta"><span><?= (int) $course['lesson_count'] ?> lessons</span><span><?= e($course['estimated_time']) ?></span></div>
                <div class="progress-label"><span>Your progress</span><strong><?= $percent ?>%</strong></div><progress value="<?= $percent ?>" max="100"><?= $percent ?>%</progress>
                <a class="button button-full" href="#course-<?= (int) $course['id'] ?>">View lessons</a>
            </article>
            <?php endforeach; ?>
        </div>

        <?php foreach ($courses as $course): ?>
        <?php $lessons = Learning::lessonsForCourse((int) $course['id'], (int) $user['id']); ?>
        <section class="lesson-path" id="course-<?= (int) $course['id'] ?>">
            <div class="lesson-path-heading"><div class="course-icon" style="--course-accent: <?= e($course['colour']) ?>" aria-hidden="true"><?= e($course['icon']) ?></div><div><span class="eyebrow"><?= e($course['level']) ?> path</span><h2><?= e($course['title']) ?></h2><p><?= e($course['short_description']) ?></p></div></div>
            <div class="lesson-list">
                <?php foreach ($lessons as $index => $lesson): ?>
                <article class="lesson-row <?= e($lesson['progress_status']) ?>">
                    <div class="lesson-number" aria-hidden="true"><?= $lesson['progress_status'] === 'completed' ? '✓' : $index + 1 ?></div>
                    <div class="lesson-row-copy"><div class="lesson-title-row"><h3><?= e($lesson['title']) ?></h3><span class="difficulty <?= e($lesson['difficulty']) ?>"><?= e(ucfirst($lesson['difficulty'])) ?></span></div><p><?= e($lesson['summary']) ?></p><div class="lesson-meta"><span>⏱ <?= (int) $lesson['duration_minutes'] ?> min</span><span>🧠 <?= e($lesson['concepts']) ?></span><?php if ((int) $lesson['best_score'] > 0): ?><span>⭐ <?= (int) $lesson['best_score'] ?>%</span><?php endif; ?></div></div>
                    <a class="button <?= $lesson['progress_status'] === 'completed' ? 'button-secondary' : '' ?>" href="<?= e(url('lesson.php?id=' . (int) $lesson['id'])) ?>"><?= $lesson['progress_status'] === 'completed' ? 'Review' : ($lesson['progress_status'] === 'in_progress' ? 'Continue' : 'Start') ?></a>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>
