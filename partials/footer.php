</main>
<?php if ($user): ?>
    <footer class="app-footer"><span><?= e(setting('site_name', 'CodeMwana')) ?> <?= e(config('app.version', '2.0.0')) ?></span><a href="<?= e(url('help.php')) ?>">Help and guidance</a></footer>
    </div>
</div>
<?php else: ?>
<footer class="public-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand" href="<?= e(url('index.php')) ?>"><span class="brand-mark">CM</span><span><strong><?= e(setting('site_name', 'CodeMwana')) ?></strong><small><?= e(setting('site_tagline', 'Learn. Build. Shine.')) ?></small></span></a>
            <p><?= e(setting('site_description', 'A safe programming learning platform for children.')) ?></p>
        </div>
        <div><h2>Learning</h2><a href="<?= e(url('index.php#learning')) ?>">Learning paths</a><a href="<?= e(url('login.php')) ?>">Learner sign in</a><?php if ((string) setting('registration_open', '1') === '1'): ?><a href="<?= e(url('register.php')) ?>">Create account</a><?php endif; ?></div>
        <div><h2>Platform</h2><a href="<?= e(url('about.php')) ?>">About CodeMwana</a><a href="<?= e(url('privacy.php')) ?>">Privacy and safety</a><a href="<?= e(url('help.php')) ?>">Help centre</a></div>
    </div>
    <div class="container footer-bottom"><span>&copy; <?= date('Y') ?> <?= e(setting('organisation_name', 'CodeMwana Learning Programme')) ?></span><span>Designed and developed in Zambia</span></div>
</footer>
<?php endif; ?>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<?php if (!empty($pageScript)): ?><script src="<?= e(asset('js/' . $pageScript)) ?>" defer></script><?php endif; ?>
</body>
</html>
