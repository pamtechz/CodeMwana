<?php
require_once __DIR__ . '/app/bootstrap.php';
if (Auth::check()) redirect('dashboard.php');
$stats = Learning::publicStatistics();
$courses = Learning::courses();
$features = Learning::homeFeatures();
$announcements = Learning::announcements(2);
$registrationOpen = (string) setting('registration_open', '1') === '1';
$previewCourse = $courses[0] ?? null;
$previewLessons = $previewCourse ? Learning::lessonsForCourse((int) $previewCourse['id']) : [];
$previewLesson = $previewLessons[0] ?? null;
$pageTitle = '';
$bodyClass = 'landing-page';
require base_path('partials/header.php');
?>
<section class="hero-section">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><?= e(setting('hero_eyebrow')) ?></span>
            <h1><?= e(setting('hero_title')) ?></h1>
            <p><?= e(setting('hero_text')) ?></p>
            <div class="hero-actions">
                <?php if ($registrationOpen): ?><a class="button button-large" href="<?= e(url('register.php')) ?>"><?= e(setting('primary_action_text')) ?><?= icon('arrow-right') ?></a><?php endif; ?>
                <a class="button button-large button-secondary" href="#learning"><?= e(setting('secondary_action_text')) ?></a>
            </div>
            <div class="hero-trust"><span><?= icon('shield-check') ?> Sandboxed execution</span><span><?= icon('terminal') ?><?= number_format($stats['languages']) ?> programming languages</span><span><?= icon('school') ?> Built for guided learning</span></div>
        </div>
        <div class="hero-product" aria-label="CodeMwana curriculum preview">
            <div class="browser-frame">
                <div class="browser-bar"><span></span><span></span><span></span><small><?= e(setting('site_name', 'CodeMwana')) ?> curriculum workspace</small></div>
                <div class="preview-shell">
                    <aside><div class="preview-brand">CM</div><?php foreach (['home','book-open','terminal','chart'] as $item): ?><i><?= icon($item) ?></i><?php endforeach; ?></aside>
                    <div class="preview-main">
                        <div class="preview-top"><div><small>Live curriculum preview</small><strong><?= e($previewCourse['title'] ?? 'Learning paths are ready') ?></strong></div><span class="avatar small"><?= e($previewCourse ? initials($previewCourse['title']) : 'CM') ?></span></div>
                        <div class="preview-progress"><div><span>Published learning path</span><strong><?= e($previewCourse['title'] ?? setting('site_name', 'CodeMwana')) ?></strong><small><?= $previewCourse ? (int) $previewCourse['lesson_count'] . ' database-backed lessons' : 'Curriculum content is managed by administrators' ?></small></div><div class="progress-ring"><b><?= $previewCourse ? (int) $previewCourse['lesson_count'] : 0 ?></b></div></div>
                        <div class="preview-cards"><article><span><?= icon($previewLesson['icon'] ?? 'terminal') ?></span><strong><?= e($previewLesson['title'] ?? 'Create your first lesson') ?></strong><small><?= $previewLesson ? (int) $previewLesson['duration_minutes'] . ' minutes' : 'Managed from the curriculum centre' ?></small></article><article><span><?= icon('route') ?></span><strong><?= e($previewCourse['level'] ?? 'Beginner') ?></strong><small><?= e($previewCourse['audience'] ?? 'Structured learning') ?></small></article></div>
                        <div class="preview-code"><code><?= $previewLesson && trim((string) $previewLesson['starter_code']) !== '' ? nl2br(e($previewLesson['starter_code'])) : '<b>SAY</b> <em>"Welcome to CodeMwana"</em>' ?></code><button type="button"><?= icon('play') ?> Preview</button></div>
                    </div>
                </div>
            </div>
            <div class="floating-card floating-one"><?= icon('blocks') ?><span><strong><?= number_format($stats['lessons']) ?> published lessons</strong><small>Loaded from the curriculum database</small></span></div>
            <div class="floating-card floating-two"><?= icon('folder-code') ?><span><strong><?= number_format($stats['projects']) ?> learner projects</strong><small>Saved with version history</small></span></div>
        </div>
    </div>
</section>
<section class="platform-stats">
    <div class="container stats-row">
        <div><strong><?= number_format($stats['languages']) ?></strong><span>Programming languages</span></div>
        <div><strong><?= number_format($stats['courses']) ?></strong><span>Learning paths</span></div>
        <div><strong><?= number_format($stats['lessons']) ?></strong><span>Complete lessons</span></div>
        <div><strong><?= number_format($stats['projects']) ?></strong><span>Projects saved</span></div>
    </div>
</section>
<section class="section" id="learning">
    <div class="container">
        <div class="section-heading centered"><span class="eyebrow">Structured curriculum</span><h2>Start with thinking. Progress to creating.</h2><p>Each path has clear outcomes, ordered lessons, practical challenges and assessment questions stored in the platform database.</p></div>
        <div class="course-showcase">
            <?php foreach ($courses as $index => $course): ?>
                <article class="public-course-card" style="--course:<?= e($course['colour']) ?>">
                    <div class="course-card-top"><span class="course-icon"><?= icon($course['icon']) ?></span><span class="course-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span></div>
                    <span class="course-meta"><?= e($course['level']) ?> · <?= (int) $course['lesson_count'] ?> lessons</span>
                    <h3><?= e($course['title']) ?></h3><p><?= e($course['short_description']) ?></p>
                    <div class="course-footer"><span><?= icon('clock') ?><?= e($course['estimated_time']) ?></span><a href="<?= e(url($registrationOpen ? 'register.php' : 'login.php')) ?>"><?= $registrationOpen ? 'Begin path' : 'Sign in' ?><?= icon('arrow-right') ?></a></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section section-soft" id="experience">
    <div class="container">
        <div class="section-heading split"><div><span class="eyebrow">Complete learning experience</span><h2>More than a collection of coding pages.</h2></div><p>CodeMwana combines curriculum, assessment, learner operations and teaching oversight in one responsive application.</p></div>
        <div class="feature-grid">
            <?php foreach ($features as $feature): ?><article class="feature-card"><span class="feature-icon"><?= icon($feature['icon']) ?></span><h3><?= e($feature['title']) ?></h3><p><?= e($feature['description']) ?></p></article><?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section workflow-section">
    <div class="container workflow-grid">
        <div><span class="eyebrow light">A clear learning loop</span><h2>Learn the idea, practise it, prove it, build with it.</h2><p>Every operation has a purpose and visible feedback. Learners always know what happened and what to do next.</p></div>
        <ol class="workflow-list"><li><span>01</span><div><strong>Enrol in a path</strong><p>Choose a path based on its outcomes, level and lesson sequence.</p></div></li><li><span>02</span><div><strong>Complete guided lessons</strong><p>Read examples, predict output and work through practical challenges.</p></div></li><li><span>03</span><div><strong>Submit assessment</strong><p>Receive a score and an explanation for every question.</p></div></li><li><span>04</span><div><strong>Create a project</strong><p>Save original programs and retain previous versions when code changes.</p></div></li></ol>
    </div>
</section>
<?php if ($announcements): ?><section class="section"><div class="container"><div class="section-heading"><span class="eyebrow">Platform notices</span><h2>Latest from CodeMwana</h2></div><div class="announcement-grid"><?php foreach ($announcements as $notice): ?><article class="announcement-card"><span><?= e(date('j M Y', strtotime($notice['published_at']))) ?></span><h3><?= e($notice['title']) ?></h3><p><?= e($notice['body']) ?></p></article><?php endforeach; ?></div></div></section><?php endif; ?>
<section class="section final-cta"><div class="container"><div class="cta-panel"><div><span class="eyebrow light"><?= $registrationOpen ? 'Begin with a real account' : 'Continue your learning' ?></span><h2>Your progress should belong to you.</h2><p><?= $registrationOpen ? 'Create a learner account and keep every lesson, score, achievement and project connected across sessions.' : 'Registration is managed by the platform administrator. Existing learners can sign in to continue.' ?></p></div><a class="button button-light button-large" href="<?= e(url($registrationOpen ? 'register.php' : 'login.php')) ?>"><?= $registrationOpen ? 'Create learner account' : 'Sign in' ?><?= icon('arrow-right') ?></a></div></div></section>
<?php require base_path('partials/footer.php'); ?>
