<?php
require_once __DIR__ . '/app/bootstrap.php';
$stats = Learning::publicStatistics();
$siteName = (string) setting('site_name', 'CodeMwana');
$pageTitle = 'About';
$pageDescription = 'How ' . $siteName . ' helps young people build programming skills through guided learning and practical work.';
$bodyClass = 'content-page';
require base_path('partials/header.php');
?>
<section class="content-hero">
    <div class="container content-hero-grid">
        <div>
            <span class="eyebrow">About <?= e($siteName) ?></span>
            <h1>Clear programming education for young creators.</h1>
            <p><?= e(setting('site_description', $siteName . ' combines guided lessons, practical coding and visible progress in one learner-friendly platform.')) ?></p>
        </div>
        <div class="content-hero-stats">
            <div><strong><?= number_format($stats['courses']) ?></strong><span>Learning paths</span></div>
            <div><strong><?= number_format($stats['lessons']) ?></strong><span>Guided lessons</span></div>
            <div><strong><?= number_format($stats['languages']) ?></strong><span>Coding workspaces</span></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container prose-shell">
        <article class="panel prose-panel">
            <span class="eyebrow">Our purpose</span>
            <h2>Make the first steps in coding understandable</h2>
            <p>Programming can feel difficult when learners meet too many new ideas at once. <?= e($siteName) ?> organises learning into short lessons, examples, practical challenges and quizzes so each concept can be understood before the next one begins.</p>

            <h2>Learn by creating</h2>
            <p>Learners do more than read explanations. They write programs, test ideas, correct errors, build projects and review the progress saved to their accounts.</p>

            <h2>Support for different learning stages</h2>
            <p>Beginners can start with computational thinking and guided activities before moving into widely used programming languages and web-development workspaces.</p>

            <h2>Designed for schools and independent learners</h2>
            <p>The platform works across phones, tablets and computers. Teachers can guide learning and review progress, while learners keep their lessons, results and projects together.</p>
        </article>

        <aside class="content-side">
            <section class="panel">
                <span class="eyebrow">Learning experience</span>
                <h2>What learners can do</h2>
                <ul class="check-list">
                    <li>Follow ordered learning paths</li>
                    <li>Practise concepts in Code Lab</li>
                    <li>Complete quizzes with feedback</li>
                    <li>Save and continue coding projects</li>
                    <li>Review progress and achievements</li>
                    <li>Learn on different screen sizes</li>
                </ul>
            </section>
            <section class="panel">
                <span class="eyebrow">Need guidance?</span>
                <h2>Use the Help centre</h2>
                <p>Find clear instructions for signing in, beginning lessons, running programs, providing program input and saving projects.</p>
                <a class="button button-secondary" href="<?= e(url('help.php')) ?>">Open Help centre</a>
            </section>
        </aside>
    </div>
</section>
<?php require base_path('partials/footer.php'); ?>
