<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_role('admin');

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = request_int('id');

    if ($action === 'toggle_course' && $id > 0) {
        Database::query('UPDATE courses SET is_published=CASE WHEN is_published=1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
        activity('course_visibility_changed', ['course_id' => $id]);
        flash('success', 'Learning-path publication status updated.');
        redirect('admin/content.php');
    }
    if ($action === 'toggle_lesson' && $id > 0) {
        Database::query('UPDATE lessons SET is_published=CASE WHEN is_published=1 THEN 0 ELSE 1 END, updated_at=CURRENT_TIMESTAMP WHERE id=?', [$id]);
        activity('lesson_visibility_changed', ['lesson_id' => $id]);
        flash('success', 'Lesson publication status updated.');
        redirect('admin/content.php');
    }
}

$query = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? 'all');
if (!in_array($status, ['all', 'published', 'draft'], true)) $status = 'all';

$courseWhere = $lessonWhere = [];
$courseParams = $lessonParams = [];
if ($query !== '') {
    $like = '%' . $query . '%';
    $courseWhere[] = '(c.title LIKE ? OR c.short_description LIKE ? OR c.level LIKE ?)';
    array_push($courseParams, $like, $like, $like);
    $lessonWhere[] = '(l.title LIKE ? OR l.summary LIKE ? OR c.title LIKE ?)';
    array_push($lessonParams, $like, $like, $like);
}
if ($status !== 'all') {
    $published = $status === 'published' ? 1 : 0;
    $courseWhere[] = 'c.is_published=?';
    $courseParams[] = $published;
    $lessonWhere[] = 'l.is_published=?';
    $lessonParams[] = $published;
}

$courses = Database::fetchAll(
    'SELECT c.*, COUNT(l.id) AS lesson_count, SUM(CASE WHEN l.is_published=1 THEN 1 ELSE 0 END) AS published_lesson_count
     FROM courses c LEFT JOIN lessons l ON l.course_id=c.id' .
    ($courseWhere ? ' WHERE ' . implode(' AND ', $courseWhere) : '') .
    ' GROUP BY c.id ORDER BY c.sort_order,c.title',
    $courseParams
);
$lessons = Database::fetchAll(
    'SELECT l.*, c.title AS course_title, c.colour AS course_colour, COUNT(q.id) AS question_count
     FROM lessons l JOIN courses c ON c.id=l.course_id LEFT JOIN quiz_questions q ON q.lesson_id=l.id' .
    ($lessonWhere ? ' WHERE ' . implode(' AND ', $lessonWhere) : '') .
    ' GROUP BY l.id ORDER BY c.sort_order,l.sort_order,l.title LIMIT 300',
    $lessonParams
);
$stats = [
    'courses' => (int) Database::scalar('SELECT COUNT(*) FROM courses', [], 0),
    'published_courses' => (int) Database::scalar('SELECT COUNT(*) FROM courses WHERE is_published=1', [], 0),
    'lessons' => (int) Database::scalar('SELECT COUNT(*) FROM lessons', [], 0),
    'published_lessons' => (int) Database::scalar('SELECT COUNT(*) FROM lessons WHERE is_published=1', [], 0),
    'questions' => (int) Database::scalar('SELECT COUNT(*) FROM quiz_questions', [], 0),
];

$pageTitle = 'Curriculum management';
$bodyClass = 'admin-content-page curriculum-management-page';
require base_path('partials/header.php');
?>
<link rel="stylesheet" href="<?= e(asset('css/curriculum.css')) ?>">

<section class="workspace-section page-intro curriculum-page-intro">
    <div><a class="back-link" href="<?= e(url('admin/dashboard.php')) ?>"><?= icon('arrow-left') ?>Administration</a><span class="eyebrow">Curriculum operations</span><h1>Curriculum management</h1><p>Build learning paths, create lessons and manage assessments from dedicated full-page editors.</p></div>
    <div class="page-intro-actions curriculum-primary-actions"><a class="button button-secondary" href="<?= e(url('admin/lesson-edit.php')) ?>"><?= icon('plus') ?>Create lesson</a><a class="button" href="<?= e(url('admin/course-edit.php')) ?>"><?= icon('plus') ?>Create learning path</a></div>
</section>

<section class="curriculum-stat-grid" aria-label="Curriculum summary">
    <article class="curriculum-stat-card"><span class="metric-icon purple"><?= icon('map') ?></span><div><small>Learning paths</small><strong><?= $stats['courses'] ?></strong><span><?= $stats['published_courses'] ?> published</span></div></article>
    <article class="curriculum-stat-card"><span class="metric-icon blue"><?= icon('book-open') ?></span><div><small>Lessons</small><strong><?= $stats['lessons'] ?></strong><span><?= $stats['published_lessons'] ?> published</span></div></article>
    <article class="curriculum-stat-card"><span class="metric-icon green"><?= icon('check-circle') ?></span><div><small>Assessment questions</small><strong><?= $stats['questions'] ?></strong><span>Database-backed</span></div></article>
</section>

<section class="panel curriculum-filter-panel">
    <form method="get" class="curriculum-filter-form">
        <div class="field curriculum-search-field"><label for="curriculum-search">Search curriculum</label><div class="search-field"><?= icon('search') ?><input id="curriculum-search" name="q" value="<?= e($query) ?>" aria-label="Search learning paths and lessons"></div></div>
        <div class="field curriculum-status-field"><label for="curriculum-status">Publication status</label><select id="curriculum-status" name="status"><option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All records</option><option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option><option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option></select></div>
        <div class="curriculum-filter-actions"><button class="button button-secondary" type="submit">Apply filters</button><?php if ($query !== '' || $status !== 'all'): ?><a class="text-link" href="<?= e(url('admin/content.php')) ?>">Clear filters</a><?php endif; ?></div>
    </form>
</section>

<div class="curriculum-dashboard-grid">
    <section class="panel curriculum-data-card" aria-labelledby="paths-heading">
        <div class="panel-heading curriculum-card-heading"><div><span class="eyebrow">Learning paths</span><h2 id="paths-heading"><?= count($courses) ?> matching paths</h2><p>Organise lessons into clear learner journeys.</p></div><a class="button button-small" href="<?= e(url('admin/course-edit.php')) ?>"><?= icon('plus') ?>New path</a></div>
        <?php if (!$courses): ?><div class="empty-state compact"><span><?= icon('map') ?></span><h3>No learning paths found</h3><p>Create a path or change the filters.</p></div><?php else: ?>
        <div class="curriculum-record-list">
            <?php foreach ($courses as $course): ?>
            <article class="curriculum-record curriculum-path-record">
                <div class="curriculum-record-main"><span class="course-icon compact" style="--course:<?= e($course['colour']) ?>"><?= icon($course['icon']) ?></span><div class="curriculum-record-copy"><div class="curriculum-record-title-row"><strong><?= e($course['title']) ?></strong><span class="status-badge <?= (int) $course['is_published'] ? 'success' : 'warning' ?>"><?= (int) $course['is_published'] ? 'Published' : 'Draft' ?></span></div><p><?= e($course['short_description']) ?></p><div class="curriculum-meta-row"><span><?= icon('book-open') ?><?= (int) $course['lesson_count'] ?> lessons</span><span><?= icon('check-circle') ?><?= (int) $course['published_lesson_count'] ?> published</span><span><?= icon('clock') ?><?= e($course['estimated_time']) ?></span><span><?= e($course['level']) ?></span></div></div></div>
                <div class="curriculum-record-actions"><a class="button button-small button-secondary" href="<?= e(url('course.php?course=' . urlencode($course['slug']))) ?>">Preview</a><a class="button button-small" href="<?= e(url('admin/course-edit.php?id=' . (int) $course['id'])) ?>"><?= icon('edit') ?>Edit</a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_course"><input type="hidden" name="id" value="<?= (int) $course['id'] ?>"><button class="button button-small button-secondary" type="submit"><?= (int) $course['is_published'] ? 'Unpublish' : 'Publish' ?></button></form></div>
            </article>
            <?php endforeach; ?>
        </div><?php endif; ?>
    </section>

    <section class="panel curriculum-data-card" aria-labelledby="lessons-heading">
        <div class="panel-heading curriculum-card-heading"><div><span class="eyebrow">Lessons and assessments</span><h2 id="lessons-heading"><?= count($lessons) ?> matching lessons</h2><p>Edit lesson content and maintain assessment readiness.</p></div><a class="button button-small" href="<?= e(url('admin/lesson-edit.php')) ?>"><?= icon('plus') ?>New lesson</a></div>
        <?php if (!$lessons): ?><div class="empty-state compact"><span><?= icon('book-open') ?></span><h3>No lessons found</h3><p>Create a lesson or change the filters.</p></div><?php else: ?>
        <div class="curriculum-record-list">
            <?php foreach ($lessons as $lesson): ?>
            <article class="curriculum-record curriculum-lesson-record">
                <div class="curriculum-record-main"><span class="lesson-sequence" style="--course:<?= e($lesson['course_colour']) ?>"><?= (int) $lesson['sort_order'] ?></span><div class="curriculum-record-copy"><div class="curriculum-record-title-row"><strong><?= e($lesson['title']) ?></strong><span class="status-badge <?= (int) $lesson['is_published'] ? 'success' : 'warning' ?>"><?= (int) $lesson['is_published'] ? 'Published' : 'Draft' ?></span></div><p><?= e($lesson['summary']) ?></p><div class="curriculum-meta-row"><span><?= icon('map') ?><?= e($lesson['course_title']) ?></span><span><?= icon('clock') ?><?= (int) $lesson['duration_minutes'] ?> min</span><span><?= icon('check-circle') ?><?= (int) $lesson['question_count'] ?> questions</span><span><?= e(ucfirst($lesson['difficulty'])) ?></span></div></div></div>
                <div class="curriculum-record-actions"><a class="button button-small button-secondary" href="<?= e(url('lesson.php?lesson=' . urlencode($lesson['slug']))) ?>">Preview</a><a class="button button-small button-secondary" href="<?= e(url('admin/questions.php?lesson=' . (int) $lesson['id'])) ?>">Assessment</a><a class="button button-small" href="<?= e(url('admin/lesson-edit.php?id=' . (int) $lesson['id'])) ?>"><?= icon('edit') ?>Edit</a><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_lesson"><input type="hidden" name="id" value="<?= (int) $lesson['id'] ?>"><button class="button button-small button-secondary" type="submit"><?= (int) $lesson['is_published'] ? 'Unpublish' : 'Publish' ?></button></form></div>
            </article>
            <?php endforeach; ?>
        </div><?php endif; ?>
    </section>
</div>

<?php require base_path('partials/footer.php'); ?>
