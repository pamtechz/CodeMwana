<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$user=current_user();
$identifier=(string)($_GET['lesson']??request_int('id'));
$lesson=Learning::lesson($identifier);
if(!$lesson){http_response_code(404);$pageTitle='Lesson not found';require base_path('partials/header.php');echo '<section class="state-page"><div class="state-card"><span class="state-icon">404</span><h1>Lesson not found</h1><p>The requested lesson is not published or no longer exists.</p><a class="button" href="'.e(url('courses.php')).'">Browse learning paths</a></div></section>';require base_path('partials/footer.php');exit;}
Learning::markLessonStarted((int)$user['id'],(int)$lesson['id']);
$navigation=Learning::lessonNavigation((int)$lesson['course_id'],(int)$lesson['sort_order']);
$progress=Database::fetch('SELECT status,best_score FROM progress WHERE user_id=? AND lesson_id=?',[$user['id'],$lesson['id']]);
$questionCount=(int)Database::scalar('SELECT COUNT(*) FROM quiz_questions WHERE lesson_id=?',[$lesson['id']]);
$pageTitle=$lesson['title'];$bodyClass='lesson-page';require base_path('partials/header.php');
?>
<div class="lesson-breadcrumb"><a href="<?= e(url('courses.php')) ?>">Learning paths</a><?= icon('arrow-right') ?><a href="<?= e(url('course.php?course='.urlencode($lesson['course_slug']))) ?>"><?= e($lesson['course_title']) ?></a><?= icon('arrow-right') ?><span><?= e($lesson['title']) ?></span></div>
<section class="lesson-title-card" style="--course:<?= e($lesson['course_colour']) ?>"><div class="lesson-title-main"><span class="course-icon large"><?= icon($lesson['icon']) ?></span><div><div class="inline-meta"><span><?= e(ucfirst($lesson['difficulty'])) ?></span><span><?= icon('clock') ?><?= (int)$lesson['duration_minutes'] ?> minutes</span><span><?= $questionCount ?> quiz questions</span></div><h1><?= e($lesson['title']) ?></h1><p><?= e($lesson['summary']) ?></p></div></div><div class="lesson-status <?= e($progress['status']??'in_progress') ?>"><?= icon(($progress['status']??'')==='completed'?'check-circle':'play') ?><span><strong><?= e(ucfirst(str_replace('_',' ',$progress['status']??'in_progress'))) ?></strong><small>Best score <?= (int)($progress['best_score']??0) ?>%</small></span></div></section>
<div class="lesson-layout">
<article class="lesson-article panel">
    <section class="lesson-objective"><span><?= icon('trophy') ?></span><div><small>Learning objective</small><strong><?= e($lesson['learning_objective']) ?></strong></div></section>
    <div class="lesson-content"><?= $lesson['content_html'] ?></div>
    <section class="concept-strip"><div><small>Core concepts</small><strong><?= e($lesson['concepts']) ?></strong></div><div><small>Vocabulary</small><strong><?= e($lesson['vocabulary']) ?></strong></div></section>
    <section class="challenge-card"><div class="challenge-heading"><span><?= icon('terminal') ?></span><div><small>Practical challenge</small><h2>Apply what you learned</h2></div></div><p><?= e($lesson['challenge_text']) ?></p><?php if(trim((string)$lesson['starter_code'])!==''): ?><div class="code-snippet"><div><span>Starter program</span><button type="button" data-copy-code>Copy code</button></div><pre id="starter-code"><code><?= e($lesson['starter_code']) ?></code></pre></div><a class="button" href="<?= e(url('playground.php?lesson='.urlencode($lesson['slug']))) ?>"><?= icon('terminal') ?>Open challenge in Code Lab</a><?php endif; ?></section>
    <section class="assessment-callout"><div><span class="eyebrow">Check your understanding</span><h2>Complete the lesson quiz</h2><p>Your highest score is saved. A score of 60% or more completes this lesson.</p></div><a class="button button-large" href="<?= e(url('quiz.php?lesson='.urlencode($lesson['slug']))) ?>">Start <?= $questionCount ?>-question quiz<?= icon('arrow-right') ?></a></section>
</article>
<aside class="lesson-side">
    <section class="panel lesson-toc"><span class="eyebrow">Lesson guide</span><h2>On this page</h2><a href="#main-content">Lesson overview</a><a href=".lesson-content">Learning content</a><a href=".challenge-card">Practical challenge</a><a href=".assessment-callout">Assessment</a></section>
    <?php if(in_array($user['role'],['teacher','admin'],true)): ?><section class="panel teacher-note"><span><?= icon('users') ?></span><div><small>Teaching note</small><p><?= e($lesson['teacher_note']) ?></p></div></section><?php endif; ?>
</aside>
</div>
<nav class="lesson-navigation" aria-label="Lesson navigation"><?php if($navigation['previous']): ?><a href="<?= e(url('lesson.php?lesson='.urlencode($navigation['previous']['slug']))) ?>"><?= icon('arrow-left') ?><span><small>Previous lesson</small><strong><?= e($navigation['previous']['title']) ?></strong></span></a><?php else: ?><span></span><?php endif; ?><?php if($navigation['next']): ?><a class="next" href="<?= e(url('lesson.php?lesson='.urlencode($navigation['next']['slug']))) ?>"><span><small>Next lesson</small><strong><?= e($navigation['next']['title']) ?></strong></span><?= icon('arrow-right') ?></a><?php else: ?><a class="next" href="<?= e(url('course.php?course='.urlencode($lesson['course_slug']))) ?>"><span><small>Path complete</small><strong>Review the learning path</strong></span><?= icon('arrow-right') ?></a><?php endif; ?></nav>
<?php require base_path('partials/footer.php'); ?>
