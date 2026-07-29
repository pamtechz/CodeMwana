<?php
require_once __DIR__ . '/app/bootstrap.php';
require_auth();
$lessonId = request_int('id');
$lesson = Learning::lesson($lessonId);
if (!$lesson) { http_response_code(404); exit('Lesson not found.'); }
$user = current_user();
Learning::markLessonStarted((int) $user['id'], $lessonId);
$courseLessons = Learning::lessonsForCourse((int) $lesson['course_id'], (int) $user['id']);
$currentIndex = 0;
foreach ($courseLessons as $index => $item) if ((int) $item['id'] === $lessonId) $currentIndex = $index;
$previous = $courseLessons[$currentIndex - 1] ?? null;
$next = $courseLessons[$currentIndex + 1] ?? null;
$pageTitle = $lesson['title'];
$bodyClass = 'app-page lesson-page';
require base_path('partials/header.php');
?>
<section class="lesson-header">
    <div class="container lesson-header-grid">
        <div><a class="back-link" href="<?= e(url('courses.php#course-' . (int) $lesson['course_id'])) ?>">← Back to <?= e($lesson['course_title']) ?></a><span class="eyebrow light"><?= e($lesson['course_icon']) ?> Lesson <?= $currentIndex + 1 ?> of <?= count($courseLessons) ?></span><h1><?= e($lesson['title']) ?></h1><p><?= e($lesson['summary']) ?></p><div class="lesson-header-meta"><span>⏱ <?= (int) $lesson['duration_minutes'] ?> minutes</span><span>📊 <?= e(ucfirst($lesson['difficulty'])) ?></span><span>🧠 <?= e($lesson['concepts']) ?></span></div></div>
        <div class="lesson-progress-ring" style="--progress: <?= (int) round((($currentIndex + 1) / max(1, count($courseLessons))) * 100) ?>"><strong><?= $currentIndex + 1 ?>/<?= count($courseLessons) ?></strong><small>path lesson</small></div>
    </div>
</section>
<section class="section lesson-section">
    <div class="container lesson-layout">
        <article class="lesson-content prose">
            <?= $lesson['content_html'] ?>
            <section class="challenge-box" id="challenge">
                <div class="challenge-label"><span aria-hidden="true">⚡</span> Your challenge</div>
                <h2>Try the idea yourself</h2>
                <p><?= e($lesson['challenge_text']) ?></p>
                <?php if ($lesson['starter_code']): ?>
                <div class="starter-code"><div><span>Starter code</span><button type="button" data-copy-code>Copy</button></div><pre><code id="starter-code"><?= e($lesson['starter_code']) ?></code></pre></div>
                <a class="button" href="<?= e(url('playground.php?lesson=' . $lessonId)) ?>">Open this challenge in Code Lab <span aria-hidden="true">→</span></a>
                <?php endif; ?>
            </section>
            <section class="lesson-check"><div><span class="eyebrow">Knowledge check</span><h2>Ready to test your understanding?</h2><p>A short quiz gives immediate explanations and stores your best score.</p></div><a class="button button-large" href="<?= e(url('quiz.php?lesson=' . $lessonId)) ?>">Take the quiz</a></section>
            <nav class="lesson-navigation" aria-label="Lesson navigation">
                <?php if ($previous): ?><a href="<?= e(url('lesson.php?id=' . (int) $previous['id'])) ?>"><small>Previous lesson</small><strong>← <?= e($previous['title']) ?></strong></a><?php else: ?><span></span><?php endif; ?>
                <?php if ($next): ?><a class="next" href="<?= e(url('lesson.php?id=' . (int) $next['id'])) ?>"><small>Next lesson</small><strong><?= e($next['title']) ?> →</strong></a><?php else: ?><a class="next" href="<?= e(url('courses.php')) ?>"><small>Path complete</small><strong>Choose another path →</strong></a><?php endif; ?>
            </nav>
        </article>
        <aside class="lesson-sidebar">
            <div class="lesson-toc"><h2>In this lesson</h2><a href="#idea">Main idea</a><a href="#example">Worked example</a><a href="#remember">Remember</a><a href="#challenge">Challenge</a></div>
            <div class="sidebar-card"><h2>Lesson goal</h2><p><?= e($lesson['learning_objective']) ?></p></div>
            <div class="sidebar-card vocabulary-card"><h2>Key words</h2><?php foreach (array_filter(array_map('trim', explode(',', $lesson['vocabulary']))) as $word): ?><span><?= e($word) ?></span><?php endforeach; ?></div>
        </aside>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>
