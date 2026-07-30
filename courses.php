<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user = current_user();
if (is_post()) {
    verify_csrf();
    $courseId = request_int('course_id');
    $course = Learning::course($courseId);
    if (!$course) { flash('error', 'The selected learning path is not available.'); redirect('courses.php'); }
    Learning::enroll((int)$user['id'], $courseId);
    flash('success', 'You are enrolled in ' . $course['title'] . '.');
    redirect('course.php?course=' . urlencode($course['slug']));
}
$courses = Learning::courses((int)$user['id']);
$pageTitle = 'Learning paths';
$bodyClass = 'courses-page';
require base_path('partials/header.php');
?>
<section class="workspace-section page-intro"><div><span class="eyebrow">Structured curriculum</span><h1>Learning paths</h1><p>Choose a path by outcome, level and time. Enrolment makes the ordered lessons available on your dashboard.</p></div><div class="page-intro-actions"><a class="button button-secondary" href="<?= e(url('progress.php')) ?>"><?= icon('chart') ?>View progress</a></div></section>
<div class="filter-toolbar"><div class="search-field"><?= icon('search') ?><label class="sr-only" for="course-search">Search learning paths</label><input id="course-search" data-filter-input data-filter-target=".learning-path-card" aria-label="Search learning paths"></div><div class="filter-chips" data-filter-chips><button class="is-active" type="button" data-filter="all">All paths</button><button type="button" data-filter="enrolled">Enrolled</button><button type="button" data-filter="available">Available</button></div></div>
<section class="learning-path-grid">
<?php foreach ($courses as $course): $percent=(int)$course['lesson_count']?round(((int)$course['completed_count']/(int)$course['lesson_count'])*100):0; ?>
<article class="learning-path-card" data-filter-item data-state="<?= (int)$course['is_enrolled'] ? 'enrolled' : 'available' ?>" style="--course:<?= e($course['colour']) ?>">
    <div class="path-card-banner"><span class="course-icon large"><?= icon($course['icon']) ?></span><span class="status-badge <?= (int)$course['is_enrolled']?'success':'neutral' ?>"><?= (int)$course['is_enrolled']?'Enrolled':'Available' ?></span></div>
    <div class="path-card-body"><div class="inline-meta"><span><?= e($course['level']) ?></span><span><?= e($course['audience']) ?></span></div><h2><?= e($course['title']) ?></h2><p><?= e($course['short_description']) ?></p><div class="path-facts"><span><?= icon('book-open') ?><b><?= (int)$course['lesson_count'] ?></b> lessons</span><span><?= icon('clock') ?><b><?= e($course['estimated_time']) ?></b></span></div>
    <?php if ((int)$course['is_enrolled']): ?><div class="path-progress"><span><b><?= (int)$course['completed_count'] ?></b> of <?= (int)$course['lesson_count'] ?> lessons</span><strong><?= (int)$percent ?>%</strong><div class="progress-track"><i style="width:<?= (int)$percent ?>%"></i></div></div><?php endif; ?>
    </div>
    <div class="path-card-actions"><a class="button <?= (int)$course['is_enrolled']?'':'button-secondary' ?>" href="<?= e(url('course.php?course='.urlencode($course['slug']))) ?>"><?= (int)$course['is_enrolled']?'Continue path':'View path' ?><?= icon('arrow-right') ?></a><?php if (!(int)$course['is_enrolled']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>"><button class="text-button" type="submit">Enrol now</button></form><?php endif; ?></div>
</article>
<?php endforeach; ?>
</section>
<?php require base_path('partials/footer.php'); ?>
