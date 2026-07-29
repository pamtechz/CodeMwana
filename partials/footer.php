</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand brand-footer" href="<?= e(url('index.php')) ?>">
                <span class="brand-mark" aria-hidden="true">&lt;/&gt;</span>
                <span><strong>CodeMwana</strong><small>Learn. Build. Shine.</small></span>
            </a>
            <p>A safe, low-bandwidth learning space that helps children understand programming through short lessons, quizzes and creative coding challenges.</p>
        </div>
        <div>
            <h2>Explore</h2>
            <a href="<?= e(url('courses.php')) ?>">Learning paths</a>
            <a href="<?= e(url('playground.php')) ?>">MwanaCode lab</a>
            <a href="<?= e(url('leaderboard.php')) ?>">Leaderboard</a>
        </div>
        <div>
            <h2>Project</h2>
            <a href="<?= e(url('about.php')) ?>">About the project</a>
            <a href="<?= e(url('privacy.php')) ?>">Privacy and safety</a>
            <a href="<?= e(url('help.php')) ?>">Help</a>
        </div>
    </div>
    <div class="container footer-bottom">
        <p>&copy; <?= date('Y') ?> CodeMwana. Built for ICT4410 Web Design and Development.</p>
        <p>Designed in Zambia.</p>
    </div>
</footer>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php if (!empty($pageScript)): ?>
<script src="<?= e(asset('js/' . $pageScript)) ?>" defer></script>
<?php endif; ?>
</body>
</html>
